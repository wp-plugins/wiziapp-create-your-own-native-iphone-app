<?php
/**
* @package WiziappWordpressPlugin
* @subpackage UIComponents
* @author comobix.com plugins@comobix.com
*
*/
class WiziappThumbnailHandler{
	private $post;
	private $size;
	private $_thumb_min_size;
	private $singles = array();

	public function __construct($post) {
		$this->post = $post;
		$this->size = WiziappConfig::getInstance()->getImageSize($_GET['type']);
		$this->_thumb_min_size = WiziappConfig::getInstance()->thumb_min_size;
	}

	public static function getPostThumbnail($post, $type) {
		$thumb = get_bloginfo('url') . "/?wiziapp/getthumb/" . $post->ID . '&type=' . $type;
		WiziappLog::getInstance()->write('INFO', "Requesting the post thumbnail url: {$thumb}", "WiziappThumbnailHandler.getPostThumbnail");
		return $thumb;
	}

	public function doPostThumbnail() {
		$foundImage = FALSE;
		WiziappLog::getInstance()->write('INFO', "Getting the post thumbnail: {$this->post}", "wiziapp_doPostThumbnail");
		@include_once(ABSPATH . 'wp-includes/post-thumbnail-template.php');

		$metabox_setting = get_post_meta( $this->post, 'wiziapp_metabox_setting', TRUE);
		if ( isset($metabox_setting['is_user_chosen']) && intval( $metabox_setting['is_user_chosen'] ) ) {
			// If Bloger self choose Image, from the Image to do Thumbnail
			$image_id = ( isset( $metabox_setting['post_thumbnail'] ) ) ? intval( $metabox_setting['post_thumbnail'] ) : 0;
			if ( $image_id ) {
				$image_url = wp_get_attachment_url( $image_id );
				if ( $image_url ) {
					$foundImage = $this->_processImageForThumb($image_url);
				}
			}
		} else {
			if ( function_exists('get_the_post_thumbnail') ) {
				//first we try to get the wordpress post thumbnail
				WiziappLog::getInstance()->write('INFO', "The blog supports post thumbnails (get_the_post_thumbnail method exists)", "WiziappThumbnailHandler.doPostThumbnail");
				if ( has_post_thumbnail($this->post) ) {
					$foundImage = $this->_tryWordpressThumbnail();
				}
			} else {
				WiziappLog::getInstance()->write('WARNING', "get_the_post_thumbnail method does not exists", "wiziapp_doPostThumbnail");
			}

			if ( ! $foundImage) {
				// if no wordpress thumbnail, we take the thumb from a gallery
				$foundImage = $this->_tryGalleryThumbnail();
			}

			if ( ! $foundImage ) {
				// if no thumb from a gallery, we take the thumb from a video
				$foundImage = $this->_tryVideoThumbnail();
			}

			if ( !$foundImage ) {
				// if no thumb from a video, we take the thumb from a single image
				$foundImage = $this->_trySingleImageThumbnail();
			}
		}

		if ( ! $foundImage ) {
			// If we reached this point we couldn't find a thumbnail.... Throw 404
			header("HTTP/1.0 404 Not Found");
		}
	}

	private function _tryWordpressThumbnail() {
		$post_thumbnail_id = get_post_thumbnail_id($this->post);
		$wpSize = array(
			$this->size['width'],
			$this->size['height'],
		);
		$image = wp_get_attachment_image_src($post_thumbnail_id, $wpSize);
		WiziappLog::getInstance()->write('INFO', "Got WP FEATURED IMAGE thumbnail id: {$post_thumbnail_id} attachment: {$image[0]} for post: {$this->post}", "WiziappThumbnailHandler._tryWordpressThumbnail");
		//$image = wp_get_attachment_image_src($post_thumbnail_id);
		$showedImage = $this->_processImageForThumb($image[0]);

		if ($showedImage) {
			WiziappLog::getInstance()->write('INFO', "Found and will use WP FEATURED IMAGE thumbnail: {$image[0]} for post: {$this->post}", "WiziappThumbnailHandler._tryWordpressThumbnail");
		} else {
			WiziappLog::getInstance()->write('INFO', "Will *NOT* use WP FEATURED IMAGE thumbnail for post: {$this->post}", "WiziappThumbnailHandler._tryWordpressThumbnail");
		}
		return $showedImage;
	}

