<?php if (!defined('WP_WIZIAPP_BASE')) exit();
/**
* @package WiziappWordpressPlugin
* @subpackage AdminDisplay
* @author comobix.com plugins@comobix.com
*/

class WiziappGeneratorDisplay{

	public static function display(){
		if ( ! defined('WIZIAPP_P_VERSION') || WIZIAPP_P_VERSION === '1.2.2' ){
			ob_start();
			?>
			The Wiziapp plugin needs to be upgraded, please see a
			<a href="http://www.wiziapp.com/blog/guides-tutorials/update-the-wiziapp-plugin/" target="_blank">
				guide
			</a>
			for upgrading it manually.
			<?php
			$error = ob_get_clean();
			self::_show_error($error);

			return;
		}

		$maint = FALSE;
		if ( function_exists("is_maintenance") ){
			$maint = is_maintenance();
		}
		if ( $maint ){
			// The plugin is in maintenance mode.
			self::_show_error('Your website is running in maintenance mode. While in this mode, the WiziApp plugin cannot run.');

			return;
		}

		// Before opening this display get a one time usage token
		$r = new WiziappHTTPRequest();
		$response = $r->api(array(), '/generator/getToken?app_id=' . WiziappConfig::getInstance()->app_id, 'POST');

		if ( is_wp_error($response) ) {
			self::_show_error($response->get_error_message());

			return;
		} elseif ( ! is_array($response) || empty($response['body']) ){
			self::_show_error('Connection problem, please contact the Wiziapp support.');

			return;
		}

		$tokenResponse = json_decode($response['body'], TRUE);
		if ( ! $tokenResponse['header']['status'] ){
			// There was a problem with the token.
			self::_show_error($tokenResponse['header']['message']);

			return;
		}

		$token = $tokenResponse['token'];
		$httpProtocol = 'https';
		$iframeSrc = $httpProtocol.'://'.WiziappConfig::getInstance()->api_server.'/generator/index/'.$token.'?v='.WIZIAPP_P_VERSION;
		if ( WiziappConfig::getInstance()->webapp_installed || WiziappConfig::getInstance()->skip_reload_webapp ){
			$iframeSrc .= '&webapp_installed=1';
		}

		$iframeId = 'wiziapp_generator' . time();
		?>

		<style type="text/css">
			.overlay_close {
				background-image:url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/generator/close.png);
				position:absolute; right:-17px; top:-17px;
				cursor:pointer;
				height:35px;
				width:35px;
			}
			#wpadminbar{
				z-index: 99;
			}
			#wiziappBoxWrapper{
				width: 390px;
				height: 760px;
				margin: 0 auto;
				padding: 0;
				background: url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/simulator/phone.png) no-repeat scroll 8px 8px;
			}
			#wiziappBoxWrapper.sim_loaded{
				background-image: none;
			}
			#wiziappBoxWrapper #loading_placeholder{
				position: absolute;
				color:#E0E0E0;
				font-weight:bold;
				height:60px;
				top: 260px;
				left: 170px;
				width:75px;
				z-index: 0;
			}
			#wiziappBoxWrapper.sim_loaded #loading_placeholder{
				display: none;
			}
			#wiziappBoxWrapper iframe{
				visibility: hidden;
			}
			#wiziappBoxWrapper.sim_loaded iframe{
				visibility: visible;
			}
			#wiziapp_generator_container{
				background: #fff;
			}
			.processing_modal{
				background: url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/generator/Pament_Prossing_Lightbox.png) no-repeat top left;
				display:none;
				height: 70px;
				padding: 25px 35px;
				width: 426px;
			}

			#enter_license_modal{
				background: url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/cms/popwin.png) no-repeat top left;
				height: 280px;
				width: 540px;
				font-size: 16px;
				padding: 0px;
			}
			#enter_license_modal .error{
				margin-left: 20px;
				padding-left: 20px;
				background: url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/cms/validetion_error_Icon.png) no-repeat left center;
				color: #ff6161;
				clear: both;
			}

			#enter_license_modal .success{
				margin-left: 20px;
				background: url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/cms/V_Icon.png) no-repeat left center;
				text-indent: 20px;
				clear: both;
			}

			#enter_license_modal .processing_message{
				font-size: 16px;
				margin-bottom: 20px;
			}
			#enter_license_modal input{
				background: url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/cms/email.png) no-repeat top left;
				height: 31px;
				line-height: 31px;
				width: 203px;
				text-indent: 5px;
				float: left;
				margin: 0px 10px 0px 20px;
			}

			#enter_license_modal .wizi_button{
				background: url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/cms/CancelBTN.png) no-repeat top left !important;
				height: 33px;
				width: 86px;
				text-align: center;
				line-height: 33px;
				float: left;
				text-decoration: none;
				text-transform: uppercase;
				color: #474747;
				font-size: 12px;
				font-weight: bold;
				margin: 0px 4px 0px 0px;
			}
			#enter_license_modal #submit_license{
				background: url("http://cdn.wiziapp.net/images/cms/retry_lb_BTN.png") no-repeat top left;
			}
			#enter_license_modal div.wrapper{
				margin: 46px 46px 25px;
			}
			#enter_license_modal h2{
				padding-left: 20px;
				margin-bottom:30px;
				color:#0fb3fb;
			}

			#publish_modal .processing_message{
				font-size: 17px;
			}
			#publish_modal .loading_indicator{
				margin: 8px auto 2px;
			}
			#create_account_modal_close{
				display: none;
				clear: both;
				float: none;
			}
			.processing_modal .error{
				margin: 0;
				width: 407px;
				border: 0 none;
			}
			#create_account_modal{
				padding: 25px 55px;
			}
			#create_account_modal .error{
				background: url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/generator/validetion_error_Icon.png) no-repeat 0 5px;
				padding: 5px 0 20px 40px;
				width: 360px;
				border: 0 none;
			}

			.processing_message{
				color: #000000;
				font-size: 18px;
				font-family: arial;
				margin: 2px 0;
				padding-left: 20px;
			}

			.processing_modal .loading_indicator{
				background: url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/generator/lightgrey_counter.gif) no-repeat;
				width: 35px;
				height: 35px;
				margin: 2px auto;
			}
			#general_error_modal{
				z-index: 999;
			}
		</style>

		<script type="text/javascript" src="<?php echo esc_attr(plugins_url('themes/admin/scripts/jquery.tools.min.js', dirname(dirname(__FILE__)))); ?>"></script>
		<script type="text/javascript">
			var WIZIAPP_HANDLER = (function(){
				jQuery(document).ready(function(){
					jQuery('.report_issue').click(reportIssue);
					jQuery('.retry_processing').click(retryProcessing);

					jQuery('#general_error_modal').bind('closingReportForm', function(){
						jQuery(this).addClass('s_container');
					});

					jQuery('#submit_license').bind('click', registerLicense);
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

				var actions = {
					informErrorProcessing: function(params){
						var $box = jQuery('#'+params.el);
						$box
						.find('.processing_message').hide().end()
						.find('.loading_indicator').hide().end()
						.find('.error').text(params.message).show().end()
						.find('.close').show().end();

						$box = null;
					},
					closeProcessing: function(params){
						jQuery('#'+params.el).data("overlay").close();
						if ( typeof(params.scrollTop) != 'undefined' ){
							jQuery(document).scrollTop(0);
						}

						if (typeof(params.reload) != 'undefined'){
							if (params.reload == 1){
								if (typeof(params.qs) != 'undefined'){
									var href = top.location.href;
									var seperator = '?';
									if (href.indexOf('?')){
										seperator = '&';
									}
									href += seperator + unescape(params.qs);
									top.location.replace(href);
								} else {
									top.location.reload(true);
								}
							}
						}

						if ( typeof(params.resizeTo) != 'undefined' ){
							actions.resizeGeneratorIframe({height: params.resizeTo});
						}
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
						} else {
							$box.show();
							$box.data("overlay").load();
						}
						$box = null;
					},
					showProcessing: function(params){
						var $box = jQuery('#'+params.el);
						$box
						.find('.error').hide().end()
						.find('.loading_indicator').show().end()
						.find('.close:not(.nohide)').hide().end()
						.find('.processing_message').show().end();

						if (!$box.data("overlay")){
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
						} else {
							$box.show();
							$box.data("overlay").load();
						}

						$box = null;
					},
					showSim: function(params){
						var url = decodeURIComponent(params.url);
						url = url + '&rnd=' + Math.floor(Math.random()*999999);
						var $box = jQuery("#wiziappBoxWrapper");
						if ($box.length == 0){
							$box = jQuery("<div id='wiziappBoxWrapper'><div class='close overlay_close'></div><div id='loading_placeholder'>Loading...</div><iframe id='wiziappBox'></iframe>");
							$box.find("iframe").attr('src', url+"&preview=1").unbind('load').bind('load', function(){
								jQuery("#wiziappBoxWrapper").addClass('sim_loaded');
							});

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
						} else {
							$box.show();
							$box.data("overlay").load();
						}

						$box = null;
					},
					resizeGeneratorIframe: function(params){
						jQuery("#<?php echo $iframeId; ?>").css({
							'height': (parseInt(params.height) + 50) + 'px'
						});
					}
				};

				function retryProcessing(event){
					event.preventDefault();
					document.location.reload(true);
					return false;
				};

				function registerLicense(event){
					if ( jQuery(this).is('.pending') ){
						return false;
					}
					jQuery(this).addClass('pending');

					jQuery('#enter_license_modal .error').hide();
					var key = jQuery('#enter_license_modal input').val();
					if ( key.length == 0 ){
						jQuery(this).removeClass('pending');
						return false;
					}

					var params = {
						'action': 'wiziapp_register_license',
						'key': key
					};

					jQuery.post(ajaxurl, params, function(data){
						if ( data && data.header && data.header.status ){
							// License updated, inform and reload
							jQuery('#enter_license_modal .success').text('License key updated, please standby...').show();
							top.document.location.reload(true);
						} else {
							// Error,
							jQuery('#enter_license_modal .error')
							.text(data.header.message)
							.show();
						}
						jQuery('#submit_license').removeClass('pending');
						}, 'json');
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

				return {
					handleRequest: function(q){
						var paramsArray = q.split('&');
						var params = {};
						for (var i = 0; i < paramsArray.length; ++i){
							var parts = paramsArray[i].split('=');
							params[parts[0]] = decodeURIComponent(parts[1]);
						}
						if (typeof(actions[params.action]) == "function"){
							actions[params.action](params);
						}
						params = q = paramsArray = null;
					}
				};
			})();

			jQuery(document).ready(function($){
				$("<iframe frameborder='0'>")
				.css({
					'overflow': 'hidden',
					'width': '100%',
					'height': '1000px',
					'border': '0px none'
				})
				.attr({
					'src': "<?php echo $iframeSrc; ?>",
					'frameborder': '0',
					'id': '<?php echo $iframeId; ?>'
				})
				.prependTo("#wiziapp_generator_container");
			});
		</script>

		<div id="wiziapp_generator_container"></div>

		<div class="hidden wiziapp_errors_container s_container" id="general_error_modal">
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

		<div class="processing_modal" id="enter_license_modal">
			<div class="wrapper">
				<h2>Enter license key</h2>
				<p class="processing_message">You can find your license key in the confirmation e-mail received after purchase</p>
				<p>
					<input type="text" name="license_key" size="20" maxlength="40" />
					<a class="wizi_button" id="submit_license" href="javascript:void(0);">Submit</a>
					<a class="wizi_button close nohide" href="javascript:void(0);">Cancel</a>
				</p>
				<p class="error" class="errorMessage hidden"></p>
				<p class="success" class="hidden"></p>
			</div>
		</div>

		<div class="processing_modal" id="create_account_modal">
			<p class="processing_message">Please wait while we place your order...</p>
			<div class="loading_indicator"></div>
			<p class="error" class="errorMessage hidden"></p>
			<a class="close hidden" href="javascript:void(0);">&lt; Back</a>
		</div>

		<div class="processing_modal" id="publish_modal">
			<p class="processing_message">Please wait while we process your request...</p>
			<div class="loading_indicator"></div>
			<p class="error" class="errorMessage hidden"></p>
			<a class="close hidden" href="javascript:void(0);">Go back</a>
		</div>

		<div class="processing_modal" id="reload_modal">
			<p class="processing_message">It seems your session has timed out.</p>
			<p>please <a href="javascript:top.document.location.reload(true);">refresh</a> this page to try again</p>
			<p class="error" class="errorMessage hidden"></p>
			<a class="close hidden" href="javascript:void(0);">Go back</a>
		</div>
		<?php
	}

	private static function _show_error($message){
		?>
			<div class="wiziapp_errors_container s_container" style="top:40%;">
				<div class="errors">
					<div class="wiziapp_error">
						<?php echo $message; ?>
					</div>
				</div>
			</div>
		<?php
	}
}