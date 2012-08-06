<?php
WiziappLog::getInstance()->write('INFO', 'Loaded index template', 'themes.default.index');
/**
* Get access to the globals
*/
global $wiziapp_block, $cPage, $nextPost, $prevPost, $postsScreen, $wiziappQuery;
/**
* Start wordpress loop, the condition for the loop was prepared in the screens functions
* but a minute before starting the loop get the header and footer to try and avoid problems with plugins
* that from some reason resets the query object or more it, or messing with the buffers
*/
WiziappContentHandler::getInstance()->registerPluginScripts();
WiziappLog::getInstance()->write('INFO', 'Registered scripts', 'themes.default.index');
// Force the head to run only once
if ( empty($GLOBALS['wpHeadHtml']) ){
    WiziappLog::getInstance()->write('INFO', 'Getting the template header', 'themes.default.index');
    ob_start();
    wp_head();
    $GLOBALS['wpHeadHtml'] = ob_get_clean();
}

if ( empty($GLOBALS['wpFooterHtml']) ){
    WiziappLog::getInstance()->write('INFO', 'Getting the template footer', 'themes.default.index');
    ob_start();
    wp_footer();
    $GLOBALS['wpFooterHtml'] = ob_get_clean();
}
wp_reset_query();
WiziappLog::getInstance()->write('INFO', 'Wordpress loop reset', 'themes.default.index');
query_posts($wiziappQuery);
WiziappLog::getInstance()->write('INFO', 'Queried the posts: '.$GLOBALS['wp_query']->post_count, 'themes.default.index');

$wpQueryObject = $GLOBALS['wp_query'];
if (have_posts()) :
    WiziappLog::getInstance()->write('INFO', 'We have posts to process', 'themes.default.index');
	$GLOBALS['WiziappOverrideScripts'] = TRUE;
	if (!isset($GLOBALS['WiziappEtagOverride'])){
		$GLOBALS['WiziappEtagOverride'] = '';
	}
	$injectLoadedScript = '<script type="text/javascript">WIZIAPP.doLoad();</script>';

	// Start capturing output from loop events
	ob_start();
	$GLOBALS['wp_query'] = $wpQueryObject;
	while (have_posts()) : the_post();
		// Save the Query object so no plugin can alter it...
		$wpQueryObject = $GLOBALS['wp_query'];

		$GLOBALS['WiziappEtagOverride'] .= serialize($post);
		WiziappLog::getInstance()->write('INFO', "The id: {$post->ID}", 'themes.default.index');

		if ( isset($GLOBALS['wp_posts_listed']) ){
			if ( in_array($post->ID, $GLOBALS['wp_posts_listed']) ){
				continue;
			} else {
				$GLOBALS['wp_posts_listed'][] = $post->ID;
			}
		}

		/**
		* In this template we are only doing posts list
		* for posts list we will to pre-load the post template so get the template
		* inside a string to pass it to the component building functions
		*/
		$contents = null;

		if ( WiziappConfig::getInstance()->usePostsPreloading() ){

			WiziappLog::getInstance()->write('INFO', 'Preloading the posts', 'themes.default.index');
			ob_start();
			$obLevelStart = ob_get_level();

			include('_content.php');

			$contents = ob_get_contents();
			// Inject the doLoad method to avoid timing issues when getting the post in this bundle
			$contents = str_replace('</body>', $injectLoadedScript.'</body>', $contents);
			$obLevelEnd = ob_get_level();
			if ( $obLevelEnd == $obLevelStart ){
				ob_end_clean();
			} else if ( $obLevelEnd > $obLevelStart ){
				// Someone opened a new output buffer cache that might mess up our loop, reset the buffer to what we need
				while ( $obLevelEnd > $obLevelStart ){
					ob_end_clean();
					--$obLevelEnd;
				}
			} else {
				// Someone closed it for us, just make sure it is cleaned
				ob_clean();
			}
		}
		$postsScreen->appendComponentByLayout($cPage, $wiziapp_block, $post->ID, $contents);

		// Reset the query back to what it should be
		$GLOBALS['wp_query'] = $wpQueryObject;
		//$wpCurrentPost = $wpQueryObject->current_post;
	endwhile;
	ob_end_clean(); // End capturing output from loop events
	// In case something in the template changed, add the modified date to the etag

	$GLOBALS['WiziappEtagOverride'] .= date("F d Y H:i:s.", filemtime(dirname(__FILE__).'/_content.php'));
	$GLOBALS['WiziappEtagOverride'] .= date("F d Y H:i:s.", filemtime(dirname(__FILE__).'/index.php'));
else :
	WiziappLog::getInstance()->write('ERROR', "No posts???", "themes.iphone.index");
endif;