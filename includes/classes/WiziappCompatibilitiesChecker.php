<?php if (!defined('WP_WIZIAPP_BASE')) exit();

class WiziappCompatibilitiesChecker{

	public $critical = FALSE;
	public $testedConnection = FALSE;
	public $hadConnectionError = FALSE;

	public function scanningTestAsHtml(){
		 $html = '';

		$netCheck = $this->testConnection();
		if ( WiziappError::isError($netCheck) ){
			$html .= $netCheck->getHTML();
		}

		$php = $this->testPhpRequirements();
		if ( WiziappError::isError($php) ){
			$html .= $php->getHTML();
		}

		$db = $this->testDatabase();
		if ( WiziappError::isError($db) ){
			$html .= $db->getHTML();
		}

		$token = $this->testToken();
		if ( WiziappError::isError($token) ){
			$html .= $token->getHTML();
		}

		return array(
			'text' => $html,
			'is_critical' => $this->foundCriticalIssues()
		);
	}

	public function fullTestAsHtml(){
		$html = '';

		$netCheck = $this->testConnection();
		if ( WiziappError::isError($netCheck) ){
			$html .= $netCheck->getHTML();
		}

		$php = $this->testPhpRequirements();
		if ( WiziappError::isError($php) ){
			$html .= $php->getHTML();
		}

		$phpGraphic = $this->testPhpGraphicRequirements();
		if ( WiziappError::isError($phpGraphic) ){
			$html .= $phpGraphic->getHTML();
		}

		$allowFopen = $this->testAllowUrlFopen();
		if ( WiziappError::isError($allowFopen) ){
			$html .= $allowFopen->getHTML();
		}

		$token = $this->testToken();
		if ( WiziappError::isError($token) ){
			$html .= $token->getHTML();
		}

		$dirs = $this->testWritingPermissions();
		if ( WiziappError::isError($dirs) ){
			$html .= $dirs->getHTML();
		}

		if ( empty($html) ){
			return '';
		}

		return self::create_error_block( array( 'text' => $html, 'is_critical' => $this->foundCriticalIssues(), ) );
	}

	public function foundCriticalIssues(){
		return $this->critical;
	}

	public function testWritingPermissions($return_as_html = true){
		$logs = WiziappLog::getInstance()->checkPath();
		$is_cache_enabled = WiziappCache::getCacheInstance()->is_cache_enabled();

		$thumbsHandler = new WiziappImageHandler();
		$thumbs = $thumbsHandler->checkPath();

		if ( $is_cache_enabled && $logs && $thumbs ){
			return TRUE;
		}

		if ( ! $return_as_html){
			return FALSE;
		}

		$message = 'It seems that your server settings are blocking access to certain directories. The WiziApp plugin requires writing permissions to the following directories:<br /><ul>';

		if ( ! $is_cache_enabled ){
			$message .= '<li>wp-content/uploads</li>';
		}
		if ( ! $logs ) {
			$message .= '<li>wp-content/plugins/wiziapp/logs</li>';
		}

		if ( ! $thumbs ){
			$message .= '<li>wp-content/plugins/wiziapp/cache</li>';
		}

		$message .= '</ul>Though you may choose not to provide these permissions, this would mean that any requests by your iPhone App readers would be made in real time, which would deny you the advantages of caching.';

		// @todo format this i18n wordpress function usage to allow params and send the dir list as a parameter
		return new WiziappError('writing_permissions_error', __($message, 'wiziapp'));
	}

	public function testDatabase(){
		if ( WiziappDB::getInstance()->isInstalled() ){
			return TRUE;
		}

		// Try to recover
		WiziappDB::getInstance()->install();

		if ( ! WiziappDB::getInstance()->isInstalled() ){
			$this->critical = TRUE;
			return new WiziappError('database_error', __('Your WordPress installation does not have permission to create tables in your database.', 'wiziapp'));
		}
	}

