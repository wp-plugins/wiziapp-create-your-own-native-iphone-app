<?php
/**
* Handles the display of the application, checks if the request for the blog came from a
* supported known application and if so directs it to the CMS Plugin theme.
* When displaying posts inside our templates makes sure to convert what is needed
*
* @package WiziappWordpressPlugin
* @subpackage ContentDisplay
* @author comobix.com plugins@comobix.com
*/
class WiziappContentHandler {
	var $mobile;
	private $inApp;
	var $debug = FALSE;
	private $inSave = FALSE;

	static $shouldDisplayAppstoreLinkFlag = TRUE;

	private static $_instance = null;

	private $originalTemplateDir = '';
	private $originalTemplateDirUri = '';
	private $originalStylesheetDir = '';
	private $originalStylesheetDirUri = '';


	/**
	* Apply all of the classes hooks to the right requests,
	* we don't need to start this request every time, just when it is possibly needed
	*/
	private function __construct() {
		$this->mobile = false;
		$this->inApp = false;

		add_action('plugins_loaded', array(&$this, 'detectAccess'), 99);
		add_action('plugins_loaded', array(&$this, 'avoidWpTouchIfNeeded'), 1);

		if ( strpos($_SERVER['REQUEST_URI'], '/wp-admin') === false
			&& strpos($_SERVER['REQUEST_URI'], 'xmlrpc') === false) {
			// Don't change the template directory when in the admin panel
			add_filter('stylesheet', array(&$this, 'get_stylesheet'), 99);
			add_filter('theme_root', array(&$this, 'theme_root'), 99);
			add_filter('theme_root_uri', array(&$this, 'theme_root_uri'), 99);
			add_filter('template', array(&$this, 'get_template'), 99);

			add_filter( 'template_directory', array( &$this, 'save_template_directory' ), 1);
			add_filter( 'template_directory_uri', array( &$this, 'save_template_directory_uri' ), 1);
			add_filter( 'stylesheet_directory', array( &$this, 'save_stylesheet_directory' ), 1);
			add_filter( 'stylesheet_directory_uri', array( &$this, 'save_stylesheet_directory_uri' ), 1);

			add_filter( 'template_directory', array( &$this, 'reset_template_directory' ), 99);
			add_filter( 'template_directory_uri', array( &$this, 'reset_template_directory_uri' ), 99);
			add_filter( 'stylesheet_directory', array( &$this, 'reset_stylesheet_directory' ), 99);
			add_filter( 'stylesheet_directory_uri', array( &$this, 'reset_stylesheet_directory_uri' ), 99);

			//add_filter('wp_head', array(&$this, 'do_head_section'), 99);
			add_filter('the_content', array(&$this, 'trigger_before_content'), 1);
			add_filter('the_content', array(&$this, 'convert_content'), 999);
			add_filter('the_category', array(&$this, 'convert_categories_links'), 99);
		} else {
			if (strpos($_SERVER['REQUEST_URI'], 'wiziapp') !== false) {
				// Avoid cache in the admin
				header("Cache-Control: no-store, no-cache, must-revalidate");
				header("Expires: " . gmdate("D, d M Y H:i:s", time() - 3600) . " GMT");
				add_filter('admin_head', array(&$this, 'do_admin_head_section'), 99);
			}
		}
	}

	public function setInSave() {
		$this->inSave = TRUE;
		$this->removeKnownFilters();
	}

	public function isInApp() {
		return $this->inApp;
	}

	public function isInSave() {
		return $this->inSave;
	}


	/**
	* We are doing some of the functionality ourselves so reduce the overhead...
	*/
	public function removeKnownFilters() {
		remove_filter('the_content', 'addthis_social_widget');
		remove_filter('the_content', 'A2A_SHARE_SAVE_to_bottom_of_content', 98);
		remove_filter("gettext", "ws_plugin__s2member_translation_mangler", 10, 3);
		remove_filter('the_content', 'shrsb_position_menu');
		remove_action('wp_head',   'dl_copyright_protection');
		remove_action('wp_footer', 'thisismyurl_noframes_killframes');
		remove_action('wp_head', array('anchor_utils', 'ob_start'), 10000);
	}

