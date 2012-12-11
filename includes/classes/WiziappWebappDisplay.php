<?php
/**
* @package WiziappWordpressPlugin
* @subpackage WebApp
* @author comobix.com plugins@comobix.com
*/

class WiziappWebappDisplay{

	function installFinish(){
		WiziappLog::getInstance()->write('INFO', "The webapp install is finished, marking it", 'WiziappWebappDisplay.installFinish');

		$r = new WiziappHTTPRequest();
		$response = $r->api(array(), '/application/'.WiziappConfig::getInstance()->app_id.'/webappInstalled', 'POST');

		if ( is_wp_error($response) ){
			WiziappLog::getInstance()->write('ERROR', "Unable to set installation finished statue: ".print_r($response, TRUE), 'WiziappWebappDisplay.installFinish');
			self::returnResults(FALSE, 'install_finish');
			return;
		}

		WiziappConfig::getInstance()->webapp_installed = TRUE;

		$ch = new WiziappContentEvents();
		$ch->updateCacheTimestampKey();

		$status = TRUE;
		self::returnResults($status, 'install_finish');
	}

	public function updateConfig(){
		$contentPrefix = "var config = ";
		$contentSuffix = ';';

		self::update('conf', 'config.js', FALSE, '', $contentPrefix, $contentSuffix);
	}

	public function updateHandshake(){
		$contentPrefix = "var handshake = ";
		$contentSuffix = ';';

		self::update('handshake', 'handshake.js', FALSE, '', $contentPrefix, $contentSuffix, TRUE);
	}

	public function updateDisplay(){
		self::update('webCss', 'main.css');
	}

	public function updateEffects(){
		$pluginUrl = WP_PLUGIN_URL.'/'.dirname(WP_WIZIAPP_BASE);
		$imagesBase = $pluginUrl.'/themes/webapp/resources/';
		$contentPrefix = "var jsInstructionsBase = '{$imagesBase}';var jsInstructions = ";
		$contentSuffix = ';';

		self::update('webeffects', 'effects_config.js', FALSE, '', $contentPrefix, $contentSuffix);
	}

	public function updateImages(){
		self::update('images', 'images.zip', TRUE, 'images');
	}

	public function updateIcons(){
		self::update('icons', 'icons.zip', TRUE, 'icons');
	}

	public function updateSplash(){
		self::update('splash', 'default.png');
	}

	private function update($type, $filename, $zip=FALSE, $dir='', $contentPrefix='', $contentSuffix='', $checkJSONError=FALSE){
		$r = new WiziappHTTPRequest();
		$response = $r->api(array(), '/application/'.WiziappConfig::getInstance()->app_id.'/'.$type, 'GET');

		if ( is_wp_error($response) ){
			WiziappLog::getInstance()->write('ERROR', "Unable to get the {$type}: ".print_r($response, TRUE), 'WiziappWebappDisplay.update');
			self::returnResults(FALSE, 'update_'.$type);
			return;
		}

		if ($checkJSONError){
			$json = json_decode($response['body'], true);
			if ($json === null){
				self::returnResults(FALSE, 'update_'.$type);
				return;
			}
			if (isset($json['header']) && is_array($json['header']) && isset($json['header']['status']) && $json['header']['status'] === false){
				self::returnResults('retry', 'update_'.$type);
				return;
			}
		}

		// Save this in the application configuration file
		$base = WiziappContentHandler::getInstance()->_get_plugin_dir();
		$file = $base.'themes/webapp/resources/'.$filename;
		$dirPath = $base.'themes/webapp/resources/'.$dir;
		$content = "{$contentPrefix}{$response['body']}{$contentSuffix}";
		if ( $type === 'webCss' ){
			$content = str_replace('/simulator/rgba', 'http://'.WiziappConfig::getInstance()->api_server.'/simulator/rgba', $content);
		}

		$url = wp_nonce_url('admin.php?page=wiziapp_webapp_display','wiziapp-webapp-options');
		if ( ( $creds = request_filesystem_credentials($url, '', FALSE, FALSE, NULL) ) === FALSE ){
			WiziappLog::getInstance()->write('ERROR', "Dont have permissions to upload the file", 'WiziappWebappDisplay.update');
			self::returnResults(FALSE, 'update_'.$type);
			return;
		}

		if ( ! WP_Filesystem($creds) ){
			WiziappLog::getInstance()->write('ERROR', "Cant start the filesystem object", 'WiziappWebappDisplay.update');
			self::returnResults(FALSE, 'update_'.$type);
			return;
		}

		@file_put_contents($file, $content);
		if ( ! @file_exists($file) ){
			WiziappLog::getInstance()->write('ERROR', "Unable to write the {$type} file: ".$file, 'WiziappWebappDisplay.update');
			self::returnResults(FALSE, 'update_'.$type);
			return;
		}

		if ( $zip ){
			// If a zip file and the file exists, unzip the file
			if ( ! @unzip_file($file, $dirPath) ){
				WiziappLog::getInstance()->write('ERROR', "Can not unzip the file {$file}", 'WiziappWebappDisplay.update');
				self::returnResults(FALSE, 'update_'.$type);
				return;
			}

			@unlink($file);
			if ( @file_exists($file) ){
				WiziappLog::getInstance()->write('WARNING', "Cant delete the {$type} {$file}", 'WiziappWebappDisplay.update');
				self::returnResults(FALSE, 'update_'.$type);
				return;
			}
		}

		self::returnResults(TRUE, 'update_'.$type);
	}