	public function testToken(){
		// If we don't have a token, try to get it again
		$activated = ! empty(WiziappConfig::getInstance()->plugin_token);
		if (  !$activated ){
			$cms = new WiziappCms();
			$activated = $cms->activate();
		}

		if ( !$activated ) {
			$errors = new WiziappError();
			if ( !$this->testedConnection ){
				$connTest = $this->testConnection();
				if ( WiziappError::isError($connTest) ){
					$errors = $connTest;
				}
			}
			if ( !$this->hadConnectionError ){
				// If we already had connections errors, we are showing the problems from those errors
				// Else...
				$this->critical = TRUE;
				$errors->add('missing_token', __('Oops! It seems that the main server is not responding. Please make sure that your internet connection is working, and try again.', 'wiziapp'));
			}
			return $errors;
		}
		return TRUE;
	}

	public function testWebServer($return_as_html = true){
		if (isset($_SERVER['SERVER_SOFTWARE'])) { // Microsoft-IIS/x.x (Windows xxxx)
			if (stripos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') === FALSE) {
				return TRUE;
			} else {
				if ($return_as_html) {
					return new WiziappError('iis_server_found', __('It appears that your blog is running on an IIS server; the WiziApp plugin does not save logs in this architecture', 'wiziapp'));
				} else {
					return FALSE;
				}
			}
		} else {
			if ($return_as_html) {
				return new WiziappError('iis_server_found', __('It appears that your blog is running on an IIS server; the WiziApp plugin does not save logs in this architecture', 'wiziapp'));
			} else {
				return FALSE;
			}
		}
	}

	public function testOperatingSystem(){
		if (isset($_SERVER['SERVER_SOFTWARE'])) {
			if (stripos($_SERVER['SERVER_SOFTWARE'], 'Win32') === FALSE) {
				return 'Linux';
			} else {
				return 'Windows';
			}
		} else {
			return 'Unknown';
		}
	}

	public function testPhpGraphicRequirements($return_as_html = true){
		$gotGD = extension_loaded('gd');
		$gotImagick = extension_loaded('imagick');
		if ( !$gotGD && !$gotImagick ){
			if ($return_as_html) {
				return new WiziappError('missing_php_requirements', __('Wiziapp requires either the GD or the ImageMagick PHP extension to be installed on the server. Please contact your hosting provider to enable one of these extensions, otherwise the thumbnails will not function properly', 'wiziapp'));
			} else {
				return FALSE;
			}
		}

		// If we got till here all is good
		return TRUE;
	}

	public function testAllowUrlFopen($return_as_html = true){
		if ( ini_get('allow_url_fopen') != '1' ){
			if ($return_as_html) {
				$error_message =
				'Your host is blocking the PHP directive allow_url_fopen which is required by the WiziApp plugin.
				Please change the "allow_url_fopen=Off" with "allow_url_fopen=On".
				In most cases you can do this by editing your php.ini file, in other cases you should be able to change these settings on your hosting cPanel.';
				return new WiziappError('missing_php_requirements', __($error_message, 'wiziapp'));
			} else {
				return FALSE;
			}
		}

		// If we got till here all is good
		return TRUE;
	}

	public function testPhpRequirements(){
		//xml processing curl or other ways to open remote streams enabled allow_url_fopen as on/true gd / imagemagick lib installed
		$errors = new WiziappError();

		/*
		$gotGD =extension_loaded('gd');
		$gotImagick = extension_loaded('imagick');
		if ( !$gotGD && !$gotImagick ){
		$errors->add('missing_php_requirements', __('Wiziapp requires either the GD or the ImageMagick PHP extension to be installed on the server. Please contact your hosting provider to enable one of these extensions, otherwise the thumbnails will not function properly', 'wiziapp'));
		}
		if ( ini_get('allow_url_fopen') != '1' ){
		$errors->add('missing_php_requirements', __('Your host blocked the PHP directive allow_url_fopen. Wiziapp needs allow_url_fopen to use images that are hosted on other websites as thumbnails', 'wiziapp'));
		}
		*/

		if ( !extension_loaded('libxml') || !extension_loaded('dom') ){
			$errors->add('missing_php_requirements', __('In order for WiziApp to operate, libxml and the DOM extension must be installed and enabled. ', 'wiziapp'));
			$this->critical = TRUE;
		}

		if ( count($errors->get_error_codes()) > 0 ){
			return $errors;
		} else {
			return TRUE;
		}
	}