	public function forceInApp() {
		WiziappLog::getInstance()->write('INFO', "Forcing the application display", "WiziappContentHandler.forceInApp");
		$this->setInApp();
	}

	private function setInApp() {
		$this->mobile = TRUE;
		$this->inApp = TRUE;
		$this->removeKnownFilters();
	}

	public function avoidWpTouchIfNeeded() {
		$this->detectAccess();
		if ( $this->inApp ) {
			remove_action( 'plugins_loaded', 'wptouch_create_object' );
		}
	}
	/**
	* Detect if we have been access from the application, the application uses a pre-defined protocol for it's
	* requests, so if something is not there its not the application.
	*
	*/
	function detectAccess() {
		//WiziappLog::getInstance()->write('INFO', "Detecting access type", "WiziappContentHandler");
		$appToken = isset($_SERVER['HTTP_APPLICATION']) ? $_SERVER['HTTP_APPLICATION'] : '';
		$udid = isset($_SERVER['HTTP_UDID']) ? $_SERVER['HTTP_UDID'] : '';
		$display_download_from_appstore = (bool) WiziappConfig::getInstance()->display_download_from_appstore;

		//WiziappLog::getInstance()->write('DEBUG', "The headers are: {$appToken} and {$udid}", "WiziappContentHandler");

		if (strpos($_SERVER['REQUEST_URI'], 'wiziapp/') !== FALSE) {
			$this->inApp = TRUE;
		}

		if ((!empty($appToken) && !empty($udid)) || $this->inApp) {
			WiziappLog::getInstance()->write('INFO', "In the application display", "WiziappContentHandler.detectAccess");

			$this->setInApp();
		} else {
			$this->mobile = FALSE;
			$this->inApp = FALSE;

			if (isset($_SERVER['HTTP_USER_AGENT']) && $display_download_from_appstore) {
				$is_iPhone = stripos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== FALSE && stripos($_SERVER['HTTP_USER_AGENT'], 'Mac OS X') !== FALSE;
				$is_iPod   = stripos($_SERVER['HTTP_USER_AGENT'], 'iPod')   !== FALSE && stripos($_SERVER['HTTP_USER_AGENT'], 'Mac OS X') !== FALSE;
				$is_other  = stripos($_SERVER['HTTP_USER_AGENT'], 'iPad')   !== FALSE || stripos($_SERVER['HTTP_USER_AGENT'], 'Android')  !== FALSE || stripos($_SERVER['HTTP_USER_AGENT'], 'webOS') !== FALSE;
				if ( ($is_iPhone || $is_iPod) && ! $is_other ) {
					if (WiziappContentHandler::$shouldDisplayAppstoreLinkFlag && WiziappConfig::getInstance()->appstore_url != '') {
						// moving the cookie check to the browser
						//if (!isset($_COOKIE['WIZI_SHOW_APPSTORE_URL']) || $_COOKIE['WIZI_SHOW_APPSTORE_URL'] == 0) {
						add_action('wp_head', array(&$this, 'displayAppstoreAppURL'), 1);
						WiziappContentHandler::$shouldDisplayAppstoreLinkFlag = FALSE;
						/**$timeout = time() + (60 * 60 * 24);
						setcookie("WIZI_SHOW_APPSTORE_URL", WiziappConfig::getInstance()->appstore_url_timeout, $timeout, "/");*/
						//}
					}
				}
			}

			//WiziappLog::getInstance()->write('DEBUG', "Didn't recognize the headers, normal browsing", "WiziappContentHandler");
		}
	}

