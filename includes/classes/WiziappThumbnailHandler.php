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
    private $limitSize;
    private $singles = array();

    public function __construct($post, $size, $limitSize){
        $this->post = $post;
        $this->size = $size;
        $this->limitSize = $limitSize;
    }

    public static function getPostThumbnail($post, $size, $limitSize){
        $thumb = get_bloginfo('url') . "/?wiziapp/getthumb/{$post->ID}&width={$size['width']}&height={$size['height']}&limitWidth={$limitSize['height']}&limitHeight={$limitSize['height']}";
        WiziappLog::getInstance()->write('info', "Requesting the post thumbnail url: {$thumb}", "wiziapp_getPostThumbnail");
        return $thumb;
    }

   public function doPostThumbnail(){
        $foundImage = FALSE;
        WiziappLog::getInstance()->write('info', "Getting the post thumbnail: {$this->post}", "wiziapp_doPostThumbnail");
        @include_once(ABSPATH . 'wp-includes/post-thumbnail-template.php');
        if(function_exists('get_the_post_thumbnail')){ //first we try to get the wordpress post thumbnail
            WiziappLog::getInstance()->write('debug', "The blog supports post thumbnails (get_the_post_thumbnail method exists)", "wiziapp_doPostThumbnail");
            if (has_post_thumbnail($this->post)){
                $foundImage = $this->tryWordpressThumbnail();
            }
        } else {
            WiziappLog::getInstance()->write('debug', "get_the_post_thumbnail method does not exists", "wiziapp_doPostThumbnail");
        }

        if (!$foundImage){ // if no wordpress thumbnail, we take the thumb from a gallery
            $foundImage = $this->tryGalleryThumbnail();
            if ( !$foundImage ){
                // if no thumb from a gallery, we take the thumb from a video
                $foundImage = $this->tryVideoThumbnail();
                if ( !$foundImage ){
                    // if no thumb from a video, we take the thumb from a single image
                    $foundImage = $this->trySingleImageThumbnail();
                }
            }
        }

        if ( !$foundImage ){
            // If we reached this point we couldn't find a thumbnail.... Throw 404
            header("HTTP/1.0 404 Not Found");
        }
        return;
    }

    function tryWordpressThumbnail() {
        $post_thumbnail_id = get_post_thumbnail_id($this->post);
        $wpSize = array(
            $this->size['width'],
            $this->size['height'],
        );
        $image = wp_get_attachment_image_src($post_thumbnail_id, $wpSize);
        WiziappLog::getInstance()->write('info', "Got WP FEATURED IMAGE thumbnail id: {$post_thumbnail_id} attachment: {$image[0]} for post: {$this->post}", "wiziapp_tryWordpressThumbnail");
        //$image = wp_get_attachment_image_src($post_thumbnail_id);
        $showedImage = $this->processImageForThumb($image[0]);

        if ($showedImage) {
            WiziappLog::getInstance()->write('info', "Found and will use WP FEATURED IMAGE thumbnail: {$image[0]} for post: {$this->post}", "wiziapp_tryWordpressThumbnail");
        } else {
            WiziappLog::getInstance()->write('info', "Will *NOT* use WP FEATURED IMAGE thumbnail for post: {$this->post}", "wiziapp_tryWordpressThumbnail");
        }
        return $showedImage;
    }

    function tryGalleryThumbnail() {
        $post_media = WiziappDB::getInstance()->find_post_media($this->post, 'image');
        $showedImage = FALSE;

        if(!empty($post_media)){
            $singlesCount = count($this->singles);
            $galleryCount = 0;
            foreach($post_media as $media) {
                $encoding = get_bloginfo('charset');
                $dom = new WiziappDOMLoader($media['original_code'], $encoding);
                $tmp = $dom->getBody();
                $attributes = (object) $tmp[0]['img']['attributes'];

                $info = json_decode($media['attachment_info']);
                if (!isset($info->metadata)){ // Single image
                    if ($singlesCount < WiziappConfig::getInstance()->max_thumb_check){
                        WiziappLog::getInstance()->write('info', "Found SINGLE IMAGE {$attributes->src} for post: {$this->post}, and will put aside for use if needed.", "wiziapp_tryGalleryThumbnail");
                        $this->singles[] = $attributes->src;
                        ++$singlesCount;
                    }
                } else {
                    if ($galleryCount < WiziappConfig::getInstance()->max_thumb_check){
                        if ($showedImage = $this->processImageForThumb($attributes->src)){
                           WiziappLog::getInstance()->write('info', "Found and will use GALLERY thumbnail: {$media['attachment_info']['attributes']['src']} for post: {$this->post}", "wiziapp_tryGalleryThumbnail");
                            return $showedImage;
                        }
                        ++$galleryCount;
                    }
                }
            }
        } else {
            WiziappLog::getInstance()->write('info', "No GALLERY/SINGLE IMAGE found for post: {$this->post}", "wiziapp_tryGalleryThumbnail");
        }
        return $showedImage;
    }

    function tryVideoThumbnail() {
        $showedImage = FALSE;
        $post_media = WiziappDB::getInstance()->find_post_media($this->post, 'video');
        if(!empty($post_media)){
            $media = $post_media[key($post_media)];
            $info = json_decode($media['attachment_info']);
            if(intval($info->bigThumb->width) >= ($this->size['width'] * 0.8)){
                $image = new WiziappImageHandler($info->bigThumb->url);
                $showedImage = $image->wiziapp_getResizedImage($this->size['width'], $this->size['height'], 'adaptiveResize', true);
                WiziappLog::getInstance()->write('info', "Found and will use VIDEO thumbnail: {$image[0]} for post: " . $this->post, "tryVideoThumbnail");
            }
        } else {
            WiziappLog::getInstance()->write('info', "No VIDEO found for post: {$this->post}", "wiziapp_tryVideoThumbnail");
        }
        return $showedImage;
    }

    function trySingleImageThumbnail() {
        $showedImage = FALSE;
        foreach($this->singles as $single) {
            $image = new WiziappImageHandler($single);  // The original image
            $image->load();
            $width = $image->getNewWidth();
            $height = $image->getNewHeight();
    //        width=320&height=162&limitWidth=135&limitHeight=135
            if((intval($width) >= $this->limitSize['width']) && (intval($height) >= $this->limitSize['height'])){
                if(intval($width) >= ($this->size['width'] * 0.8) && intval($height) >= ($this->size['height'] * 0.8)){
                    $showedImage = $this->processImageForThumb($single);
                    WiziappLog::getInstance()->write('info', "Found and will use SINGLE IMAGE thumbnail: {$image[0]} for post: " . $this->post, "wiziapp_trySingleImageThumbnail");
                } else {
                    WiziappLog::getInstance()->write('info', "Will *NOT* use SINGLE IMAGE thumbnail for post " . $this->post . ". Size doesnt fit our requirements. Width: " . $width . " Height: " . $height, "wiziapp_trySingleImageThumbnail");
                }
            } else {
                WiziappLog::getInstance()->write('info', "Will *NOT* use SINGLE IMAGE thumbnail for post " . $this->post . ". Size doesnt fit our requirements. Width: " . $width . " Height: " . $height, "wiziapp_trySingleImageThumbnail");
            }
        }

        return $showedImage;
    }

    function processImageForThumb($src){
        $showedImage = FALSE;
        if (!empty($src)){
            $image = new WiziappImageHandler($src);  // The original image
            $image->load();
            $width = $image->getNewWidth();
            $height = $image->getNewHeight();

            if(intval($width) >= $this->limitSize['width'] && intval($height) >= $this->limitSize['height']){
                if(intval($width) >= ($this->size['width'] * 0.8) && intval($height) >= ($this->size['height'] * 0.8)){
                    //$imageUrl = $image->getResizedImageUrl($src, $size['width'], $size['height'], 'adaptiveResize', true);
                    $image->wiziapp_getResizedImage($this->size['width'], $this->size['height'], 'adaptiveResize', true);
                    $showedImage = TRUE;
                }
            }
        }
        return $showedImage;
    }
}