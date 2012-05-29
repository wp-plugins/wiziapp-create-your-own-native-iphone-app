<?php
/**
* @package WiziappWordpressPlugin
* @subpackage PushNotifications
* @author comobix.com plugins@comobix.com
*/
class WiziappPush {

	public static function create_push_notification($post_id, $post) {
		if ( wp_is_post_revision( $post_id ) ) {
			// If the Post is a revision
			return;
		}
		if ( ! isset( $_POST['original_publish'] ) ) {
			// If this is not the Publish or Update event from the Post or Page Edit Form.
			return;
		}
		if ( empty(WiziappConfig::getInstance()->settings_done) || ! is_object($post) ) {
			return;
		}

		if ( ! (bool) WiziappConfig::getInstance()->notify_on_new_post  && $post->post_type === 'post' ) {
			WiziappLog::getInstance()->write('INFO', "We are set not to notify on new post.", 'WiziappPush.publishPost');
			return;
		}
		if ( ! (bool) WiziappConfig::getInstance()->notify_on_new_page && $post->post_type === 'page' ) {
			WiziappLog::getInstance()->write('INFO', "We are set not to notify on new page.", 'WiziappPush.publishPost');
			return;
		}

		// Check, is the Post excluded by WiziApp Exclude plugin
		$post = apply_filters('exclude_wiziapp_push', $post);
		if ( $post == NULL ) {
			return;
		}

		if ( $post->post_type === 'post' ) {
			// If done the Publish or Update of the Post from the Post Edit Form.
			$is_post_publishing = $_POST['original_publish'] === 'Publish' && isset( $_POST['publish'] ) && $_POST['publish'] === 'Publish';
			$is_post_updating   = $_POST['original_publish'] === 'Update'  && isset( $_POST['save'] )    && $_POST['save'] === 'Update';

			if ( $is_post_publishing  && ! isset( $_POST['wizi_published_push'] ) ) {
				// If done the Publish, but the Push Notification on publish event is not permitted from the Wiziapp Metabox
				return;
			} elseif ( $is_post_updating  && ! isset( $_POST['wizi_updated_push'] ) ) {
				// If done the Updating, but the Push Notification on updating event is not permitted from the Wiziapp Metabox
				return;
			}
		}

		// @todo Get this from the saved options
		$tabId = WiziappConfig::getInstance()->main_tab_index;
		$request = null;
		$excluded_users = array();
		WiziappLog::getInstance()->write('INFO', "Notifying on new post", 'WiziappPush.publishPost');

		if ( WiziappConfig::getInstance()->aggregate_notifications ) {
			WiziappLog::getInstance()->write('INFO', "We need to aggregate the messages", 'WiziappPush.publishPost');
			// We might need to send this later...
			// let's check
			if (!isset(WiziappConfig::getInstance()->counters)) {
				WiziappConfig::getInstance()->counters = array('posts'=>0);
			}
			// Increase the posts count
			WiziappConfig::getInstance()->counters['posts'] += 1;

			// If the sum is set and not 0 we need to aggragate by posts count
			if ( WiziappConfig::getInstance()->aggregate_sum ) {
				// Have we reached or passed our trashhold
				if ( WiziappConfig::getInstance()->counters['posts'] >= WiziappConfig::getInstance()->aggregate_sum ) {
					// We need to notify on all the new posts
					$sound = WiziappConfig::getInstance()->trigger_sound;
					$badge = (WiziappConfig::getInstance()->show_badge_number) ? WiziappConfig::getInstance()->counters['posts']: 0;
					$request = array(
						'type'=>1,
						'sound'=>$sound,
						'badge'=>$badge,
						'excluded_users'=>$excluded_users,
					);
					if ( WiziappConfig::getInstance()->show_notification_text ) {
						$request['content'] = urlencode(stripslashes(WiziappConfig::getInstance()->counters['posts'].' new posts published'));
						$request['params'] = "{\"tab\": \"{$tabId}\"}";
					}
					// reset the counter
					WiziappConfig::getInstance()->counters['posts'] = 0;
				}
			}
		} else { // We are not aggragating the message
			foreach (get_users(array('fields' => 'ID',)) as $user_id) {
				$wiziapp_push_settings = get_user_meta($user_id, 'wiziapp_push_settings', TRUE);

				$is_generally_not_chosen =
				( ! isset($wiziapp_push_settings['tags']) || empty($wiziapp_push_settings['tags'])) &&
				( ! isset($wiziapp_push_settings['categories']) || empty($wiziapp_push_settings['categories'])) &&
				( ! isset($wiziapp_push_settings['authors']) || empty($wiziapp_push_settings['authors']));

				if ($is_generally_not_chosen) {
					continue;
				} else {
					if (isset($wiziapp_push_settings['authors']) && is_array($wiziapp_push_settings['authors']) && in_array($post->post_author, $wiziapp_push_settings['authors'])) {
						continue;
					}

					foreach (wp_get_object_terms($post_id, array('category', 'post_tag',)) as $product_term) {
						if ($product_term->taxonomy === 'category' && in_array($product_term->term_id, $wiziapp_push_settings['categories'])) {
							continue;
						} elseif ($product_term->taxonomy === 'post_tag' && in_array($product_term->term_id, $wiziapp_push_settings['tags'])) {
							continue;
						}
					}

					$excluded_users[] = $user_id;
				}
			}

			$sound = WiziappConfig::getInstance()->trigger_sound;
			$badge = WiziappConfig::getInstance()->show_badge_number;
			$request = array(
				'type'=>1,
				'sound'=>$sound,
				'badge'=>$badge,
				'excluded_users'=>$excluded_users,
			);
			if ( WiziappConfig::getInstance()->show_notification_text ) {
				$request['content'] = urlencode( stripslashes( WiziappSettingMetabox::get_push_message( $post_id ) ) );
				$request['params'] = "{\"tab\": \"{$tabId}\"}";
			}
		}
		// Done setting up what to send, now send it..

		// Make sure we have a reason to even send this message
		if ( $request == null || (!$request['sound'] && !$request['badge'] && !$request['content'] )) {
			return;
		}
		// We have something to send
		WiziappLog::getInstance()->write('INFO', "About to send a single notification event...", 'WiziappPush.publishPost');
		$r = new WiziappHTTPRequest();
		$response = $r->api($request, '/push', 'POST');
	}

