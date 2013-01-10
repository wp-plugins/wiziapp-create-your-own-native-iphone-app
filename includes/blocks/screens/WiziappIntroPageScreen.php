<?php

class WiziappIntroPageScreen{

	public function run() {
		if ( ! isset($_GET['device']) || ! in_array( $_GET['device'], array( 'iphone', 'android', TRUE ) ) ){
			wp_redirect( home_url() );
			return;
		}

		switch ( $_GET['device'] ) {
			case 'iphone':
				$button_image = 'app_store';
				$download_place = 'App Store';
				$store_url = WiziappConfig::getInstance()->appstore_url;
				break;
			case 'android':
				$button_image = 'play_store';
				$download_place = 'Google Play';
				$store_url = WiziappConfig::getInstance()->playstore_url;
				break;
		}

		$is_show_desktop =
		WiziappConfig::getInstance()->webapp_installed &&
		( WiziappConfig::getInstance()->webapp_active || ( isset($_GET['androidapp']) && $_GET['androidapp'] === '1' ) );

		$wiziapp_plugin_url = plugins_url( dirname( WP_WIZIAPP_BASE ) );
		$app_icon = $wiziapp_plugin_url.'/themes/webapp/resources/icons/'.basename(WiziappConfig::getInstance()->getAppIcon());

		include WIZI_DIR_PATH.'/themes/intropage/index.php';
	}

	public static function get_intro_page_info(){
		if ( empty($_POST['device_type']) ){
			return;
		}

		$is_url_exist =
		( $_POST['device_type'] === 'iphone'  && ! empty(WiziappConfig::getInstance()->appstore_url)  ) ||
		( $_POST['device_type'] === 'android' && ! empty(WiziappConfig::getInstance()->playstore_url) );

		if ( ! $is_url_exist || intval(WiziappConfig::getInstance()->display_download_from_appstore) !== 1 ){
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