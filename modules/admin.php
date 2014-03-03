<?php
	require_once(dirname(dirname(__FILE__)).'/includes/hook.php');
	require_once(dirname(dirname(__FILE__)).'/includes/settings.php');
	require_once(dirname(dirname(__FILE__)).'/includes/menus.php');
	require_once(dirname(__FILE__).'/switcher.php');
	require_once(dirname(__FILE__).'/push.php');

	class WiziappPluginModuleAdmin
	{
		function init()
		{
			wiziapp_plugin_hook()->hookLoad(array(&$this, 'load'));
			wiziapp_plugin_hook()->hookLoadAdmin(array(&$this, 'loadAdmin'));
		}

		function load()
		{
			if (!isset($_GET['wiziapp_plugin']) || $_GET['wiziapp_plugin'] !== 'verify' || !isset($_GET['wiziapp_plugin_nonce']) || !isset($_GET['wiziapp_plugin_action']))
			{
				return;
			}
			$i = wp_nonce_tick();
			echo ($_GET['wiziapp_plugin_nonce'] === wp_hash($i.'wiziapp_plugin_'.$_GET['wiziapp_plugin_action'], 'nonce') || $_GET['wiziapp_plugin_nonce'] === wp_hash(($i-1).'wiziapp_plugin_'.$_GET['wiziapp_plugin_action'], 'nonce'))?'1':'0';
			exit;
		}

		function loadAdmin()
		{
			add_action('admin_menu', array(&$this, 'admin_menu'));
			add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
			add_action('wp_ajax_wiziapp_plugin_admin_settings_update', array(&$this, 'update_settings'));
			add_action('wp_ajax_wiziapp_plugin_upgrade_dismiss', array(&$this, 'upgrade_dismiss'));
		}

		function admin_enqueue_scripts($page)
		{
			wp_register_style('wiziapp-plugin-admin-icon', wiziapp_plugin_hook()->plugins_url('/styles/admin-icon.css'), array());
			wp_enqueue_style('wiziapp-plugin-admin-icon');
			if ($page !== 'toplevel_page_wiziapp-plugin-settings')
			{
				return;
			}
			wp_enqueue_media();
			add_thickbox();
			wp_register_style('wiziapp-plugin-admin', wiziapp_plugin_hook()->plugins_url('/styles/admin.css'), array());
			wp_register_script('wiziapp-plugin-admin', wiziapp_plugin_hook()->plugins_url('/scripts/admin.js'), array('wp-plupload'));

			wp_enqueue_style('wiziapp-plugin-admin');
			wp_enqueue_script('wiziapp-plugin-admin');
			wp_enqueue_script('customize-loader');
		}

		function admin_menu()
		{
			add_menu_page(__('Wiziapp', 'wiziapp-plugin'), __('Wiziapp', 'wiziapp-plugin'), 'administrator', 'wiziapp-plugin-settings', array(&$this, 'admin_menu_page'), 'none');
		}

		function update_settings()
		{
			if (!isset($_POST['name']) || !isset($_POST['value']))
			{
				return;
			}
			switch ($_POST['name'])
			{
				case 'analytics':
					wiziapp_plugin_settings()->setAnalytics($_POST['value']);
					break;
				case 'webapp_active':
					wiziapp_plugin_settings()->setWebappActive($_POST['value'] === 'true');
					break;
				case 'webapp_tablet_active':
					wiziapp_plugin_settings()->setWebappTabletActive($_POST['value'] === 'true');
					break;
				case 'webapp_theme':
					wiziapp_plugin_settings()->setWebappTheme($_POST['value']);
					break;
				case 'webapp_icon':
					$succ = wiziapp_plugin_settings()->setWebappIcon($_POST['value']);
					echo json_encode(array('extra' => array('url' => wiziapp_plugin_settings()->getWebappIcon(), 'error' => !$succ)));
					exit;
				case 'webapp_name':
					wiziapp_plugin_settings()->setWebappName($_POST['value']);
					break;
				case 'webapp_navigation':
					wiziapp_plugin_settings()->setWebappMenu($_POST['value']);
					break;
				case 'android_active':
					wiziapp_plugin_settings()->setAndroidActive($_POST['value'] === 'true');
					break;
				case 'android_theme':
					wiziapp_plugin_settings()->setAndroidTheme($_POST['value']);
					break;
				case 'android_icon':
					wiziapp_plugin_settings()->setAndroidNeedBuild(true);
					$succ = wiziapp_plugin_settings()->setAndroidIcon($_POST['value']);
					echo json_encode(array('states' => array('android_app'), 'extra' => array('url' => wiziapp_plugin_settings()->getAndroidIcon(), 'error' => !$succ)));
					exit;
				case 'android_name':
					if ($_POST['value'] !== wiziapp_plugin_settings()->getAndroidName())
					{
						wiziapp_plugin_settings()->setAndroidNeedBuild(true);
						wiziapp_plugin_settings()->setAndroidName($_POST['value']);
						echo '{"states":["android_app"]}';
						exit;
					}
					break;
				case 'android_splash':
					wiziapp_plugin_settings()->setAndroidNeedBuild(true);
					$succ = wiziapp_plugin_settings()->setAndroidSplash($_POST['value']);
					echo json_encode(array('states' => array('android_app'), 'extra' => array('url' => wiziapp_plugin_settings()->getAndroidSplash(), 'error' => !$succ)));
					exit;
				case 'android_navigation':
					wiziapp_plugin_settings()->setAndroidMenu($_POST['value']);
					break;
				case 'android_intro_screen':
					wiziapp_plugin_settings()->setAndroidIntroScreen($_POST['value'] === 'true');
					break;
				case 'android_push_sender_id':
					if ($_POST['value'] !== wiziapp_plugin_settings()->getAndroidPushSenderId())
					{
						wiziapp_plugin_settings()->setAndroidNeedBuild(true);
						wiziapp_plugin_settings()->setAndroidPushSenderId($_POST['value']);
						echo '{"states":["android_app"]}';
						exit;
					}
					break;
				case 'android_push_api_key':
					if ($_POST['value'] !== wiziapp_plugin_settings()->getAndroidPushApiKey())
					{
						wiziapp_plugin_settings()->setAndroidPushApiKey($_POST['value']);
						wiziapp_plugin_module_push()->setup();
					}
					break;
				case 'android_admob_id':
					if ($_POST['value'] !== wiziapp_plugin_settings()->getAndroidAdmobId())
					{
						wiziapp_plugin_settings()->setAndroidNeedBuild(true);
						wiziapp_plugin_settings()->setAndroidAdmobId($_POST['value']);
						echo '{"states":["android_app"]}';
						exit;
					}
					break;
				case 'android_app':
					$value = (wiziapp_plugin_settings()->getAndroidName() === '' || wiziapp_plugin_settings()->getAndroidIcon() === false || wiziapp_plugin_settings()->getAndroidSplash() === false)?'need-values':(wiziapp_plugin_settings()->getAndroidNeedBuild()?'need-build':(wiziapp_plugin_settings()->getAndroidBuildToken()?'building':'download'));
					echo '{"value":"'.$value.'","extra":"'.addcslashes(wiziapp_plugin_settings()->getAndroidDownload(), "\\\0..\37\"'\177..\377").'"}';
					exit;
/*				case 'ipad_active':
					wiziapp_plugin_settings()->setIPadActive($_POST['value'] === 'true');
					break;
				case 'ipad_theme':
					wiziapp_plugin_settings()->setIPadTheme($_POST['value']);
					break;
				case 'ipad_navigation':
					wiziapp_plugin_settings()->setIPadMenu($_POST['value']);
					break;*/
				case 'adsense_client':
					wiziapp_plugin_settings()->setAdsenseClient($_POST['value']);
					break;
				case 'adsense_slot':
					wiziapp_plugin_settings()->setAdsenseSlot($_POST['value']);
					break;
				case 'ad_iframe_url':
					wiziapp_plugin_settings()->setAdIFrameUrl($_POST['value']);
					break;
				case 'ad_iframe_width':
					wiziapp_plugin_settings()->setAdIFrameWidth($_POST['value']);
					break;
				case 'ad_iframe_height':
					wiziapp_plugin_settings()->setAdIFrameHeight($_POST['value']);
					break;
			}
			echo '{}';
			exit;
		}

		function upgrade_dismiss()
		{
			/* Remove as many of the old plugin's settings as we can properly detect
			 * Not done here:
			 * Deleting the wp-uploads/wiziapp_data_files folder
			 * Deleting extra tables and metadata
			 * Sending deactivation message to old server
			 * Deleting QR code widget options
			 */
			wp_clear_scheduled_hook('wiziapp_daily_function_hook');
			delete_option('wiziapp_screens');
			delete_option('wiziapp_components');
			delete_option('wiziapp_pages');
			delete_option('wiziapp_last_processed');
			delete_option('wiziapp_featured_post');
			delete_option('wiziapp_settings');
		}

		function admin_menu_page()
		{
?>
<div class="wiziapp-plugin-admin-container" data-wiziapp-plugin-admin-url="<?php echo esc_attr(function_exists('admin_url')?admin_url():(trailingslashit(get_bloginfo('wpurl')).'wp-admin/')); ?>">
	<div class="wiziapp-plugin-admin-header">
		<div class="wiziapp-plugin-admin-header-tab wiziapp-plugin-admin-header-tab-active" id="wiziapp-plugin-admin-tab-header-settings">
				<?php _e('Settings', 'wiziapp-plugin'); ?>

		</div>
		<div class="wiziapp-plugin-admin-header-tab" id="wiziapp-plugin-admin-tab-header-themes">
				<?php _e('Themes', 'wiziapp-plugin'); ?>

		</div>
		<div class="wiziapp-plugin-admin-header-tab" id="wiziapp-plugin-admin-tab-header-plugins">
				<?php _e('Plugins', 'wiziapp-plugin'); ?>

		</div>
		<div class="wiziapp-plugin-admin-header-links">
			<a href="http://wordpress.org/support/view/plugin-reviews/wiziapp-create-your-own-native-iphone-app?filter=5" id="wiziapp-plugin-admin-header-links-rate"></a>
			<a href="https://www.facebook.com/pages/Wiziapp/642159355802851" id="wiziapp-plugin-admin-header-links-fb"></a>
			<a href="http://twitter.com/WiziApp" id="wiziapp-plugin-admin-header-links-twitter"></a>
			<a href="https://plus.google.com/u/1/b/100834172826355967488/100834172826355967488/posts" id="wiziapp-plugin-admin-header-links-gplus"></a>
			<a href="http://www.wiziapp.com/blog/feed/" id="wiziapp-plugin-admin-header-links-rss"></a>
		</div>
	</div>
	<div class="wiziapp-plugin-admin-tab wiziapp-plugin-admin-tab-active" id="wiziapp-plugin-admin-tab-settings">
<?php
			$this->_renderTabSettings();
?>
	</div>
	<div class="wiziapp-plugin-admin-tab" id="wiziapp-plugin-admin-tab-themes">
<?php
			$this->_renderTabThemes();
?>
	</div>
	<div class="wiziapp-plugin-admin-tab" id="wiziapp-plugin-admin-tab-plugins">
<?php
			$this->_renderTabPlugins();
?>
	</div>
<?php
	if (get_option('wiziapp_settings') !== false && !class_exists('WiziappConfig'))
	{
?>
		<div id="wiziapp-plugin-admin-upgraded" class="hidden" data-title="<?php _e('Upgrade', 'wiziapp-plugin'); ?>">
			<div class="wiziapp-plugin-ajax-loader-container">
				<div class="wiziapp-plugin-admin-upgraded-description">
					<p><?php _e('Welcome to the new WiziApp plugin version, which includes a major upgrade with new features and mobile themes directory to choose from. You can always customize your App’s settings and your mobile theme from the WiziApp plugin control panel.', 'wiziapp-plugin') ?></p>
					<p><?php _e('<strong>Users that have already purchased a premium license in the past</strong>, please contact <a href="mailto:support@wiziapp.com">support@wiziapp.com</a> in order to activate the Android App and the new “Pro” theme, free of charge.', 'wiziapp-plugin') ?></p>
					<p><?php _e('Users that have already purchased a native iPhone App, please download and activate the "Wiziapp iOS" plugin in order to keep your iPhone App activated.', 'wiziapp-plugin') ?></p>
					<p><?php _e('Please don’t hesitate to contact us for any further information.', 'wiziapp-plugin') ?></p>
				</div>
				<a href="#" class="wiziapp-plugin-admin-upgraded-donelink"><?php _e('Done', 'wiziapp-plugin'); ?></a>
				<div class="wiziapp-plugin-ajax-loader hidden"></div>
			</div>
		</div>
<?php
	}
?>
	<div class="wiziapp-plugin-ajax-loader-container wiziapp-plugin-admin-overlay">
		<div class="wiziapp-plugin-ajax-loader"></div>
	</div>
</div>
<?php
		}

		function _renderTabSettings()
		{
			wiziapp_plugin_settings()->setAutocommit(false);
			$themes = wiziapp_plugin_module_switcher()->get_themes();
			// Double check existence of configured themes
			if (isset($themes['wiziapp']))	// This should always be true. But better safe than sorry
			{
				if (!isset($themes[wiziapp_plugin_settings()->getWebAppTheme()]))
				{
					wiziapp_plugin_settings()->setWebAppTheme('wiziapp');
				}
				if (!isset($themes[wiziapp_plugin_settings()->getAndroidTheme()]))
				{
					wiziapp_plugin_settings()->setAndroidTheme('wiziapp');
				}
/*				if (!isset($themes[wiziapp_plugin_settings()->getIPadTheme()]))
				{
					wiziapp_plugin_settings()->setIPadTheme('wiziapp');
				}*/
			}
			$menus = wiziapp_plugin_menus()->getMenus();
			// Double check existence of configured menus
			if (function_exists('wp_get_nav_menu_object') && ($menu = wp_get_nav_menu_object('wiziapp_custom')))
			{
				$menu_id = strval($menu->term_id);
				if (!isset($menus[wiziapp_plugin_settings()->getWebappMenu()]))
				{
					wiziapp_plugin_settings()->setWebappMenu($menu_id);
				}
				if (!isset($menus[wiziapp_plugin_settings()->getAndroidMenu()]))
				{
					wiziapp_plugin_settings()->setAndroidMenu($menu_id);
				}
/*				if (!isset($menus[wiziapp_plugin_settings()->getIPadMenu()]))
				{
					wiziapp_plugin_settings()->setIPadMenu($menu_id);
				}*/
			}
			wiziapp_plugin_settings()->setAutocommit(true);
			$this->_renderOptionsBox(array(
				'id' => 'general',
				'title' => __('General Settings', 'wiziapp-plugin'),
				'items' => array(
					array(
						'id' => 'analytics',
						'type' => 'text',
						'label' => __('Google Analytics', 'wiziapp-plugin'),
						'description' => __(' Enter the full code: &#34;UA-..&#34;', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getAnalytics(),
						'extra' => array(
							'type' => 'description',
							'description' => __('Google Analytics ID', 'wiziapp-plugin')
						)
					)
				)
			));
			$this->_renderOptionsBox(array(
				'id' => 'html5',
				'title' => __('HTML5 WebApp Settings', 'wiziapp-plugin'),
				'items' => array(
					array(
						'id' => 'webapp_active',
						'type' => 'switch',
						'label' => __('Activate WebApp', 'wiziapp-plugin'),
						'description' => __('Activate the HTML5 WebApp for users that access your website from their smartphone&#39;s browsers', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getWebappActive()
					),
					array(
						'id' => 'webapp_tablet_active',
						'type' => 'switch',
						'label' => __('Activate WebApp for Tablets', 'wiziapp-plugin'),
						'description' => __('Activate the HTML5 WebApp for users that access your website from their tablet&#39;s browsers', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getWebappTabletActive()
					),
					array(
						'id' => 'webapp_theme',
						'type' => 'select',
						'label' => __('Theme', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getWebAppTheme(),
						'options' => $themes,
						'extra' => array(
							'type' => 'button',
							'label' => __('Customize theme', 'wiziapp-plugin')
						)
					),
					array(
						'id' => 'webapp_icon',
						'type' => 'image',
						'label' => __('Home Screen shortcut icon', 'wiziapp-plugin'),
						'description' => __('Please upload your App icon, 1024*1024 PNG file, the icon is a graphic that represents your application on the device&#39;s home screen', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getWebappIcon()
					),
					array(
						'id' => 'webapp_name',
						'type' => 'text',
						'label' => __('Home Screen icon title', 'wiziapp-plugin'),
						'description' => __('A title that will display below your icon. We recommend to use up to 10 characters', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getWebappName()
					),
					array(
						'id' => 'webapp_navigation',
						'type' => 'select',
						'label' => __('Navigation', 'wiziapp-plugin'),
						'description' => __('Choose your Web App Menu. You can use the default menu, or customize it', 'wiziapp-plugin'),
						'description_link_text' => __('here', 'wiziapp-plugin'),
						'description_link' => '#',
						'value' => wiziapp_plugin_settings()->getWebappMenu(),
						'options' => $menus,
						'extra' => array(
							'type' => 'button',
							'label' => __('Edit menu', 'wiziapp-plugin')
						)
					)
				)
			));
			ob_start();
?>
<div class="wiziapp-plugin-admin-build-android-description">
	<ul>
		<li><?php _e('Create your own Native Android App', 'wiziapp-plugin') ?></li>
		<li><?php _e('Publish your App to Google play Store', 'wiziapp-plugin') ?></li>
		<li><?php _e('Unlimited push notification', 'wiziapp-plugin') ?></li>
	</ul>
</div>
<div class="wiziapp-plugin-admin-build-android-billing">
	<div class="wiziapp-plugin-admin-build-android-billing-price">
		<span class="wiziapp-plugin-admin-build-android-billing-price-amount"><?php _e('$79', 'wiziapp-plugin') ?></span><span class="wiziapp-plugin-admin-build-android-billing-price-duration"><?php _e('/Year', 'wiziapp-plugin') ?></span>
		<div class="wiziapp-plugin-admin-build-android-billing-price-comment"><?php _e('Not Auto Renewable', 'wiziapp-plugin') ?></div>
	</div>
	<div class="wiziapp-plugin-admin-build-android-billing-buy">
		<a href="#" title="<?php _e('Android Application', 'wiziapp-plugin') ?>"><?php _e('Create Now', 'wiziapp-plugin') ?><span></span></a>
	</div>
	<div class="wiziapp-plugin-admin-build-android-billing-license">
		<?php _e('Already have a license key?', 'wiziapp-plugin') ?> <a href="#" title="<?php _e('License', 'wiziapp-plugin') ?>"><?php _e('Activate now', 'wiziapp-plugin') ?></a>
	</div>
</div>
<?php
			$buy_box = ob_get_clean();
			$this->_renderOptionsBox(array(
				'id' => 'android',
				'title' => __('Android App', 'wiziapp-plugin'),
				'state' => 'loading',
				'states' => array(
					'loading' => '<div class="wiziapp-plugin-ajax-loader"></div>',
					'buy' => $buy_box
				),
				'items' => array(
					array(
						'id' => 'android_active',
						'type' => 'switch',
						'label' => __('Activate Android App', 'wiziapp-plugin'),
						// 'description' => __('Activate the Android App for users that access your website from smartphone&#39;s and Tablets/iPad browsers ', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getAndroidActive()
					),
					array(
						'id' => 'android_theme',
						'type' => 'select',
						'label' => __('Theme', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getAndroidTheme(),
						'options' => $themes,
						'extra' => array(
							'type' => 'button',
							'label' => __('Customize theme', 'wiziapp-plugin')
						)
					),
					array(
						'id' => 'android_icon',
						'type' => 'image',
						'label' => __('Home Screen installation icon', 'wiziapp-plugin'),
						'description' => __('Please upload your App icon, 1024*1024 PNG file, the icon is a graphic that represents your application on the device&#39;s home screen', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getAndroidIcon()
					),
					array(
						'id' => 'android_name',
						'type' => 'text',
						'label' => __('App installation name', 'wiziapp-plugin'),
						'description' => __('A title that will display below your icon. We recommend to use up to 10 characters', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getAndroidName()
					),
					array(
						'id' => 'android_splash',
						'type' => 'image',
						'label' => __('Splash Screen', 'wiziapp-plugin'),
						'description' => __("Splash Screen is the initial screen displayed each time the end user launches your app. Please upload a PNG\n480(width) X 800(height) file", 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getAndroidSplash()
					),
					array(
						'id' => 'android_navigation',
						'type' => 'select',
						'label' => __('Navigation', 'wiziapp-plugin'),
						'description' => __('Choose your Android App Menu. You can use the default menu, or customize it', 'wiziapp-plugin'),
						'description_link_text' => __('here', 'wiziapp-plugin'),
						'description_link' => '#',
						'value' => wiziapp_plugin_settings()->getAndroidMenu(),
						'options' => $menus,
						'extra' => array(
							'type' => 'button',
							'label' => __('Edit menu', 'wiziapp-plugin')
						)
					),
					array(
						'id' => 'android_intro_screen',
						'type' => 'switch',
						'label' => __('Display Intro Screen', 'wiziapp-plugin'),
						'description' => __('Display an intro screen for users that visit your site via an android device&#39;s browser, offering them to download your Android App ', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getAndroidIntroScreen()
					),
					array(
						'id' => 'android_push_sender_id',
						'type' => 'text',
						'label' => __('Google Cloud Project Number', 'wiziapp-plugin'),
						'description' => __('Notify users about new content using push notification, Get this value from the Google Cloud Console,', 'wiziapp-plugin'),
						'description_link_text' => __('See How', 'wiziapp-plugin'),
						'description_link' => 'http://www.wiziapp.com/blog/guides-tutorials/creating-google-cloud-messaging-for-android/',
						'value' => wiziapp_plugin_settings()->getAndroidPushSenderId()
					),
					array(
						'id' => 'android_push_api_key',
						'type' => 'text',
						'label' => __('Google Cloud API Key', 'wiziapp-plugin'),
						'description' => __('To enable the push notification service, register your App with Google cloud,', 'wiziapp-plugin'),
						'description_link_text' => __('See How', 'wiziapp-plugin'),
						'description_link' => 'http://www.wiziapp.com/blog/guides-tutorials/creating-google-cloud-messaging-for-android/',
						'value' => wiziapp_plugin_settings()->getAndroidPushApiKey()
					),
					array(
						'id' => 'android_admob_id',
						'type' => 'text',
						'label' => __('Admob Id', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getAndroidAdmobId()
					),
					array(
						'id' => 'android_app',
						'type' => 'state',
						'label' => __('APK File', 'wiziapp-plugin'),
						'description' => __('The APK file is the final installation file of your App. To recreate the APK, just re-enter the &#34;Project Number&#34;.', 'wiziapp-plugin'),
						'value' => (wiziapp_plugin_settings()->getAndroidName() === '' || wiziapp_plugin_settings()->getAndroidIcon() === false || wiziapp_plugin_settings()->getAndroidSplash() === false)?'need-values':(wiziapp_plugin_settings()->getAndroidNeedBuild()?'need-build':(wiziapp_plugin_settings()->getAndroidBuildToken()?'building':'download')),
						'states' => array(
							'need-values' => array(
								'content' => __('Icon, splash, and application name are required to build an application', 'wiziapp-plugin')
							),
							'need-build' => array(
								'content' => '<input type="button" value="'.__('Create new APK', 'wiziapp-plugin').'" /><span>'.__('Please click this button to update your APK with the latest changes', 'wiziapp-plugin').'</span>'
							),
							'building' => array(
								'content' => '<span>'.__('Building APK file...', 'wiziapp-plugin').'</span>',
								'temporary' => true
							),
							'download' => array(
								'content' => '<a href="'.esc_attr(wiziapp_plugin_settings()->getAndroidDownload()).'">'.__('Download APK', 'wiziapp-plugin').'</a>'
							)
						)
					),
					array(
						'id' => 'android_published',
						'type' => 'state',
						'label' => __('Google Play Store', 'wiziapp-plugin'),
						'description' => __('Publish your Android App to the Google Play Store.', 'wiziapp-plugin'),
						'description_link_text' => __('See How', 'wiziapp-plugin'),
						'description_link' => 'http://www.wiziapp.com/blog/guides-tutorials/publishing-your-android-app/',
						'value' => (wiziapp_plugin_settings()->getAndroidPackage() === false)?'no-app':(wiziapp_plugin_settings()->getAndroidPublished()?'published':'not-published'),
						'states' => array(
							'no-app' => array(
								'content' => __('Application not yet built', 'wiziapp-plugin')
							),
							'not-published' => array(
								'content' => '<span><span></span>'.__('Not published', 'wiziapp-plugin').'</span>'
							),
							'published' => array(
								'content' => '<span><span></span>'.__('Published', 'wiziapp-plugin').'</span>',
							)
						)
/*					),
					array(
						'id' => 'android_page',
						'type' => 'link',
						'label' => __('App Page', 'wiziapp-plugin'),
						'description' => __('Use the App Page in order to promote your app', 'wiziapp-plugin'),
						'description_link_text' => __('See How', 'wiziapp-plugin'),
						'description_link' => 'http://droidyoursite.com/blog/?p=12',
						'value' => false,
						'value_label' => __('App Page Link', 'wiziapp-plugin'),
						'value_label_no_link' => __('App Page not yet available', 'wiziapp-plugin')*/
					)
				)
			));
			ob_start();
?>
<div class="wiziapp-plugin-admin-build-ios-description">
	<ul>
		<li><?php _e('Create your own Native iPhone &amp; iPad Apps', 'wiziapp-plugin') ?></li>
		<li><?php _e('Publish your App to Apple AppStore', 'wiziapp-plugin') ?></li>
		<li><?php _e('Unlimited push notification', 'wiziapp-plugin') ?></li>
	</ul>
</div>
<div class="wiziapp-plugin-admin-build-ios-billing">
	<div class="wiziapp-plugin-admin-build-ios-billing-price">
		<div class="wiziapp-plugin-admin-build-ios-billing-price-comment"><?php _e('Starting from', 'wiziapp-plugin') ?></div>
		<span class="wiziapp-plugin-admin-build-ios-billing-price-amount"><?php _e('$199', 'wiziapp-plugin') ?></span><span class="wiziapp-plugin-admin-build-ios-billing-price-duration"><?php _e('/Year', 'wiziapp-plugin') ?></span>
		<div class="wiziapp-plugin-admin-build-ios-billing-price-comment"><?php _e('Not Auto Renewable', 'wiziapp-plugin') ?></div>
	</div>
	<div class="wiziapp-plugin-admin-build-ios-billing-buy">
		<a href="#TB_inline?width=800&height=600&inlineId=wiziapp-plugin-admin-ios-download" class="thickbox" title="<?php _e('Create now', 'wiziapp-plugin') ?>"><?php _e('Create Now', 'wiziapp-plugin') ?><span></span></a>
	</div>
</div>
<?php
			$buy_box = ob_get_clean();
			$this->_renderOptionsBox(array(
				'id' => 'ios',
				'title' => __('Native iOS App', 'wiziapp-plugin'),
				'state' => 'buy',
				'states' => array(
					'buy' => $buy_box
				),
				'items' => array()
			));
/*			$this->_renderOptionsBox(array(
				'id' => 'ipad',
				'title' => __('iPad Settings', 'wiziapp-plugin'),
				'items' => array(
					array(
						'id' => 'ipad_active',
						'type' => 'switch',
						'label' => __('Activate iPad App', 'wiziapp-plugin'),
						'description' => __('Activate the WebApp for users that access your website using an iPad device, this button also enable the iPad native App, Install the &#34;Wiziapp iOS&#34; plugin for purchasing the iPad native App.', 'wiziapp-plugin'),
						'description_link_text' => __('Install the Wiziapp plugin', 'wiziapp-plugin'),
						'description_link' => 'http://wiziapp.com/',
						'value' => wiziapp_plugin_settings()->getIPadActive()
					),
					array(
						'id' => 'ipad_theme',
						'type' => 'select',
						'label' => __('Theme', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getIPadTheme(),
						'options' => $themes,
						'extra' => array(
							'type' => 'button',
							'label' => __('Customize theme', 'wiziapp-plugin')
						)
					),
					array(
						'id' => 'ipad_navigation',
						'type' => 'select',
						'label' => __('Navigation', 'wiziapp-plugin'),
						'description' => __('Choose your iPad App Menu. You can use the default menu, or customize it', 'wiziapp-plugin'),
						'description_link_text' => __('here', 'wiziapp-plugin'),
						'description_link' => '#',
						'value' => wiziapp_plugin_settings()->getIPadMenu(),
						'options' => $menus,
						'extra' => array(
							'type' => 'button',
							'label' => __('Edit menu', 'wiziapp-plugin')
						)
					)
				)
			));*/
			$this->_renderOptionsBox(array(
				'id' => 'monetization',
				'title' => __('Monetization', 'wiziapp-plugin'),
				'items' => array(
					array(
						'id' => 'adsense_client',
						'type' => 'text',
						'label' => __('Google Adsense Publisher ID', 'wiziapp-plugin'),
						'description' => __('You will find the Adsense publisher ID on your Google Adsense account - Account settings tab - Account information section.', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getAdsenseClient()
					),
					array(
						'id' => 'adsense_slot',
						'type' => 'text',
						'label' => __('Google Adsense Slot ID', 'wiziapp-plugin'),
						'description' => __('To get the Slot ID, you should log on to your account, add a new ad unit, size “320×50”. Once you save your new ad unit OR just view an existing one, please click the “Get Code” and you should find within the code the “google_ad_slot”, please enter here its numeric value.', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getAdsenseSlot()
					),
					array(
						'id' => 'ad_iframe_url',
						'type' => 'text',
						'label' => __('iFrame ad URL', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getAdIFrameUrl()
					),
					array(
						'id' => 'ad_iframe_width',
						'type' => 'text',
						'label' => __('iFrame ad width', 'wiziapp-plugin'),
						'description' => __('Width of iFrame ad in pixels', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getAdIFrameWidth()
					),
					array(
						'id' => 'ad_iframe_height',
						'type' => 'text',
						'label' => __('iFrame ad height', 'wiziapp-plugin'),
						'description' => __('Height of iFrame ad in pixels', 'wiziapp-plugin'),
						'value' => wiziapp_plugin_settings()->getAdIFrameHeight()
					)
				)
			));
?>
		<div id="wiziapp-plugin-admin-ios-download" class="hidden">
			<div class="wiziapp-plugin-ajax-loader-container">
				<div class="wiziapp-plugin-admin-ios-download-description"><?php _e('In order to create your own Native iOS App please download and install the "WiziApp iOS App" plugin', 'wiziapp-plugin') ?></div>
				<div class="wiziapp-plugin-admin-ios-download-button"><a href="<?php echo esc_attr(wiziapp_plugin_hook()->plugins_url('/wiziapp-ios-app.zip'));?>"><?php _e('Download Now', 'wiziapp-plugin') ?></a></div>
				<div class="wiziapp-plugin-ajax-loader hidden"></div>
			</div>
		</div>

		<div id="wiziapp-plugin-admin-settings-box-change-note" class="hidden">
			<p>
<?php
			_e('Please note, if you are using a cached plugin or a cached service, this change may take several hours to take effect. You can clear the cached in order for it to take effect immediately.', 'wiziapp-plugin');
?>
			</p>
		</div>
<?php
		}

		function _renderOptionsBox($box)
		{
?>
		<div class="wiziapp-plugin-admin-settings-box<?php
			if (!empty($box['states']))
			{
?>
 wiziapp-plugin-admin-settings-box-has-states<?php
			}
?>" id="wiziapp-plugin-admin-settings-box-<?php echo esc_attr($box['id']); ?>">
			<div class="wiziapp-plugin-admin-settings-box-header">
				<span class="wiziapp-plugin-admin-settings-box-header-text">
					<?php echo $box['title']; ?>

				</span>
			</div>
<?php
			if (!empty($box['states']))
			{
				foreach ($box['states'] as $state => $content)
				{
?>
			<div class="wiziapp-plugin-admin-settings-box-body<?php
			if (!empty($box['state']) && $box['state'] === $state)
			{
?>
 wiziapp-plugin-admin-settings-box-body-active<?php
			}
?>" id="wiziapp-plugin-admin-settings-box-<?php echo $box['id']; ?>-body-<?php echo esc_attr($state); ?>">
				<?php echo $content; ?>

			</div>
<?php
				}
			}
?>
			<div class="wiziapp-plugin-admin-settings-box-body<?php
			if (!empty($box['states']) && empty($box['state']))
			{
?>
 wiziapp-plugin-admin-settings-box-body-active<?php
			}
?>"<?php
			if (!empty($box['states']))
			{
?>
 id="wiziapp-plugin-admin-settings-box-<?php echo $box['id']; ?>-body"<?php
			}
?>>
<?php
			foreach ($box['items'] as $item)
			{
				$this->_renderOptionsItem($item);
			}
?>
				<div class="wiziapp-plugin-admin-settings-box-options-end">
				</div>
			</div>
		</div>
<?php
		}

		function _renderOptionsItem($item)
		{
?>
				<div class="wiziapp-plugin-admin-settings-box-option wiziapp-plugin-admin-settings-box-option-<?php echo $item['type']; ?>" data-wiziapp-plugin-admin-option-id="<?php echo $item['id']; ?>">
					<div class="wiziapp-plugin-admin-settings-box-label">
						<?php echo $item['label']; ?>

<?php
			if (isset($item['description']))
			{
?>
						<div class="wiziapp-plugin-admin-settings-box-label-description">
							<?php echo str_replace(PHP_EOL, '<br />', $item['description']); ?>

<?php
				if (isset($item['description_link']))
				{
?>
							<a href="<?php echo esc_attr($item['description_link']); ?>"><?php echo $item['description_link_text']; ?></a>

<?php
				}
?>
						</div>
<?php
			}
?>
					</div>
					<div class="wiziapp-plugin-admin-settings-box-value">
<?php
			if (isset($item['extra']))
			{
				switch ($item['extra']['type'])
				{
					case 'title_value':
						$this->_renderOptionsExtraTitleValue($item['extra']);
						break;
				}
			}
			switch ($item['type'])
			{
				case 'text':
					$this->_renderOptionsValueText($item);
					break;
				case 'select':
					$this->_renderOptionsValueSelect($item);
					break;
				case 'switch':
					$this->_renderOptionsValueSwitch($item);
					break;
				case 'number':
					$this->_renderOptionsValueNumber($item);
					break;
				case 'link':
					$this->_renderOptionsValueLink($item);
					break;
				case 'image':
					$this->_renderOptionsValueImage($item);
					break;
				case 'state':
					$this->_renderOptionsValueState($item);
					break;
			}
			if (isset($item['extra']))
			{
				switch ($item['extra']['type'])
				{
					case 'description':
						$this->_renderOptionsExtraDescription($item['extra']);
						break;
					case 'button':
						$this->_renderOptionsExtraButton($item['extra']);
						break;
				}
			}
?>
					</div>
				</div>
<?php
		}

		function _renderOptionsValueText($item)
		{
?>
						<input type="text" value="<?php echo esc_attr($item['value']); ?>" />
<?php
		}

		function _renderOptionsValueSelect($item)
		{
?>
						<select>
<?php
			foreach ($item['options'] as $key => $value)
			{
?>
							<option value="<?php echo esc_attr($key); ?>"<?php
				if ($item['value'] === (string)$key)
				{
?>
 selected="selected"<?php
				}
?>>
								<?php echo esc_html($value); ?>

							</option>
<?php
			}
?>
						</select>
<?php
		}

		function _renderOptionsValueSwitch($item)
		{
?>
						<div class="wiziapp-plugin-admin-settings-box-value-switch<?php
			if ($item['value'])
			{
?>
 wiziapp-plugin-admin-settings-box-value-switch-on<?php
			}
?>">
						</div>
<?php
		}

		function _renderOptionsValueNumber($item)
		{
?>
						<input type="text" value="<?php echo esc_attr($item['value']); ?>" />
						<span class="wiziapp-plugin-admin-settings-box-value-unit">
							<?php echo $item['value_unit']; ?>

						</span>
<?php
		}

		function _renderOptionsValueLink($item)
		{
?>
						<div class="wiziapp-plugin-admin-settings-box-value-link<?php
			if ($item['value'] !== false)
			{
?>
 wiziapp-plugin-admin-settings-box-value-has-link<?php
			}
?>">
							<a href="<?php echo esc_attr($item['value']); ?>">
								<?php echo $item['value_label']; ?>

							</a>
							<span class="wiziapp-plugin-admin-settings-box-value-no-link">
								<?php echo $item['value_label_no_link']; ?>

							</span>
						</div>
<?php
		}

		function _renderOptionsValueImage($item)
		{
?>
						<div class="wiziapp-plugin-admin-settings-box-value-image<?php
			if ($item['value'] !== false)
			{
?>
 wiziapp-plugin-admin-settings-box-value-has-image<?php
			}
?>">
							<div class="wiziapp-plugin-admin-settings-box-value-image-preview">
								<img src="<?php echo esc_attr($item['value']); ?>" />
							</div>
							<div class="wiziapp-plugin-admin-settings-box-value-image-uploader supports-drag-drop">
								<?php _e('Drop a file here or', 'wiziapp-plugin'); ?>
								<a href="#">
									<?php _e('select a file', 'wiziapp-plugin'); ?>

								</a>
							</div>
							<div class="media-item" style="display: none">
								<div class="progress">
									<div class="percent"></div>
									<div class="bar"></div>
								</div>
							</div>
							<div class="wiziapp-plugin-admin-settings-box-value-image-error">
								<?php _e('Incorrect file type or image size', 'wiziapp-plugin'); ?>
							</div>
						</div>
<?php
		}

		function _renderOptionsValueState($item)
		{
			foreach ($item['states'] as $name => $state)
			{
?>
						<div id="wiziapp-plugin-admin-settings-box-option-<?php echo $item['id']; ?>-state-<?php echo $name; ?>" class="wiziapp-plugin-admin-settings-box-value-state<?php
			if ($item['value'] === $name)
			{
?>
 wiziapp-plugin-admin-settings-box-value-state-active<?php
			}
			if (!empty($state['temporary']))
			{
?>
 wiziapp-plugin-admin-settings-box-value-state-temporary<?php
			}
?>" data-wiziapp-plugin-admin-option-state="<?php echo $name; ?>">
							<?php echo $state['content']; ?>

						</div>
<?php
			}
		}

		function _renderOptionsExtraDescription($item)
		{
?>
						<span class="wiziapp-plugin-admin-settings-box-value-description">
							<?php echo $item['description']; ?>

						</span>
<?php
		}

		function _renderOptionsExtraButton($item)
		{
?>
						<span class="wiziapp-plugin-admin-settings-box-value-button">
							<input type="button" value="<?php echo $item['label']; ?>" />
						</span>
<?php
		}

		function _renderOptionsExtraTitleValue($item)
		{
?>
						<div class="wiziapp-plugin-admin-settings-box-value-title<?php
				if ($item['value'] === false)
				{
?>
 wiziapp-plugin-admin-settings-box-value-title-no-value<?php
				}
?>">
							<?php echo $item['description']; ?>

							<span class="wiziapp-plugin-admin-settings-box-value-title-value">
								<?php echo esc_html($item['value']); ?>

							</span>
						</div>
<?php
		}

		function _renderTabThemes()
		{
			$themes = wiziapp_plugin_module_switcher()->get_themes(false);
			$active_theme = wiziapp_plugin_settings()->getWebappTheme();
			if ($active_theme !== wiziapp_plugin_settings()->getAndroidTheme() || $active_theme !== wiziapp_plugin_settings()->getIPadTheme())
			{
				$active_theme = false;
			}
			$admin_base = function_exists('admin_url')?admin_url():(trailingslashit(get_bloginfo('wpurl')).'wp-admin/');
			foreach ( $themes as $key => $theme )
			{
?>
		<div class="available-theme <?php echo ($key === $active_theme)?'is-active-theme':'is-not-active-theme'; ?>" data-wiziapp-plugin-admin-theme="<?php echo esc_attr($key); ?>">
<?php
				$template    = $theme['Template'];
				$stylesheet  = $theme['Stylesheet'];
				$title       = __($theme['Name'], $theme['TextDomain']);
				$version     = __($theme['Version'], $theme['TextDomain']);
				$author      = __($theme['Author'], $theme['TextDomain']);
				$description = wptexturize(__($theme['Description'], $theme['TextDomain']));

				if (!$title)
				{
					$title = $stylesheet;
				}

				$parent = false;
				if ($template != $stylesheet && isset($themes[$template]))
				{
					$parent = __($themes[$template]['Name'], $themes[$template]['TextDomain']);
					if (!$parent)
					{
						$parent = $template;
					}
				}

				$preview_link = esc_attr(add_query_arg(
					array('preview' => 1, 'template' => urlencode($template), 'stylesheet' => urlencode($stylesheet), 'preview_iframe' => true, 'TB_iframe' => 'true'),
					get_bloginfo('url')));
				$customize_link = esc_attr($admin_base.'customize.php?wiziapp_plugin=customize&theme='.urlencode($stylesheet).'&return='.urlencode($admin_base+'admin.php?page=wiziapp-plugin-settings'));

				$actions = array();
				$actions['activate'] = '<span class="hide-if-not-active-theme">'.__('Current Theme', 'wiziapp-plugin').'</span>';
				$actions['activate'] .= '<a href="#" class="activatelink hide-if-active-theme" title="'
					. esc_attr( sprintf( __( 'Activate &#8220;%s&#8221;' ), $title ) ) . '">' . __( 'Activate' ) . '</a>';

				$actions['preview'] = '<a href="' . $preview_link . '" class="hide-if-customize" title="'
					. esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $title ) ) . '">' . __( 'Preview' ) . '</a>';

				if ( current_user_can( 'edit_theme_options' ) )
				{
					$actions['preview'] .= '<a href="' . $customize_link . '" class="load-customize hide-if-no-customize hide-if-not-active-theme">'
						. __('Customize') . '</a>';
					$actions['preview'] .= '<a href="' . $customize_link . '" class="load-customize hide-if-no-customize hide-if-active-theme">'
						. __('Live Preview') . '</a>';
				}

				$screenshot = $theme['Screenshot'];
				if ($screenshot)
				{
					$screenshot = esc_attr(wiziapp_plugin_module_switcher()->theme_root_uri().'/'.$stylesheet.'/'.$screenshot);
				}
?>
				<a href="<?php echo $preview_link; ?>" class="screenshot hide-if-customize">
<?php
				if ($screenshot)
				{
?>
						<img src="<?php echo $screenshot; ?>" alt="" />
<?php
				}
?>
				</a>
				<a href="<?php echo $customize_link; ?>" class="screenshot load-customize hide-if-no-customize">
<?php
				if ($screenshot)
				{
?>
						<img src="<?php echo $screenshot; ?>" alt="" />
<?php
				}
?>
				</a>

				<h3><?php echo $title; ?></h3>
				<div class="theme-author"><?php printf( __( 'By %s' ), $author ); ?></div>
				<div class="action-links">
					<ul>
<?php
				foreach ( $actions as $action )
				{
?>
							<li><?php echo $action; ?></li>
<?php
				}
?>
						<li class="wiziapp-plugin-theme-install wiziapp-plugin-theme-hide-if-not-need-update"><a href="#" title="<?php _e('Update theme', 'wiziapp-plugin') ?>"><?php _e('Update', 'wiziapp-plugin') ?></a></li>
						<li class="hide-if-no-js"><a href="#" class="theme-detail"><?php _e('Details') ?></a></li>
					</ul>
				</div>

				<div class="themedetaildiv hide-if-js">
					<p><strong><?php _e('Version: '); ?></strong><?php echo $version; ?></p>
					<p><?php echo $description; ?></p>
<?php
				if ($parent !== false)
				{
?>
					<p class="howto"><?php printf( __( 'This <a href="%1$s">child theme</a> requires its parent theme, %2$s.' ) . '</p>', __( 'http://codex.wordpress.org/Child_Themes' ), $parent ); ?></p>
<?php
				}
?>
				</div>
		</div>
<?php
			}
			$preview_link = esc_attr(add_query_arg(
				array('preview' => 1, 'template' => '{p}', 'stylesheet' => '{}', 'preview_iframe' => true, 'TB_iframe' => 'true'),
				get_bloginfo('url')));
			$customize_link = esc_attr($admin_base.'customize.php?wiziapp_plugin=customize&theme={}&return='.urlencode($admin_base+'admin.php?page=wiziapp-plugin-settings'));
?>
		<div class="available-theme wiziapp-plugin-ajax-loader-container">
			<div class="screenshot"></div>
			<div class="wiziapp-plugin-ajax-loader"></div>
		</div>
		<div class="wiziapp-plugin-admin-themes-template hidden">
			<div class="available-theme is-not-active-theme">
				<a href="#" class="screenshot">
					<img src="" alt="" />
				</a>
				<h3></h3>
				<div class="theme-author"><?php printf( __( 'By %s' ), '{}' ); ?></div>
				<div class="action-links">
					<ul>
						<li class="wiziapp-plugin-theme-hide-if-not-installed">
							<span class="hide-if-not-active-theme"><?php _e('Current Theme', 'wiziapp-plugin'); ?></span>
							<a href="#" class="activatelink hide-if-active-theme" title="<?php echo esc_attr( sprintf( __( 'Activate &#8220;%s&#8221;' ), '{}' ) ); ?>"><?php _e( 'Activate' ); ?></a>
						</li>
						<li class="wiziapp-plugin-theme-hide-if-not-installed">
							<a href="<?php echo $preview_link; ?>" class="hide-if-customize" title="<?php echo esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), '{}' ) ); ?>"><?php _e( 'Preview' ); ?></a>
<?php
			if ( current_user_can( 'edit_theme_options' ) )
			{
?>
							<a href="<?php echo $customize_link; ?>" class="load-customize hide-if-no-customize hide-if-not-active-theme"><?php _e('Customize'); ?></a>
							<a href="<?php echo $customize_link; ?>" class="load-customize hide-if-no-customize hide-if-active-theme"><?php _e('Live Preview'); ?></a>
<?php
			}
?>
						</li>
						<li class="wiziapp-plugin-theme-install wiziapp-plugin-theme-hide-if-not-need-update"><a href="#" title="<?php _e('Update theme', 'wiziapp-plugin') ?>"><?php _e('Update', 'wiziapp-plugin') ?></a></li>
						<li class="wiziapp-plugin-theme-install wiziapp-plugin-theme-hide-if-installed"><a href="#" title="<?php _e('Installing theme', 'wiziapp-plugin') ?>"><?php _e('Install', 'wiziapp-plugin') ?></a></li>
						<li class="wiziapp-plugin-theme-pricing wiziapp-plugin-theme-hide-if-installed"><a href="#" title="<?php _e('Pricing', 'wiziapp-plugin') ?>"><?php _e('See Pricing', 'wiziapp-plugin') ?></a></li>
						<li class="wiziapp-plugin-theme-demo wiziapp-plugin-theme-hide-if-installed"><a href=""><?php _e('Live Demo', 'wiziapp-plugin') ?></a></li>
						<li class="hide-if-no-js"><a href="#" class="theme-detail"><?php _e('Details') ?></a></li>
					</ul>
				</div>

				<div class="themedetaildiv hide-if-js">
					<p><strong><?php _e('Version: '); ?></strong></p>
					<p></p>
					<p class="howto"><?php printf( __( 'This <a href="%1$s">child theme</a> requires its parent theme, %2$s.' ) . '</p>', __( 'http://codex.wordpress.org/Child_Themes' ), '{}' ); ?></p>
				</div>
			</div>
		</div>
		<div id="wiziapp-plugin-admin-themes-billing" class="hidden">
			<div class="wiziapp-plugin-ajax-loader-container">
				<div class="wiziapp-plugin-admin-themes-billing-sidebar">
					<ul class="wiziapp-plugin-admin-themes-billing-packages">
					</ul>
					<div class="wiziapp-plugin-admin-themes-billing-license">
						<?php _e('Already have a license key?', 'wiziapp-plugin') ?> <a href="#" title="<?php _e('License', 'wiziapp-plugin') ?>"><?php _e('Activate now', 'wiziapp-plugin') ?></a>
					</div>
					<div class="wiziapp-plugin-admin-themes-billing-demo"><a href=""><?php _e('Live Demo', 'wiziapp-plugin') ?></a></div>
				</div>
				<div class="wiziapp-plugin-admin-themes-billing-main">
					<h3></h3>
					<div class="wiziapp-plugin-admin-themes-billing-screenshot">
						<div class="wiziapp-plugin-admin-themes-billing-screenshot-nav">
							<a href="#" class="wiziapp-plugin-admin-themes-billing-screenshot-prev">&lt;</a>
							<a href="#" class="wiziapp-plugin-admin-themes-billing-screenshot-next">&gt;</a>
						</div>
					</div>
					<div class="wiziapp-plugin-admin-themes-billing-description"></div>
				</div>
				<div class="wiziapp-plugin-admin-themes-billing-end"></div>
				<div class="wiziapp-plugin-ajax-loader hidden"></div>
			</div>
		</div>
		<div id="wiziapp-plugin-admin-billing-type" class="hidden">
			<div class="wiziapp-plugin-ajax-loader-container">
				<div class="wiziapp-plugin-admin-billing-type-details">
					<div class="wiziapp-plugin-admin-billing-type-details-product">
						<span class="wiziapp-plugin-admin-billing-type-details-label"><?php _e('Product: ', 'wiziapp-plugin') ?></span>
					</div>
					<div class="wiziapp-plugin-admin-billing-type-details-license">
						<span class="wiziapp-plugin-admin-billing-type-details-label"><?php _e('License: ', 'wiziapp-plugin') ?></span>
					</div>
					<div class="wiziapp-plugin-admin-billing-type-details-price">
						<span class="wiziapp-plugin-admin-billing-type-details-label"><?php _e('Price: ', 'wiziapp-plugin') ?></span>
					</div>
					<div class="wiziapp-plugin-admin-billing-type-details-terms">
						<input type="checkbox" /> <span class="wiziapp-plugin-admin-billing-type-details-label"><?php _e('I accept WiziApp ', 'wiziapp-plugin') ?><a href="http://wiziapp.com/terms" target="_blank"><?php _e('Terms &amp; Conditions', 'wiziapp-plugin') ?></a></span>
					</div>
				</div>
				<h3 class="wiziapp-plugin-admin-billing-type-selection-title"><?php _e('Proceed with:', 'wiziapp-plugin') ?></h3>
				<div class="wiziapp-plugin-admin-billing-type-selection">
					<p><a href="#" class="wiziapp-plugin-admin-billing-type-paypal"></a></p>
					<p><a href="#" class="wiziapp-plugin-admin-billing-type-cardcom"></a></p>
				</div>
				<div class="wiziapp-plugin-ajax-loader hidden"></div>
			</div>
		</div>
		<div id="wiziapp-plugin-admin-license" class="hidden">
			<div class="wiziapp-plugin-admin-license wiziapp-plugin-ajax-loader-container">
				<h3><?php _e('Input license key', 'wiziapp-plugin') ?></h3>
				<p><input class="wiziapp-plugin-admin-license-key" type="text" /><input class="wiziapp-plugin-admin-license-activate" type="button" value="<?php _e('Activate', 'wiziapp-plugin') ?>" /></p>
				<p class="error hidden"><?php _e('Invalid license, or license already used', 'wiziapp-plugin') ?></p>
				<div class="wiziapp-plugin-ajax-loader hidden"></div>
			</div>
		</div>
<?php
		}

		function _renderTabPlugins()
		{
			require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			$res = plugins_api( 'query_plugins', array( 'author' => 'wiziapp') );
			if (is_wp_error($res))
			{
				return;
			}
			$wp_list_table = _get_list_table('WP_Plugin_Install_List_Table', array( 'screen' => 'plugin-install') );
			$wp_list_table->items = $res->plugins;
			$wp_list_table->display();
		}
	}

	$module = new WiziappPluginModuleAdmin();
	$module->init();
