<?php
	require_once(dirname(dirname(__FILE__)).'/includes/hook.php');

	class WiziappPluginModuleCompatibility
	{
		function init()
		{
			add_filter('supercacherewriteconditions', array(&$this, 'supercacherewriteconditions'));
			wiziapp_plugin_hook()->hookInstall(array(&$this, 'checkW3TotalCache'));
			wiziapp_plugin_hook()->hookInstall(array(&$this, 'checkQuickCache'));
		}

		function supercacherewriteconditions($condition_rules)
		{
			if (!is_array($condition_rules))
			{
				return $condition_rules;
			}

			// Avoid Wiziapp interference
			$condition_rules[] = 'RewriteCond %{HTTP_USER_AGENT} !iPhone [NC]';
			$condition_rules[] = 'RewriteCond %{HTTP_USER_AGENT} !iPod [NC]';
			$condition_rules[] = 'RewriteCond %{HTTP_USER_AGENT} !Android [NC]';
			$condition_rules[] = 'RewriteCond %{HTTP_USER_AGENT} !IEMobile [NC]';
			$condition_rules[] = 'RewriteCond %{HTTP_USER_AGENT} !iPad [NC]';
			$condition_rules[] = 'RewriteCond %{HTTP_USER_AGENT} !wiziapp_user_agent=ipad_app [NC]';
			$condition_rules[] = 'RewriteCond %{HTTP_USER_AGENT} !72dcc186a8d3d7b3d8554a14256389a4 [NC]';

			return $condition_rules;
		}

		function checkW3TotalCache() {
			if (!function_exists('w3_instance')) {
				return;
			}
			$agentgroups = array(
				'wiziapp_html5' => array(
					'(iPhone|iPod).*Mac\ OS\ X',
					'Mac\ OS\ X.*(iPhone|iPod)',
					'Android.*AppleWebKit',
					'AppleWebKit.*Android',
					'Windows.*IEMobile.*Phone',
					'Windows.*Phone.*IEMobile',
					'IEMobile.*Windows.*Phone',
					'IEMobile.*Phone.*Windows',
					'Phone.*Windows.*IEMobile',
					'Phone.*IEMobile.*Windows',
				),
				'wiziapp_android' => array(
					'72dcc186a8d3d7b3d8554a14256389a4'
				),
				'wiziapp_ipad' => array(
					'wiziapp_user_agent=ipad_app'
				)
			);
			$needed = false;
			$config = w3_instance('W3_Config');
			$groups = $config->get_array('mobile.rgroups');
			foreach ($agentgroups as $groupname => $agents)
			{
				if (isset($groups[$groupname])) {
					$group = $groups[$groupname];
					if (count($group) === 4 && isset($group['theme']) && isset($group['enabled']) && isset($group['redirect']) && isset($group['agents']) &&
						$group['theme'] === '' && $group['enabled'] === true && $group['redirect'] === '' && count($group['agents']) === count($agents)) {
						$found = false;
						foreach ($agents as $agent) {
							if (!in_array($agent, $group['agents'])) {
								$found = true;
								break;
							}
						}
						if (!$found) {
							/* This group is already present and enabled */
							continue;
						}
					}
				}
				$needed = true;
				$groups[$groupname] = array(
					'theme' => '',
					'enabled' => true,
					'redirect' => '',
					'agents' => $agents,
				);
			}
			if ($needed)
			{
				$config->set('mobile.rgroups', $groups);
				$config->save(false);
			}
		}

		function checkQuickCache() {
			if (!class_exists ('c_ws_plugin__qcache_menu_pages')) {
				return false;
			}
			$prev_agents = preg_split ("/[\r\n\t]+/", $GLOBALS['WS_PLUGIN__']['qcache']['o']['dont_cache_these_agents']);
			$agents = $prev_agents;
			foreach (array('iPhone', 'iPod', 'Android', 'IEMobile', 'wiziapp_user_agent=ipad_app', '72dcc186a8d3d7b3d8554a14256389a4') as $agent) {
				if (!in_array($agent, $agents)) {
					$agents[] = $agent;
				}
			}
			if ($agents === $prev_agents) {
				return false;
			}
			c_ws_plugin__qcache_menu_pages::update_all_options(array('ws_plugin__qcache_dont_cache_these_agents' => implode(PHP_EOL, $agents)), true, true, false);
			return true;
		}
	}

	$module = new WiziappPluginModuleCompatibility();
	$module->init();
