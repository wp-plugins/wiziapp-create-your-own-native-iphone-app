<?php

class WiziappIntroPageScreen{

	public function run() {
		if ( ! isset($_GET['device']) || ! in_array( $_GET['device'], array( 'iphone', 'android', TRUE ) ) ) {
			wp_redirect( WiziappContentHandler::getInstance()->get_blog_property('url') );
			return;
		}

		switch ( $_GET['device'] ) {
			case 'iphone':
				$download_place = 'Apple';
				$button_image = 'appstore.png';
				$store_url = WiziappConfig::getInstance()->appstore_url;
				$delay_period = 30*6;
				break;
			case 'android':
				$download_place = 'Android';

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

		include WIZI_DIR_PATH.'/themes/intropage/index.php';
	}

	public static function get_intro_page_info() {
		if ( empty($_POST['device_type']) ) {
			return;
		}

		$is_url_exist =
		( $_POST['device_type'] === 'iphone' && ! empty(WiziappConfig::getInstance()->appstore_url) ) ||
		(
			$_POST['device_type'] === 'android' &&
			( ! empty(WiziappConfig::getInstance()->playstore_url) || ! empty(WiziappConfig::getInstance()->apk_file_url) ) &&
			intval(WiziappConfig::getInstance()->endorse_download_android_app) === 1
		);

		if ( ! $is_url_exist || intval(WiziappConfig::getInstance()->display_download_from_appstore) !== 1 ) {
			return;
		}

		echo 'allow_show_intro_page';
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