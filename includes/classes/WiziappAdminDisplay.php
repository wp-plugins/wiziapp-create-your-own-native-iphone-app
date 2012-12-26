<?php
/**
* @package WiziappWordpressPlugin
* @subpackage AdminDisplay
* @author comobix.com plugins@comobix.com
*/

class WiziappAdminDisplay {
	/**
	* Sets up the admin menu according to the application configuration state.
	* for a fully installed app we show a full menu but until then
	* way make things more complicated for the user
	*/
	public function setup(){
		$configured = WiziappConfig::getInstance()->settings_done;

		//$configured = FALSE;
		if (isset($_GET['wiziapp_configured']) && $_GET['wiziapp_configured'] == 1){
			$configured = TRUE;
		}
		if ( isset($_GET['skip_reload_webapp']) && $_GET['skip_reload_webapp'] == 1 ){
			WiziappConfig::getInstance()->skip_reload_webapp = TRUE;
		}

		$iconPath = WiziappConfig::getInstance()->getCdnServer() . "/images/cms/WiziSmallIcon.png";

		$installer = new WiziappInstaller();

		// if (current_user_can('administrator') && !empty($options['app_token'])){
		if ( current_user_can('administrator') ){
			add_action('admin_notices', array('WiziappAdminDisplay', 'configNotice'));
			//add_action('admin_notices', array('WiziappAdminDisplay', 'versionCheck'));
			add_action('admin_notices', array('WiziappAdminDisplay', 'upgradeCheck'));
			add_action('admin_notices', array('WiziappAdminDisplay', 'displayMessageCheck'));
			// Add CSS and Javascript for the Wiziapp Activate notice on the Admin panel
			add_action( 'admin_enqueue_scripts', array('WiziappAdminDisplay', 'styles_javascripts' ) );

			if ( WiziappConfig::getInstance()->finished_processing === FALSE || is_null($configured) ){
				add_menu_page('WiziApp', 'WiziApp', 'administrator', 'wiziapp', array('WiziappPostInstallDisplay', 'display'), $iconPath);
			} elseif ( $installer->needUpgrade() ){
				add_menu_page('WiziApp', 'WiziApp', 'administrator', 'wiziapp', array('WiziappUpgradeDisplay', 'display'), $iconPath);
			} elseif ($configured === FALSE){
				if (isset($_GET['wiziapp_reload_webapp'])) {
					add_menu_page('WiziApp', 'WiziApp', 'administrator', 'wiziapp', array('WiziappWebappDisplay', 'display'), $iconPath);
				}
				else {
					add_menu_page('WiziApp', 'WiziApp', 'administrator', 'wiziapp', array('WiziappGeneratorDisplay', 'display'), $iconPath);
				}
			} elseif ( ! WiziappConfig::getInstance()->webapp_installed && ! WiziappConfig::getInstance()->skip_reload_webapp ){
				add_menu_page('WiziApp', 'WiziApp', 'administrator', 'wiziapp', array('WiziappWebappDisplay', 'display'), $iconPath);
			} else {
				// We are installed and configured
				// add_submenu_page('wiziapp', __('dashboard'), __('dashboard'), 'administrator', 'wiziapp_dashboard_display', 'wiziapp_dashboard_display');
				add_menu_page('WiziApp', 'WiziApp', 'administrator', 'wiziapp', array('WiziappAdminDisplay', 'dashboardDisplay'), $iconPath);
				// This is to avoid having the top menu duplicated as a sub menu
				add_submenu_page('wiziapp', '', '', 'administrator', 'wiziapp', '');

				if (WiziappConfig::getInstance()->app_live !== FALSE){
					add_submenu_page('wiziapp', __('Statistics'), __('Statistics'), 'administrator',
						'wiziapp_statistics_display', array('WiziappAdminDisplay', 'statisticsDisplay'));
				}
				add_submenu_page('wiziapp', __('App Info'), __('App Info'), 'administrator',
					'wiziapp_app_info_display', array('WiziappAdminDisplay', 'appInfoDisplay'));
				add_submenu_page('wiziapp', __('My Account'), __('My Account'), 'administrator',
					'wiziapp_my_account_display', array('WiziappAdminDisplay', 'myAccountDisplay'));
				add_submenu_page('wiziapp', __('Settings'), __('Settings'), 'administrator',
					'wiziapp_settings_display', array('WiziappAdminDisplay', 'settingsDisplay'));

				add_submenu_page('wiziapp', __('Reactivate'), __('Reactivate'), 'administrator',
					'wiziapp_webapp_display', array('WiziappWebappDisplay', 'display'));
			}

			$sd = new WiziappSupportDisplay();
			add_submenu_page('wiziapp', __('Support'), __('Support'), 'administrator',
				'wiziapp_support_display', array($sd, 'display'));
		}

		global $submenu;
		if ((isset($submenu['wiziapp'][2][0]) && $submenu['wiziapp'][2][0] == 'My Account') ||
			(isset($submenu['wiziapp'][3][0]) && $submenu['wiziapp'][3][0] == 'My Account')){
			if ($submenu['wiziapp'][0][0] == 'Create your App'){
				array_shift($submenu['wiziapp']);
			}
		} else {
			$submenu['wiziapp'][0][0] = __('Create your App');
			$submenu['wiziapp'][0][1] = __('administrator');
			$submenu['wiziapp'][0][2] = __('admin.php?page=wiziapp');
			$submenu['wiziapp'][0][3] = __('Create your App');
		}
	}

