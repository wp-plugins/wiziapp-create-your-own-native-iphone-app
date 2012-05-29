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
	<style type="text/css">
		<?php
			$baseCssFileName = dirname(__FILE__) . '/style.css';
			$cssFileName = dirname(__FILE__) . '/' . WiziappConfig::getInstance()->wiziapp_theme_name . '.css';

			$baseFile = file_get_contents($baseCssFileName);
			$file = file_get_contents($cssFileName);
			$css = $baseFile . $file;

			// remove comments
			$css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
			// remove tabs, spaces, newlines, etc.
			$css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);
			// Get CDN Server address
			$cdnServer = WiziappConfig::getInstance()->getCdnServer();
			$css = str_replace("@@@WIZIAPP_CDN@@@", $cdnServer, $css);

			if ( isset($_GET['sim']) && isset($_GET['sim']) == 1 ) {
				$css .= ' body{ overflow-y: hidden; }';
			}

			echo $css;
		?>
	</style>
	<link id="themeCss" rel="stylesheet" href="https://<?php echo WiziappConfig::getInstance()->api_server . '/application/postViewCss/'.WiziappConfig::getInstance()->app_id.'?v=' . WIZIAPP_VERSION . '&c=' . (WiziappConfig::getInstance()->configured ? 1 : 0);  ?>" type="text/css" />
</head>
<body>