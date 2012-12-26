<?php

class WiziappDownloadAppScreen extends WiziappBaseScreen{

	public static $template_vars;

	public function run() {
		$user_device = intval( WiziappContentHandler::getInstance()->isHTML() );

		if ( $user_device === 0 || $user_device === 3 ) {
			wp_redirect( home_url() );
			exit;
		}

		if ( $user_device === 1 ) {
			self::$template_vars = array(
				'button_image' => 'app_store',
				'download_place' => 'App Store',
				'store_url' => WiziappConfig::getInstance()->appstore_url,
			);
		} elseif ( $user_device === 2 ) {
			self::$template_vars = array(
				'button_image' => 'play_store',
				'download_place' => 'Google Play',
				'store_url' => WiziappConfig::getInstance()->playstore_url,
			);
		}

		get_template_part( 'downloadapp_screen' );
	}

	public function set_desktop_mode() {

	}

	public static function get_template_vars() {
		wp_register_script(
			'downloadapp',
			get_bloginfo('template_url').'/scripts/downloadapp.js',
			array(),
			WIZIAPP_P_VERSION,
			FALSE
		);

		wp_enqueue_script('downloadapp');
		wp_head();

		return self::$template_vars;
	}
}