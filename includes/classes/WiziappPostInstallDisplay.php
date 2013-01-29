<?php if (!defined('WP_WIZIAPP_BASE')) exit();
/**
* @package WiziappWordpressPlugin
* @subpackage Core
* @author comobix.com plugins@comobix.com
*/

class WiziappPostInstallDisplay{

	/**
	* The last line of defense against the fatal errors that might be caused by external plugins.
	* This methods is registered in the batch processing function and will handle situations
	* when the batch script ended due to a fatal error by alerting on the error to the client
	*/
	public function batchShutdown(){
		$error = error_get_last();
		if ($error['type'] != 1){
			return;
		}

		if (isset($GLOBALS['wiziapp_post']) && $GLOBALS['wiziapp_post']){
			ob_end_clean();

			$header = array(
				'action' => 'batch_shutdown',
				'status' => FALSE,
				'code' => 500,
				'message' => 'Unable to process post ' . $GLOBALS['wiziapp_post'],
			);

			header("HTTP/1.0 200 OK");
			echo json_encode(array('header' => $header, 'post' => $GLOBALS['wiziapp_post']));
		} elseif (isset($GLOBALS['wiziapp_page']) && $GLOBALS['wiziapp_page']){
			ob_end_clean();

			$header = array(
				'action' => 'batch_shutdown',
				'status' => FALSE,
				'code' => 500,
				'message' => 'Unable to process page ' . $GLOBALS['wiziapp_page'],
			);

			header("HTTP/1.0 200 OK");
			echo json_encode(array('header' => $header, 'page' => $GLOBALS['wiziapp_page']));
		}

		exit();
	}

	public function batchProcess_Posts(){
		WiziappLog::getInstance()->write('DEBUG', "Got a request to process posts as a batch: " . print_r($_POST, TRUE),
			"post_install.wiziapp_batch_process_posts");

		global $wpdb;
		$status = TRUE;
		$message = '';

		if ( ! isset($_POST['posts']) ){
			$status = FALSE;
			$message = 'incorrect usage';
		} else {
			ob_start();
			ini_set('display_errors', 0);
			register_shutdown_function(array('WiziappPostInstallDisplay', 'batchShutdown'));

			$postsIds = explode(',', $_POST['posts']);
			foreach ($postsIds as $id){
				WiziappLog::getInstance()->write('INFO', "Processing post: {$id} inside the batch",
					"post_install.wiziapp_batch_process_posts");
				$GLOBALS['wiziapp_post'] = $id;

				if ( ! empty($id) ){
					$ce = new WiziappContentEvents();
					$ce->savePost($id);
				} else {
					WiziappLog::getInstance()->write('ERROR', "Received an empty post id: {$id} inside the batch",
						"post_install.wiziapp_batch_process_posts");
				}

				WiziappLog::getInstance()->write('INFO', "Finished processing post: {$id} inside the batch",
					"post_install.wiziapp_batch_process_posts");
			}
		}

		$header = array(
			'action' => 'batch_process_posts',
			'status' => $status,
			'code' => ($status) ? 200 : 500,
			'message' => $message,
		);

		WiziappLog::getInstance()->write('DEBUG', "Finished processing the requested post batch, going to return: " . print_r($_POST['posts'], TRUE).' '.print_r($header, TRUE),
			"post_install.wiziapp_batch_process_posts");

		echo json_encode(array('header' => $header));
		exit();
	}

	public function batchProcess_Pages(){
		WiziappLog::getInstance()->write('DEBUG', "Got a request to process pages as a batch: " . print_r($_POST, TRUE),
			"post_install.wiziapp_batch_process_pages");
		global $wpdb;
		$status = TRUE;
		$message = '';

		if ( ! isset($_POST['pages']) ){
			$status = FALSE;
			$message = 'incorrect usage';
		} else {
			ob_start();
			ini_set('display_errors', 0);
			register_shutdown_function(array('WiziappPostInstallDisplay', 'batchShutdown'));

			$pagesIds = explode(',', $_POST['pages']);
			foreach ($pagesIds as $id){
				WiziappLog::getInstance()->write('INFO', "Processing page: {$id} inside the batch",
					"post_install.wiziapp_batch_process_pages");
				$GLOBALS['wiziapp_page'] = $id;

				if ( ! empty($id) ){
					$ce = new WiziappContentEvents();
					$ce->savePage($id);
				} else {
					WiziappLog::getInstance()->write('ERROR', "Received an empty page id: {$id} inside the batch",
						"post_install.wiziapp_batch_process_pages");
				}

				WiziappLog::getInstance()->write('INFO', "Finished processing page: {$id} inside the batch",
					"post_install.wiziapp_batch_process_pages");
			}
		}

		$header = array(
			'action' => 'batch_process_posts',
			'status' => $status,
			'code' => ($status) ? 200 : 500,
			'message' => $message,
		);

		WiziappLog::getInstance()->write('DEBUG', "Finished processing the requested page batch:" . print_r($_POST['pages'], TRUE) .", going to return: " . print_r($header, TRUE),
			"post_install.wiziapp_batch_process_pages");

		echo json_encode(array('header' => $header));
		exit();
	}

