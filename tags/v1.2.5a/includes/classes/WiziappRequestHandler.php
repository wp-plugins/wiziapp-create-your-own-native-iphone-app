<?php if (!defined('WP_WIZIAPP_BASE')) exit();
/**
* This class handles incoming request to the CMS.
* It will check if the request is ours and handle the 
* related web services
* 
* @package WiziappWordpressPlugin
* @subpackage Core
* @author comobix.com plugins@comobix.com
* 
*/
class WiziappRequestHandler {
    private $errorReportingLevel = 0;
    /**
    * Simple PHP4 style constructor to add the required actions
    */
    function WiziappRequestHandler() {
        add_action('parse_request', array(&$this, 'handleRequest'));
        add_action('init', array(&$this, 'logInitRequest'), 1);
    }
    
    function logInitRequest(){
        //WiziappLog::getInstance()->write('info', "Got a request for the blog: ", "remote.WiziappRequestHandler.logInitRequest");
        $request = $_SERVER['QUERY_STRING'];
        if (strpos($request, 'wiziapp/') !== FALSE){
            global $restricted_site_access;
            if ( !empty($restricted_site_access) ){
                // Avoid site restrictions by restricted site access plugin, this plugin will prevent our hooks from running
                remove_action('parse_request', array($restricted_site_access, 'restrict_access'), 1);
            }
        }
    }

    /*
     * Intercept any incoming request to the blog, if the request is for our web services
     * which are identified by the wiziapp prefix pass it on to processing, if not 
     * do nothing with it and let wordpress handle it
     * 
     * @see WiziappRequestHandler::_routeRequest
     * @params WP object  the main wordpress object is passed by reference
     */
    function handleRequest($wp){
    //function handleRequest(){
        //$request = $_SERVER['REQUEST_URI'];
        $request = $wp->request;
        if (empty($request)){
            // doesn't rewrite the requests, try to get the query string
            $request = urldecode($_SERVER['QUERY_STRING']);
        }

        WiziappLog::getInstance()->write('info', "Got a request for the blog: ".print_r($request, TRUE),
                                        "WiziappRequestHandler.handleRequest");
        
        //if (strpos($request, 'wiziapp/') === 0){
        if (($pos = strpos($request, 'wiziapp/')) !== FALSE){

            if ($pos != 0){
                $request = substr($request, $pos);
            }            
            
            $request = str_replace('?', '&', $request);
            
            $this->_routeRequest($request);
        } 
    }

    public function handleGeneralError(){
        $error = error_get_last();

        if(($error['type'] === E_ERROR) || ($error['type'] === E_USER_ERROR)){
            ob_end_clean();
            $header = array(
                'action' => 'handleGeneralError',
                'status' => FALSE,
                'code' => 500,
                'message' => 'There was a critical error running the service',
            );

            WiziappLog::getInstance()->write('Error', "Caught an error: ".print_r($error, TRUE),
                        "WiziappRequestHandler.handleGeneralError");

            if ( $this->errorReportingLevel !== 0 ){
                //$header['message'] = $error['message'];
                $header['message'] = implode('::', $error);
            }

            echo json_encode(array('header' => $header));
            exit();
        }
    }

