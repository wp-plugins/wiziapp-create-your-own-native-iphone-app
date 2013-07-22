<?php
	global $tabBar;
	if ( ! isset($tabBar) ){
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
			<a href="" data-role="button" target="_blank">Google+</a>
		</p>
		<p>
			<a href="#" data-rel="back" data-theme="e" data-role="button">Cancel</a>
		</p>
	</div><!-- /content -->
</div><!-- /page -->

<?php
	if ( WiziappHelpers::check_open_x_condition() ) {
		?>
		<a href="#wiziapp_openxad_body" id="wiziapp_openxad_open" data-transition="slide" data-rel="dialog"></a>
		<div id="wiziapp_openxad_body" data-role="dialog">
			<div data-role="content" data-theme="c">
				<!--	<div style="height: 480px; width: 320px; background-color: blue; margin: 0 auto;"></div>	-->
				<script type='text/javascript'>
					<!--//<![CDATA[
					var m3_u = (location.protocol=='https:'?'https://50.56.70.210/openx/www/delivery/ajs.php':'http://50.56.70.210/openx/www/delivery/ajs.php');
					var m3_r = Math.floor(Math.random()*99999999999);
					if (!document.MAX_used) document.MAX_used = ',';
					document.write ("<scr"+"ipt type='text/javascript' src='"+m3_u);
					document.write ("?zoneid=255");
					document.write ('&amp;cb=' + m3_r);
					if (document.MAX_used != ',') document.write ("&amp;exclude=" + document.MAX_used);
					document.write (document.charset ? '&amp;charset='+document.charset : (document.characterSet ? '&amp;charset='+document.characterSet : ''));
					document.write ("&amp;loc=" + escape(window.location));
					if (document.referrer) document.write ("&amp;referer=" + escape(document.referrer));
					if (document.context) document.write ("&context=" + escape(document.context));
					if (document.mmm_fo) document.write ("&amp;mmm_fo=1");
					document.write ("'><\/scr"+"ipt>");
					//]]>-->
				</script>
				<noscript>
					<a href='http://50.56.70.210/openx/www/delivery/ck.php?n=ac139345&amp;cb=INSERT_RANDOM_NUMBER_HERE' target='_blank'>
						<img src='http://50.56.70.210/openx/www/delivery/avw.php?zoneid=255&amp;cb=INSERT_RANDOM_NUMBER_HERE&amp;n=ac139345' border='0' alt='' />
					</a>
				</noscript>
			</div><!-- /content -->
			<div data-role="footer" data-theme="d">
				<a id="wiziapp_openxad_close" href="#" data-role="button" data-rel="back" data-theme="c">Skip</a>
			</div><!-- /header -->
		</div><!-- /page -->
		<?php
	}

	require(dirname(__FILE__).'/image_viewer.php')
?>

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