<?php
	get_header();
?>
<div data-role="page">
	<div data-role="header" data-id="header" data-position="fixed" class="navigation">
		<?php
			wiziapp_back_button();
		?>
		<h1><?php echo WiziappConfig::getInstance()->app_name; ?></h1>
	</div><!-- /header -->

	<div data-role="content">
		<iframe class="content_iframe screen_content" frameborder="0" src="<?php echo esc_html($screen_content['screen']['items']['link']); ?>" style="width: 100%; height: 100%; max-width: 100%; max-height: 100%"></iframe>
	</div><!-- /content -->

	<div data-id="external-tabbar" data-role="footer" data-position="fixed" data-tap-toggle="false" class="nav-tabbar">
		<div data-role="navbar" class="nav-postbar" data-grid="d">
			<ul class="webview_bar header navigation">
				<li class="button_wrapper share_button_wrapper">
					<a href="#sharing_menu" class="browser_button_send" data-inline="true" data-rel="dialog" data-transition="slideup"></a>
				</li>

				<li class="browser_button_container browser_button_disabled">
					<div class="browser_button_back browser_button_back_disabled"></div>
					<span class="title">Back</span>
				</li>
				<li class="browser_button_container browser_button_safari_container">
					<span class="browser_button_safari"></span>
					<span class="title">Safari</span>
				</li>
				<li class="browser_button_container browser_button_disabled">
					<div class="browser_button_forward browser_button_forward_disabled"></div>
					<span class="title">Forward</span>
				</li>
				<li class="browser_button_container browser_button_refresh_container">
					<div class="browser_button_refresh"></div>
					<span class="title">Refresh</span>
				</li>
			</ul>
		</div><!-- /navbar-->
	</div><!-- /footer -->
</div><!-- /page -->
<?php
	get_footer();