	public static function dashboardDisplay(){
		self::includeGeneralDisplay('dashboard');
	}

	public static function statisticsDisplay(){
		self::includeGeneralDisplay('statistics');
	}

	public static function settingsDisplay(){
		self::includeGeneralDisplay('settings');
	}

	public static function myAccountDisplay(){
		self::includeGeneralDisplay('myAccount');
	}

	function appInfoDisplay(){
		self::includeGeneralDisplay('appInfo', TRUE);
	}
	protected static function includeGeneralDisplay($display_action, $includeSimOverlay = TRUE){
		$r = new WiziappHTTPRequest();
		$response = $r->api(array(), '/generator/getToken?app_id=' . WiziappConfig::getInstance()->app_id, 'POST');
		if ( is_wp_error($response) ){
			WiziappLog::getInstance()->write('ERROR', 'There was an error getting the token from the admin: '.print_r($response, TRUE), 'WiziappAdminDisplay.includeGeneralDisplay');
			/**
			* @todo get the design for the failure screen
			*/
			echo '<div class="error">'.__('There was a problem contacting wiziapp services, please try again in a few minutes',
				'wiziapp').'</div>';
			exit();
		}

		// We are here, so all is good and the main services are up and running
		$tokenResponse = json_decode($response['body'], TRUE);
		if (!$tokenResponse['header']['status']){
			// There was a problem with the token
			WiziappLog::getInstance()->write('ERROR', 'Got the token from the admin but something is not right::'.print_r($response, TRUE), 'WiziappAdminDisplay.includeGeneralDisplay');
			echo '<div class="error">' . $tokenResponse['header']['message'] . '</div>';
		} else {
			WiziappLog::getInstance()->write('INFO', 'Got the token going to render the display', 'WiziappAdminDisplay.includeGeneralDisplay');
			$token = $tokenResponse['token'];
			$httpProtocol = 'https';
			if ( $includeSimOverlay ){
			?>
			<script src="http://cdn.jquerytools.org/1.2.5/all/jquery.tools.min.js"></script>
			<style>
				#wpadminbar{
					z-index: 99;
				}
				.overlay_close {
					background-image:url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/generator/close.png);
					position:absolute; right:-17px; top:-17px;
					cursor:pointer;
					height:35px;
					width:35px;
				}
				#wiziappBoxWrapper{
					width: 390px;
					height: 760px;
					margin: 0px auto;
					padding: 0px;
				}
			</style>
			<script type="text/javascript">
				var WIZIAPP_HANDLER = (function(){
					jQuery(document).ready(function(){
						jQuery('.report_issue').click(reportIssue);
						jQuery('.retry_processing').click(retryProcessing);

						jQuery('#general_error_modal').bind('closingReportForm', function(){
							jQuery(this).removeClass('s_container')
						});
					});

					function wiziappReceiveMessage(event){
						// Just wrap our handleRequest
						if ( event.origin == '<?php echo "http://" . WiziappConfig::getInstance()->api_server ?>' ||
							event.origin ==  '<?php echo "https://" . WiziappConfig::getInstance()->api_server ?>' ){
							WIZIAPP_HANDLER.handleRequest(event.data);
						}
					};

					if ( window.addEventListener ){
						window.addEventListener("message", wiziappReceiveMessage, false);
					}

					function retryProcessing(event){
						event.preventDefault();
						document.location.reload(true);
						return false;
					};

					function reportIssue(event){
						// Change the current box style so it will enable containing the report form
						event.preventDefault();
						var $box = jQuery('#general_error_modal');
						var $el = $box.find('.report_container');

						var params = {
							action: 'wiziapp_report_issue',
							data: $box.find('.wiziapp_error').text()
						};

						$el.load(ajaxurl, params, function(){
							var $mainEl = jQuery('#general_error_modal');
							$mainEl
							.removeClass('s_container')
							.find(".errors_container").hide().end()
							.find(".report_container").show().end();
							$mainEl = null;
						});

						var $el = null;
						return false;
					};

					var actions = {
						changeTab: function(params){
							top.document.location.replace('<?php echo get_admin_url();?>admin.php?page='+params.page);
						},
						informGeneralError: function(params){
							var $box = jQuery('#'+params.el);
							$box
							.find('.wiziapp_error').text(params.message).end();

							if ( parseInt(params.retry) == 0 ){
								$box.find('.retry_processing').hide();
							} else {
								$box.find('.retry_processing').show();
							}

							if ( parseInt(params.report) == 0 ){
								$box.find('.report_issue').hide();
							} else {
								$box.find('.report_issue').show();
							}

							if (!$box.data("overlay")){
								$box.overlay({
									fixed: true,
									top: 200,
									left: (screen.width / 2) - ($box.outerWidth() / 2),
									/**mask: {
									color: '#fff',
									loadSpeed: 200,
									opacity: 0.1
									},*/
									// disable this for modal dialog-type of overlays
									closeOnClick: false,
									closeOnEsc: false,
									// load it immediately after the construction
									load: true,
									onBeforeLoad: function(){
										var $toCover = jQuery('#wpbody');
										var $mask = jQuery('#wiziapp_error_mask');
										if ( $mask.length == 0 ){
											$mask = jQuery('<div></div>').attr("id", "wiziapp_error_mask");
											jQuery("body").append($mask);
										}

										$mask.css({
											position:'absolute',
											top: $toCover.offset().top,
											left: $toCover.offset().left,
											width: $toCover.outerWidth(),
											height: $toCover.outerHeight(),
											display: 'block',
											opacity: 0.9,
											backgroundColor: '#444444'
										});

										$mask = $toCover = null;
									}
								});
							}
							else {
								$box.show();
								$box.data("overlay").load();
							}
							$box = null;
						},
						showProcessing: function(params){
							var $box = jQuery('#'+params.el);
							$box
							.find('.error').hide().end()
							.find('.close').hide().end()
							.find('.processing_message').show().end();

							if ( !$box.data("overlay") ){
								$box.overlay({
									fixed: true,
									top: 200,
									left: (screen.width / 2) - ($box.outerWidth() / 2),
									mask: {
										color: '#444444',
										loadSpeed: 200,
										opacity: 0.9
									},

									// disable this for modal dialog-type of overlays
									closeOnClick: false,
									// load it immediately after the construction
									load: true
								});
							}
							else {
								$box.show();
								$box.data("overlay").load();
							}

							$box = null;
						},
						showSim: function(params){
							var url = decodeURIComponent(params.url);
							var $box = jQuery("#wiziappBoxWrapper");
							if ( $box.length == 0 ){
								$box = jQuery("<div id='wiziappBoxWrapper'><div class='close overlay_close'></div><iframe id='wiziappBox'></iframe>");
								$box.find("iframe").attr('src', url+"&preview=1");

								$box.appendTo(document.body);

								$box.find("iframe").css({
									'border': '0px none',
									'height': '760px',
									'width': '390px'
								});

								$box.overlay({
									top: 20,
									fixed: false,
									mask: {
										color: '#444',
										loadSpeed: 200,
										opacity: 0.8
									},
									closeOnClick: true,
									onClose: function(){
										jQuery("#wiziappBoxWrapper").remove();
									},
									load: true
								});
							}
							else {
								$box.show();
								$box.data("overlay").load();
							}

							$box = null;
						},
						resizeGeneratorIframe: function(params){
							jQuery("#wiziapp_frame").css({
								'height': (parseInt(params.height) + 50) + 'px'
							});
						}
					};

					return {
						handleRequest: function(q){
							var paramsArray = q.split('&');
							var params = {};
							for ( var i = 0; i < paramsArray.length; ++i){
								var parts = paramsArray[i].split('=');
								params[parts[0]] = decodeURIComponent(parts[1]);
							}
							if ( typeof(actions[params.action]) == "function" ){
								actions[params.action](params);
							}
							params = q = paramsArray = null;
						}
					};
				})();
			</script>
			<!--Google analytics-->
			<script type="text/javascript">
				var _gaq = _gaq || [];
				if (typeof(_gaq.splice) == 'function'){
					_gaq.splice(0, _gaq.length);
				}
				var analytics_account = '<?php echo WiziappConfig::getInstance()->analytics_account; ?>';
				var url = '<?php echo WiziappConfig::getInstance()->api_server; ?>';

