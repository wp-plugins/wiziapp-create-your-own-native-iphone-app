<?php

class WiziappHelpers{
    public static function makeShortString($str, $len){
        if (strlen($str) > $len) {
            $str = wordwrap($str, $len);
            $str = substr($str, 0, strpos($str, "\n"));
            if ($str[strlen($str) - 1] == ','){
                $str = substr($str, 0, strlen($str) - 1);
            }
            $str = $str . '...';
        }
        return $str;
    }
}