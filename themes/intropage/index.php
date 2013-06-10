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
			window.intro_page_parameters = {
				store_url: '<?php echo $store_url; ?>',
				delay_period: '<?php echo $delay_period; ?>',
				site_url: '<?php echo $site_url; ?>',
				desktop_site_url: '<?php echo $desktop_site_url; ?>'
			};
		</script>
	</head>
	<body onload="wiziapp_intro_page_load();">
		<div id="title">
			<div>
				<img src="<?php echo $app_icon; ?>" alt="Application Icon">
			</div>

			<div>
				<?php echo WiziappHelpers::makeShortString(WiziappConfig::getInstance()->app_name, 20).PHP_EOL; ?>
			</div>
		</div>

		<div id="arrow_up"></div>

		<p id="intro_page_postclick" class="display_none">
			Please pull-down your notification center to complete the installation
		</p>

		<p id="download_button_title">
			<?php echo  $download_text; ?>
		</p>

		<div id="download_from_store">
			<img src="<?php echo $wiziapp_plugin_url; ?>/themes/intropage/left_arrow.png" alt="Left Arrow"   class="intro_page_arrow">
			<img src="<?php echo $wiziapp_plugin_url; ?>/themes/intropage/<?php echo $button_image; ?>" alt="Application Store">
			<img src="<?php echo $wiziapp_plugin_url; ?>/themes/intropage/right_arrow.png" alt="Right Arrow" class="intro_page_arrow">
		</div>

		<p>
			<?php
			if ( $is_update ) {
				?>
				<span id="no_thanks_notation"></span>
				<?php
			} else {
				?>
				<span id="no_thanks_notation">No thanks.</span> Continue to:
				<?php
			}
			?>
		</p>

		<?php
		if ( $is_update ) {
			?>
			<div id="mobile_site">Not Now</div>
			<?php
		} elseif ( $is_show_desktop ) {
			?>
			<div id="mobile_site">Mobile Site</div>

			<div id="desktop_site">Desktop Site</div>
			<?php
		} else {
			?>
			<div id="mobile_site">Website</div>
			<?php
		}
		?>
	</body>
</html>