	function displayAppstoreAppURL () {
		/**
		* @todo move this to an external file
		*/
	?>
	<script type="text/javascript">
		function wiziappGetCookie(c_name) {
			var i,x,y,ARRcookies=document.cookie.split(";");
			for (i=0;i<ARRcookies.length;i++) {
				x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
				y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
				x=x.replace(/^\s+|\s+$/g,"");
				if (x==c_name) {
					return unescape(y);
				}
			}
		}

		function wiziappSetCookie(c_name,value,exdays) {
			var exdate=new Date();
			exdate.setDate(exdate.getDate() + exdays);
			var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
			document.cookie=c_name + "=" + c_value;
		}

		(function() {
				// We show "Appstore URL" message, if "WIZI_SHOW_APPSTORE_URL" cookie not exist
				if ( ! Boolean( wiziappGetCookie("WIZI_SHOW_APPSTORE_URL") ) ) {
					wiziappSetCookie("WIZI_SHOW_APPSTORE_URL",1,7);

					if ( Boolean( wiziappGetCookie("WIZI_SHOW_APPSTORE_URL") ) ) {
						// We are able to create the cookie, so it is ok
						var res = confirm("Download our App from the App Store");
						if (res == true) {
							// If user choose to see Appstore URL, show the message next after six months
							wiziappSetCookie("WIZI_SHOW_APPSTORE_URL",1,30*6);
							location.replace("<?php echo WiziappConfig::getInstance()->appstore_url; ?>");
						}
					}
				}
		})();
	</script>';
	<?php
	}

	/**
	* Handle the links converting, will convert images and post links according to
	* the app protocol.
	*
	* @param array $matches the array returned from preg_replace_callback
	* @return string the link found after converting to the app format
	*/
	function _handle_links_converting($matches) {
		$link = $matches[0];
		$url = $matches[2];
		// To support "Styling Page-Links" feature - http://codex.wordpress.org/Styling_Page-Links
		$styling_page_links = $matches[3];
		$post_id = url_to_postid($url);
		if ($post_id) {
			$post = get_post($post_id);
			$newUrl = '';
			if ($post->post_type == 'page') {
				$newUrl = WiziappLinks::pageLink($post_id);
			} elseif ($post->post_type == 'post') {
				$newUrl = WiziappLinks::postLink($post_id, $styling_page_links);
			}

			if ($newUrl == '') {
				$newUrl = $url;
			}
			$link = str_replace($url, $newUrl, $link);
		} else {
			// If it is an image, convert to open image
			if (    strpos($url, '.png') !== FALSE ||
				strpos($url, '.gif') !== FALSE ||
				strpos($url, '.jpg') !== FALSE ||
				strpos($url, '.jpeg') !== FALSE ) {
				$newUrl = WiziappLinks::linkToImage($url);
				$partLink = substr($link, 0, strpos($link, '>'));
				$secondPartLink = substr($link, strpos($link, '>'));

				$link = str_replace($url, $newUrl, $partLink) . $secondPartLink;
			}
		}
		return $link;
	}
	/**
	 *
	 * @param int $post_id the content id we are processing
	 * @param string $content
	 * @return array $replacements the array with the instructions for str_replace
	 */
	function _getGaleriesReplacementCode($post_id, $content) {
		if ($content != "") { // This might happen in cases like an empty nextgen album (with no galleries)
			$dom = new DOMDocument();
			libxml_use_internal_errors(true);
			@$dom->loadHTML($content); // Hide the errors

			$loadingErrors = libxml_get_errors();
			if ( count($loadingErrors) > 0 ) {
				WiziappLog::getInstance()->write('WARNING', "After loading the DOM for post: {$post_id} the errors are:".print_r($loadingErrors, TRUE), "WiziappContentHandler._getGaleriesReplacementCode");
			}
			libxml_clear_errors();

			$xpath = new DOMXPath($dom);
			// $images = $xpath->query('//img/parent::*[not(text()) and count(*)=1]/parent::*[not(text()) and count(*)=1]/parent::*[not(text()) and count(*)=1]/parent::*[id]/child::*/child::*/child::*/child::img');
			$images = $xpath->query('//p[not(text()) and count(*)=1]/a[not(text()) and count(*)=1]/img');
			$galleries = array();
			$nb = $images->length;
			if ($nb > 0) {
				for($pos=0; $pos<$nb; $pos++) {
					$image = $images->item($pos);
					$grand_parent = $image->parentNode->parentNode;
					$prev_image = ($pos>0)?$images->item($pos-1):null;
					if ($prev_image!= null && $grand_parent->isSameNode($prev_image->parentNode->parentNode->nextSibling)) {
						$galleries[count($galleries)][]=$image;
					} else {
						$galleries[count($galleries)+1] = array($image);
					}

				}
				foreach ($galleries as $gallery) {
					$first_image = array_shift($gallery);
					foreach ($gallery as $image) {
						$ancor = $image->parentNode;
						$first_image->parentNode->parentNode->appendChild($ancor);
						// $image->parentNode->parentNode->removeChild($ancor);
					}
				}
			}
			/**$content = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace( array('<html>', '</html>', '<body>', '</body>',
			"\n", "\r"), array('', '', '', '', '', ''), $dom->saveHTML()));*/
		}
	}

