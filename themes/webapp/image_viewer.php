<div id="image_viewer_template">
	<div data-role="page" data-theme="z" class="image_viewer">
		<div data-role="header" data-id="header" data-position="fixed" class="navigation">
			<?php
				wiziapp_back_button('#');
			?>
			<h1></h1>
		</div><!-- /header -->

		<div data-role="content" class="content">
			<div class="all-wrapper">
				<div class="template">
					<div class="single-wrapper single-wrapper-displaced">
						<div class="direct-wrapper ui-loading">
							<img class="thumbnail" src="<?php echo WiziappHelpers::get_pixelSRC_attr(); ?>" border="0" />
							<div class="ui-loader ui-corner-all ui-body-a ui-loader-default" style="position: absolute">
								<span class="ui-icon ui-icon-loading"></span>
							</div>
						</div>
					</div>
				</div>
				<div class="single-wrapper single-wrapper-displaced">
					<div class="direct-wrapper">
						<img class="fullsize" src="<?php echo WiziappHelpers::get_pixelSRC_attr(); ?>" border="0" />
					</div>
				</div>
			</div>
		</div><!-- /content -->

		<div data-id="image-viewer-tabbar" data-role="footer" data-position="fixed" data-tap-toggle="false" class="nav-tabbar image_viewer_tabbar">
			<div data-role="navbar" class="nav-tabbar" data-grid="d">
				<ul>
					<li>
						<a href="#sharing_menu" class="image-viewer-share" data-icon="custom" data-rel="dialog" data-transition="pop"></a>
					</li>
					<li>
						<a href="#" class="image-viewer-back" data-icon="custom" data-transition="slide" data-direction="reverse"></a>
					</li>
					<li>
						<a href="#" class="image-viewer-play" data-icon="custom" data-transition="slide"></a>
					</li>
					<li>
						<a href="#" class="image-viewer-forward" data-icon="custom" data-transition="slide"></a>
					</li>
					<li>
						<a href="#" class="image-viewer-post" data-icon="custom" data-transition="slide" data-direction="reverse"></a>
					</li>
				</ul>
			</div><!-- /tabbar-->
		</div><!-- /footer -->
	</div><!-- /page -->
</div><!-- /template -->
