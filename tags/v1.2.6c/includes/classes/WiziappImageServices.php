<?php

class WiziappImageServices{
    public static function getByRequest(){
        $width = $_GET['width'];
         
        $image = new WiziappImageHandler($_GET['url']);
        $image->wiziapp_getResizedImage($width, $_GET['height'], $_GET['type'], $_GET['allow_up']);
    }
}