	/**
	* Get the images we scanned for this post and it's replacement code
	*
	* @param int $post_id the content id we are processing
	* @return array $replacements the array with the instructions for str_replace
	*/
	function _getImagesReplacementCode($post_id) {
		$single_img_src = array();
		$auxiliary = array(
			'wordpress-gallery-id'			  => 1,
			'data-wiziapp-cincopa-id'		  => 1,
			'data-wiziapp-nextgen-gallery-id' => 1,
		);
		$imagesElements = WiziappDB::getInstance()->get_content_images($post_id);
		$replacements = array(
			'find' => array(),
			'replace' => array(),
		);

		if ($imagesElements !== FALSE) {
			foreach ($imagesElements as $image) {
				// Load the image to the DOM processing
				$dom = new WiziappDOMLoader($image['original_code'], get_bloginfo('charset'));
				$imageDOM = $dom->getBody();

				WiziappLog::getInstance()->write('INFO', ">>> About to replace :: {$image['original_code']}", 'WiziappContentHandler._getImagesReplacementCode');
				$replacements['find'][] = $image['original_code'];

				$newImage = '<img ';
				WiziappLog::getInstance()->write('DEBUG', ">>> The image info :: " . print_r($image, TRUE), 'WiziappContentHandler._getImagesReplacementCode');
				$attachInfo = json_decode($image['attachment_info'], TRUE);
				foreach ($attachInfo['attributes'] as $attrName => $attrValue) {
					$value = str_replace('\\', '/', $attrValue);
					if ( in_array($attrName, array('src', 'title', 'alt')) ) {
						$value = $imageDOM[0]['img']['attributes'][$attrName];
						$attachInfo['attributes'][$attrName] = $value;

						/**
						* In the Gallery case, in entrance to a Post, need not to send the HTTP Request to all an Images of the Gallery.
						* As we show a first Image of the Gallery only.
						* So we change all "src" attributes of the Images to single first.
						*/
						if ( $attrName === 'src' ) {
							$gallery_attribute = array_intersect_key( $attachInfo['attributes'], $auxiliary );
							list( $gallery_name, $gallery_id ) = each( $gallery_attribute );

							if ( $gallery_name != '' && ctype_alnum( $gallery_id ) ) {
								if ( isset( $single_img_src[$gallery_name][$gallery_id] ) && $single_img_src[$gallery_name][$gallery_id] !== '' ) {
									$value = $single_img_src[$gallery_name][$gallery_id];
								} else {
									$single_img_src[$gallery_name][$gallery_id] = $value;
								}
							}
						}
					}

					$newImage .= " {$attrName}=\"{$value}\"";
				}

				/**
				* @todo Fix (remove) this once ticket 710 is fixed
				*/
				global $thumbSize;
				if (count($imagesElements) >= WiziappConfig::getInstance()->count_minimum_for_appear_in_albums) {
					$thumb = new WiziappImageHandler($attachInfo['attributes']['src']);
					$thumbSize = WiziappConfig::getInstance()->getImageSize('album_thumb');
					$url = $thumb->getResizedImageUrl($attachInfo['attributes']['src'], $thumbSize['width'], $thumbSize['height']);
					$newImage .= " data-image-thumb=\"" . $url . "\"";
				}

				$id_code = " data-wiziapp-id=\"{$image['id']}\" ";
				$newImage .= $id_code . ' />';
				WiziappLog::getInstance()->write('INFO', ">>> with this:: {$newImage}", 'WiziappContentHandler._getImagesReplacementCode');

				// Wordpress save the image without closing /> so let's check this too
				$replacements['find'][] = str_replace(' />', '>', $image['original_code']);
				$replacements['replace'][] = $newImage;
				// We have 2 find elements per image, so we need to replace, otherwise things gets buggy... :P
				$replacements['replace'][] = $newImage;
			}
		}
		return $replacements;
	}

