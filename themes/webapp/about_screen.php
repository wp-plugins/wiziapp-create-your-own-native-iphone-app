<?php
	get_header();
?>

<div data-role="page">
	<div data-role="header" data-id="header" data-position="fixed" class="navigation">
		<?php
			$tabBar->getBackButton();
		?>
		<h1><?php echo $screen_content['screen']['title']; ?></h1>
	</div><!-- /header -->

	<div data-role="content" data-scroll="true" class="<?php echo $screen_content['screen']['class']; ?>" data-footer-id="tabbar">
		<div class="info_container" data-auto-height-calc="true" data-height-by=".aboutContent">
			<div class="<?php echo $screen_content['screen']['class']; ?>_title title attribute"><?php echo $screen_content['screen']['items']['title']; ?></div>
			<div class="<?php echo $screen_content['screen']['class']; ?>_version version attribute"><?php echo $screen_content['screen']['items']['version']; ?></div>
			<img src="<?php echo $screen_content['screen']['items']['imageURL']; ?>" class="<?php echo $screen_content['screen']['class']; ?>_image image attribute" />
			<div class="<?php echo $screen_content['screen']['class']; ?>_aboutTitle aboutTitle attribute"><?php echo $screen_content['screen']['items']['aboutTitle']; ?></div>
			<div class="<?php echo $screen_content['screen']['class']; ?>_aboutContent aboutContent attribute"><?php echo $screen_content['screen']['items']['aboutContent']; ?></div>
		</div>
	</div><!-- /content -->
<?php echo $tabBar->getBar(); ?>
</div><!-- /page -->

<?php
	get_footer();