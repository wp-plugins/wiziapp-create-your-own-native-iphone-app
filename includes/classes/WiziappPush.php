<?php
/**
* @package WiziappWordpressPlugin
* @subpackage PushNotifications
* @author comobix.com plugins@comobix.com
*/

class WiziappPush{
    public static function publishPost($post){
    	// Check, is the Post excluded by WiziApp Exclude plugin
    	$post = apply_filters('exclude_wiziapp_push', $post);
        if ( $post == NULL ){
            return;
        }

        if ( empty(WiziappConfig::getInstance()->settings_done) ){
            return;
        }
        $post_id = $post->ID;
        // @todo Get this from the saved options
        $tabId = WiziappConfig::getInstance()->main_tab_index;

        if ( !WiziappConfig::getInstance()->notify_on_new_post ){
            WiziappLog::getInstance()->write('info', "We are set not to notify on new post...", 'WiziappPush.publishPost');
            return;
        }
        WiziappLog::getInstance()->write('info', "Notifying on new post", 'WiziappPush.publishPost');
        $request = null;
        if ( WiziappConfig::getInstance()->aggregate_notifications ){
            WiziappLog::getInstance()->write('info', "We need to aggregate the messages", 'WiziappPush.publishPost');
            // We might need to send this later...
            // let's check
            if (!isset(WiziappConfig::getInstance()->counters)) {
                WiziappConfig::getInstance()->counters = array('posts'=>0);
            }
            // Increase the posts count
            WiziappConfig::getInstance()->counters['posts'] += 1;

            // If the sum is set and not 0 we need to aggragate by posts count
            if ( WiziappConfig::getInstance()->aggregate_sum ){
                // Have we reached or passed our trashhold
                if ( WiziappConfig::getInstance()->counters['posts'] >= WiziappConfig::getInstance()->aggregate_sum ){
                    // We need to notify on all the new posts
                    $sound = WiziappConfig::getInstance()->trigger_sound;
                    $badge = (WiziappConfig::getInstance()->show_badge_number) ? WiziappConfig::getInstance()->counters['posts']: 0;
                    $users = 'all';
                    $request = array(
                        'type'=>1,
                        'sound'=>$sound,
                        'badge'=>$badge,
                        'users'=>$users,
                    );
                    if ( WiziappConfig::getInstance()->show_notification_text ){
                        $request['content'] = urlencode(stripslashes(WiziappConfig::getInstance()->counters['posts'].' new posts published'));
                        $request['params'] = "{\"tab\": \"{$tabId}\"}";
                    }
                    // reset the counter
                    WiziappConfig::getInstance()->counters['posts'] = 0;
                }
            }

        } else { // We are not aggragating the message
            $sound = WiziappConfig::getInstance()->trigger_sound;
            $badge = WiziappConfig::getInstance()->show_badge_number;
            $users = 'all';
            $request = array(
                'type'=>1,
                'sound'=>$sound,
                'badge'=>$badge,
                'users'=>$users,
            );
            if ( WiziappConfig::getInstance()->show_notification_text ){
                $request['content'] = urlencode(stripslashes(__('New Post Published', 'wiziapp')));
                //$request['params'] = "{tab: \"{$tabId}\"}";
                $request['params'] = "{\"tab\": \"{$tabId}\"}";
            }
        }
        // Done setting up what to send, now send it..

        // Make sure we have a reason to even send this message
        if ( $request == null || (!$request['sound'] && !$request['badge'] && !$request['content'] )){
            return;
        }
        // We have something to send
        WiziappLog::getInstance()->write('info', "About to send a single notification event...", 'WiziappPush.publishPost');
        $r = new WiziappHTTPRequest();
        $response = $r->api($request, '/push', 'POST');
    }

    function intervalPush($period, $period_text){
        if ( !WiziappConfig::getInstance()->notify_on_new_post ){
            return;
        }
        $request = null;
        $tabId = WiziappConfig::getInstance()->main_tab_index;
        if ( WiziappConfig::getInstance()->aggregate_notifications && WiziappConfig::getInstance()->notify_periods == $period){
            if (!isset(WiziappConfig::getInstance()->counters)) {
                // We don't have any counters in place yet, no need to run
                return;
            }
            if ( WiziappConfig::getInstance()->counters['posts'] > 0 ){
                $sound = WiziappConfig::getInstance()->trigger_sound;
                $badge = (WiziappConfig::getInstance()->show_badge_number) ? WiziappConfig::getInstance()->counters['posts']: 0;
                $users = 'all';
                $request = array(
                    'type'=>1,
                    'sound'=>$sound,
                    'badge'=>$badge,
                    'users'=>$users,
                );
                if ( WiziappConfig::getInstance()->show_notification_text ){
                    $request['content'] = urlencode(stripslashes(WiziappConfig::getInstance()->counters['posts'].__(' new posts published ', 'wiziapp').$period_text));
                    $request['params'] = "{\"tab\": \"{$tabId}\"}";
                }
                // reset the counter
                WiziappConfig::getInstance()->counters['posts'] = 0;
            }
        }
    }

    function daily(){
        $this->intervalPush('day', __('today', 'wiziapp'));
    }
    function weekly(){
        $this->intervalPush('week', __('this week', 'wiziapp'));
    }
    function monthly(){
        $this->intervalPush('month', __('this month', 'wiziapp'));
    }

}
