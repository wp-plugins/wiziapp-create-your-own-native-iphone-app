<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="UTF-8">
		<title><?php echo wiziapp_get_webapp_title(); ?></title>

		<meta name="viewport" content="width=320, initial-scale=1.0, user-scalable=0">
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="apple-mobile-web-app-status-bar-style" content="black" />

		<link rel="shortcut icon" href="<?php echo wiziapp_get_app_icon(); ?>" />
		<link rel="apple-touch-icon" href="<?php echo wiziapp_get_app_icon(); ?>" />
		<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />

		<link rel="stylesheet" href="<?php echo get_bloginfo('template_url'); ?>/downloadapp.css" type="text/css" />

		<?php
			$vars_array = WiziappDownloadAppScreen::get_template_vars();
		?>

		<script type="text/javascript">
			window.store_url = '<?php echo $vars_array['store_url']; ?>';
			window.site_url = '<?php echo site_url(); ?>';
		</script>
	</head>
	<body>
		<div class="title">
			<div>
				<img src="<?php echo wiziapp_get_app_icon(); ?>" alt="">
			</div>

			<div>
				<?php echo WiziappHelpers::makeShortString(WiziappConfig::getInstance()->app_name, 20); ?>
			</div>
		</div>

		<div id="download_from_store" class="button <?php echo $vars_array['button_image']; ?>">Download from the <?php echo $vars_array['download_place']; ?></div>

		<div>OR</div>


		<div id="mobile_site" class="button mobile_site">Go to the mobile site</div>

		<div id="desktop_site">Desktop Site</div>
	</body>
</html>