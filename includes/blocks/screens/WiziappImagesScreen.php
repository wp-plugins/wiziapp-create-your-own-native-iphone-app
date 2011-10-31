<?php
/**
* @package WiziappWordpressPlugin
* @subpackage Display
* @author comobix.com plugins@comobix.com
*
*/

class WiziappImagesScreen extends WiziappBaseScreen{
    protected $name = 'images';
    protected $type = 'list';

    // @todo Add paging support here
    public function run(){
        $numberOfPosts = WiziappConfig::getInstance()->comments_list_limit;

        $screen_conf = $this->getConfig();

        $page = array();

        $args = array(
            'post_type' => 'attachment',
            'numberposts' => -1,
            'post_status' => null,
            'post_parent' => null, // any parent
        );
        $attachments = get_posts($args);

        $counter = 0;
        foreach($attachments as $attachment){
            $isImage = wp_attachment_is_image($attachment->ID);
            if ( $isImage && $counter < $numberOfPosts ){
                $this->appendComponentByLayout($page, $screen_conf['items'], $attachment);
                ++$counter;
            }
            if ( $counter == $numberOfPosts ){
                break;
            }
         }

         $title = __('Gallery', 'wiziapp');
         $screen = $this->prepare($page, $title, 'gallery', false, true);

         $screen['screen']['default'] = 'grid';
         $screen['screen']['sub_type'] = 'image';
         $this->output($screen);
    }

    // @todo Add paging support here
    public function runByPost($params){
        $post_id = $params;
        $ids = FALSE;
        if ( is_array($params) ){
            $post_id = $params[0];
            $ids = $params[1];
        }

        if($ids){
            $images_ids = explode('_', $ids);
        }else{
            $images_ids = false;
        }
        $post = get_post($post_id);

        $screen_conf = $this->getConfig();
        $page = array();

        if ($post->post_type == 'page') {
            // Get all of the page images as stored on our table
            $images = WiziappDB::getInstance()->find_page_media($post_id, 'image');
        } else {
            // Get all of the post images as stored on our table
            $images = WiziappDB::getInstance()->find_post_media($post_id, 'image');
        }

        /**if ( ! function_exists('wp_load_image') ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }  */

        foreach($images as $image_info){
            //$attributes = json_decode($image_info['attachment_info'], TRUE);

            /**if(is_array($images_ids) && (!in_array($image_info['id'], $images_ids) && !in_array($attributes['metadata']['id'], $images_ids))){
                continue;
            }*/

            if ( is_array($images_ids) ){
                if ( !in_array($image_info['id'], $images_ids) ){
                    continue;
                }
            }

            //$image = $attributes['attributes'];
            $dom = new WiziappDOMLoader($image_info['original_code'], get_bloginfo('charset'));
            $imageDOM = $dom->getBody();
            $image = $imageDOM[0]['img']['attributes'];

            $pid = $image_info['id'];
            $image['pid'] = $pid;
            $image['description'] = '';
            $image['alttext'] = $image['title'];
            $image['imageURL'] = $image['src'];
            $image['relatedPost'] = $post_id;

            // The images component will take care of the resizing
            $image['thumbURL'] = $image['src'];

            $this->appendComponentByLayout($page, $screen_conf['items'], $image, true);
         }

         $title = str_replace('&amp;', '&', $post->post_title);
         $screen = $this->prepare($page, $title, 'gallery', false, true);

         $screen['screen']['default'] = 'grid';
         $screen['screen']['sub_type'] = 'image';
         $this->output($screen);
    }
}