	public function updateManifest(){
		$status = FALSE;
		$fileList = array();

		// Scan the resources
		$pluginUrl = str_replace(get_bloginfo('url'), '', WP_PLUGIN_URL) . '/' . dirname(WP_WIZIAPP_BASE);
		$base = WiziappContentHandler::getInstance()->_get_plugin_dir();

		$path = $base.'themes/webapp/resources/';
		$dir = opendir($path);
		while ( FALSE !== ( $file = readdir($dir) ) ){
			if ( strpos($file, '.') != 0 && $file != "Thumbs.db" && $file != 'cache.manifest' ){
				$fileList[] = $pluginUrl . '/themes/webapp/resources/' . $file;
			}
		}

		$path = $base.'themes/webapp/resources/icons/';
		$dir = opendir($path);
		while ( FALSE !== ( $file = readdir($dir) ) ){
			if (strpos($file,'.') != 0 && $file != "Thumbs.db"){
				$fileList[] = $pluginUrl . '/themes/webapp/resources/icons/' . $file;
			}
		}

		$path = $base.'themes/webapp/resources/images/';
		$dir = opendir($path);
		while ( FALSE !== ( $file = readdir($dir) ) ){
			if (strpos($file,'.') != 0 && $file != "Thumbs.db"){
				$fileList[] = $pluginUrl . '/themes/webapp/resources/images/' . $file;
			}
		}

		$updatedAt = date('d-m-Y h:i:s A');
		$version = WIZIAPP_P_VERSION;
		// Build the
		$files = implode("\n", $fileList);

		$content =
		"CACHE MANIFEST

		# Last Update: {$updatedAt}
		# Plugin Version: {$version}

		CACHE:
		/
		{$files}
		{$pluginUrl}/themes/webapp/jquery.mobile-1.1.1.css
		{$pluginUrl}/themes/webapp/scripts/jquery.mobile-1.1.1.js?ver={$version}
		{$pluginUrl}/themes/webapp/scripts/scrollview.js?ver={$version}
		{$pluginUrl}/themes/webapp/scripts/jquery.mobile.scrollview.js?ver={$version}
		{$pluginUrl}/themes/webapp/scripts/jquery.easing.1.3.js?ver={$version}
		{$pluginUrl}/themes/webapp/jquery.mobile.scrollview.css
		{$pluginUrl}/themes/webapp/style.css

		NETWORK:
		*

		#FALLBACK:
		/ {$pluginUrl}/themes/webapp/webapp_static.html
		";

		$manifestFile = $base.'themes/webapp/resources/cache.manifest';
		@file_put_contents($manifestFile, trim($content));

		if ( file_exists($manifestFile) ){
			$status = TRUE;
		}

		self::returnResults($status, 'update_manifest');
	}

	private static function returnResults($status, $action){
		$header = array(
			'action' => $action,
			'status' => $status,
			'code' => ($status) ? 200 : 500,
			'message' => '',
		);

		echo json_encode(array('header' => $header));
		exit;
	}