	/**
	 * Check for the ability to issue outgoing requests
	 * and accept requests from the api server.
	 *
	 * Send a request to the admin to check access to this address
	 * it's POST since we need a more restrictive method, there is way
	 * to allow Wordpress to send GET request but not POST
	 *
	 * The post request must have a value to avoid issues with Content-Length  invalid and
	 * 413 Request Entity Too Large as a result...
	 *
	 * Covers the publicly accessible and out going requests tests
	 *
	 * @return bool|WiziappError can return true if everything is ok or an error object
	 */
	public function testConnection(){
		$this->testedConnection = TRUE;

		$r = new WiziappHTTPRequest();
		$response = $r->api( array( 'url' => urlencode( WiziappContentHandler::getInstance()->get_blog_property('url') ), ), '/cms/checkUrl', 'POST' );

		if ( is_wp_error($response) ){
			// If we couldn't connect to the host, outbound connections might be blocked
			if ( "couldn't connect to host" == $response->get_error_message() ){
				$this->critical = TRUE;
				$this->hadConnectionError = TRUE;

				return new WiziappError(
				'testing_connection_failed',
				__('It seems that your server is blocked from issuing outgoing requests to our server. Please make sure your firewall and any other security measures enable outgoing connections.', 'wiziapp')
				);
			}

			return new WiziappError($response->get_error_code(), $response->get_error_message());
		}

		// The request worked, but was our server able to contact our url?
		$checkResult = json_decode($response['body']);

		if ( empty($checkResult) ){
			if ( isset($response['response']) && isset($response['response']['code']) && $response['response']['code'] === FALSE ){
				$this->critical = TRUE;
				$this->hadConnectionError = TRUE;

				return new WiziappError(
					'testing_connection_failed',
					__('Your host does not allow any kind of outgoing requests. WiziApp requires either HTTP Extension, cURL, Streams, or Fsockopen to be installed and enabled. Please contact your hosting provider to address this issue.', 'wiziapp')
				);
			}

			// The response wasn't in a json format
			return new WiziappError('testing_connection_failed', 'The WiziApp plugin has encountered a problem. Please contact us at support@wiziapp.com to see how we can help you resolve this issue');
		}

		// The response is ok, let's check when our server is saying
		if ( ! $checkResult->header->status ){
			$rewrite_rules_message = WiziappHelpers::check_rewrite_rules();
			$appropriate_message = ( $rewrite_rules_message !== '' ) ? $rewrite_rules_message : $checkResult->header->message;

			return new WiziappError('testing_connection_failed', $appropriate_message);
		}

		// If we made it this far, all is good
		return TRUE;
	}

	public static function create_error_block( array $error){
		ob_start();
		?>
		<div id="wiziapp_compatibilities_errors" class="wiziapp_errors_container">
			<div class="errors_container">
				<div class="errors"><?php echo $error['text']; ?></div>

				<div class="buttons">
					<a href=javascript:void(0); id="wiziapp_report_problem"><?php echo __('Report a Problem', 'wiziapp'); ?></a>
					<?php
					if ( $error['is_critical'] ){
						?>
						<a href=javascript:window.location.reload(); id="wiziapp_retry_compatibilities"><?php echo __('Retry', 'wiziapp'); ?></a>
						<?php
					} else {
						?>
						<a href=javascript:void(0); id="wiziapp_close_compatibilities" class="close"><?php echo __('OK', 'wiziapp'); ?></a>
						<?php
					}
					?>
				</div>
			</div>
			<div class="hidden report_container"></div>
		</div>
		<?php

		return ob_get_clean();
	}
}