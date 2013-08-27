<?php if (!defined('WP_WIZIAPP_BASE')) exit();
/**
* @package WiziappWordpressPlugin
* @subpackage WebApp
* @author comobix.com plugins@comobix.com
*/

class WiziappWebappDisplay{

	/**
	* Need to deactivate the Webapp feature on the "Wiziapp plugin upgrade" process,
	* as the "wiziapp/themes/webapp/resources" folder is not exist already,
	* so, the Webapp feature will not work.
	* First, need to be safe, it is really the "Wiziapp plugin upgrade" process
	*/
	public static function deactivate_on_upgrade(){
		if ( ! is_admin() || ! isset($_SERVER['HTTP_REFERER']) || ! isset($_SERVER['REQUEST_URI']) ){
			return;
		}

		$http_referer = urldecode($_SERVER['HTTP_REFERER']);
		$request_uri  = urldecode($_SERVER['REQUEST_URI']);

		$is_upgrade_process =
		preg_match('/\/wp-admin\/update\.php\?action=upgrade-plugin.*?&plugin=wiziapp[a-z\-]*?\/wiziapp.php/i', $http_referer) &&
		preg_match('/\/wp-admin\/update\.php\?action=activate-plugin.*?&plugin=wiziapp[a-z\-]*?\/wiziapp.php/i', $request_uri);
		if ( ! $is_upgrade_process ) {
			return;
		}

		WiziappConfig::getInstance()->webapp_installed = FALSE;
	}

	public function installFinish(){
		// Copy the CSS file to Resorces folder, as he has paths, relative to the Resorces folder
		@copy(WIZI_DIR_PATH.'themes/webapp/style_aux.css', WiziappContentHandler::getInstance()->get_blog_property('data_files_dir').'/resources/style_aux.css');

		WiziappLog::getInstance()->write('INFO', "The webapp install is finished, marking it", 'WiziappWebappDisplay.installFinish');

		$r = new WiziappHTTPRequest();
		$response = $r->api(array(), '/application/'.WiziappConfig::getInstance()->app_id.'/webappInstalled', 'POST');

		if ( is_wp_error($response) ){
			WiziappLog::getInstance()->write('ERROR', "Unable to set installation finished statue: ".print_r($response, TRUE), 'WiziappWebappDisplay.installFinish');
			$this->_returnResults('retry', 'Connection error, please try again.');
		}

		WiziappConfig::getInstance()->webapp_installed = TRUE;

		$ch = new WiziappContentEvents();
		$ch->updateCacheTimestampKey();

		$this->_returnResults('success', 'success');
	}

	public function updateConfig(){
		if ( ( $resources = $this->_check_writing_permissions() ) !== '' ){
			$this->_returnResults('retry', 'The '.$resources.' directory is not writable, please set the directory permission to 0777 and try again.');
		}

		$contentPrefix = "var config = ";
		$contentSuffix = ';';

		$this->_update('conf', 'config.js', FALSE, '', $contentPrefix, $contentSuffix);
	}

	public function updateHandshake(){
		if ( ( $resources = $this->_check_writing_permissions() ) !== '' ){
			$this->_returnResults('retry', 'The '.$resources.' directory is not writable, please set the directory permission to 0777 and try again.');
		}

		$contentPrefix = "var handshake = ";
		$contentSuffix = ';';

		$this->_update('handshake', 'handshake.js', FALSE, '', $contentPrefix, $contentSuffix, TRUE);
	}

	public function updateDisplay(){
		if ( ( $resources = $this->_check_writing_permissions() ) !== '' ){
			$this->_returnResults('retry', 'The '.$resources.' directory is not writable, please set the directory permission to 0777 and try again.');
		}

		$this->_update('webCss', 'main.css');
	}

	public function updateEffects(){
		if ( ( $resources = $this->_check_writing_permissions() ) !== '' ){
			$this->_returnResults('retry', 'The '.$resources.' directory is not writable, please set the directory permission to 0777 and try again.');
		}

		$imagesBase = WiziappContentHandler::getInstance()->get_blog_property('data_files_url').'/resources/';
		$contentPrefix = "var jsInstructionsBase = '{$imagesBase}';var jsInstructions = ";
		$contentSuffix = ';';

		$this->_update('webeffects', 'effects_config.js', FALSE, '', $contentPrefix, $contentSuffix);
	}

