<?php
/**
* @package WiziappWordpressPlugin
* @subpackage Display
* @author comobix.com plugins@comobix.com
*
*/

class WiziappCommentsScreen extends WiziappBaseScreen{
    protected $name = 'comments';
    protected $type = 'list';

    public function run(){

    }

    // @todo: Add paging support here
    public function runByPost($post_id){
        $screen_conf = $this->getConfig();

        $comments = get_approved_comments($post_id);

        $allSection = array(
            'section' => array(
                'title' => '',
                'id' => 'all_comments',
                'items' => array(),
            )
        );

        foreach($comments as $comment){
    //        $comment_id = $comment->comment_ID;
            // Only add top level comments unless told otherwise
            if ( $comment->comment_parent == 0 ){
                $this->appendComponentByLayout($allSection['section']['items'], $screen_conf['items'], $comment);
            }
         }

    //     $post = get_post($post_id);
         //$title = str_replace('&amp;', '&', $post->post_title);
         $title = __('Comments', 'wiziapp');

         $screen = $this->prepareSection(array($allSection), $title, "List", false, false, 'comments_screen');
         $this->output($screen);
    }

    function runByComment($params){
        $post_id = $params[0];
        $p_comment_id = $params[1];
        $screen_conf = $this->getConfig('sub_list');

        $page = array();
        $comments = get_approved_comments($post_id);
        // First add the parent comment to the list
        $parentCommentSection = array(
            'section' => array(
                'title' => '',
                'id'    => 'parent_comment',
                'items' => array(),
            )
         );

        $subCommentsSection = array(
            'section' => array(
                'title' => '',
                'id'    => 'subComments',
                'items' => array(),
            )
         );

        $comment = get_comment($p_comment_id);

        $this->appendComponentByLayout($parentCommentSection['section']['items'], $screen_conf['header'], $comment);
        foreach($comments as $comment){
            // Only add top level comments unless told otherwise
            if ( $comment->comment_parent == $p_comment_id ){
                $this->appendComponentByLayout($subCommentsSection['section']['items'], $screen_conf['items'], $comment);
            }
         }

         //$post = get_post($post_id);
         //$title = str_replace('&amp;', '&', $post->post_title);
         $title = __("Comments", 'title');

         $screen = $this->prepareSection(array($parentCommentSection, $subCommentsSection), $title, "List");
         $this->output($screen);
    }

    function runByMyComments($author_id){
        $numberOfPosts = WiziappConfig::getInstance()->comments_list_limit;

        $screen_conf = $this->getConfig('user_list');

        $page = array();

        global $wpdb;

        $key = md5( serialize( "number={$numberOfPosts}&user_id={$author_id}")  );
        $last_changed = wp_cache_get('last_changed', 'comment');
        if ( !$last_changed ) {
            $last_changed = time();
            wp_cache_set('last_changed', $last_changed, 'comment');
        }
        $cache_key = "get_comments:$key:$last_changed";

        if ( $cache = wp_cache_get( $cache_key, 'comment' ) ) {
            $comments = $cache;
        } else {
            $approved = "comment_approved = '1'";
            $order = 'DESC';
            $orderby = 'comment_date_gmt';
            $number = 'LIMIT ' . $numberOfPosts;
            $post_where = "user_id = '{$author_id}' AND ";

            $comments = $wpdb->get_results( "SELECT * FROM $wpdb->comments WHERE $post_where $approved ORDER BY $orderby $order $number" );
            wp_cache_add( $cache_key, $comments, 'comment' );
        }

        foreach($comments as $comment){
            $this->appendComponentByLayout($page, $screen_conf['items'], $comment);
         }

         $title = __('My Comments', 'wiziapp');

         $this->output($this->prepare($page, $title, "List"));
    }
}