	public function batchProcess_Finish(){
		WiziappLog::getInstance()->write('INFO', "The batch processing is finished - 1",
			"post_install.wiziapp_batch_process_finish");

		// Send the profile again, and allow it to fail since it's just an update
		$cms = new WiziappCms();
		$cms->activate();

		// Mark the processing as finished
		WiziappConfig::getInstance()->finished_processing = TRUE;

		$status = TRUE;

		$header = array(
			'action' => 'batch_processing_finish',
			'status' => $status,
			'code' => ($status) ? 200 : 500,
			'message' => '',
		);

		WiziappLog::getInstance()->write('INFO', "The batch processing is finished - 2",
			"post_install.wiziapp_batch_process_finish");

		echo json_encode(array('header' => $header));
		exit;
	}

	public function reportIssue(){
		$report = new WiziappIssueReporter($_POST['data']);

		ob_start();
		$report->render();
		$content = ob_get_clean();
		echo $content;
		exit();
	}

	public function display(){
		global $wpdb;

		// Make sure we are installed....
		if ( ! WiziappConfig::getInstance()->isInstalled() ){
			$installer = new WiziappInstaller();
			$installer->install();
			// If we are here, we already seen the message...
			WiziappConfig::getInstance()->install_notice_showed = TRUE;
		}

		// Test for compatibilities issues with this installation
		$checker = new WiziappCompatibilitiesChecker();
		$errorsHtml = $checker->scanningTestAsHtml();

		$querystr =
		"SELECT DISTINCT(wposts.id), wposts.post_title
		FROM $wpdb->posts wposts
		WHERE wposts.ID not in (
			SELECT post_id
			FROM $wpdb->postmeta
			WHERE meta_key = 'wiziapp_processed' AND meta_value = '1'
		)
		AND wposts.post_status = 'publish'
		AND wposts.post_type = 'post'
		ORDER BY wposts.post_date DESC
		LIMIT 0, 50";

		$posts = $wpdb->get_results($querystr, OBJECT);
		$numposts = count($posts);
		$postsIds = array();
		$postsNames = array();
		foreach($posts as $post){
			$postsIds[] = $post->id;
			$postsNames[] = $post->post_title;
		}

		WiziappLog::getInstance()->write('DEBUG', "Going to process the following posts ids: " . print_r($postsIds, TRUE),
			"post_install.wiziapp_activate_display");
		WiziappLog::getInstance()->write('DEBUG', "Going to process the following posts names: " . print_r($postsNames, TRUE),
			"post_install.wiziapp_activate_display");

		$pagesQuery =
		"SELECT DISTINCT(wposts.id), wposts.post_title
		FROM $wpdb->posts wposts
		WHERE wposts.ID not in (
			SELECT post_id
			FROM $wpdb->postmeta
			WHERE meta_key = 'wiziapp_processed' AND meta_value = '1'
		)
		AND wposts.post_status = 'publish'
		AND wposts.post_type = 'page'
		ORDER BY wposts.post_date DESC
		LIMIT 0, 20";

		$pages = $wpdb->get_results($pagesQuery, OBJECT);
		$numOfPages = count($pages);
		$pagesIds = array();
		$pagesNames = array();

		foreach ($pages as $page){
			// Get the parent
			$shouldAdd = TRUE;

			if ( isset($page->post_parent) ){
				$parentId = (int)$page->post_parent;

				if ($parentId > 0){
					$parent = get_page($parentId);
					if ($parent->post_status != 'publish'){
						$shouldAdd = FALSE;
					}
				}
			}

			if ($shouldAdd){
				$pagesIds[] = $page->id;
				$pagesNames[] = $page->post_title;
			}
		}

		WiziappLog::getInstance()->write('DEBUG', "Going to process the following pages ids: " . print_r($pagesIds, TRUE),
			"post_install.wiziapp_activate_display");
		WiziappLog::getInstance()->write('DEBUG', "Going to process the following pages names: " . print_r($pagesNames, TRUE),
			"post_install.wiziapp_activate_display");
	?>
	<script type="text/javascript" src="<?php echo esc_attr(plugins_url('themes/admin/scripts/jquery.tools.min.js', dirname(dirname(__FILE__)))); ?>"></script>
	<style>
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
			position: absolute;
			top: 40px;
			right: 4px;
		}

