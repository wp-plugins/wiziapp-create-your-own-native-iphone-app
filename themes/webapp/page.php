<?php
if ( have_posts() ) {
	ob_start();
	global $post, $tabBar;
	setup_postdata($post);
	$tabBar = new WiziappTabbarBuilder();

	WiziappTheme::getPostHeaders(true);
	ob_end_clean();	// Do NOT write anything before the template header

	// Before handing the content, make sure this post is scanned
	$processed = get_post_meta($post->ID, 'wiziapp_processed');
	if (empty($processed)) {
		$ce = new WiziappContentEvents();
		$ce->savePost($post);
	}

	get_header();
?>

<div data-role="page" data-theme="z">
	<div data-role="header" data-id="header" data-position="fixed">
<?php
		if ($tabBar->getTabFromURL() !== false) {
			$tabBar->getBackButton();
		}
		else if ($post->post_parent){
			$parent_post = get_post($post->post_parent);
			wiziapp_back_button(WiziappLinks::pageLink($parent_post->ID), $parent_post->post_title);
		}
		else{
			$pages_screen = new WiziappPagesScreen();
			wiziapp_back_button('nav://list/' . urlencode(WiziappContentHandler::getInstance()->get_blog_property('url') . '/?wiziapp/content/list/pages'), $pages_screen->getTitle());
		}
?>
		<h1><?php echo WiziappConfig::getInstance()->app_name; ?></h1>
	</div><!-- /header -->

	<div data-role="content">
		<div class="page_content">
			<div class="post">
				<?php
					$pLink = WiziappLinks::postLink($post->ID);

					if ( is_page($post->ID) ) {
						$config = WiziappComponentsConfiguration::getInstance();

						if ( in_array( 'pages', $config->getAttrToAdd('postDescriptionCellItem') ) ) {
							$subPages = get_pages(array(
								'child_of' => $post->ID,
								'sort_column' => 'menu_order',
							));

							if ($subPages) {
							?>
							<div>
								<ul class="wiziapp_bottom_nav wiziapp_pages_nav albums_list">
									<?php
										foreach ($subPages as $subPage) {
										?>
										<li>
											<a href="<?php echo WiziappLinks::pageLink($subPage->ID); ?>">
												<div class="wiziapp_pages_item">
													<p class="attribute text_attribute title wiziapp_pages_title"><?php echo ($subPage->post_title); ?></p>
													<span class="rowCellIndicator"></span>
												</div>
											</a>
										</li>
										<?php
										}
									?>
								</ul>
							</div>
							<?php
							}
						}
					}
				?>

				<h2 class="pageitem">
					<a id="post_title" href="<?php echo $pLink ?>" rel="bookmark" title="<?php the_title(); ?>">
						<?php the_title(); ?>
					</a>
				</h2>

				<div class="pageitem">
					<div class="post" id="post-<?php the_ID(); ?>">
						<div id="singlentry">
							<?php
								WiziappProfiler::getInstance()->write('Before the thumb inside the post ' . $post->ID, 'theme._content');

								@set_time_limit(60);
								WiziappThumbnailHandler::getPostThumbnail($post, 'posts_thumb');

								WiziappProfiler::getInstance()->write('after the thumb inside the post ' . $post->ID, 'theme._content');
								WiziappProfiler::getInstance()->write('Before the content inside the post ' . $post->ID, 'theme._content');

								global $more;
								$more = -1;

								the_content('');

								WiziappProfiler::getInstance()->write('After the content inside the post ' . $post->ID, 'theme._content');
							?>
						</div>
					</div>
				</div>
				<?php
				if ( ! is_page() ) {
			?>
					<div class="clear"></div>
					<ul class="wiziapp_bottom_nav">
						<?php
							WiziappTheme::getCategoriesNav();
							WiziappTheme::getTagsNav();
						?>
					</ul>
					<div class="clear"></div>
			<?php
		}
?>
			</div>
			<br />
<?php /*
			<div id="debug" style="background-color: #c0c0c0;">
				####AREA 51####
				<div id="swipeme" style="height: 50px; background-color: #ccc;">
					PLACE HOLDER
				</div>
				<a id="reload" href="#" onclick="top.location.reload(true)">RELOAD</a><br />
				<a id="swipeLeft" href="cmd://event/swipeRight"></a>
				<a id="swipeRight" href="cmd://event/swipeLeft"></a>
			</div>
*/ ?>
			<!-- The link below is for handing video in the simulator, the application shows the video itself while the simulator only shows an image. -->
			<a href="cmd://open/video" id="dummy_video_opener"></a>

<?php
		if ( WiziappConfig::getInstance()->is_paid !== '1' ){
			echo WiziappConfig::getInstance()->getWiziappBranding();
		}
?>
		</div><!-- page_content -->

		<script type="text/javascript">
			<?php
				/**
				* This class handle all the webview events and provides an external interface for the application
				* and the simulator. The simulator is getting some special treatment to help capture links and such
				*/
			?>

			window.galleryPrefix = "<?php echo WiziappLinks::postImagesGalleryLink($post->ID); ?>%2F";
			window.wiziappDebug = <?php echo (isset(WiziappConfig::getInstance()->wiziapp_log_threshold) && intval(WiziappConfig::getInstance()->wiziapp_log_threshold) !== 0) ? "true" : "false"; ?>;
			window.wiziappPostHeaders = <?php echo json_encode(WiziappTheme::getPostHeaders(FALSE)); ?>;
			window.wiziappRatingUrl = '<?php echo WiziappContentHandler::getInstance()->get_blog_property('url'); ?>/?wiziapp/getrate/post/<?php echo $post->ID ?>';
			window.wiziappCommentsCountUrl = '<?php echo WiziappContentHandler::getInstance()->get_blog_property('url'); ?>/?wiziapp/post/<?php echo $post->ID?>/comments';
			window.multiImageWidthLimit = "<?php echo WiziappConfig::getInstance()->multi_image_width; ?>";
			window.multiImageHeightLimit = "<?php echo WiziappConfig::getInstance()->multi_image_height; ?>";
			window.simMode = <?php echo (isset($_GET['sim']) && $_GET['sim']) ? 'true' : 'false'; ?>;
			window.wiziappCdn = "<?php echo WiziappConfig::getInstance()->getCdnServer(); ?>";
		</script>
	</div><!-- Content -->

	<?php echo $tabBar->getBar(); ?>
</div><!-- page -->

<?php
	get_footer();
} else {
	// No content???
	get_header();
	get_footer();
}