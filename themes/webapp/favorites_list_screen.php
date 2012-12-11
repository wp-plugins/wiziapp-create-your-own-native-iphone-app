<?php
if ( $screen_content['screen']['update'] === TRUE ){
	echo $components;
} else {
	get_header();
?>

<div data-role="page" data-theme="z">
	<div data-role="header" data-id="header" data-position="fixed" class="navigation">
		<?php
			$tabBar->getBackButton();
		?>
		<h1><?php echo $screen_content['screen']['title']; ?></h1>
	</div><!-- /header -->

	<div data-role="content" data-scroll="true" class="<?php echo $screen_content['screen']['class']; ?>" data-footer-id="tabbar">
		<div class="content-primary">
			<?php echo $components; ?>
		</div>
	</div><!-- /content -->

<?php echo $tabBar->getBar(); ?>
</div><!-- /page -->
<?php get_footer(); ?>
<?php
}