	public function updateImages(){
		if ( ( $resources = $this->_check_writing_permissions() ) !== '' ){
			$this->_returnResults('retry', 'The '.$resources.' directory is not writable, please set the directory permission to 0777 and try again.');
		}

		$this->_update('images', 'images.zip', TRUE, 'images');
	}

	public function updateIcons(){
		if ( ( $resources = $this->_check_writing_permissions() ) !== '' ){
			$this->_returnResults('retry', 'The '.$resources.' directory is not writable, please set the directory permission to 0777 and try again.');
		}

		$this->_update('icons', 'icons.zip', TRUE, 'icons');
	}

	public function updateSplash(){
		if ( ( $resources = $this->_check_writing_permissions() ) !== '' ){
			$this->_returnResults('retry', 'The '.$resources.' directory is not writable, please set the directory permission to 0777 and try again.');
		}

		$this->_update('splash', 'default.png');
	}

	private function _update($type, $filename, $zip=FALSE, $dir='', $contentPrefix='', $contentSuffix='', $checkJSONError=FALSE){
		$r = new WiziappHTTPRequest();
		$response = $r->api(array(), '/application/'.WiziappConfig::getInstance()->app_id.'/'.$type, 'GET');

		if ( is_wp_error($response) ){
			WiziappLog::getInstance()->write('ERROR', "Unable to get the {$type}: ".print_r($response, TRUE), 'WiziappWebappDisplay._update');
			$this->_returnResults('retry', 'Connection error, please try again.');
		}

		if ($checkJSONError){
			$json = json_decode($response['body'], true);

			if ( $json === NULL || ! isset($json['header']) || ! is_array($json['header']) || ! isset($json['header']['status']) ){
				WiziappLog::getInstance()->write('ERROR', '$json === NULL, $response[\'body\'] = '.$response['body'], 'WiziappWebappDisplay._update');
				$this->_returnResults('fatal', 'fatal');
			} elseif ( $json['header']['status'] == FALSE ){
				if ( isset($json['header']['action']) && $json['header']['action'] === 'handshake' && isset($json['header']['message']) && $json['header']['message'] === 'The_Application_is_saved_but_it_is_not_completed_yet_please_click_retry' ){
					$this->_returnResults('recycle', 'The Application is saved but it is not completed yet, please click retry.');
				} else {
					WiziappLog::getInstance()->write('ERROR', 'Something into the Handshake response is false, $response[\'body\'] = '.$response['body'], 'WiziappWebappDisplay._update');
					$this->_returnResults('fatal', 'fatal');
				}
			}
		}

		// Save this in the application configuration file
		$base = $this->_get_resources_path();
		$file = $base.DIRECTORY_SEPARATOR.$filename;
		$dirPath = $base.DIRECTORY_SEPARATOR.$dir;
		$content = "{$contentPrefix}{$response['body']}{$contentSuffix}";

		if ( $type === 'webCss' ){
			$content = str_replace('/simulator/rgba', 'http://'.WiziappConfig::getInstance()->api_server.'/simulator/rgba', $content);
		}

		@file_put_contents($file, $content);
		if ( ! @file_exists($file) ){
			WiziappLog::getInstance()->write('ERROR', "Unable to write the {$type} file: ".$file, 'WiziappWebappDisplay._update');
			$this->_returnResults('fatal', 'fatal');
		}

		if ( $zip ){
			if ( class_exists('ZipArchive') ){
				$zip = new ZipArchive;

				if ( $zip->open($file) === TRUE ){
					$zip->extractTo($dirPath);
					$zip->close();
				} else {
					WiziappLog::getInstance()->write('ERROR', 'Can not unzip the file '.$file.' by PHP class ZipArchive', 'WiziappWebappDisplay._update');
					$this->_returnResults('fatal', 'fatal');
				}
			} else {
				WiziappLog::getInstance()->write('ERROR', '! class_exists(\'ZipArchive\')', 'WiziappWebappDisplay._update');

				$url = wp_nonce_url('admin.php?page=wiziapp_webapp_display','wiziapp-webapp-options');
				ob_start();
				if ( ( $creds = request_filesystem_credentials($url, '', FALSE, FALSE, NULL) ) === FALSE ){
					WiziappLog::getInstance()->write('ERROR', "Dont have permissions to upload the file", 'WiziappWebappDisplay.update');
				}
				if ( ! WP_Filesystem($creds) ){
					WiziappLog::getInstance()->write('ERROR', "Cant start the filesystem object", 'WiziappWebappDisplay.update');
				}
				ob_end_clean();
				if ( ! @unzip_file($file, $dirPath) ){
					WiziappLog::getInstance()->write('ERROR', 'Can not unzip the file '.$file.' by WP function unzip_file()', 'WiziappWebappDisplay._update');
					$this->_returnResults('fatal', 'fatal');
				}
			}

			@unlink($file);
			if ( @file_exists($file) ){
				WiziappLog::getInstance()->write('WARNING', "Cant delete the {$type} {$file}", 'WiziappWebappDisplay._update');
			}
		}

		$this->_returnResults('success', 'success');
	}

