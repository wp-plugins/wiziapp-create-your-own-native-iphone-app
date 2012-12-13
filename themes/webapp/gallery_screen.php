<?php
if ( $screen_content['screen']['update'] === TRUE ){
	echo $components;
} else {
	get_header();
?>

<div data-role="page" data-theme="z" data-post-processing="buildGallery">
	<div data-role="header" data-id="header" data-position="fixed" class="navigation">
<?php
	if ($back_content === false){
		$tabBar->getBackButton();
	} else {
		wiziapp_back_button($back_content['url'], $back_content['text']);
	}
?>
		<h1><?php echo $screen_content['screen']['title']; ?></h1>
		<?php // <div class="gallery_toggle ui-btn-right"></div> ?>
	</div><!-- /header -->

	<div data-role="content" data-scroll="true" class="gallery_screen_grid <?php echo $screen_content['screen']['class']; ?>" data-footer-id="tabbar">
		<div class="content-primary">
			<?php echo $components; ?>
		</div>
	</div><!-- /content -->

<?php echo $tabBar->getBar(); ?>
</div><!-- /page -->

<?php
	get_footer();
}