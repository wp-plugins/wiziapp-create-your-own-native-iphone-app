<?php
	global $tabBar;
	if (!isset($tabBar))
	{
		$tabBar = new WiziappTabbarBuilder();
	}
?>
<div data-role="page" id="moreScreen" data-theme="z">
	<div data-role="header" data-id="header" data-position="fixed">
		<h1><?php echo __('More', 'wiziapp'); ?></h1>
	</div><!-- /header -->

	<div data-role="content" class="screen" data-footer-id="tabbar">
		<?php echo $tabBar->getMorePage(); ?>
	</div><!-- /content -->

	<?php echo $tabBar->getBar('moreScreen'); ?>
</div><!-- /page -->

<div data-role="page" id="favorites" class="got_empty_state" data-class="favorites_screen" data-theme="z">
	<div data-role="header" data-id="header" data-position="fixed">
		<?php
			$tabBar->getBackButton('favorites');
		?>
		<h1><?php echo 'Favorites'; ?></h1>
		<div class="edit_favorites ui-btn-right" data-alternate-text="<?php echo __('Done', 'wiziapp');?>" data-text="<?php echo __('Edit', 'wiziapp');?>"><?php echo __('Edit', 'wiziapp');?></div>
	</div><!-- /header -->

	<div data-role="content" class="screen flat_screen" data-footer-id="tabbar"></div>

<?php echo $tabBar->getBar('favorites'); ?>
</div><!-- /page -->

<div data-role="page" id="login" class="system_page" data-class="login_screen" data-theme="z">
	<div data-role="header" data-id="header" data-position="fixed">
		<div class="cancel_screen ui-btn-left" data-alternate-text="<?php echo __('Cancel', 'wiziapp');?>" data-text="<?php echo __('Edit', 'wiziapp');?>"><?php echo __('Cancel', 'wiziapp');?></div>
		<h1><?php echo __('Login', 'wiziapp'); ?></h1>
	</div><!-- /header -->

	<div data-role="content" class="screen flat_screen" data-footer-id="tabbar">
		<form method="post" action="#">
			<div class="fields">
				<div class="clear"></div>
				<input data-role="none" type="text" name="username" id="username" value="" placeholder="Username"/>

				<label for="password" class="">Password</label>
				<input type="text" name="password" id="password" value="" placeholder="Password"/>
			</div>

			<a href="#" data-role="button" data-shadow="false" data-corners="false" data-inline="true" class="button login_button"><?php echo __('Login', 'wiziapp'); ?></a>
			<a href="#" data-role="button" data-shadow="false" data-corners="false" data-inline="true" data-theme="z" class="button cancel_button"><?php echo __('Cancel', 'wiziapp');?></a>
			<br />
			<a href="#" data-role="button" data-shadow="false" data-corners="false" data-inline="true" class="transparent_button forgot_password_button"><?php echo __('Forgot Password?', 'wiziapp'); ?></a>
			<a href="#" data-role="button" data-shadow="false" data-corners="false" data-inline="true" data-theme="z" class="transparent_button register_button"><?php echo __('Register', 'wiziapp');?></a>
		</form>
	</div><!-- /content -->
</div><!-- /page -->

<div data-role="dialog" id="sharing_menu">
	<div data-role="content">
		<p>
			<a href="" data-role="button" target="_blank">Facebook</a>
		</p>
		<p>
			<a href="" data-role="button" target="_blank">Twitter</a>
		</p>
		<p>
			<a href="" data-role="button">Email</a>
		</p>
		<p>
			<a href="#" data-rel="back" data-theme="e" data-role="button">Cancel</a>
		</p>
		<?php
			/*
			$file = dirname(__FILE__).'/resources/config.js';
			$config = file_get_contents($file);
			$config = str_replace('var config = ', '', substr($config, 0, strlen($config)-1));
			$configObj = json_decode($config);
			$sharingServices = $configObj->sharing;
			$providerNames = array();

			foreach ( $sharingServices as $provider => $enabled ){
			if ( ! ( is_bool($enabled) && $enabled ) ) {
			continue;
			}

			$providerName = $provider;
			if ( ( $pos = strpos($providerName, '_') ) !== FALSE ){
			$providerName = substr($providerName, 0, $pos);
			}

			$providerNames[] = $providerName;
			}

			$providerNames = array_unique($providerNames);
			sort($providerNames);

			if ( ( $amount = count($providerNames) ) > 0 ){
			for ($i = 0, $amount; $i < $amount; $i++){
			?>
			<a class="sharing_provider <?php echo $enabled ? 'enabled' : 'hidden'; ?>" data-provider="<?php echo $providerNames[$i]; ?>" data-transition="slidedown" data-rel="dialog" data-role="button">
			<?php echo $providerNames[$i]; ?>
			</a>
			<?php
			}
			}
			*/
		?>
	</div><!-- /content -->
</div><!-- /page -->

<?php require(dirname(__FILE__).'/image_viewer.php') ?>

<div id="dialogs" class="hidden"></div>
<div id="sandbox" class="hidden"></div>

<?php wp_footer(); ?>

<div class="hidden">
	<a href="#" id="webview_links_handler"></a>
</div>

<style type="text/css">
	<?php echo $tabBar->getCss().PHP_EOL; ?>
</style>
</body>
</html>