	private function _returnResults($status, $message){
		$results = array(
			'status'  => $status,
			'message' => $message,
		);

		echo json_encode($results);
		exit;
	}

	private function _check_writing_permissions(){
		$resources = $this->_get_resources_path();

		if ( @is_readable($resources) && @is_writable($resources)) {
			return '';
		}

		if ( @chmod($resources, 0755)) {
			return '';
		}

		WiziappLog::getInstance()->write('ERROR', 'The "Resources" directory is not readable or not writable: '.$resources, "WiziappWebappDisplay._check_writing_permissions");
		return $resources;
	}

	private function _get_resources_path(){
		return WiziappContentHandler::getInstance()->get_blog_property('data_files_dir').'/resources';
	}

	public function display(){
		?>
		<style type="text/css">
			#wpbody{
				background-color: #fff;
			}
			#wiziapp_posts_progress_bar_container{
				border: 1px solid #0000FF;
				background-color: #C0C0FF;
				position: relative;
				width: 300px;
				height: 30px;
			}
			#wiziapp_posts_progress_bar_container .progress_bar{
				background-color: #0000FF;
				width: 0%;
			}
			.progress_bar{
				position: absolute;
				top: 0px;
				left: 0px;
				height: 100%;
			}
			.progress_indicator{
				color: #fff;
				font-weight: bolder;
				text-align:center;
				width: 100%;
				position: absolute;
				top: 0px;
				left: 0px;
			}
			#wiziapp_finalize_title, #wiziapp_pages_title, #all_done{
				display: none;
			}
			#just_a_moment{
				background: url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/cms/processingJustAmoment.png) no-repeat top center;
				width: 262px;
				margin: 50px auto 17px;
				height: 32px;
			}
			#wizi_icon_processing{
				background: url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/cms/wiziapp_processing_icon.png) no-repeat top center;
				width: 135px;
				height: 93px;
			}
			#wizi_icon_wrapper{
				margin: 52px auto 29px;
				position: relative;
				height:  93px;
				width: 235px;
			}
			.text_label{
				color: #0ca0f5;
				font-weight: bold;
				text-align: center;
				font-size: 14px;
				margin: 5px 0 9px;
			}
			#main_progress_bar_container{
				width: 260px;
				height: 12px;
				position: relative;
				margin: 0px auto;
			}
			#main_progress_bar_bg{
				position: absolute;
				top: 0px;
				left: 0px;
				width: 100%;
				height: 100%;
				background: url(<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>/images/cms/progress_bar_bg.png) no-repeat;
				z-index: 2;
			}
			#main_progress_bar{
				z-index: 1;
				position: absolute;
				top: 0px;
				left: 0px;
				height: 100%;
				background-color: #0ca0f5;
			}
			#current_progress_indicator{
				font-size: 17px;
				margin: 10px;
			}
			#wizi_be_patient{
				margin: 15px 0px 9px 32px;
			}
			div.wiziapp_errors_container{
				display: none;
			}
		</style>

		<script type="text/javascript" src="<?php echo esc_attr(plugins_url('themes/admin/scripts/jquery.tools.min.js', dirname(dirname(__FILE__)))); ?>"></script>

		<script type="text/javascript">
			(function($){

				var wiziapp_errors_container;
				var wiziapp_message_wrapper;
				var current_progress_label;
				var fatal_error_message = "There was a problem installing the Webapp, please contact support.";
				var try_again_message = "Connection error, please try again.";
				var retry_button;
				var progressTimer = null;
				var progressWait = 30;
				var step_number = 0;
				var retry_amount = 0;
				var recycle_start_time = 0;
				var update_steps = ['handshake', 'config', 'display', 'effects', 'images', 'icons', 'splash'];
				var update_msg = [
					'<?php echo __('Installing WebApp', 'wiziapp')?>',
					'<?php echo __('Updating configuration', 'wiziapp')?>',
					'<?php echo __('Updating display settings', 'wiziapp')?>',
					'<?php echo __('Updating special effects', 'wiziapp')?>',
					'<?php echo __('Updating images', 'wiziapp')?>',
					'<?php echo __('Updating icons', 'wiziapp')?>',
					'<?php echo __('Updating splash screen', 'wiziapp')?>',
				];
				var overlayParams = {
					top: 100,
					onClose: function(){
						$("#wiziapp_error_mask").hide();
					},
					onBeforeLoad: function(){
						var $toCover = $('#wpbody');

						var $mask = $('#wiziapp_error_mask');
						if ( $mask.length == 0 ){
							$mask = $('<div></div>').attr("id", "wiziapp_error_mask");
							$("body").append($mask);
						}

						$mask.css({
							position:'absolute',
							top: $toCover.offset().top,
							left: $toCover.offset().left,
							width: $toCover.outerWidth(),
							height: $("#wpwrap").outerHeight(),
							display: 'block',
							"z-index": 10,
							opacity: 0.9,
							backgroundColor: '#444444'
						});

						$mask = $toCover = null;
					},
					closeOnClick: false,
					closeOnEsc: false,
					load: true
				};

				$(document).ready(function(){
					$.ajaxSetup({
						timeout: 60*1000,
						error: function(jqXHR, textStatus, errorThrown){
							clearTimeout(progressTimer);

							if (textStatus == 'timeout'){
								display_error_message(true, try_again_message);
							} else if( jqXHR.status == 0 ){
								display_error_message(true, try_again_message);
							} else if( jqXHR.status == 404 ){
								display_error_message(false, fatal_error_message);
							} else if( jqXHR.status == 500 ){
								display_error_message(false, fatal_error_message);
							} else if( textStatus == 'parsererror' ){
								display_error_message(false, fatal_error_message);
							} else {
								display_error_message(false, fatal_error_message);
							}
						}
					});

					wiziapp_errors_container = $("div.wiziapp_errors_container");
					wiziapp_message_wrapper = wiziapp_errors_container.find("div.errors_container div.errors div.wiziapp_error")
					retry_button = $("#wiziapp_retry_compatibilities");

					overlayParams.left = (screen.width / 2) - (wiziapp_errors_container.outerWidth() / 2),
					wiziapp_errors_container
					.find("div.errors_container a")
					.click(reaction_to_error);

					// Start sending requests to generate content till we are getting a flag showing we are done
					current_progress_label = $("#current_progress_label");
					startProcessing();
				});

				function startProcessing(){
					if ( typeof(update_steps[step_number]) === 'undefined' ){
						requestFinalizingProcessing();
						return;
					}

					var params = {
						action: 'wiziapp_update_' + update_steps[step_number]
					};

					$.post(ajaxurl, params, handleUpdateResponse, 'json');

					progressTimer = setTimeout(updateProgressBarByTimer, 1000 * progressWait);
				}

				function handleUpdateResponse(data){
					try {
						if ( data.status === "fatal" ){
							// Response is failed, display the "Cancel" button
							display_error_message(false, fatal_error_message);
						} else if ( data.status === "recycle" && typeof data.message === "string" && data.message.length != 0 ){
							// Response is failed, retry automatic, without show error
							var current_time = (new Date()).getTime();

							if ( recycle_start_time > 0 ){
								if ( current_time - recycle_start_time < 30000 ){
									if ( current_time%4 == 0 ){
										updateProgressBarByTimer();
									}

									startProcessing();
								} else {
									// Time of the automatic recycle was expired, display a choice "Cancel" or "Retry"
									display_error_message(true, data.message);
								}
							} else {
								recycle_start_time = current_time;
								updateProgressBarByTimer();
								startProcessing();
							}
						} else if ( data.status === "retry" && typeof data.message === "string" && data.message.length != 0 ){
							// Response is failed, display a choice "Cancel" or "Retry"
							display_error_message(true, data.message);
						} else if ( data.status === "success" ){
							// Response is successful, can to continue
							++step_number;
							retry_amount = recycle_start_time = 0;

							updateProgressBar();
							startProcessing();
						} else{
							// Response is failed, display the "Cancel" button
							display_error_message(false, fatal_error_message);
						}
					} catch(e) {
						// Response is failed, display a choice "Cancel" or "Retry"
						display_error_message(true, try_again_message);
					}
				};

				function display_error_message(retry_ability, message){
					recycle_start_time = 0;

					if ( retry_ability && retry_amount < 3 ){
						++retry_amount;

						// Show the "Retry" button
						retry_button.show();
					} else {
						// Show the "Retry" button
						retry_button.hide();
						message = fatal_error_message;
					}

					// Switch on the Overlay
					wiziapp_message_wrapper.text(message);
					wiziapp_errors_container
					.overlay(overlayParams)
					.load();
				}

				function reaction_to_error(event){
					event.preventDefault();

					if ( $(event.currentTarget).attr("id") === "wiziapp_report_problem" ){
						// The user chooses a "Cancel"
						handleFinalizingProcessing( { "status" : "skip" } );
					}

					startProcessing();

					// Switch off the Overlay
					wiziapp_errors_container
					.children("a:first-child")
					.trigger("click");

					return false;
				}

				function updateProgressBarByTimer(){
					var current = $("#current_progress_indicator").text();

					if (current.length == 0){
						current = 0;
					} else if (current.indexOf('%') != -1){
						current.replace('%', '');
					}

					current = parseInt(current) + 1;

					if (current != 100){
						$("#main_progress_bar").css('width', current + '%');
						$("#current_progress_indicator").text(current + '%');
					}
				};

				function updateProgressBar(){
					clearTimeout(progressTimer);
					progressTimer = null;

					var total_items = update_steps.length
					var done = (step_number / total_items) * 100;

					if (step_number < update_steps.length){
						current_progress_label.text(update_msg[step_number]+"...");
					} else {
						current_progress_label.text("<?php echo __('Finalizing...', 'wiziapp'); ?>");
					}

					$("#main_progress_bar").css('width', done + '%');
					$("#current_progress_indicator").text(Math.floor(done) + '%');
				};

				function requestFinalizingProcessing(){
					var params = {
						action: 'wiziapp_install_webapp_finish'
					};

					$.post(ajaxurl, params, handleFinalizingProcessing, 'json');
				};

				function handleFinalizingProcessing(data){
					$("#wiziapp_finalize_title").show();

					var url = window.location.href;

					if ( url.indexOf("wiziapp_webapp_display") !== -1 || url.indexOf("&wiziapp_reload_webapp=1") !== -1 ){
						var replace_get = "";

						try {
							if ( data.status === "skip" && url.indexOf("&skip_reload_webapp=1") < 0 ){
								replace_get = "&skip_reload_webapp=1";
							}
						} catch(e) {}
						url =
						url
						.replace('wiziapp_webapp_display', 'wiziapp')
						.replace("&wiziapp_reload_webapp=1", replace_get);
					} else if (url.search(/(page=wiziapp[^_])|(page=wiziapp$)/i) !== -1){
						try {
							if ( data.status === "skip" && url.indexOf("&skip_reload_webapp=1") < 0 ){
								url = url.replace('page=wiziapp', 'page=wiziapp&skip_reload_webapp=1');
							}
						} catch(e) {}
					}

					window.location.replace(url);
				}

			})(jQuery);
		</script>

		<div id="wiziapp_activation_container">
			<div id="just_a_moment"></div>
			<p id="wizi_be_patient" class="text_label"><?php echo __('Please be patient while we activate your mobile App. It may take several minutes.', 'wiziapp');?></p>
			<div id="wizi_icon_wrapper">
				<div id="wizi_icon_processing"></div>
				<div id="current_progress_label" class="text_label"><?php echo __('Initializing...', 'wiziapp'); ?></div>
			</div>
			<div id="main_progress_bar_container">
				<div id="main_progress_bar"></div>
				<div id="main_progress_bar_bg"></div>
			</div>
			<p id="current_progress_indicator" class="text_label"></p>

			<p id="wiziapp_finalize_title" class="text_label">
				<?php echo __('Ready, if the page doesn\'t change in a couple of seconds click ', 'wiziapp'); ?><span id="finializing_activation"><?php echo __('here', 'wiziapp'); ?></span>
			</p>

			<div class="wiziapp_errors_container">
				<div class="errors_container">
					<div class="errors">
						<div class="wiziapp_error"></div>
					</div>
					<div class="buttons">
						<a id="wiziapp_report_problem" 			href="javascript:void(0);">Cancel Scanning</a>
						<a id="wiziapp_retry_compatibilities" 	href="javascript:void(0);">Retry</a>
					</div>
				</div>
			</div>
		<?php
	}
}