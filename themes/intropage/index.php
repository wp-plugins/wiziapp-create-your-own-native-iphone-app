<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="UTF-8">
		<title><?php echo get_bloginfo('description', 'display'); ?></title>

		<meta name="viewport" content="width=320.1, initial-scale=1.0, user-scalable=0">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black">

		<link rel="shortcut icon" 	 href="<?php echo $app_icon; ?>">
		<link rel="apple-touch-icon" href="<?php echo $app_icon; ?>">
		<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

		<link rel="stylesheet" href="<?php echo $wiziapp_plugin_url; ?>/themes/intropage/style.css" type="text/css">

		<script type="text/javascript" src="<?php echo $wiziapp_plugin_url.'/themes/intropage/intro_page.js'; ?>"></script>
		<script type="text/javascript">
			var intro_page_parameters = intro_page_parameters || {};

			intro_page_parameters.store_url = '<?php echo $store_url; ?>';
			intro_page_parameters.site_url = '<?php echo $site_url; ?>';
			intro_page_parameters.desktop_site_url = '<?php echo $desktop_site_url; ?>';

			intro_page_parameters.app_id = '<?php echo WiziappConfig::getInstance()->app_id; ?>';
			intro_page_parameters.playstore_condition = '<?php echo intval($playstore_condition); ?>';
			intro_page_parameters.delay_period = '<?php echo $delay_period; ?>';
			intro_page_parameters.analytics_account = '<?php echo WiziappConfig::getInstance()->analytics_account; ?>';
		</script>

		<?php
		if ($is_android_device) {
			?>
			<script type="text/javascript">
				var _gaq = _gaq || [];
				_gaq.push(
					['_setAccount', intro_page_parameters.analytics_account],
					['_trackEvent', "AndroidIntroScreen", "AndroidIntroDisplayed", intro_page_parameters.app_id]
				);

				(function() {
					var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
					ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
					var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
				})();
			</script>
			<?php
		}
		?>
	</head>
	<body>
		<div id="title">
			<div>
				<img src="<?php echo $app_icon; ?>" alt="Application Icon">
			</div>

			<div>
				<?php echo WiziappConfig::getInstance()->app_name.PHP_EOL; ?>
			</div>
		</div>

		<div id="android_app_download">
			<div class="first_child">
				<p>Allow "<?php echo WiziappConfig::getInstance()->app_name; ?>" direct installation</p>
				<?php echo $download_note; ?>
			</div>
			<div class="second_child">
				<p>Complete installation</p>
				Please pull-down your notification center and click on "app<?php echo WiziappConfig::getInstance()->app_id; ?>.apk"
			</div>
			<div class="third_child">
				<p>app<?php echo WiziappConfig::getInstance()->app_id; ?>.apk</p>
				<p>Download complete</p>
			</div>
			<div class="fourth_child">
				If for any reason the download doesn't complete, click <a href="<?php echo $store_url; ?>">here</a> to retry
			</div>
		</div>

		<p id="download_button_title">
			<?php echo  $download_text; ?>
		</p>

		<div id="download_from_store">
			<img src="<?php echo $wiziapp_plugin_url; ?>/themes/intropage/left_arrow.png" alt="Left Arrow"   class="intro_page_arrow">
			<img src="<?php echo $wiziapp_plugin_url; ?>/themes/intropage/<?php echo $button_image; ?>" alt="Application Store">
			<img src="<?php echo $wiziapp_plugin_url; ?>/themes/intropage/right_arrow.png" alt="Right Arrow" class="intro_page_arrow">
		</div>

		<?php
		if ( $is_update ) {
			?>
			<div>
				<div id="continue_to" class="mobile_site_button">Not Now</div>
			</div>
			<?php
		} elseif ( $is_show_desktop ) {
			?>
			<div>
				No thanks. Continue to:

				<div id="continue_to" class="mobile_site_button">Mobile Site</div>
			</div>

			<div id="desktop_site">Desktop Site</div>
			<?php
		} else {
			?>
			<div>
				No thanks. Continue to:
				<div id="continue_to" class="mobile_site_button">Website</div>
			</div>
			<?php
		}
		?>

		<div>
			<input type="checkbox" id="remember" name="remember" value="1">
			Remember my choice
		</div>
	</body>
</html>