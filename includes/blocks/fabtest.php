<?php
add_shortcode('fab', 'fabshortcode');

function fabshortcode(){
    $images = array(
        '1' => array(
            'src' => 'http://www.joystickdivision.com/kate02b.jpg',
            'alt' => 'Image Alt',
            'title' => 'Image Title',
            'css_class' => 'my_css_class_1 my_css_class_2',
            'gallery_id' => 'my_gallery_1'
        ),
        '2' => array(
            'src' => 'http://www.whuzzups.com/wp-content/uploads/2011/07/Kate-Moss.jpg',
            'gallery_id' => 'my_gallery_1'
        )
    );

    $videos = array(
        '1' => array(
            'src' => 'http://www.youtube.com/watch?v=2HZxF0naty4'
        ),
        '2' => array(
            'src' => 'http://www.vimeo.com/7069913'
        )
    );

    $audios = array(
        '1' => array(
            'src' => 'http://fabian.comobix.com/wp-content/uploads/2011/08/03-Heirloom.mp3',
            'title' => 'Pink Floyd - The Wall',
            'duration' => '3:06'
        ),
        '2' => array(
            'src' => 'http://fabian.comobix.com/wp-content/uploads/2011/08/03-Heirloom.mp3',
            'title' => 'Pink Floyd - Wish you were here'
        )
    );

    $obj = apply_filters('wiziapp_3rd_party_plugin', '[fab]', 'image', $images);
//    $obj = apply_filters('wiziapp_3rd_party_plugin', '[fab]', 'video', $videos);
//    $obj = apply_filters('wiziapp_3rd_party_plugin', '[fab]', 'audio', $audios);
    return $obj;
}