    protected function setUseCachedResponse(){
        WiziappLog::getInstance()->write('info', "Nothing to output the app should use the cache",
            "WiziappRequestHandler.setUseCachedResponse");
        /**
        * IIS needs us to be very specific
        */
        header('Content-Length: 0');
        WiziappLog::getInstance()->write('info', "Sent the content-length",
            "WiziappRequestHandler.setUseCachedResponse");

        header("HTTP/1.1 304 Not Modified");
        WiziappLog::getInstance()->write('info', "sent 304 Not Modified for the app",
            "WiziappRequestHandler.setUseCachedResponse");
    }
    /*
    * serves as a routing table, if the incoming request has our 
    * prefix, check if we can handle the requested method, if so
    * call the method.
    * 
    * This is the first routing table function, it will separate the
    * content requests from the webservices requests.
    * 
    * One major difference between the webservices and the content requests
    * is that the content requests are getting cached on the server side and webservices requests 
    * shouldn't ever be cached as a whole.
    */
    function _routeRequest($request){
        $this->errorReportingLevel = error_reporting(0);
        register_shutdown_function(array($this, 'handleGeneralError'));
        
        $fullReq = explode('&', $request);
        $req = explode('/', $fullReq[0]);
    
        $service = $req[1];
        $action = $req[2];
        
        if ($service == 'user'){
            if ($action == 'check' || $action == 'login'){
                $this->runService('Login', 'check', FALSE);
            } elseif ($action == 'track'){
                WiziappCmsUserAccountHandler::pushSubscription($req[3], $req[4]);
            /**} elseif($action == 'register'){ // - Disabled for now
                $this->runScreenBy('System', 'Register', null);*/
            } elseif($action == 'forgot_pass'){
                $this->runScreenBy('System', 'ForgotPassword', null);
            }   
        } elseif($service == 'content' || $service == 'search') {
            // Content requests should trigger a the caching
            $cache = new WiziappCache;
            $key = str_replace('/', '_', $request);
            $qs = str_replace('&', '_', $_SERVER['QUERY_STRING']);
            $qs = str_replace('=', '', $qs);
            $key .= $qs;
            
			if (function_exists('is_multisite') && is_multisite()) {
				global $wpdb;
				$key .= $wpdb->blogid;
			} 

            $key .= WiziappContentEvents::getCacheTimestampKey();
            
            global $wiziappLoader;
            $key .= $wiziappLoader->getVersion();
            
            // Added the accept encoding headers, so we won't try to return zip when we can't
            $httpXcept = isset($_SERVER['HTTP_X_CEPT_ENCODING'])?$_SERVER['HTTP_X_CEPT_ENCODING']:'';
            $httpAccept = isset($_SERVER['HTTP_ACCEPT_ENCODING'])?$_SERVER['HTTP_ACCEPT_ENCODING']:'';
            $etagHeader = isset($_SERVER['HTTP_IF_NONE_MATCH'])?$_SERVER['HTTP_IF_NONE_MATCH']:'';
            $key .= str_replace(', ', '_', "{$httpXcept}_{$httpAccept}_{$etagHeader}");
            $key .= str_replace(',', '', $key);
            $key .= WIZIAPP_P_VERSION;
            //if ( $cache->beginCache(md5($key)) ){
            if ($cache->beginCache(md5($key), array('duration'=>30))){
                $output = $this->_routeContent($req);

                $cache->endCache($output);
                
                if (!$output){
                    $this->setUseCachedResponse();
                } else {
					ob_end_flush();
				}
            } else {
                /**
                 * Since the etag is part of the request an the cache key,
                 * If we got that right there is no need to return content
                 */
				if ( isset($_SERVER['HTTP_IF_NONE_MATCH']) ){
					// Sent headers = 
					$headersList = headers_list();
					$etagSent = '';
					$found = FALSE;
					for($h=0,$total=count($headersList);$h<$total && !$found;++$h){
						if ( strpos($headersList[$h], 'ETag:') === 0 ){
							$etagSent = str_replace('ETag: ', '', $headersList[$h]);
							$found = TRUE;
						}
					}
					if ( $etagSent == $etagHeader ) {
						$this->setUseCachedResponse();
					}
				}
            }

            
            /**
            * The content services are the only thing that will expose themselves and 
            * do a clean exit, the rest of the services will pass the handling to wordpress 
            * if they weren't able to process the request due to missing parameters and such
            */
            exit();    
//        } elseif( $service == 'rate' ) {
//            wiziapp_rate_content($req);
        } elseif($service == 'getrate') {
            //wiziapp_the_rating_wrapper($req);
            echo " "; // Currently disabled
            exit();
        } elseif ($service == "getimage"){
            WiziappImageServices::getByRequest();
            exit();
        } elseif ($service == "getthumb"){
            $wth = new WiziappThumbnailHandler($req[2], array('width'=>$_GET['width'], 'height'=>$_GET['height']), array('width'=>$_GET['limitWidth'], 'height'=>$_GET['limitHeight']));
            $wth->doPostThumbnail();
            exit();
        } elseif($service == 'post') {
            if ($req[3] == "comments") {
                $this->runService('Comment', 'getCount', $req[2]);
            }
        } elseif($service == 'comment') {
            $this->runService('Comment', 'add', $request);
        /**} elseif ( $service == 'search' ){
            $this->_routeContent($req); */
        } elseif ($service == 'keywords'){
            $this->runScreenBy('Search', 'Keywords', null);
        } elseif ($service == 'system'){
            if ($action == 'screens'){
                $this->runService('System', 'updateScreenConfiguration');
            } else if ($action == 'components'){
                $this->runService('System', 'updateComponentsConfiguration');
            } else if ($action == 'pages'){
                $this->runService('System', 'updatePagesConfiguration');
            } else if ($action == 'frame'){
                $this->runScreenBy('System', 'Frame');
            } else if ($action == 'settings'){
                $this->runService('System', 'updateConfiguration');
            } else if ($action == 'thumbs'){
                $this->runService('System', 'updateThumbsConfiguration');
            } else if ($action == 'check'){
                $this->runService('System', 'checkInstalledPlugin');
            } else if ( $action == 'logs' ){
                $this->runService('System', 'listLogs');
            } else if ( $action == 'getLog' ){
                $this->runService('System', 'getLogFile', $req[3]);
            }
        }
    }
    