				_gaq.push(['_setAccount', analytics_account]);
				_gaq.push(['_setDomainName', url.replace('api.', '.')]);
				_gaq.push(['_setAllowLinker', true]);
				_gaq.push(['_setAllowHash', false]);
				_gaq.push(['_trackPageview', '/ActivePluginGoal.php']);

				(function(){
					var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
					ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
					var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
				})();
			</script>
			<?php
			}
		?>
		<style>
			#wiziapp_container{
				background: #fff;
			}
			.processing_modal{
				background: url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/generator/Pament_Prossing_Lightbox.png) no-repeat top left;
				display:none;
				width:486px;
				height: 53px;
				padding:35px;
			}
			#general_error_modal{
				z-index: 999;
			}
		</style>
		<div id="wiziapp_container">
			<?php
				$iframeSrc = $httpProtocol . '://' . WiziappConfig::getInstance()->api_server . '/cms/controlPanel/' . $display_action . '?app_id=' .
				WiziappConfig::getInstance()->app_id . '&t=' . $token . '&v='.WIZIAPP_P_VERSION;
				WiziappLog::getInstance()->write('INFO', 'The iframe src is: '.$iframeSrc, 'WiziappAdminDisplay.includeGeneralDisplay');
			?>

			<iframe id="wiziapp_frame" src=""
				style="overflow: hidden; width:100%; height: 880px; border:0px none;" frameborder="0"></iframe>
			<script type="text/javascript">
				var iframe_src = "<?php echo $iframeSrc; ?>";
				document.getElementById("wiziapp_frame").src = iframe_src;
			</script>
		</div>

		<div class="wiziapp_errors_container s_container hidden" id="general_error_modal">
			<div class="errors_container">
				<div class="errors">
					<div class="wiziapp_error"></div>
				</div>
				<div class="buttons">
					<a href="javascript:void(0);" class="report_issue">Report a Problem</a>
					<a class="retry_processing close" href="javascript:void(0);">Retry</a>
				</div>
			</div>
			<div class="report_container hidden">

			</div>
		</div>

		<div class="processing_modal" id="reload_modal">
			<p class="processing_message">It seems your session has timed out.</p>
			<p>please <a href="javascript:top.document.location.reload(true);">refresh</a> this page to try again</p>
			<p class="error" class="errorMessage hidden"></p>
			<a class="close hidden" href="javascript:void(0);">Go back</a>
		</div>
		<?php
		}
	}

	public function versionCheck(){
		$needCheck = TRUE;
		$needShow = TRUE;

		// Check only if we didn't check in the last 12 hours
		if ( isset(WiziappConfig::getInstance()->last_version_checked_at) ){
			// We checked for the version already, but was it in the last 12 hours?
			if ((time() - WiziappConfig::getInstance()->last_version_checked_at) <= 60*60*12){
				// We need to check again
				$needCheck = FALSE;
			}
		}
		if ( $needCheck ){
			// Get the current version
			if ( empty(WiziappConfig::getInstance()->wiziapp_avail_version) ){
				WiziappConfig::getInstance()->wiziapp_avail_version = WIZIAPP_P_VERSION;
			}
			$r = new WiziappHTTPRequest();
			$response = $r->api(array(), '/cms/version', 'POST');
			if ( !is_wp_error($response) ){
				$vResponse = json_decode($response['body'], TRUE);
				if ( !empty($vResponse) ){
					WiziappConfig::getInstance()->wiziapp_avail_version = $vResponse['version'];
					WiziappConfig::getInstance()->last_version_checked_at = time();
					//update_option('wiziapp_settings', $options);
				}
			}
		}

		if ( WiziappConfig::getInstance()->wiziapp_avail_version != WIZIAPP_P_VERSION ){
			if ( isset(WiziappConfig::getInstance()->show_need_upgrade_msg) && WiziappConfig::getInstance()->show_need_upgrade_msg === FALSE ){
				// The user choose to hide the version alert, but was the version alert for the version he saw?
				if ( WiziappConfig::getInstance()->last_version_shown === WiziappConfig::getInstance()->wiziapp_avail_version ){
					$needShow = FALSE;
				}
			}

			if ( $needShow ){
			?>
			<!--                <div id="wiziapp_upgrade_needed_message" class="updated fade">-->
			<!--                    <p style="line-height: 150%">-->
			<!--                        An important update is available for the WiziApp WordPress plugin.-->
			<!--                        <br />-->
			<!--                        Make sure to <a href="plugins.php">update</a> as soon as possible, to enjoy the security, bug fixes and new features contained in this update.-->
			<!--                    </p>-->
			<!--                    <p>-->
			<!--                        <input id="wiziappHideUpgrade" type="button" class="button" value="Hide this message" />-->
			<!--                    </p>-->
			<!--                    <script type="text/javascript">-->
			<!--                    jQuery(document).ready(function(){-->
			<!--                        jQuery("#wiziappHideUpgrade").click(function(){-->
			<!--                            var params = {-->
			<!--                                action: 'wiziapp_hide_upgrade_msg'-->
			<!--                            };-->
			<!--                            jQuery.post(ajaxurl, params, function(data){-->
			<!--                                jQuery("#wiziapp_upgrade_needed_message").remove();-->
			<!--                            });-->
			<!--                        });-->
			<!--                    });-->
			<!--                    </script>-->
			<!--                </div>-->
			<?php
			}
		}
	}

	public function displayMessageCheck(){
		if (WiziappConfig::getInstance()->wiziapp_admin_messages_subject != '' && WiziappConfig::getInstance()->wiziapp_admin_messages_message != ''){
		?>
		<div id="wiziapp_display_message_message" class="error fade">
			<p style="line-height: 150%">
				<?php echo WiziappConfig::getInstance()->wiziapp_admin_messages_message; ?>
			</p>
			<p>
				<input id="wiziappDisplayUserMessageButton" type="button" class="button" value="Hide this message" />
			</p>
			<script type="text/javascript">
				jQuery(document).ready(function(){
						jQuery("#wiziappDisplayUserMessageButton").click(function(){
								var params = {
									action: 'wiziapp_hide_display_message_msg'
								};
								jQuery.post(ajaxurl, params, function(data){
										jQuery("#wiziapp_display_message_message").remove();
								});
						});
				});
			</script>
		</div>
		<?php
		}
	}

	public function upgradeCheck(){
		$installer = new WiziappInstaller();
		$page = (isset($_GET['page'])) ? $_GET['page'] : '';

		if ( $installer->needUpgrade() && $page != 'wiziapp' ){
		?>
		<div id="wiziapp_internal_upgrade_needed_message" class="updated fade">
			<p style="line-height: 150%">
				WiziApp needs one more step to finish the upgrading process, click <a href="admin.php?page=wiziapp">here</a> to upgrade your database.
				<br />
				Make sure to update as soon as you can to enjoy the security, bug fixes and new features this update contain.
			</p>
		</div>
		<?php
		}
	}

	/**
	* Displays a notice for the user
	*/
	public function configNotice(){
		$show_notice_condition = ! isset( WiziappConfig::getInstance()->wiziapp_showed_config_once ) || WiziappConfig::getInstance()->wiziapp_showed_config_once !== TRUE;

		if ( $show_notice_condition ){
		?>
		<div id="wiziapp_active_notice" class="updated">
			<div></div>
			<div></div>
		</div>
		<?php
			WiziappConfig::getInstance()->wiziapp_showed_config_once = TRUE;
		}

		if (!WiziappConfig::getInstance()->email_verified &&
			WiziappConfig::getInstance()->settings_done &&
			WiziappConfig::getInstance()->show_email_verified_msg &&
			WiziappConfig::getInstance()->finished_processing){
		?>
		<div id="wiziapp_email_verified_message" class="error fade">
			<p style="line-height: 150%">
				Your Email address is not verified yet. We have sent you a verification Email, please go to your Email account and click the verify link.
				<br />
				In case you haven’t got this email please go to <a href="admin.php?page=wiziapp_my_account_display">my account</a> and click "verify email".
			</p>
			<p>
				<input id="wiziappHideVerify" type="button" class="button" value="Hide this message" />
			</p>
			<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery("#wiziappHideVerify").click(function(){
						var params = {
							action: 'wiziapp_hide_verify_msg'
						};
						jQuery.post(ajaxurl, params, function(data){
							jQuery("#wiziapp_email_verified_message").remove();
						});
					});
				});
			</script>
		</div>
		<?php
		}
	}

	public function styles_javascripts($hook){
		$print_js_condition =
		$hook === 'plugins.php' && isset($_GET['activate']) && $_GET['activate'] === 'true' &&
		( ! isset( WiziappConfig::getInstance()->wiziapp_showed_config_once ) || WiziappConfig::getInstance()->wiziapp_showed_config_once !== TRUE );

		if ( $print_js_condition ){
			$plugins_url = plugins_url( dirname( WP_WIZIAPP_BASE ) );
			wp_enqueue_script( 'active_plugin_notice', $plugins_url . '/themes/admin/active_plugin_notice.js', 'jquery' );
			wp_enqueue_style(  'active_plugin_notice', $plugins_url . '/themes/admin/active_plugin_notice.css' );
		}
	}

	public function hideVerifyMsg(){
		$status = (WiziappConfig::getInstance()->show_email_verified_msg = FALSE);

		$header = array(
			'action' => 'hideVerifyMsg',
			'status' => $status,
			'code' => ($status) ? 200 : 4004,
			'message' => '',
		);

		echo json_encode(array('header' => $header));
		exit;
	}

	public function hideUpgradeMsg(){
		$status = TRUE;

		WiziappConfig::getInstance()->show_need_upgrade_msg = FALSE;
		WiziappConfig::getInstance()->last_version_shown = WiziappConfig::getInstance()->wiziapp_avail_version;

		$header = array(
			'action' => 'hideUpgradeMsg',
			'status' => $status,
			'code' => ($status) ? 200 : 4004,
			'message' => '',
		);

		echo json_encode(array('header' => $header));
		exit;
	}

}