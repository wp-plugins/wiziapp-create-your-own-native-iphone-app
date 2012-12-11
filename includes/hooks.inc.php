<?php if (!defined('WP_WIZIAPP_BASE')) exit();
/**
* our integration with the wordpress CMS.
* this file attaches the plugin to events in wordpress by using filters and actions
*
* @todo Figure out which method is better, one place of inside the class like contentHandler
*
* @package WiziappWordpressPlugin
* @author comobix.com plugins@comobix.com
*/

//add_action('init', 'wiziapp_init');
//add_action('plugins_loaded', 'wiziapp_attach_hooks');
function wiziapp_attach_hooks(){
	$ce = new WiziappContentEvents();

	//add_action('admin_menu', 'wiziapp_setup_menu');
	add_action( 'admin_menu', array( 'WiziappAdminDisplay', 'setup' ) );

	/* Add a custom column to the users table to indicate that the user
	* logged in from his mobile device via our app
	* NOTE: Some plugins might not handle other plugins columns very nicely and cause the data not to show...
	*/
	add_filter('manage_users_columns', array('WiziappUserList', 'column'));
	add_filter('manage_users_custom_column', array('WiziappUserList', 'customColumn'), 10, 3);

	add_filter('cron_schedules', array('wiziappCronSchedules','addSchedules'));

	add_action('new_to_publish', 	 array(&$ce, 'savePost'));
	add_action('pending_to_publish', array(&$ce, 'savePost'));
	add_action('draft_to_publish', 	 array(&$ce, 'savePost'));
	add_action('private_to_publish', array(&$ce, 'savePost'));
	add_action('future_to_publish',  array(&$ce, 'savePost'));
	add_action('publish_to_publish', array(&$ce, 'savePost'));

	if ( strpos($_SERVER['REQUEST_URI'], 'wp-comments-post.php') !== FALSE && isset($_GET['output']) && $_GET['output'] == 'html' ) {
		$comment_screen = new WiziappCommentsScreen();
		add_action('set_comment_cookies', array(&$comment_screen, 'runBySelf'));
		add_filter('wp_die_handler', array(&$comment_screen, 'set_error_function'), 1);
	}

	if ( !empty(WiziappConfig::getInstance()->settings_done) ){
		add_action( 'edit_post', array( 'WiziappPush', 'create_push_notification' ), 10, 2 );
	}

	add_action( 'edit_post', array( &$ce, 'updateCacheTimestampKey' ) );

	add_action('deleted_post', array(&$ce, 'deletePost'));
	add_action('trashed_post', array(&$ce, 'deletePost'));

	add_action('untrashed_post', array(&$ce, 'recoverPost'));

	add_action('created_term', array(&$ce, 'updateCacheTimestampKey'));
	add_action('edited_term', array(&$ce, 'updateCacheTimestampKey'));
	/**
	* @todo add this function to allow updates and no new post was published notifications
	add_action('publish_to_publish', 'wiziapp_publish_updated_post');
	*/

	/**
	* Notice: publish_post might happen a few times, make sure we are only doing the action once
	* by removing the action once done
	*/
	/*
	add_action('publish_post', array('WiziappContentEvents', 'savePost'));
	add_action('publish_post', 'wiziapp_publish_post');
	*/

	// hook to avoid the Collision with the WP Super Cache
	add_filter('supercacherewriteconditions', array(&$ce, 'add_wiziapp_condition'));

	if ( !empty(WiziappConfig::getInstance()->settings_done) ){
		add_action('wiziapp_daily_function_hook', array('WiziappPush', 'daily'));
		add_action('wiziapp_weekly_function_hook', array('WiziappPush', 'weekly'));
		add_action('wiziapp_monthly_function_hook', array('WiziappPush', 'monthly'));
	}

	// Add "Delete Old Log Files" and "Delete Old Cache Files" daily Wordpress Cron job
	add_action('wiziapp_daily_function_hook', array(WiziappLog::getInstance(), 'deleteOldFiles'));
	add_action('wiziapp_daily_function_hook', array(WiziappCache::getCacheInstance(), 'delete_old_files'));

	// Handle installation functions
	register_deactivation_hook(WP_WIZIAPP_BASE, array('WiziappInstaller', 'uninstall'));
	register_activation_hook(WP_WIZIAPP_BASE, array('WiziappInstaller', 'install'));
	add_action('delete_blog', array('WiziappInstaller', 'deleteBlog'), 10, 2);

	// Update the cache when the settings are changed
	//add_action('updated_option', array('WiziappContentEvents', 'triggerCacheUpdate'));
	//add_action('profile_update', array('WiziappContentEvents', 'triggerCacheUpdateByProfile'));

	// add custom image size
	/**add_image_size('wiziapp-thumbnail', wiziapp_getThumbSize(), wiziapp_getThumbSize(), true );
	add_image_size('wiziapp-small-thumb', wiziapp_getSmallThumbWidth(), wiziapp_getSmallThumbHeight(), true );
	add_image_size('wiziapp-med-thumb', wiziapp_getMedThumbWidth(), wiziapp_getMedThumbHeight(), true );
	add_image_size('wiziapp-iphone', '320', '480', true);*/

	/**
	* Admin ajax hooks
	*/
	// Post install
	add_action('wp_ajax_wiziapp_batch_process_posts',	array('WiziappPostInstallDisplay', 'batchProcess_Posts'));
	add_action('wp_ajax_wiziapp_batch_process_pages',	array('WiziappPostInstallDisplay', 'batchProcess_Pages'));
	add_action('wp_ajax_wiziapp_batch_process_finish',	array('WiziappPostInstallDisplay', 'batchProcess_Finish'));
	add_action('wp_ajax_wiziapp_report_issue',			array('WiziappPostInstallDisplay', 'reportIssue'));

    // Web App
	add_action('wp_ajax_wiziapp_update_handshake',		array('WiziappWebappDisplay', 'updateHandshake'));
	add_action('wp_ajax_wiziapp_update_config',			array('WiziappWebappDisplay', 'updateConfig'));
	add_action('wp_ajax_wiziapp_update_display',		array('WiziappWebappDisplay', 'updateDisplay'));
    add_action('wp_ajax_wiziapp_update_effects',		array('WiziappWebappDisplay', 'updateEffects'));
	add_action('wp_ajax_wiziapp_update_images',			array('WiziappWebappDisplay', 'updateImages'));
    add_action('wp_ajax_wiziapp_update_icons',			array('WiziappWebappDisplay', 'updateIcons'));
    add_action('wp_ajax_wiziapp_update_splash',			array('WiziappWebappDisplay', 'updateSplash'));
    add_action('wp_ajax_wiziapp_update_manifest',		array('WiziappWebappDisplay', 'updateManifest'));
    add_action('wp_ajax_wiziapp_install_webapp_finish', array('WiziappWebappDisplay', 'installFinish'));

	// Upgrade
	add_action('wp_ajax_wiziapp_upgrade_database', 		array('WiziappUpgradeDisplay', 'upgradeDatabase'));
	add_action('wp_ajax_wiziapp_upgrade_configuration', array('WiziappUpgradeDisplay', 'upgradeConfiguration'));
	add_action('wp_ajax_wiziapp_upgrading_finish', 		array('WiziappUpgradeDisplay', 'upgradingFinish'));

	// admin
	add_action('wp_ajax_wiziapp_hide_verify_msg', 		   array('WiziappAdminDisplay', 'hideVerifyMsg'));
	add_action('wp_ajax_wiziapp_hide_upgrade_msg',		   array('WiziappAdminDisplay', 'hideUpgradeMsg'));
	add_action('wp_ajax_wiziapp_hide_display_message_msg', array('WiziappAdminDisplay', 'hideDisplayMessageMsg'));

	// Wizard
	add_action('wp_ajax_wiziapp_register_license', array('WiziappLicenseUpdater', 'register'));

	add_filter('wiziapp_3rd_party_plugin', array('WiziappApi', 'externalPluginContent'), 1, 3);

	// QR Code Widget hook
	if ( ! empty( WiziappConfig::getInstance()->appstore_url ) &&  ! empty( WiziappConfig::getInstance()->app_name ) ) {
		// Run in Wiziapp Application will be available on Appstore case only
		add_action( 'widgets_init', create_function( '', 'register_widget("WiziappQRCodeWidget");' ) );
	}
}

if ( !defined('WP_WIZIAPP_HOOKS_ATTACHED') ) {
	define('WP_WIZIAPP_HOOKS_ATTACHED', TRUE);
	wiziapp_attach_hooks();
}