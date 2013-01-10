<!DOCTYPE HTML>
<html>
<head profile="http://gmpg.org/xfn/11">
	<?php
		// Disable the admin bar
		if ( function_exists("show_admin_bar") ) {
			show_admin_bar(false);
		}
	?>
	<base href="<?php bloginfo('url'); ?>/" />
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<title dir="ltr"><?php echo WiziappTheme::applyRequestTitle(wp_title('&laquo;', false, 'right').get_bloginfo('name')); ?></title>
	<meta name="viewport" content="width=device-width,user-scalable=no" />
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
	<?php
		if ( !empty($GLOBALS['wpHeadHtml']) ) {
			echo $GLOBALS['wpHeadHtml'];
		} else {
			WiziappContentHandler::getInstance()->registerPluginScripts();
			wp_head();
		}
	?>
	<script type="text/javascript">
		jQuery.mobile.autoInitializePage = false;
	</script>
        <?php $themeName = is_rtl() ? 'rtl' : WiziappConfig::getInstance()->wiziapp_theme_name; ?>
        
	<link rel="stylesheet" href="<?php echo get_bloginfo('template_url').'/style.css'; ?>" type="text/css" />
	<link rel="stylesheet" href="<?php echo get_bloginfo('template_url').'/' . $themeName . '.css'; ?>" type="text/css" />
	<link id="themeCss" rel="stylesheet" href="https://<?php echo WiziappConfig::getInstance()->api_server . '/application/postViewCss/'.WiziappConfig::getInstance()->app_id.'?v=' . WIZIAPP_VERSION . '&c=' . (WiziappConfig::getInstance()->configured ? 1 : 0);  ?>" type="text/css" />
</head>
<body>