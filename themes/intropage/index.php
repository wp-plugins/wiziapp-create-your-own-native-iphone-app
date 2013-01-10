<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="UTF-8">
		<title><?php echo get_bloginfo('description', 'display'); ?></title>

		<meta name="viewport" content="width=320, initial-scale=1.0, user-scalable=0">
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="apple-mobile-web-app-status-bar-style" content="black" />

		<link rel="shortcut icon" 	 href="<?php echo $app_icon; ?>" />
		<link rel="apple-touch-icon" href="<?php echo $app_icon; ?>" />
		<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />

		<link rel="stylesheet" href="<?php echo $wiziapp_plugin_url; ?>/themes/intropage/style.css" type="text/css" />

		<?php
			$this->_get_javascript($wiziapp_plugin_url);
		?>

		<script type="text/javascript">
			window.store_url = '<?php echo $store_url; ?>';
			window.site_url  = '<?php echo site_url(); ?>';
		</script>
	</head>
	<body onload="wiziapp_intro_page_load();">
		<div class="title">
			<div>
				<img src="<?php echo $app_icon; ?>" alt="Application Icon">
			</div>

			<div>
				<?php echo WiziappHelpers::makeShortString(WiziappConfig::getInstance()->app_name, 20).PHP_EOL; ?>
			</div>
		</div>

		<div id="download_from_store" class="button <?php echo $button_image; ?>">Download from the <?php echo  $download_place; ?></div>

		<div>OR</div>

		<?php
		if ( $is_show_desktop ) {
			?>
			<div id="mobile_site" class="button mobile_site">Go to the mobile site</div>

			<div id="desktop_site">Desktop Site</div>
			<?php
		} else {
			?>
			<div id="mobile_site" class="button mobile_site">Continue to Website</div>
			<?php
		}
		?>
	</body>
</html>