	public function webappDisplay(){
	?>
	<h2>Update WebApp Resources</h2>
	<a href="javascript:void(0);" id="wiziappUpdateHandshake" data-action="handshake" class="update_button button">Update Handshake</a>
	<a href="javascript:void(0);" id="wiziappUpdateConfig"	  data-action="config"	  class="update_button button">Update Configuration</a>
	<a href="javascript:void(0);" id="wiziappUpdateDisplay"   data-action="display"   class="update_button button">Update Display</a>
	<a href="javascript:void(0);" id="wiziappUpdateEffects"   data-action="effects"   class="update_button button">Update Effects</a>
	<a href="javascript:void(0);" id="wiziappUpdateImages"    data-action="images"    class="update_button button">Update Images</a>
	<a href="javascript:void(0);" id="wiziappUpdateIcons"     data-action="icons"     class="update_button button">Update Icons</a>
	<a href="javascript:void(0);" id="wiziappUpdateSplash"    data-action="splash"    class="update_button button">Update Splash</a>
	<a href="javascript:void(0);" id="wiziappUpdateManifest"  data-action="manifest"  class="update_button button">Update Manifest</a>

	<script type="text/javascript">
		jQuery(document).ready(function($){
			$("a.update_button").bind('click', function(event){
				event.preventDefault();

				var action = $(this).attr('data-action');
				if ( action ){
					var params = {
						action: 'wiziapp_update_'+action
					};

					$.get(ajaxurl, params, function(data){
						console.log(data);
					});
				}

				return false;
			});
		});
	</script>
	<?php
	}

