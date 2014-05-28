<?php
	require_once(dirname(dirname(__FILE__)).'/includes/settings.php');
	require_once(dirname(dirname(__FILE__)).'/includes/hook.php');
	require_once(dirname(dirname(__FILE__)).'/includes/purchase_hook.php');

	class WiziappPluginModuleMonetization
	{
		function init()
		{
			$hook = new WiziappPluginPurchaseHook();
			$hook->hook('ads', '/ads', array(&$this, '_licensed'), array(&$this, '_analytics'));
			$hook->hookBalance(array(&$this, '_balance'));
		}

		function _licensed($params, $license)
		{
			if ($license === false)
			{
				return;
			}
			wiziapp_plugin_settings()->setAdAccess(true);
?>
					<script type="text/javascript">
						if (window.parent && window.parent.jQuery) {
							window.parent.jQuery("#wiziapp-plugin-admin-settings-box-monetization-body-buy").hide();
							window.parent.jQuery("#wiziapp-plugin-admin-settings-box-monetization").show();
						}
						if (window.parent && window.parent.tb_remove) {
							window.parent.tb_remove();
						}
					</script>
<?php
		}

		function _balance($balance)
		{
			wiziapp_plugin_settings()->setAdAccess($balance['count'] > 0);
		}

		function _analytics()
		{
			return '/ads/purchased';
		}
	}

	$module = new WiziappPluginModuleMonetization();
	$module->init();
