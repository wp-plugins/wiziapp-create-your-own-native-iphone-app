<?php
/**
* @package WiziappWordpressPlugin
* @subpackage UIComponents
* @author comobix.com plugins@comobix.com
*/

class WiziappLinks{
    public static function authorLink($author_id){
        return 'nav://list/' . urlencode(get_bloginfo('url') . "/?wiziapp/content/list/author/{$author_id}/posts");
    }

    public static function postLink($post_id){
        //return get_bloginfo('url')."/wiziapp/content/post/{$post_id}";
        $link = urlencode(get_permalink($post_id));
        $url = '';
        if ( !empty($link) ){
            $url = "nav://post/{$link}";
        }
        return $url;
    }

    public static function pageLink($page_id){
        return 'nav://page/' . urlencode(get_page_link($page_id));
    }

    public static function categoryLink($category_id){
        return 'nav://list/' . urlencode(get_bloginfo('url') . "/?wiziapp/content/list/category/{$category_id}");
    }

    public static function tagLink($tag_id){
        return 'nav://list/' . urlencode(get_bloginfo('url') . "/?wiziapp/content/list/tag/{$tag_id}");
    }

    public static function  postTagsLink($post_id){
        return 'nav://list/' . urlencode(get_bloginfo('url') . "/?wiziapp/content/list/post/{$post_id}/tags");
    }

    public static function linksByCategoryLink($cat_id){
        return 'nav://list/' . urlencode(get_bloginfo('url') . "/?wiziapp/content/list/links/category/{$cat_id}");
    }

    public static function postCategoriesLink($post_id){
        return 'nav://list/' . urlencode(get_bloginfo('url') . "/?wiziapp/content/list/post/{$post_id}/categories");
    }

    public static function postImagesGalleryLink($post_id){
        return 'nav://gallery/' . urlencode(get_bloginfo('url') . "/?wiziapp/content/list/post/{$post_id}/images");
    }

    public static function postCommentsLink($post_id){
        //return 'nav://list/'.urlencode(get_bloginfo('url')."/wiziapp/content/list/post/{$post_id}/comments");
        return 'nav://comments/' . urlencode(get_bloginfo('url') . "/?wiziapp/content/list/post/{$post_id}/comments");
    }

    public static function postCommentSubCommentsLink($post_id, $comment_id){
        return 'nav://comments/' . urlencode(get_bloginfo('url') . "/?wiziapp/content/list/post/{$post_id}/comments/{$comment_id}");
    }

    /**
    * If the providers won't support mp4 version of the file try the video id will be -1
    *
    *
    * @param mixed $provider
    * @param mixed $video_id
    * @param mixed $url
     * @return string
     */
    public static function videoLink($provider, $video_id, $url=''){
        $url = urlencode($url);
        return "cmd://open/video/{$provider}/{$video_id}/{$url}";
    }

    public static function audioLink($provider, $url=''){
        $url = rawurlencode($url);
        return "cmd://open/{$provider}/{$url}";
    }
    
    public static function fixAudioLink($actionURL){
		$url = str_replace('cmd://open/audio/', '', urldecode($actionURL));
        
		$url = rawurlencode(urldecode($url));
        return "cmd://open/audio/{$url}";
    }

    public static function extractProviderFromVideoLink($link){
        $tmp = str_replace('://', '', $link);
        //$tmp = split('/', $tmp);
        $tmp = explode('/', $tmp);
        return $tmp[2];
    }

    public static function videoPageLink($item_id){
        return "nav://page/" . urlencode(get_bloginfo('url') . "/?wiziapp/content/video/{$item_id}");
    }

    public static function videoDetailsLink($item_id){
        return "nav://video/" . urlencode(get_bloginfo('url') . "/?wiziapp/content/list/media/video/{$item_id}");
    }

    public static function archiveYearLink($year){
        return 'nav://list/' . urlencode(get_bloginfo('url') . "/?wiziapp/content/list/archive/{$year}");
    }

    public static function archiveMonthLink($year, $month){
        return 'nav://list/' . urlencode(get_bloginfo('url') . "/?wiziapp/content/list/archive/{$year}/{$month}");
    }

    public static function pluginAlbumLink($plugin='', $album_id){
        return 'nav://gallery/' . urlencode(get_bloginfo('url') . "/?wiziapp/content/list/gallery/{$plugin}/{$album_id}");
    }

    public static function ratingLink(){
        $url = urlencode(get_bloginfo('url') . "/?wiziapp/rate/post/");
        return "cmd://openRanking/{$url}";
    }

    public static function moreLink($page){
        // Get the current request url
        $requestUri = $_SERVER['REQUEST_URI'];
        // Isolate wiziapp part of the request
        $wsUrl = substr($requestUri, strpos($requestUri, 'wiziapp/'));

        $sep = '&';
        if (strpos($wsUrl, '?') !== FALSE){
            $wsUrl = str_replace('?', '&', $wsUrl);
        }

        $url = 'nav://list/' . urlencode(get_bloginfo('url') . "/?{$wsUrl}{$sep}wizipage={$page}");

        return $url;
    }

    public static function externalLink($url){
        return $url;
    }

    public static function linkToImage($url){
        /**
        * Make sure the image doesn't exceed the device size,
        * but only do this if we haven't converted it yet
        */

        /** We dont resize images anymore!!! Only thumbnails, on demand.
        if (strpos($url, 'wiziapp/cache/') === FALSE){
            $image = new WiziappImageHandler($url);
            $size = wiziapp_getImageSize('full_image');
            $url = $image->getResizedImageUrl($url, $size['width'], 0);
        } */

        return "cmd://open/image/" . urlencode($url);
    }

    public static function convertVideoActionToWebVideo($actionURL){
        return str_replace("open/video", "open/videopage", $actionURL);
    }
}