	function display(){
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
		#current_progress_label{
			/**position: absolute;
			top: 40px;
			right: 4px;*/
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
	</style>

	<script src="http://cdn.jquerytools.org/1.2.5/all/jquery.tools.min.js"></script>

	<script type="text/javascript">
		var progressTimer = null;
		var progressWait = 30;
		var update_step = 0;
		var update_steps = ['handshake', 'config', 'display', 'effects', 'images', 'icons', 'splash', 'manifest'];
		var update_msg = [
			'<?php echo __('Installing WebApp', 'wiziapp')?>',
			'<?php echo __('Updating configuration', 'wiziapp')?>',
			'<?php echo __('Updating display settings', 'wiziapp')?>',
			'<?php echo __('Updating special effects', 'wiziapp')?>',
			'<?php echo __('Updating images', 'wiziapp')?>',
			'<?php echo __('Updating icons', 'wiziapp')?>',
			'<?php echo __('Updating splash screen', 'wiziapp')?>',
			'<?php echo __('Updating cache settings', 'wiziapp')?>'
		];

		jQuery(document).ready(function(){
			wiziappRegisterAjaxErrorHandler();

			// Register the report an error button
			jQuery('#wiziapp_report_problem').click(function(event){
				event.preventDefault();
				var $el = jQuery(this).parents(".wiziapp_errors_container").find(".report_container");

				var data = {};
				jQuery.each(jQuery('.wiziapp_error'), function(index, error){
					var text = jQuery(error).text();
					if ( text.indexOf('.') !== -1 ){
						text = text.substr(0, text.indexOf('.'));
					}
					data[index] = text;
				});
				var params = {
					action: 'wiziapp_report_issue',
					data: jQuery.param(data, true)
				};

				$el.load(ajaxurl, params, function(){
					var $mainEl = jQuery(".wiziapp_errors_container");
					$mainEl
					.find(".errors_container").hide().end()
					.find(".report_container").show().end();
					$mainEl = null;
				});

				var $el = null;
				return false;
			});

			jQuery(".retry_processing").bind("click", retryRequest);
			// Start sending requests to generate content till we are getting a flag showing we are done
			startProcessing();
		});

		function retryRequest(event){
			event.preventDefault();
			var $el = jQuery(this);
			var request = $el.parents('.wiziapp_error').data('reqObj');

			$el.parents('.wiziapp_error').hide();
			/*
			request.error = function(req, error){
			retryingFailed();
			};
			*/
			delete request.context;
			delete request.accepts;

			jQuery.ajax(request);

			$el = null;
			return false;
		}

		function retryingFailed(req, error){
			jQuery("#internal_error_2").show();
		}

		function startProcessing(){
			if ( typeof(update_steps[update_step]) != 'undefined' ){
				var params = {
					action: 'wiziapp_update_' + update_steps[update_step]
				};

				jQuery.post(ajaxurl, params, handleUpdateResponse, 'json');
				progressTimer = setTimeout(updateProgressBarByTimer, 1000 * progressWait);
			} else {
				requestFinalizingProcessing();
			}
		}

		function handleUpdateResponse(data){
			// Update the progress bar
			if ( typeof(data) == 'undefined'  || !data ){
				// The request failed from some reason...
				jQuery("#error_updating").show();
				return;
			}

			if (data.header.status){
				if (data.header.status !== "retry"){
					++update_step;
				}

				updateProgressBar();

				startProcessing();
			} else {
				jQuery("#error_updating").show();
			}
		};

		function wiziappRegisterAjaxErrorHandler(){
			jQuery.ajaxSetup({
				timeout: 60*1000,
				error:function(req, error){
					clearTimeout(progressTimer);
					if (error == 'timeout'){
						jQuery("#internal_error").data('reqObj', this).show();
						//startProcessing();
					} else if(req.status == 0){
						jQuery("#error_network").data('reqObj', this).show();
					} else if(req.status == 404){
						jQuery("#error_activating").show();
					} else if(req.status == 500){
						jQuery("#internal_error").data('reqObj', this).show();

					} else if(error == 'parsererror'){
						jQuery("#error_activating").show();
						/*
						} else if(error == 'timeout'){
						//jQuery("#error_network").show();
						jQuery("#internal_error").data('reqObj', this).show();
						*/
					} else {
						jQuery("#error_updating").show();
					}
				}
			});
		};

		function updateProgressBarByTimer(){
			var current = jQuery("#current_progress_indicator").text();

			if (current.length == 0){
				current = 0;
			} else if (current.indexOf('%') != -1){
				current.replace('%', '');
			}

			current = parseInt(current) + 1;

			if (current != 100){
				jQuery("#main_progress_bar").css('width', current + '%');
				jQuery("#current_progress_indicator").text(current + '%');

				// Repeat only once
				//progressTimer = setTimeout(updateProgressBarByTimer, 1000*progressWait);
			}
		};

		function updateProgressBar(){
			clearTimeout(progressTimer);
			progressTimer = null;

			var total_items = update_steps.length
			var done = ((update_step) / total_items) * 100;

			if (update_step < update_steps.length){
				jQuery("#current_progress_label").text(update_msg[update_step]+"...");
			} else {
				jQuery("#current_progress_label").text("<?php echo __('Finalizing...', 'wiziapp'); ?>");
			}

			jQuery("#main_progress_bar").css('width', done + '%');
			jQuery("#current_progress_indicator").text(Math.floor(done) + '%');
		};

		function requestFinalizingProcessing(){
			var params = {
				action: 'wiziapp_install_webapp_finish'
			};

			jQuery.post(ajaxurl, params, handleFinalizingProcessing, 'json');
		};

		function handleFinalizingProcessing(data){
			jQuery("#wiziapp_finalize_title").show();
			var url = document.location.href;
			url = url.replace('wiziapp_webapp_display', 'wiziapp').replace("&wiziapp_reload_webapp=1", "");
			document.location.replace(url);
		}
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

		<p id="wiziapp_finalize_title" class="text_label"><?php echo __('Ready, if the page doesn\'t change in a couple of seconds click ', 'wiziapp'); ?><span id="finializing_activation"><?php echo __('here', 'wiziapp'); ?></span></p>

		<div id="error_activating" class="wiziapp_error hidden"><div class="icon"></div><div class="text"><?php echo __('There was an error loading the wizard, please contact support', 'wiziapp');?></div></div>
		<div id="internal_error" class="wiziapp_error hidden">
			<div class="icon"></div>
			<div class="text"><?php echo __('Connection error. Please try again.,', 'wiziapp');?> <a href="javscript:void(0);" class="retry_processing"><?php echo __('retry', 'wiziapp'); ?></a></div>
		</div>
		<div id="internal_error_2" class="wiziapp_error hidden"><div class="icon"></div><div class="text"><?php echo __('There were still errors contacting your server, please contact support', 'wiziapp');?></div></div>
		<div id="error_network" class="wiziapp_error hidden">
			<div class="icon"></div><div class="text"><?php echo __('Connection error. Please try again.', 'wiziapp');?> <a href="javscript:void(0);" class="retry_processing"><?php echo __('retry', 'wiziapp'); ?></a></div>
		</div>

		<div id="error_updating" class="wiziapp_error hidden"><div class="icon"></div><div class="text"><?php echo __('There was a problem installing the webapp, please contact support', 'wiziapp');?></div></div>
	</div>
	<?php
	}
}