	/**
	* Get the videos and audio we scanned for this post and it's replacement code
	*
	* @param int $post_id the content id we are processing
	* @return array $replacements the array with the instructions for str_replace
	*/
	function _getSpecialComponentsCode($post_id) {
		$replacements = array(
			'find' => array(),
			'replace' => array(),
			'regex' => array(
				'find' => array(),
				'replace' => array(),
			),
		);

		$specialElements = WiziappDB::getInstance()->get_content_special_elements($post_id);
		if ($specialElements !== FALSE) {
			$ve = new WiziappVideoEmbed();
			foreach ($specialElements as $element) {
				$replaceRegex = '';
				$info = json_decode($element['attachment_info'], TRUE);
				if ($element['attachment_type'] == WiziappDB::getInstance()->media_types['video']) {
					$replaceCode = $ve->getCode($info['actionURL'], $element['id'], $info['bigThumb']);

					// Get the original code and extract the video url
					$originalCode = $element['original_code'];
					$originalMatches = array();
					// Build the regex for the specific url
					$replaceRegex = '/(src)=(\'|")([^"]+)(\'|")/';
					preg_match($replaceRegex, $originalCode, $originalMatches);
					if ( !empty($originalMatches) && !empty($originalMatches[3]) ) {
						$origUrl = $originalMatches[3];
						$origUrl = str_replace(array('/', '.', '?'), array('\/', '\.', '\?'), $origUrl);
						if ( $replaceCode ) {
							$replacements['regex']['find'][] = '/(<object[^>]*>)(.*?)'.$origUrl.'(.*?)(<\/object>)/si';
							$replacements['regex']['replace'][] = $replaceCode;

							$replacements['regex']['find'][] = '/(<iframe[^>]*'.$origUrl.'[^>]*(\/?)>)(.*?)(<\/iframe>)?/si';
							$replacements['regex']['replace'][] = $replaceCode;
						}
					}
				} else {
					// audio should convert to a component
					// @todo Add single component export from the simulator engine
					$info['actionURL'] = str_replace('audio', 'video', $info['actionURL']);
					$style = '';
					if (!empty($info['imageURL'])) {
						$style = "background-image: url({$this->_getAdminImagePath()}{$info['imageURL']}.png);";
					}

					if (strlen($info['title']) > 35) {
						$title = substr($info['title'], 0, 35) . '...';
					} else {
						$title = $info['title'];
					}

					//$replaceCode = "<a href='" . $info['actionURL'] . "'><div class='audioCellItem'>
					$actionURL = str_replace('cmd://open/video/', '', $info['actionURL']);
					$actionURL = WiziappLinks::fixAudioLink($actionURL);
					$actionURL = str_replace('cmd://open/audio/', 'cmd://open/video/', $actionURL);

					$replaceCode =
						"<a href='" . $actionURL . "'><div class='audioCellItem'>
							<div class='col1'>
								<div class='imageURL' style='{$style}'></div>
							</div>
							<div class='col2'>
								<p class='title'>{$title}</p>
								<p class='duration'>{$info['duration']}</p>
							</div>
							<div class='col3'>
								<div class='playButton'></div>
							</div>
						</div></a>";

					// Get the original code and extract the video url
					$originalCode = $element['original_code'];
					$originalMatches = array();
					// Build the regex for the specific url
					$replaceRegex = '/(href)=(\'|")([^"]+)(\'|")/';
					preg_match($replaceRegex, $originalCode, $originalMatches);
					if ( !empty($originalMatches) && !empty($originalMatches[3]) ) {
						$origUrl = $originalMatches[3];
						$origUrl = str_replace(array('/', '.', '?'), array('\/', '\.', '\?'), $origUrl);

						$replacements['regex']['find'][] = '/(<a[^>]*'.$origUrl.'[^>]*>)(.*?)(<\/a>)/si';
						$replacements['regex']['replace'][] = $replaceCode;

					}
				}

				if ($replaceCode) {
					$replacements['find'][] = $element['original_code'];
					$replacements['replace'][] = $replaceCode;
				}
			}
		}
		WiziappLog::getInstance()->write('DEBUG', ">>> The replacement code is:" . print_r($replacements, TRUE), 'WiziappContentHandler._getSpecialComponentsCode');
		return $replacements;
	}

