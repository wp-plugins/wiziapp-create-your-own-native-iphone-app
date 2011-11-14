<?php
/**
* @package WiziappWordpressPlugin
* @subpackage Display
* @author comobix.com plugins@comobix.com
*
*/

class WiziappBaseScreen{
    protected $type = 'list';

    protected $name = '';

    public function getConfig($override_type=FALSE){
        $sc = new WiziappScreenConfiguration();
        $type = $this->type;
        if ( $override_type ){
            $type = $override_type;
        }
        return $sc->getScreenLayout($this->name, $type);
    }

    public function getTitle($title=''){
        if ( $title == '' ){
            $title = $this->name;
        }
        return __(WiziappConfig::getInstance()->getScreenTitle($title), 'wiziapp');
    }

    function prepareSection($page = array(), $title = '', $type = 'List', $hide = false, $show_ads = false, $css_class = ''){
        return $this->prepare($page, $title, $type, TRUE, false, $hide, $css_class, $show_ads);
    }

    function prepare($page = array(), $title = '', $type = 'Post', $sections = FALSE, $force_grouped = FALSE, $hide_separator = FALSE, $css_class = '', $show_ads = FALSE){
        $key = $sections ? 'sections' : 'items';

        $grouped = ($sections || $force_grouped) ? TRUE : FALSE;
        $css_class_name = empty($css_class) ? (($grouped) ? 'screen' : 'flat_screen') : $css_class;

        if ($grouped){
            // Verify that the app supports group, the theme might force everything to be not grouped
            if (!WiziappConfig::getInstance()->allow_grouped_lists || $title == 'Links'){
                $grouped = FALSE;
            }
        }

        $screen = array(
            'screen' => array(
                'type'    => strtolower($type),
                'title'   => $title,
                'class'   => $css_class_name,
                $key      => $page,
                'update'  => (isset($_GET['wizipage']) && $_GET['wizipage']) ? TRUE : FALSE,
                'grouped' => $grouped,
                'showAds' => $show_ads,
                //'hideCellSeparator' => $hide_separator,
            )
        );

        if (!$hide_separator) {
            $screen['screen']['separatorColor'] = WiziappConfig::getInstance()->sep_color;
        }

        return $screen;
    }

    public function output($screen_content){
        echo json_encode($screen_content);
    }

    /**
    * This method will convert the page layout instruction
    * to a known component. and then it will append it to the page
    * which is passed by reference
    *
    * @param array $page
    * @param string $block
    */
    public function appendComponentByLayout(&$page, $block){
        /**
        * Since this function is used for creating different type of pages
        * we can an unknown number of parameters depending on the
        * calling method
        */
        $params = func_get_args();
        /**
        * Removes the first two parameters from the params array
        * since we already know them by name
        */
        $tmpPage = array_shift($params);
        $tmpBlock = array_shift($params);
        $num = func_num_args();
        //WiziappLog::getInstance()->write('info', "Appending {$num} to page: ".print_r($params, TRUE), "content");

        $className = ucfirst($block['class']);
        $layout = $block['layout'];
        if (class_exists($className)){
            $obj = new $className($layout, $params);
            if ($obj->isValid()){
                $page[] = $obj->getComponent();
            }
        }
    }
}