		.text_label{
			color: #0ca0f5;
			font-weight: bold;
			text-align: center;
			font-size: 14px;
			margin: 15px 0 9px;
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

		#wiziapp_activation_container.no_js #wiziapp_js_disabled{
			display: block;
		}
		#wiziapp_activation_container.no_js #wiziapp_js_enabled{
			display: none;
		}

		#wiziapp_activation_container.js #wiziapp_js_disabled{
			display: none;
		}
		#wiziapp_activation_container.js #wiziapp_js_enabled{
			display: block;
		}
	</style>
	<?php echo "{$errorsHtml}" ?>
	<div id="wiziapp_activation_container" class="no_js">
		<div id="wiziapp_js_disabled">
			<div id="js_error" class="wiziapp_errors_container s_container">
				<div class="errors">
					<div class="wiziapp_error"><?php echo __('It appears that your browser is blocking the use of javascript. Please change your browser\'s settings and try again', 'wiziapp');?></div>
				</div>
			</div>
		</div>
		<div id="wiziapp_js_enabled">
			<div id="just_a_moment"></div>
			<p id="wizi_be_patient" class="text_label"><?php echo __('Please be patient while we generate your app. It may take several minutes.', 'wiziapp');?></p>
			<div id="wizi_icon_wrapper">
				<div id="wizi_icon_processing"></div>
				<div id="current_progress_label" class="text_label"><?php echo __('Initializing...', 'wiziapp'); ?></div>
			</div>
			<div id="main_progress_bar_container">
				<div id="main_progress_bar"></div>
				<div id="main_progress_bar_bg"></div>
			</div>
			<p id="current_progress_indicator" class="text_label"></p>

			<p id="wiziapp_finalize_title" class="text_label"><?php echo __('Ready, if the wizard doesn\'t load itself in a couple of seconds click ', 'wiziapp'); ?><span id="finializing_activation"><?php echo __('here', 'wiziapp'); ?></span></p>

			<div id="error_activating" class="wiziapp_errors_container s_container hidden">
				<div class="errors">
					<div class="wiziapp_error">
						<?php echo __('There was an error loading the wizard, please contact support', 'wiziapp');?>
					</div>
				</div>
			</div>
			<div id="internal_error" class="wiziapp_errors_container s_container hidden">
				<div class="errors">
					<div class="wiziapp_error"><?php echo __('Connection error. Please try again.,', 'wiziapp');?></div>
					<div class="buttons">
						<a href="javscript:void(0);" class="retry_processing"><?php echo __('retry', 'wiziapp'); ?></a>
					</div>
				</div>
			</div>
			<div id="internal_error_2" class="wiziapp_errors_container s_container hidden">
				<div class="errors">
					<div class="wiziapp_error">
						<?php echo __('There were still errors contacting your server, please contact support', 'wiziapp');?>
					</div>
				</div>
			</div>
			<div id="error_network" class="wiziapp_errors_container s_container hidden">
				<div class="errors">
					<div class="wiziapp_error"><?php echo __('Connection error. Please try again.', 'wiziapp');?></div>
				</div>
				<div class="buttons">
					<a href="javscript:void(0);" class="retry_processing"><?php echo __('retry', 'wiziapp'); ?></a>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		document.getElementById('wiziapp_activation_container').className = 'js';

		var can_run = <?php echo ( empty($errorsHtml) ) ? 'true' : 'false'; ?>;
		var got_critical_errors = <?php echo ($checker->foundCriticalIssues()) ? 'true' : 'false'; ?>;
		var post_ids = [<?php echo implode(',', $postsIds); ?>];
		var page_ids = [<?php echo implode(',', $pagesIds); ?>];
		var batch_size = 1;
		var progressTimer = null;
		var progressWait = 30;
		var profile_step		= <?php echo (WiziappConfig::getInstance()->finished_processing) ? 0 : 1; ?>;
		var analytics_account	= '<?php echo WiziappConfig::getInstance()->analytics_account; ?>';
		var url					= '<?php echo WiziappConfig::getInstance()->api_server; ?>';
		// var batch_size			= <?php echo WiziappConfig::getInstance()->post_processing_batch_size; ?>;

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
			if ( can_run ){
				startProcessing();
			} else {
				// Show the overlay
				var $box = jQuery('#wiziapp_compatibilities_errors');

				var overlayParams = {
					top: 100,
					left: (screen.width / 2) - ($box.outerWidth() / 2),
					/*
					mask: {
					color: '#444444',
					loadSpeed: 100,
					opacity: 0.9
					},
					*/
					onClose: function(){
						jQuery("#wiziapp_error_mask").hide();
					},
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
					},
					// disable this for modal dialog-type of overlays
					closeOnClick: false,
					closeOnEsc: false,
					// onClose: startProcessing,
					// load it immediately after the construction
					load: true
				};

				if ( ! got_critical_errors ){
					overlayParams.onClose = function(){
						jQuery("#wiziapp_error_mask").hide();
						startProcessing();
					};
				}

				$box.overlay(overlayParams);
			}
		});

		function retryRequest(event){
			event.preventDefault();
			var $el = jQuery(this);
			$el.parents('.wiziapp_errors_container').hide();
			var request = $el.parents('.wiziapp_errors_container').data('reqObj');

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
		};

		function retryingFailed(req, error){
			jQuery("#internal_error_2").show();
		};

		function startProcessing(){
			if (page_ids.length > 0){
				requestPageProcessing();
			} else if (post_ids.length > 0){
				requestPostProcessing();
			} else {
				requestFinalizingProcessing();
			}
		};

		function wiziappRegisterAjaxErrorHandler(){
			jQuery.ajaxSetup({
				timeout: 60*1000,
				error:function(req, error){
					clearTimeout(progressTimer);
					if (error == 'timeout'){
						// jQuery("#internal_error").data('reqObj', this).show();
						startProcessing();
					} else if (req.status == 0){
						jQuery("#error_network").data('reqObj', this).show();
					} else if (req.status == 404){
						jQuery("#error_activating").show();
					} else if (req.status == 500){
						// Check if this is our request..
						var data = jQuery.parseJSON(req.responseText);
						if (data){
							var requestParams = this.data.split('&');
							var itemsStr = requestParams[requestParams.length - 1].split('=')[1];

							var neededAction = '';
							var type = '';
							var failed = '';

							if ( typeof(data.post) == 'undefined' ){
								itemsStr = itemsStr.replace(data.page, '');
								neededAction = 'wiziapp_batch_process_pages';
								type = 'pages';
								failed = data.page;
							} else {
								itemsStr = itemsStr.replace(data.post, '');
								neededAction = 'wiziapp_batch_process_posts';
								type = 'posts';
								failed = data.post;
							}

							var items = unescape(itemsStr).split(',');
							var noErrorItems = cleanArray(items);

							if (noErrorItems.length > 0){
								var params = {
									action: neededAction,
									failed: failed
								};
								params[type] = noErrorItems.join(',');

								if (type == 'posts'){
									jQuery.post(ajaxurl, params, handlePostProcessing, 'json');
								} else if (type == 'pages'){
									jQuery.post(ajaxurl, params, handlePageProcessing, 'json');
								}
							} else {
								// Maybe there are more items in the queue
								startProcessing();
							}
						} else {
							// jQuery("#internal_error").data('reqObj', this).show();
							// Don't show the errors, just try to continue
							startProcessing();
						}
					} else if (error == 'parsererror'){
						// jQuery("#error_activating").show();
						startProcessing();
						/*
						} else if(error == 'timeout'){
						// jQuery("#error_network").show();
						jQuery("#internal_error").data('reqObj', this).show();
						*/
					} else {
						jQuery("#error_activating").show();
					}
				}
			});
		};

		function cleanArray(arr){
			var newArr = new Array();

			for (k in arr){
				if (arr.hasOwnProperty(k)){
					if(arr[k])
						newArr.push(arr[k]);
				}
			}

			return newArr;
		};

		function requestPageProcessing(){
			var pages = page_ids.splice(0, batch_size);

			var params = {
				action: 'wiziapp_batch_process_pages',
				pages: pages.join(',')
			};

			jQuery.post(ajaxurl, params, handlePageProcessing, 'json');
			progressTimer = setTimeout(updateProgressBarByTimer, 1000 * progressWait);
		};

		function handlePageProcessing(data){
			// Update the progress bar
			updateProgressBar();

			if ( typeof(data) == 'undefined' || ! data ){
				// The request failed from some reason... skip it
				startProcessing();
				return;
			}

			if (data.header.status){
				if (page_ids.length == 0){
					requestPostProcessing();
				} else {
					requestPageProcessing();
				}
			} else {
				var params = this.data.split('&');
				var pagesStr = params[1].split('=')[1].replace(data.page, '');
				var pages = unescape(pagesStr).split(',');
				var noErrorPages = cleanArray(pages);

				/**
				* Inform the server on the failure so we will not try to scan this page again
				* when entering this page again
				*/
				if (noErrorPages.length > 0){
					var params2 = {
						action: 'wiziapp_batch_process_pages',
						pages: noErrorPages.join(','),
						failed_page: data.page
					};
					jQuery.post(ajaxurl, params2, requestPageProcessing, 'json');
				} else {
					// Maybe there are more items in the queue
					startProcessing();
				}
			}
		};

		function requestPostProcessing(){
			var posts = post_ids.splice(0, batch_size);

			var params = {
				action: 'wiziapp_batch_process_posts',
				posts: posts.join(',')
			};

			jQuery.post(ajaxurl, params, handlePostProcessing, 'json');
			progressTimer = setTimeout(updateProgressBarByTimer, 1000 * progressWait);
		};

		function handlePostProcessing(data){
			// Update the progress bar
			updateProgressBar();

			if ( typeof(data) == 'undefined' || ! data ){
				// The request failed from some reason... skip it
				startProcessing();
				return;
			}

			if (data.header.status){
				if (post_ids.length == 0){
					requestFinalizingProcessing();
				} else {
					requestPostProcessing();
				}
			} else {
				var params = this.data.split('&');
				var postsStr = params[1].split('=')[1].replace(data.post, '');
				var posts = unescape(postsStr).split(',');
				var noErrorPosts = cleanArray(posts);

				/**
				* Inform the server on the failure so we will not try to scan this post again
				* when entering the page again
				*/
				if (noErrorPosts.length > 0){
					var params2 = {
						action: 'wiziapp_batch_process_posts',
						posts: noErrorPosts.join(','),
						failed_post: data.post
					};
					jQuery.post(ajaxurl, params2, handleProcess_Post, 'json');
				} else {
					// Maybe there are more items in the queue
					startProcessing();
				}
			}
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
				// progressTimer = setTimeout(updateProgressBarByTimer, 1000*progressWait);
			}
		};

		function updateProgressBar(){
			clearTimeout(progressTimer);
			progressTimer = null;

			// Added one for the profile activation
			var total_items = '<?php echo $numposts + $numOfPages; ?>';
			total_items += profile_step;

			var done = ((post_ids.length + page_ids.length + profile_step) / total_items) * 100;
			var left = 100 - done;

			if (page_ids.length > 0){
				jQuery("#current_progress_label").text("<?php echo __('Initializing...', 'wiziapp'); ?>");
			} else if ( post_ids.length > 0 ){
				jQuery("#current_progress_label").text("<?php echo __('Generating...', 'wiziapp'); ?>");
			} else {
				jQuery("#current_progress_label").text("<?php echo __('Finalizing...', 'wiziapp'); ?>");
			}

			jQuery("#main_progress_bar").css('width', left + '%');
			jQuery("#current_progress_indicator").text(Math.floor(left) + '%');
		};

		function requestFinalizingProcessing(){
			var params = {
				action: 'wiziapp_batch_process_finish'
			};

			jQuery.post(ajaxurl, params, handleFinalizingProcessing, 'json');
		};

		function handleFinalizingProcessing(data){
			if (data.header.status){
				--profile_step;
				// Update the progress bar
				updateProgressBar();
				jQuery("#wiziapp_finalize_title").show();
				document.location.reload();
			} else {
				// There was an error??
				jQuery("#error_activating").show();
			}
		};
	</script>

	<!--Google analytics-->
	<script type="text/javascript">
		/* <![CDATA[ */
		// Google analytics - Should *always* be in the end
		var _gaq = _gaq || [];
		if (typeof(_gaq.splice) == 'function'){
			_gaq.splice(0, _gaq.length);
		}
		_gaq.push(['_setAccount', analytics_account]);
		_gaq.push(['_setDomainName', url.replace('api.', '.')]);
		_gaq.push(['_setAllowLinker', true]);
		_gaq.push(['_setAllowHash', false]);
		_gaq.push(['_trackPageview', '/StartScanningGoal.php']);
		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
		/* ]]> */
	</script>
	<?php
	}
}