	public static function intervalPush($period, $period_text) {
		if ( !WiziappConfig::getInstance()->notify_on_new_post ) {
			return;
		}
		$request = null;
		$tabId = WiziappConfig::getInstance()->main_tab_index;
		if ( WiziappConfig::getInstance()->aggregate_notifications && WiziappConfig::getInstance()->notify_periods == $period) {
			if (!isset(WiziappConfig::getInstance()->counters)) {
				// We don't have any counters in place yet, no need to run
				return;
			}
			if ( WiziappConfig::getInstance()->counters['posts'] > 0 ) {
				$sound = WiziappConfig::getInstance()->trigger_sound;
				$badge = (WiziappConfig::getInstance()->show_badge_number) ? WiziappConfig::getInstance()->counters['posts']: 0;
				$users = 'all';
				$request = array(
					'type'=>1,
					'sound'=>$sound,
					'badge'=>$badge,
					'users'=>$users,
				);
				if ( WiziappConfig::getInstance()->show_notification_text ) {
					$request['content'] = urlencode(stripslashes(WiziappConfig::getInstance()->counters['posts'].__(' new posts published ', 'wiziapp').$period_text));
					$request['params'] = "{\"tab\": \"{$tabId}\"}";
				}
				// reset the counter
				WiziappConfig::getInstance()->counters['posts'] = 0;
			}
		}
	}

	public static function daily() {
		self::intervalPush('day', __('today', 'wiziapp'));
	}
	public static function weekly() {
		self::intervalPush('week', __('this week', 'wiziapp'));
	}
	public static function monthly() {
		self::intervalPush('month', __('this month', 'wiziapp'));
	}

}