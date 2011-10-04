<?php
/**
* @package WiziappWordpressPlugin
* @subpackage Display
* @author comobix.com plugins@comobix.com
*
*/

class WiziappVideoScreen extends WiziappBaseScreen{
    protected $name = 'video';
    protected $type = 'webview';

    public function run(){

    }

    public function runById($video_id){
        global $video_row, $blog_title;
//    $page = array();
    //$video_row = WiziappDB::getInstance()->get_videos_by_provider_id($video_id);
        $video_row = WiziappDB::getInstance()->get_videos_by_id($video_id);
        $blog_title = WiziappTheme::applyRequestTitle(wp_title('&laquo;', false, 'right').get_bloginfo('name'));

        WiziappLog::getInstance()->write('info', "Preloading the video: " . $video_id, "screens.wiziapp_buildVideoPage");

        WiziappTemplateHandler::load(dirname(__FILE__) . '/../../../themes/iphone/video.php');
    }
}