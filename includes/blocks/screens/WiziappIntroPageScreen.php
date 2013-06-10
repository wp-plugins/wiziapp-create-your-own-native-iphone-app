<?php

class WiziappIntroPageScreen{

	public function run() {
		if ( ! isset($_GET['device']) || ! in_array( $_GET['device'], array( 'iphone', 'android', TRUE ) ) ) {
			wp_redirect( WiziappContentHandler::getInstance()->get_blog_property('url') );
			return;
		}

		$is_update = isset($_GET['update-application']) && $_GET['update-application'] === '1';

		switch ( $_GET['device'] ) {
			case 'iphone':
				$download_text = 'Get the ultimate Apple experience<br>with our new App!';
				$button_image = 'appstore.png';
				$store_url = WiziappConfig::getInstance()->appstore_url;
				$delay_period = 30*6;
				break;
			case 'android':
				$download_text = $is_update ? 'A new version of our App is now available,<br>would you like to download it now?' : 'Get the ultimate Android experience<br>with our new App!';

				if ( empty(WiziappConfig::getInstance()->playstore_url) ) {
					$button_image = 'android.png';
					$store_url = WiziappConfig::getInstance()->apk_file_url;
					$delay_period = 30;
				} else {
					$button_image = 'playstore.png';
					$store_url = WiziappConfig::getInstance()->playstore_url;
					$delay_period = 30*6;
				}
				break;
		}

		$is_show_desktop =
		WiziappConfig::getInstance()->webapp_installed &&
		( WiziappConfig::getInstance()->webapp_active || ( isset($_GET['androidapp']) && $_GET['androidapp'] === '1' ) );

		$wiziapp_plugin_url = plugins_url( dirname( WP_WIZIAPP_BASE ) );
		$app_icon = WiziappContentHandler::getInstance()->get_blog_property('data_files_url').'/resources/icons/'.basename(WiziappConfig::getInstance()->getAppIcon());

		$http_referer = filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL);
		$site_url = $http_referer ? $http_referer : WiziappContentHandler::getInstance()->get_blog_property('url');
		if ( $is_update ) {
			$site_url .= ( strpos($site_url, '?') === FALSE ? '?' : '&' ).'androidapp=1';
		}
		$desktop_site_url = $site_url.( strpos($site_url, '?') === FALSE ? '?' : '&' ).'setsession=desktopsite';

		include WIZI_DIR_PATH.'/themes/intropage/index.php';
	}

	public static function get_intro_page_info() {
		$is_not_passed =
		empty($_POST['device_type']) ||
		! isset($_POST['wizi_show_store_url']) ||
		intval(WiziappConfig::getInstance()->display_download_from_appstore) !== 1;
		if ( $is_not_passed ) {
			// Not passed the Error Checking
			return;
		}

		$retun_string = 'update-application=';
		$response = '';
		$query_string = array( 'androidapp' => '0', 'abv' => '', );
		parse_str( str_replace('?', '', $_POST['query_string']), $query_string );

		switch ( $_POST['device_type'] ) {
			case 'iphone':
				if ( ! empty(WiziappConfig::getInstance()->appstore_url) && $_POST['wizi_show_store_url'] !== '1' ) {
					$response = $retun_string.'0';
				}

				break;
			case 'android':
				if ( $query_string['androidapp'] === '1' ) {
					if ( session_id() == '' ) {
						session_start();
					}

					$proper_condition =
					! empty(WiziappConfig::getInstance()->android_app_version) && ! empty($query_string['abv']) &&
					! ( isset($_SESSION['wiziapp_android_download']) && $_SESSION['wiziapp_android_download'] === 'none' ) &&
					version_compare( WiziappConfig::getInstance()->android_app_version, $query_string['abv'], '>' );
					if ( $proper_condition ) {
						$_SESSION['wiziapp_android_download'] = 'none';
						$response =  $retun_string.'1';
					}
				} else {
					$proper_condition =
					( ! empty(WiziappConfig::getInstance()->playstore_url) || ! empty(WiziappConfig::getInstance()->apk_file_url) ) &&
					intval(WiziappConfig::getInstance()->endorse_download_android_app) === 1 &&
					$_POST['wizi_show_store_url'] != '1';
					if ( $proper_condition ) {
						$response = $retun_string.'0';
					}
				}

				break;
		}

		if ( $response !== '' ) {
			setcookie('WIZI_SHOW_STORE_URL', 1, time()+(60*60*24*7));
		}

		echo $response;
	}

	private function _get_javascript($wiziapp_plugin_url) {
		wp_register_script(
			'intropage',
			$wiziapp_plugin_url.'/themes/intropage/intro_page.js',
			array(),
			WIZIAPP_P_VERSION,
			FALSE
		);

		wp_enqueue_script('intropage');
		wp_head();
	}
}