<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<?php
// Disable the admin bar
if ( function_exists("show_admin_bar") ){
	show_admin_bar(false);
}
?>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">

	<base href="<?php echo WiziappContentHandler::getInstance()->get_blog_property('url'); ?>/">
	<title><?php echo wiziapp_get_webapp_title(); ?></title>

	<meta name="viewport" content="width=320.1, initial-scale=1.0, user-scalable=0">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">

	<link rel="shortcut icon" href="<?php echo wiziapp_get_app_icon(); ?>">
	<link rel="apple-touch-icon" href="<?php echo wiziapp_get_app_icon(); ?>">
	<link rel="apple-touch-startup-image" href="<?php echo wiziapp_get_splash(); ?>">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css">
	<link rel="stylesheet" href="<?php echo WiziappContentHandler::getInstance()->get_blog_property('data_files_url').'/resources/style_aux.css'; ?>" type="text/css">
	<link rel="stylesheet" href="<?php echo WiziappContentHandler::getInstance()->get_blog_property('data_files_url').'/resources/main.css'; ?>" type="text/css">
	<link rel="stylesheet" href="<?php echo get_bloginfo('template_url').'/jquery.mobile-1.1.1.css'; ?>" type="text/css">
	<link rel="stylesheet" href="https://<?php echo WiziappConfig::getInstance()->api_server.'/application/postViewCss/'.WiziappConfig::getInstance()->app_id.'?v='.WIZIAPP_VERSION.'&c='.(WiziappConfig::getInstance()->configured ? 1 : 0);  ?>" type="text/css" id="themeCss">
	<link rel="stylesheet" href="<?php echo dirname(get_bloginfo('template_url')).'/iphone/'.WiziappConfig::getInstance()->wiziapp_theme_name.'.css'; ?>" type="text/css">
	<link rel="stylesheet" href="<?php echo dirname(get_bloginfo('template_url')).'/iphone/style.css'; ?>" type="text/css">

	<script type="text/javascript" src="<?php echo WiziappContentHandler::getInstance()->get_blog_property('data_files_url').'/resources/handshake.js'; ?>"></script>
	<script type="text/javascript" src="<?php echo WiziappContentHandler::getInstance()->get_blog_property('data_files_url').'/resources/config.js'; ?>"></script>
	<script type="text/javascript">
		window.WiziappPlatformVersion = '<?php echo WIZIAPP_P_VERSION; ?>';
		window.WiziappAccessPoint = '<?php echo WiziappContentHandler::getInstance()->get_blog_property('url'); ?>';
		window.isSingle = false;
	</script>
	<!-- wp_head start -->
	<?php
		echo get_query_var('wiziapp_google_adsense_css');
		WiziappContentHandler::getInstance()->registerWebAppScripts();
		WiziappContentHandler::getInstance()->registerPluginScripts();
		/*
		global $pageScripts;
		if ( ! empty($pageScripts) ){
		for($s = 0, $total = count($pageScripts); $s < $total; ++$s){
		wp_enqueue_script($pageScripts[$s]);
		}
		}
		*/
		wp_head();

		$wiziapp_google_analytics = WiziappHelpers::get_analytics();
		if ( $wiziapp_google_analytics['is_shown'] ) {
			echo $wiziapp_google_analytics['code'];
		}
	?>
	<!-- wp_head end -->
</head>
<body <?php body_class(); ?>>