	function _getAdminImagePath() {
		return 'http://' . WiziappConfig::getInstance()->getCdnServer() . '/images/app/themes/' . WiziappConfig::getInstance()->wiziapp_theme_name . '/';
	}

	function add_header_to_content($content) {
		global $post;
		return get_post_meta($post->ID, 'wpzoom_post_embed_code', true) . $content;
	}

	function trigger_before_content($content) {
		if ($this->inApp === TRUE) {
			WiziappLog::getInstance()->write('INFO', "Triggering before the content", 'WiziappContentHandler.trigger_before_content');
			$content = apply_filters('wiziapp_before_the_content', $content);
		}

		if ($this->inApp === TRUE || $this->inSave === TRUE) {
			$content = $this->add_header_to_content($content);
		}
		return $content;
	}

	/**
	* Convert the known content to a predefined format used by the application.
	* Called from 'the_content' filter of wordpress, running last.
	*
	* @see self::_handle_links_converting
	* @see self::_getImagesReplacementCode
	* @see self::_getSpecialComponentsCode
	*
	* @param string $content the initial content
	* @return string $content the processed content
	*/
	function convert_content($content) {
		global $post;
		WiziappLog::getInstance()->write('INFO', "In the_content filter callback the contentHandler", "WiziappContentHandler.convert_content");

		if ($this->inApp === TRUE) {
			WiziappLog::getInstance()->write('INFO', "Converting content like we are inside the app", "WiziappContentHandler.convert_content");
			WiziappProfiler::getInstance()->write("Getting the images code for post {$post->ID}", "WiziappContentHandler.convert_content");

			// Add the content id to images
			$imagesCode = $this->_getImagesReplacementCode($post->ID);

			ob_start();
			wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) );
			$content .= ob_get_clean();

			$content = str_replace('&amp;', '&', $content);
			$content = str_replace('&#038;', '&', $content);
			$content = str_replace('&#8211;', '&ndash;', $content);
			// $content = str_replace(' &gt;', '&gt;', $content);
			// $content = str_replace(' &#062;', '&#062;', $content);
			$content = str_replace('  >', '>', $content);
			$content = str_replace(' >', '>', $content);
			$content = str_replace($imagesCode['find'], $imagesCode['replace'], $content);

