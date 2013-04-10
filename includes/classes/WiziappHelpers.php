<?php if (!defined('WP_WIZIAPP_BASE')) exit();

class WiziappHelpers{

	private static $_wiziapp_rule = 'RewriteCond %{QUERY_STRING} !^wiziapp';

	public static function makeShortString($str, $len) {
		if ( strlen($str) <= $len ){
			return $str;
		}

		$str = wordwrap($str, $len, "\n", TRUE);
		$str = substr($str, 0, strpos($str, "\n"));
		if ( $str[strlen($str) - 1] == ',' ) {
			$str = substr($str, 0, strlen($str) - 1);
		}
		$str = $str . '...';

		return $str;
	}


	/**
	* Add the "RewriteCond" for the Wiziapp plugin,
	* to avoid collision with the WP Super Cache plugin.
	*
	* @param array $condition_rules - The multiple "RewriteCond"-s of the WP Super Cache plugin, from his .htaccess file
	*/
	public static function add_wiziapp_condition( $condition_rules ) {
		if ( ! is_array($condition_rules) ) {
			return $condition_rules;
		}

		// Avoid Wiziapp interference
		$condition_rules[] = self::$_wiziapp_rule;

		return $condition_rules;
	}

	public static function check_rewrite_rules() {
		global $wp_cache_mod_rewrite, $super_cache_enabled;

		if ( $super_cache_enabled !== FALSE && $wp_cache_mod_rewrite === 1 && function_exists('extract_from_markers') ) {
			// The is the WP Super Cache plugin activated and it use the mod_rewrite.
			$wp_super_cashe_rules = extract_from_markers( get_home_path().'.htaccess', 'WPSuperCache' );
			$filtered_rules = array_filter( $wp_super_cashe_rules, array( 'WiziappHelpers', 'rules_filter') );

			if ( empty($filtered_rules) ){
				// The Wiziapp rules are not exist into the WP Super Cache Rewrite MODE rules
				return
				'The WebApp could not be installed, it might be a conflict with the WP Super Cache Plugin issue.
				Please click the "Update Mod_Rewrite Rules" button on the WP Super Cache - advanced tab and try the Wiziapp plugin again.
				If it will not help, please contact the Wiziapp support.';
			}
		}

		return '';
	}

	public static function rules_filter($rule) {
		return strpos($rule, self::$_wiziapp_rule) !== FALSE;
	}

	public static function get_adsense() {
		$result_array = array(
			'upper_mask' => 1,
			'lower_mask' => 2,
			'code' => '',
			'show_in_post' => 0,
		);

		$adsense = WiziappConfig::getInstance()->adsense;
		$proper_condition =
		isset($adsense['provider_id']) && strlen($adsense['provider_id']) > 5 &&
		isset($adsense['show_in_post']) && $adsense['show_in_post'] > 0 &&
		// For now, the AdSense is not work in the Post of the Native App, so do not show it.
		strpos( urldecode($_SERVER['QUERY_STRING']), 'wiziapp/content/list/posts/recent' ) === FALSE;

		if ( ! $proper_condition ) {
			return $result_array;
		}

		ob_start();
		?>
		<script type="text/javascript"><!--
			google_ad_client = "ca-pub-<?php echo $adsense['provider_id']; ?>";
			/* testMobile */
			google_ad_slot = "";
			google_ad_width = 320;
			google_ad_height = 50;
			//-->
		</script>
		<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
		<?php
		$result_array['code'] = ob_get_clean();
		$result_array['show_in_post'] = intval($adsense['show_in_post']);
		$result_array['is_shown'] = TRUE;

		return $result_array;
	}
}