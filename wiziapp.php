<?php

/**
* Plugin Name: Wiziapp
* Description: WiziApp automatically turns your WordPress blog into a native iPhone app. Customize the app to make it your own by using our friendly wizard.
* Author: Wiziapp Solutions Ltd.
* Version: v1.3.0f
* Author URI: http://www.wiziapp.com/
*/
/**
* This is the plugin entry script, it checks for compatibility and if compatible
* it will loaded the needed files for the CMS plugin
* @package WiziappWordpressPlugin
* @author comobix.com plugins@comobix.com
*
*/

// Run only once
if (!defined('WP_WIZIAPP_BASE')) {
	define('WP_WIZIAPP_BASE', plugin_basename(__FILE__));
	define('WP_WIZIAPP_PROFILER', FALSE);
	define('WIZI_ABSPATH', realpath(ABSPATH));
	define('WIZIAPP_ENV', 'prod'); // can be dev/test/prod
	define('WIZIAPP_VERSION', 'v1.3.0f');   // MAKE SURE TO UPDATE BOTH THIS AND THE UPPER VALUE
	define('WIZIAPP_P_VERSION', '1.3.0');   // The platform version

	if (version_compare (PHP_VERSION, "5.2", ">=") && version_compare (get_bloginfo ("version"), "2.8.4", ">=")) {
		include dirname (__FILE__) . "/includes/classes/WiziappExceptions.php";
		include dirname (__FILE__) . "/includes/blocks.inc.php";
		include dirname (__FILE__) . "/includes/hooks.inc.php";
	} elseif ( is_admin() ) {
		if (!version_compare (PHP_VERSION, "5.2", ">=")) {
			register_shutdown_function ('wiziapp_shutdownWrongPHPVersion');
		} elseif (!version_compare (get_bloginfo ("version"), "2.8.4", ">=")) {
			register_shutdown_function ('wiziapp_shutdownWrongWPVersion');
		}
	}
} else {
	function wiziapp_getDuplicatedInstallMsg() {
		return '<div class="error">'
		. __( 'An older version of the plugin is installed and must be deactivated. To do this, locate the old WiziApp plugin in the WordPress plugins interface and click Deactivate, then activate the new plugin.', 'wiziapp')
		.'</div>';
	}

	die(wiziapp_getDuplicatedInstallMsg());
}

function wiziapp_shutdownWrongPHPVersion() {
	?>
		<script type="text/javascript">alert("<?php echo __('You need PHP version 5.2 or higher to use the WiziApp plugin.', 'wiziapp');?>")</script>
	<?php
}

function wiziapp_shutdownWrongWPVersion() {
	?>
		<script type="text/javascript">alert("<?php echo __('You need WordPress® 2.8.4 or higher to use the WiziApp plugin.', 'wiziapp');?>")</script>
	<?php
}