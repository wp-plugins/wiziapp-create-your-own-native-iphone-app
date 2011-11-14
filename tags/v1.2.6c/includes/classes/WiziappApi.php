<?php
/**
 * Public class to support your plugins' images/videos/audios view in Wiziapp.
 *
 * Just call our filter and give us your shortcode as it appears in the post
 * and an array of images/videos/audios as shown below, and we will display your
 * plugins' media in Wiziapp iPhone style.
 *
 *
 * Images Usage Example:
    $images = array(
        array(
            'src' => 'http://www.yoursite.com/images/image01.png',  // Mandatory Field
            'alt' => 'Image Alt',
            'title' => 'Image Title',
            'width' => '200',
            'height' => '200',
            'css_class' => 'my_css_class_1 my_css_class_2',
            'gallery_id' => 'my_gallery_1'                          // Mandatory Field
        ),
        array(
            'src' => 'http://www.yoursite.com/images/image02.png',
            'gallery_id' => 'my_gallery_1'
        )
    );
    $content = apply_filters('wiziapp_3rd_party_plugin', $content, 'image', $images);
 *
 *
 * Videos Usage Example:
    $videos = array(
        array(
            'src' => 'http://www.youtube.com/watch?v=2HZxF0naty4'   // Mandatory Field
        ),
        array(
            'src' => 'http://www.vimeo.com/7069913'
        )
    );
    $content = apply_filters('wiziapp_3rd_party_plugin', $content, 'video', $videos);
 *
 *
 * Audios Usage Example:
    $audios = array(
        array(
            'src' => 'http://www.yoursite.com/audios/pink_floyd-the_wall.mp3',   // Mandatory Field
            'title' => 'Pink Floyd - The Wall',                                  // Mandatory Field
            'duration' => '3:06'
        ),
        array(
            'src' => 'http://www.yoursite.com/audios/pink_floyd-wish_you_were_here.mp3',
            'title' => 'Pink Floyd - Wish you were here'
        )
    );
    $content = apply_filters('wiziapp_3rd_party_plugin', $content, 'audio', $audios);
*/

class WiziappApi extends WiziappMediaExtractor{
    public function __construct() {
    }

    public function externalPluginContent($content, $media_type, $medias) {
        $ch = WiziappContentHandler::getInstance();

        if (($ch->isInApp() || $ch->isInSave()) && !empty($medias)) {
            if ($media_type == 'image') {
                $wiziapp_images = '';
                foreach($medias as $image){
                	//return print_r($image);
                    $wiziapp_images .= '<a href="' . $image['src'] . '" class="wiziapp_gallery external-gallery-id">' . PHP_EOL .
                                           '<img src="' . $image['src'] . '" ' .
										   (isset($image['alt'])    ? 'alt="' . $image['alt'] . '" ' : '') .
                                           (isset($image['title'])  ? 'title="' . $image['title'] . '" ' : '') .
                                           (isset($image['width'])  ? 'width="' . $image['width'] . '" ' : '') .
                                           (isset($image['height']) ? 'height="' . $image['height'] . '" ' : '') .
                                           (isset($image['class'])  ? 'class="' . $image['css_class'] . '" ' : '') .
                                           'external-gallery-id="' . $image['gallery_id'] . '" />' . PHP_EOL .
										'</a>' . PHP_EOL;
                }
                $content = $wiziapp_images;
            } else if ($media_type == 'video') {
                $wiziapp_videos = '';
                foreach($medias as $video){
    //                $wiziapp_video = wp_oembed_get($video['src'], array('width' => 400, 'height' => 400));
                    $wiziapp_video = wp_oembed_get($video['src'], array());
                    $wiziapp_videos .= $wiziapp_video;
                }
                $content = $wiziapp_videos;
            } else if ($media_type == 'audio') {
                $wiziapp_audios = '';
                foreach($medias as $audio){
                    $wiziapp_audios .= '<a href="' . $audio['src'] . '" title="' . $audio['title'] . '">' . $audio['title'] . '</a><br />';
                }
                $content = $wiziapp_audios;
            }
        }

        return $content;
    }

}