			WiziappProfiler::getInstance()->write("Done Getting the images code for post {$post->ID}", "WiziappContentHandler.convert_content");
			//WiziappLog::getInstance()->write('INFO', "The content::" . $content, "convert_content");

			// Handle special tags: video and audio
			WiziappProfiler::getInstance()->write("Getting the special elements code for post {$post->ID}", "WiziappContentHandler.convert_content");
			$specialCode = $this->_getSpecialComponentsCode($post->ID);
			$content = str_replace($specialCode['find'], $specialCode['replace'], $content);
			$newContent = preg_replace($specialCode['regex']['find'], $specialCode['regex']['replace'], $content);

			if ( !empty($newContent) ) {
				$content = $newContent;
			} else {
				$lastError = preg_last_error();
				if ( !empty($lastError) ) {
					WiziappLog::getInstance()->write('ERROR', "There was an error replacing the codes: ({$lastError}): ".print_r($specialCode['regex'], TRUE), "WiziappContentHandler.convert_content");
				}
			}
			WiziappProfiler::getInstance()->write("Done Getting the special elements code for post {$post->ID}", "WiziappContentHandler.convert_content");

			// Handle links
			WiziappProfiler::getInstance()->write("Handling links for post {$post->ID}", "WiziappContentHandler.convert_content");
			$content = preg_replace_callback('/<a\s[^>]*href\s*=\s*([\"\']?)([^\" >]*(&page=\d+)|[^\" >]*)\\1[^>]*>(.*)<\/a>/si',
				array(&$this, "_handle_links_converting"), $content);
			WiziappProfiler::getInstance()->write("Done Handling links for post {$post->ID}", "WiziappContentHandler.convert_content");
		}

		WiziappLog::getInstance()->write('INFO', "Returning the converted content", "WiziappContentHandler.convert_content");
		return $content;
	}

	/**
	* Calculate the new size of an image based on the current and the requested size proportionally.
	*
	* @param int $width
	* @param int $height
	*
	* @return array $size
	*/
	function calcResize($width, $height) {
		$settings = array("max_width" => 288, "max_height" => -1);

		WiziappLog::getInstance()->write('info', "Resizing an image with width " . $width . " and height " . $height, "calcResize");
		WiziappLog::getInstance()->write('info', "The options are: max_width: " . $settings['max_width'] . " & max_height: " . $settings['max_height'], "calcResize");

		$size = array("width" => $width, "height" => $height);

		$width = intval($width);
		$height = intval($height);
		$ratio = $height / $width;

		WiziappLog::getInstance()->write('info', "The ratio is: " . $ratio, "calcResize");

		if ($width >= $settings['max_width']) {
			$size['width'] = round($settings['max_width']);
			$size['height'] = round($size['width'] * $ratio);
		} elseif ($settings['max_height'] != -1 && $height >= $settings['max_height']) {
			$size['height'] = $settings['max_height'];
			$size['width'] = $size['height'] / $ratio;
		}
		return $size;
	}

	function convert_categories_links($data1) {
		return $data1;
	}

	function do_head_section() {
		// Add our style sheets - no need anymore, or is there a need?
	}

	function do_admin_head_section() {
		$cssFile = dirname(__FILE__) . '/../../themes/admin/style.css';
		if ( file_exists($cssFile) ) {
			$css = file_get_contents($cssFile);
			/* remove comments */
			$css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
			/* remove tabs, spaces, newlines, etc. */
			$css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);

			$cdnServer = WiziappConfig::getInstance()->getCdnServer();

			$css = str_replace('@@@WIZIAPP_CDN@@@', $cdnServer, $css);
			echo '<style type="text/css">'. $css . '</style>';
		}
	}

	function get_stylesheet( $stylesheet ) {
		if ($this->inApp === TRUE) {
			$stylesheet = 'blank';
		}
		return $stylesheet;
	}

	function get_template( $template ) {
		$this->detectAccess();

		if ($this->inApp) {
			$template = 'iphone';
		}

		return $template;
	}

	function get_template_directory( $value ) {
		$this->detectAccess();

		$theme_root = $this->_get_plugin_dir();
		if ($this->inApp) {
			$value = trailingslashit($theme_root) . 'themes';
		}

		return $value;
	}

	function theme_root( $path ) {
		$this->detectAccess();

		$theme_root = $this->_get_plugin_dir();
		if ($this->inApp) {
			$path = trailingslashit($theme_root) . 'themes';
		}

		return $path;
	}

	public function save_template_directory($val) {
		$this->originalTemplateDir = $val;

		return $val;
	}

	public function reset_template_directory($val) {
		$this->detectAccess();
		if ( $this->inApp ) {
			$val = $this->originalTemplateDir;
		}

		return $val;
	}

	public function save_template_directory_uri($val) {
		$this->originalTemplateDirUri = $val;

		return $val;
	}

	public function reset_template_directory_uri($val) {
		$this->detectAccess();
		if ( $this->inApp ) {
			$val = $this->originalTemplateDirUri;
		}

		return $val;
	}

	public function save_stylesheet_directory_uri($val) {
		$this->originalStylesheetDirUri = $val;

		return $val;
	}

	public function reset_stylesheet_directory_uri($val) {
		$this->detectAccess();
		if ( $this->inApp ) {
			$val = $this->originalStylesheetDirUri;
		}

		return $val;
	}

	public function save_stylesheet_directory($val) {
		$this->originalStylesheetDir = $val;

		return $val;
	}

	public function reset_stylesheet_directory($val) {
		$this->detectAccess();
		if ( $this->inApp ) {
			$val = $this->originalStylesheetDir;
		}

		return $val;
	}

	function theme_root_uri( $url ) {
		$this->detectAccess();

		if ($this->inApp) {
			$dir = realpath($this->_get_plugin_dir());
			$url =str_replace(WIZI_ABSPATH, get_bloginfo('wpurl') . '/', $dir) . "/themes";
			$url = str_replace(DIRECTORY_SEPARATOR, '/', $url);
		}

		return $url;
	}

	public function registerPluginScripts() {
		if ($this->inApp) {
			$cdnUrl = WiziappConfig::getInstance()->getCdnServer();
			wp_register_script('wiziapp_mousewheel',
				$cdnUrl . '/scripts/jquery.mousewheel.min.js',
				array('jquery'),
				WIZIAPP_P_VERSION, TRUE );

			wp_register_script('wiziapp_scrolling',
				$cdnUrl . '/scripts/jScrollPane-1.2.3.min.js',
				array('jquery'),
				WIZIAPP_P_VERSION, TRUE );

			wp_register_script('wiziapp_base_lite',
				$cdnUrl . '/scripts/api/1/apps/scripts_lite.js',
				array('jquery'),
				WIZIAPP_P_VERSION, TRUE );

			wp_register_script('wiziapp_content',
				$cdnUrl . '/scripts/api/1/apps/content_'.WIZIAPP_VERSION.'.js',
				array('jquery','wiziapp_base_lite'),
				WIZIAPP_P_VERSION, TRUE );

			wp_enqueue_script('jquery');
			wp_enqueue_script('wiziapp_base_lite');
			wp_enqueue_script('wiziapp_mousewheel');
			wp_enqueue_script('wiziapp_scrolling');
			wp_enqueue_script('wiziapp_content');
		}

	}

	function _get_plugin_dir() {
		return trailingslashit(trailingslashit(dirname(__FILE__)) . trailingslashit('..') . trailingslashit('..'));
	}

	/**
	 * @static
	 * @return WiziappContentHandler
	 */
	public static function getInstance() {
		if ( is_null(self::$_instance) ) {
			self::$_instance = new WiziappContentHandler();
		}

		return self::$_instance;
	}
}
