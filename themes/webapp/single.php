<?php
if ( have_posts() ) {
	// ob_start();
	global $post, $tabBar;
	setup_postdata($post);
	$tabBar = new WiziappTabbarBuilder();

	$wiziapp_google_adsense = WiziappHelpers::get_adsense();
	set_query_var('wiziapp_google_adsense_css', $wiziapp_google_adsense['css']);

	// Before handing the content, make sure this post is scanned
	$processed = get_post_meta($post->ID, 'wiziapp_processed');
	if (empty($processed)) {
		$ce = new WiziappContentEvents();
		$ce->savePost($post);
	}

	get_header();
?>

<div data-role="page" data-theme="z" class="post_loaded_event">
	<?php $tabBar->post_header_bar($post); ?>

	<div data-role="content">
		<div class="page_content<?php echo $wiziapp_google_adsense['is_shown'] ? ' wiziapp_google_adsenes' : ''; ?>">
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
											<a href="<?php echo WiziappLinks::pageLink($subPage->ID); ?>" data-transition="slide">
												<div class="album_item wiziapp_pages_item">
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
					<div class="single-post-meta-top">
						<div id="author_and_date">
							<span class="postDescriptionCellItem_author">By
								<a href="<?php echo WiziappLinks::authorLink($post->post_author); ?>" data-transition="slide"><?php the_author(); ?></a>
							</span>
							&nbsp;
							<span class="postDescriptionCellItem_date"><?php echo WiziappTheme::formatDate($post->post_date); ?></span>
						</div>
					</div>
					<div class="clear"></div>

					<?php
				if ( $wiziapp_google_adsense['show_in_post'] & $wiziapp_google_adsense['upper_mask'] ) {
					echo $wiziapp_google_adsense['code'];
				}
			?>

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

		if ( $wiziapp_google_adsense['show_in_post'] & $wiziapp_google_adsense['lower_mask'] ) {
			echo $wiziapp_google_adsense['code'];
		}
					?>
			</div>
			<br />
			<div id="debug" style="background-color: #c0c0c0;">
				####AREA 51####
				<div id="swipeme" style="height: 50px; background-color: #ccc;">
					PLACE HOLDER
				</div>
				<a id="reload" href="#" onclick="top.location.reload(true)">RELOAD</a><br />
				<a id="swipeLeft" href="cmd://event/swipeRight"></a>
				<a id="swipeRight" href="cmd://event/swipeLeft"></a>
			</div>
			<!-- The link below is for handing video in the simulator, the application shows the video itself while the simulator only shows an image. -->
			<a href="cmd://open/video" id="dummy_video_opener"></a>

<?php
		if ( WiziappConfig::getInstance()->is_paid !== '1' ){
?>
			<div style="text-align: center; font-size: 12px;">WordPress mobile theme by WiziApp</div>
<?php
		}
?>
		</div><!-- page_content -->
	</div><!-- Content -->

	<?php $tabBar->post_footer_bar($post); ?>
</div><!-- page -->

<?php
	get_footer();
} else {
	// No content???
	get_header();
	get_footer();
}