    function _routeContent($req){
        // We are running our content web services make sure we have a clean buffer
        ob_end_clean();
        ob_start();
        header('Cache-Control: no-cache, must-revalidate');
        $offset = 3600 * 24; // 24 hours
        header('Expires: ' . gmdate("D, d M Y H:i:s", time() + $offset) . ' GMT');
        $type = $req[2];
        $id = $req[3];

        if ( $type != 'video' ){
            header('Content-Type: application/json; charset: utf-8');
        } else {
            header('Content-Type: text/html; charset: utf-8');
        }
                        
        if ($req[1] == 'search'){
            $this->runScreenBy('Search', 'Query', null);
        } else {
            if ($type == "scripts"){
                $this->runService('ContentScripts', 'get');
            } elseif ($type == 'about'){
                $this->runScreenBy('System', 'About', null);
            } elseif ( $type == 'video' ){
                $this->runScreenBy('Video', 'Id', $id);
            } elseif ($type == "list"){
                $sub_type = $req[3];
                WiziappLog::getInstance()->write('info', "Listing... The sub type is: {$sub_type}",
                                                "WiziappRequestHandler._routeContent");
                if ($sub_type == "categories")    {
                    $this->runScreen('Categories');
                } elseif($sub_type == "allcategories"){
                    $this->runService('Lists', 'categories');
                } elseif($sub_type == "tags"){
                    $this->runScreen('Tags');
                } elseif($sub_type == "alltags"){
                    $this->runService('Lists', 'tags');
                } elseif($sub_type == "posts"){
                    $show_by = $req[4];
                    if ($show_by == 'recent'){
                        $this->runScreenBy('Posts', 'Recent');
                    }
                } elseif ($sub_type == "pages") {
                    $this->runScreen('Pages');
                } elseif ($sub_type == "allpages") {
                    $this->runService('Lists', 'pages');
                } elseif ($sub_type == "post") {  // list/post/{id}/comments
                    $show = $req[5];
                    if ($show == "comments"){
                        if (isset($req[6]) && $req[6] != 0){
                            $this->runScreenBy('Comments', 'Comment', array($req[4], $req[6]));
                        } else {
                            $this->runScreenBy('Comments', 'Post', $req[4]);
                        }
                    } elseif ($show == "categories"){
                        $this->runScreenBy('Categories', 'Post', $req[4]);
                    } elseif ($show == "tags"){
                        $this->runScreenBy('Tags', 'Post', $req[4]);
                    } elseif ($show == "images"){
                        if(isset($_GET['ids']) && !empty($_GET['ids'])){
                            $this->runScreenBy('Images', 'Post', array($req[4], $_GET['ids']));
                        } else {
                            $this->runScreenBy('Images', 'Post', $req[4]);
                        }
                    }
                } elseif ($sub_type == "category"){
                    $this->runScreenBy('Posts', 'Category', $req[4]);
                } elseif ($sub_type == "tag"){
                    $this->runScreenBy('Posts', 'Tag', $req[4]);
                } elseif ($sub_type == "user"){
                    $show = $req[5];
                    if ($show == "comments"){
                        $this->runScreenBy('Posts', 'MyComments', $req[4]);
                    } elseif ($show == "commented"){
                        $this->runScreenBy('Posts', 'AuthorCommented', $req[4]);
                    }
                } elseif ($sub_type == 'author'){
                    $authorId = $req[4];
                    if ($req[5] == 'posts'){
                        $this->runScreenBy('Posts', 'Author', $authorId);
                    }
                } elseif( $sub_type == 'alllinks' ) {
                        $this->runService('Lists', 'links');
                } elseif ($sub_type == 'links') {
                    if (!empty($req[4])){
                        $show = $req[4];   
                        if ($show == 'categories') {
                            $this->runScreen('LinksCategories');
                        } elseif ($show == 'category'){
                            $this->runScreenBy('Links', 'Category', $req[5]);
                        }
                    } else {
                        $this->runScreen('Links');
                    }
                } elseif ($sub_type == "archive"){
                    $year = $req[4];
                    $month = $req[5];
                    $dayOfMonth = $req[6];
                    // Year
                    if (isset($year)){
                        // Month
                        if (isset($month)){
                            // Day of month
                            if (isset($dayOfMonth)){
                                $this->runScreenBy('Posts', 'DayOfMonth', array($year, $month, $dayOfMonth));
                            } else {
                                $this->runScreenBy('Posts', 'Month', array($year, $month));
                            } 
                        } else {
                            // Just year, no month
                            $this->runScreenBy('Archives', 'Year', $year);
                        }
                    } else {
                        $this->runScreen('Archives');
                    }
                    
                } elseif ($sub_type == "favorites"){
                    $this->runScreenBy('Posts', 'Ids', $_GET['pids']);
                } elseif ($sub_type == "media"){
                    $show = $req[4];
                    if ($show == "images"){
                        $this->runScreen('Images');
                    } elseif($show == 'videos') {
                        $this->runScreen('Videos');
                    //} elseif ($show == 'video') {

//                    } elseif ($show == 'videoembed') {
//                        $vid_id = $req[5];
//                        wiziapp_buildVideoEmbedPage($vid_id);
                    } elseif ($show == 'audios'){
                        $this->runScreen('Audios');
                    }
                } elseif ($sub_type == "galleries"){
                    $this->runScreen('Albums');
                } elseif ($sub_type == "gallery"){                
                    $plugin = $req[4];
                    $plugin_item_id = $req[5];
                    if ($plugin == 'videos' && $plugin_item_id == 'all_videos'){
                        $this->runScreen('Videos');
                    } else {
                        $this->runScreenBy('Albums', 'Plugin', array($plugin, $plugin_item_id));
                    }
                } elseif ($sub_type == "attachment"){
                    $attachmentId = $req[4];
                    $show = $req[5];
                    if ($show == "posts"){
                        $this->runScreenBy('Posts', 'Attachment', $attachmentId);
                    }
                } 
            }     
        }
        
        /**
        * Gzip the output, support weird headers - Moved to the caching class that actually does the output
        */
        /**$encoding = false; 
        if ( isset($_SERVER["HTTP_ACCEPT_ENCODING"]) ){
            $HTTP_ACCEPT_ENCODING = $_SERVER["HTTP_ACCEPT_ENCODING"]; 
            if ( isset($_SERVER["HTTP_X_CEPT_ENCODING"]) ){
                WiziappLog::getInstance()->write('info', "GOT A WEIRD HEADER", "remote.WiziappRequestHandler");
                $HTTP_ACCEPT_ENCODING = $_SERVER["HTTP_X_CEPT_ENCODING"];
            }
            if( headers_sent() ) 
                $encoding = false; 
            else if( strpos($HTTP_ACCEPT_ENCODING, 'x-gzip') !== false ) 
                $encoding = 'x-gzip'; 
            else if( strpos($HTTP_ACCEPT_ENCODING,'gzip') !== false ) 
                $encoding = 'gzip'; 
                
        }  */
        
        $contents = ob_get_clean();
        
        WiziappLog::getInstance()->write('info', "BTW the get params were:".print_r($_GET, TRUE), "WiziappRequestHandler._routeContent");
        if (isset($_GET['callback'])){
            WiziappLog::getInstance()->write('debug', "The callback GET param set:".$_GET["callback"] . "(" . $contents . ")", "WiziappRequestHandler._routeContent");
            // Support cross-domain ajax calls for webclients
            // @todo Add a check to verify this is a web client
            header('Content-Type: text/javascript; charset: utf-8');
            $contents = $_GET["callback"] . "({$contents})";  
        } else {
            WiziappLog::getInstance()->write('debug', "The callback GET param is not set", "WiziappRequestHandler._routeContent");
        }
        
        // Check if the content had changed according to the e-tag
        if (isset($GLOBALS['WiziappEtagOverride']) && !empty($GLOBALS['WiziappEtagOverride'])){
            $checksum = md5($GLOBALS['WiziappEtagOverride']);
        } else {
            $checksum = md5($contents);            
        }

        WiziappLog::getInstance()->write('info', "The checksum for the content is: {$checksum}", "WiziappRequestHandler._routeContent");
        
	    $checksum = '"' . $checksum.WiziappContentEvents::getCacheTimestampKey() . '"';
        header('ETag: ' . $checksum);    
        $shouldProcess = TRUE;              
        WiziappLog::getInstance()->write('info', "The if not matched header is: {$_SERVER['HTTP_IF_NONE_MATCH']}", "WiziappRequestHandler._routeContent");
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim(stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $checksum){
            WiziappLog::getInstance()->write('info', "It's a match!!!", "WiziappRequestHandler._routeContent");
            // No change, return 304
            //header ("HTTP/1.0 304 Not Modified"); 
            
            //$shouldProcess = FALSE;
            
            return FALSE;
        } else {
            // The headers do not match
            WiziappLog::getInstance()->write('info', "The headers do not match: " . trim(stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) .
                                          " and the etag was {$checksum}", "WiziappRequestHandler._routeContent");
        }
        //ob_end_clean();
        if ($shouldProcess){
            // Return the content
        //    if($encoding) 
          //  { 
                /**
                * Although gzip encoding is best handled by the zlib.output_compression 
                * Our clients sometimes send a different accpet encoding header like X-cpet-Encoding
                * in that case the only way to catch it is to manually handle the compression 
                * and headers check
                */
            /**    $len = strlen($contents); 
                header('Content-Encoding: '.$encoding); 
                echo "\x1f\x8b\x08\x00\x00\x00\x00\x00"; 
                $contents = gzcompress($contents, 9); 
                $contents = substr($contents, 0, $len); 
            } */
            echo $contents;
        } else {
			ob_end_clean();
		}     
        return TRUE;
    }

    public function runService($service_type, $service_method, $param=null){
        $serviceClassName = "Wiziapp{$service_type}Services";
        $serviceClass = new $serviceClassName();
        if ( is_callable(array($serviceClass, $service_method))){
            if ( $param == null ){
                $serviceClass->$service_method();
            } else {
                $serviceClass->$service_method($param);
            }
        }
    }

    public function runScreen($screen_class_name){
        $className = "Wiziapp{$screen_class_name}Screen";
        $screen = new $className();
        $screen->run();
    }

     public function runScreenBy($screen_class_name, $by_func_name, $param=null){
        $className = "Wiziapp{$screen_class_name}Screen";
         $funcName = "runBy{$by_func_name}";

        $screen = new $className();
        if ( $param == null ){
            $screen->$funcName();
        } else {
            $screen->$funcName($param);
        }
    }
}