	private function _tryGalleryThumbnail() {
		$post_media = WiziappDB::getInstance()->find_post_media($this->post, 'image');
		$showedImage = FALSE;

		if (!empty($post_media)) {
			$singlesCount = count($this->singles);
			$galleryCount = 0;
			foreach($post_media as $media) {
				$encoding = get_bloginfo('charset');
				$dom = new WiziappDOMLoader($media['original_code'], $encoding);
				$tmp = $dom->getBody();
				$attributes = (object) $tmp[0]['img']['attributes'];

				$info = json_decode($media['attachment_info']);
				if (!isset($info->metadata)) { // Single image
					if ($singlesCount < WiziappConfig::getInstance()->max_thumb_check) {
						WiziappLog::getInstance()->write('INFO', "Found SINGLE IMAGE {$attributes->src} for post: {$this->post}, and will put aside for use if needed.", "WiziappThumbnailHandler._tryGalleryThumbnail");
						$this->singles[] = $attributes->src;
						++$singlesCount;
					}
				} else {
					if ($galleryCount < WiziappConfig::getInstance()->max_thumb_check) {
						if ($showedImage = $this->_processImageForThumb($attributes->src)) {
							WiziappLog::getInstance()->write('INFO', "Found and will use GALLERY thumbnail for post: {$this->post}", "WiziappThumbnailHandler._tryGalleryThumbnail");
							return $showedImage;
						}
						++$galleryCount;
					}
				}
			}
		} else {
			WiziappLog::getInstance()->write('INFO', "No GALLERY/SINGLE IMAGE found for post: {$this->post}", "WiziappThumbnailHandler._tryGalleryThumbnail");
		}
		return $showedImage;
	}

	private function _tryVideoThumbnail() {
		$showedImage = FALSE;
		$post_media = WiziappDB::getInstance()->find_post_media($this->post, 'video');
		if (!empty($post_media)) {
			$media = $post_media[key($post_media)];
			$info = json_decode($media['attachment_info']);
			if (intval($info->bigThumb->width) >= ($this->size['width'] * 0.8)) {
				$image = new WiziappImageHandler($info->bigThumb->url);
				$showedImage = $image->wiziapp_getResizedImage($this->size['width'], $this->size['height'], 'adaptiveResize', true);
				WiziappLog::getInstance()->write('INFO', "Found and will use VIDEO thumbnail for post: " . $this->post, "WiziappThumbnailHandler._tryVideoThumbnail");
			}
		} else {
			WiziappLog::getInstance()->write('INFO', "No VIDEO found for post: {$this->post}", "WiziappThumbnailHandler._tryVideoThumbnail");
		}

		return $showedImage;
	}

	private function _trySingleImageThumbnail() {
		$showedImage = FALSE;
		foreach($this->singles as $single) {
			$image = new WiziappImageHandler($single);  // The original image
			$image->load();
			$width = $image->getNewWidth();
			$height = $image->getNewHeight();
			if (($width >= $this->_thumb_min_size) && ($height >= $this->_thumb_min_size)) {
				if (($width >= ($this->size['width'] * 0.8)) && ($height >= ($this->size['height'] * 0.8))) {
					$showedImage = $this->_processImageForThumb($single);
					WiziappLog::getInstance()->write('INFO', "Found and will use SINGLE IMAGE thumbnail for post: " . $this->post, "WiziappThumbnailHandler._trySingleImageThumbnail");
				} else {
					WiziappLog::getInstance()->write('INFO', "Will *NOT* use SINGLE IMAGE thumbnail for post ".$this->post.". Size doesnt fit our requirements. Width: ".$width." Height: ".$height, "WiziappThumbnailHandler._trySingleImageThumbnail");
				}
			} else {
				WiziappLog::getInstance()->write('INFO', "Will *NOT* use SINGLE IMAGE thumbnail for post " . $this->post . ". Size doesnt fit our requirements. Width: " . $width . " Height: " . $height, "WiziappThumbnailHandler._trySingleImageThumbnail");
			}
		}

		return $showedImage;
	}

	private function _processImageForThumb($src) {
		$showedImage = FALSE;
		if ( ! empty($src) ) {
			$image = new WiziappImageHandler($src);  // The original image
			$image->load();
			$width = $image->getNewWidth();
			$height = $image->getNewHeight();

			if ( intval($width) >= $this->_thumb_min_size && intval($height) >= $this->_thumb_min_size ) {
				if ( intval($width) >= ($this->size['width'] * 0.8) && intval($height) >= ($this->size['height'] * 0.8) ) {
					//$imageUrl = $image->getResizedImageUrl($src, $size['width'], $size['height'], 'adaptiveResize', true);
					try {
						$image->wiziapp_getResizedImage( $this->size['width'], $this->size['height'], 'adaptiveResize', true );
						$showedImage = TRUE;
					} catch (Exception $e) {
						WiziappLog::getInstance()->write('ERROR', $e->getMessage(), "WiziappThumbnailHandler._processImageForThumb");
					}
				}
			}
		}
		return $showedImage;
	}

}