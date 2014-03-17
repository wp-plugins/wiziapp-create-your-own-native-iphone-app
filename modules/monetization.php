<?php
	require_once(dirname(dirname(__FILE__)).'/includes/settings.php');
	require_once(dirname(dirname(__FILE__)).'/includes/hook.php');
	require_once(dirname(dirname(__FILE__)).'/includes/purchase_hook.php');

	class WiziappPluginModuleMonetization
	{
		function init()
		{
			$hook = new WiziappPluginPurchaseHook();
			$hook->hook('ads', '/ads', array(&$this, '_licensed'));
		}

		function _licensed()
		{
?>
					<script type="text/javascript">
						if (window.parent && window.parent.jQuery) {
							window.parent.jQuery("#wiziapp-plugin-admin-settings-box-monetization-body-buy").removeClass("wiziapp-plugin-admin-settings-box-body-active");
							window.parent.jQuery("#wiziapp-plugin-admin-settings-box-monetization-body").addClass("wiziapp-plugin-admin-settings-box-body-active");
						}
						if (window.parent && window.parent.tb_remove) {
							window.parent.tb_remove();
						}
					</script>
<?php
		}
	}

	$module = new WiziappPluginModuleMonetization();
	$module->init();
