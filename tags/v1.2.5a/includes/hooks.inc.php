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
    add_action('admin_menu', array('WiziappAdminDisplay', 'setup'));

    /* Add a custom column to the users table to indicate that the user 
    * logged in from his mobile device via our app
    * NOTE: Some plugins might not handle other plugins columns very nicely and cause the data not to show...
    */
    add_filter ('manage_users_columns', array('WiziappUserList', 'column'));
    add_filter ('manage_users_custom_column', array('WiziappUserList', 'customColumn'), 10, 3);

    add_filter('cron_schedules', array('wiziappCronSchedules','addSchedules'));

    add_action('new_to_publish', array(&$ce, 'savePost'));
    add_action('pending_to_publish', array(&$ce, 'savePost'));
    add_action('draft_to_publish', array(&$ce, 'savePost'));
    add_action('private_to_publish', array(&$ce, 'savePost'));
    add_action('future_to_publish', array(&$ce, 'savePost'));
    add_action('publish_to_publish', array(&$ce, 'savePost'));

    if ( !empty(WiziappConfig::getInstance()->settings_done) ){
        add_action('new_to_publish', array('WiziappPush','publishPost'));
        add_action('pending_to_publish', array('WiziappPush','publishPost'));
        add_action('draft_to_publish', array('WiziappPush','publishPost'));
        add_action('private_to_publish', array('WiziappPush','publishPost'));
        add_action('future_to_publish', array('WiziappPush','publishPost'));
    }
    
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
    /**add_action('publish_post', array('WiziappContentEvents', 'savePost'));
    add_action('publish_post', 'wiziapp_publish_post');*/

    if ( !empty(WiziappConfig::getInstance()->settings_done) ){
        add_action('wiziapp_daily_function_hook', array('WiziappPush', 'daily'));
        add_action('wiziapp_weekly_function_hook', array('WiziappPush', 'weekly'));
        add_action('wiziapp_monthly_function_hook', array('WiziappPush', 'monthly'));
    }

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
    add_action('wp_ajax_wiziapp_batch_posts_processing', array('WiziappPostInstallDisplay', 'batchPostsProcessing'));
    add_action('wp_ajax_wiziapp_batch_process_pages', array('WiziappPostInstallDisplay', 'batchProcessPages'));
    add_action('wp_ajax_wiziapp_batch_processing_finish', array('WiziappPostInstallDisplay', 'batchProcessingFinish'));
    add_action('wp_ajax_wiziapp_report_issue', array('WiziappPostInstallDisplay', 'reportIssue'));

    // Upgrade
    add_action('wp_ajax_wiziapp_upgrade_database', array('WiziappUpgradeDisplay', 'upgradeDatabase'));
    add_action('wp_ajax_wiziapp_upgrade_configuration', array('WiziappUpgradeDisplay', 'upgradeConfiguration'));
    add_action('wp_ajax_wiziapp_upgrading_finish', array('WiziappUpgradeDisplay', 'upgradingFinish'));

    // admin
    add_action('wp_ajax_wiziapp_hide_verify_msg', array('WiziappAdminDisplay', 'hideVerifyMsg'));
    add_action('wp_ajax_wiziapp_hide_upgrade_msg', array('WiziappAdminDisplay', 'hideUpgradeMsg'));

    add_filter('wiziapp_3rd_party_plugin', array('WiziappApi', 'externalPluginContent'), 1, 3);
}


if ( !defined('WP_WIZIAPP_HOOKS_ATTACHED') ) {
    define('WP_WIZIAPP_HOOKS_ATTACHED', TRUE);
    wiziapp_attach_hooks();
}
