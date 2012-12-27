<?php

class WiziappInstaller
{
    public function needUpgrade() {
		/*
		if ( ! WiziappDB::getInstance()->isInstalled() ) {
		// We are not installed, we don't have nothing to upgrade, we need a full scan...
		return FALSE;
		}
		*/

		return ( WiziappDB::getInstance()->needUpgrade() || WiziappConfig::getInstance()->needUpgrade() );
    }

    public function upgradeDatabase() {
        $upgraded = TRUE;

        if ( WiziappDB::getInstance()->needUpgrade() ) {
            $upgraded = WiziappDB::getInstance()->upgrade();
        }

        return $upgraded;
    }

    public function upgradeConfiguration() {
        $upgraded = TRUE;

        if ( WiziappConfig::getInstance()->needUpgrade() ) {
            $upgraded = WiziappConfig::getInstance()->upgrade();
        }

        return $upgraded;
    }

    public function install(){
        // Check for capability
        if (!current_user_can('activate_plugins')) {
            return;
        }

        WiziappDB::getInstance()->install();
        WiziappConfig::getInstance()->install();

		WiziappConfig::getInstance()->webapp_installed = FALSE;

        // Register tasks
        if (!wp_next_scheduled('wiziapp_daily_function_hook')) {
            wp_schedule_event(time(), 'daily', 'wiziapp_daily_function_hook' );
            wp_schedule_event(time(), 'weekly', 'wiziapp_weekly_function_hook' );
            wp_schedule_event(time(), 'monthly', 'wiziapp_monthly_function_hook' );
        }

        // Activate the blog with the global services
        $cms = new WiziappCms();
        $cms->activate();

        $restoreHandler = new WiziappUserServices();
        $restoreHandler->restoreUserData();
    }

	protected static function doUninstall(){
		WiziappDB::getInstance()->uninstall();

        // Remove scheduled tasks
        wp_clear_scheduled_hook('wiziapp_daily_function_hook');
        wp_clear_scheduled_hook('wiziapp_weekly_function_hook');
        wp_clear_scheduled_hook('wiziapp_monthly_function_hook');

        // Deactivate the blog with the global services
        try{
            $cms = new WiziappCms();
            $cms->deactivate();
        } catch(Exception $e){
            // If it failed, it's ok... move on
        }

        // Remove option of the "Wiziapp QR Code Widget" on it exist case.
		if ( get_option( $wiziapp_qrcode_widget_option = 'widget_' . WiziappConfig::getInstance()->wiziapp_qrcode_widget_id_base ) ) {
			delete_option( $wiziapp_qrcode_widget_option );
		}

        // Remove all options - must be done last
        delete_option('wiziapp_screens');
        delete_option('wiziapp_components');
        delete_option('wiziapp_pages');
        delete_option('wiziapp_last_processed');
        delete_option('wiziapp_featured_post');

        WiziappConfig::getInstance()->uninstall();
	}

    /**
    * Revert the installation to remove everything the plugin added
    */
    public function uninstall(){
		if (function_exists('is_multisite') && is_multisite()) {
			global $wpdb;
			// check if it is a network de-activation - if so, run the de-activation function for each blog id
			if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)) {
				$old_blog = $wpdb->blogid;
				// Get all blog ids
				$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
				foreach ($blogids as $blog_id) {
					switch_to_blog($blog_id);
					self::doUninstall();
				}
				switch_to_blog($old_blog);
				return;
			}
		} else {
			self::doUninstall();
        }
    }

    public function deleteBlog($blog_id, $drop){
		global $wpdb;
		$switched = false;
		$currentBlog = $wpdb->blogid;
		if ( $blog_id != $currentBlog ) {
			switch_to_blog($blog_id);
			$switched = true;
		}

		self::doUninstall();

		if ( $switched ) {
			switch_to_blog($currentBlog);
		}
	}
}

// End of file
