<?php

global $tabBar;
$tabBar = new WiziappTabbarBuilder();

get_header();
?>

<div data-role="page" class="index" data-fullscreen="true" data-theme="z">
	<?php
	if ( ! ( isset($_GET['androidapp']) && $_GET['androidapp'] === '1' ) ) {
		?>
			<div id="splash">
				<img src="<?php echo wiziapp_get_splash(); ?>" />
			</div>
		<?php
	}
	?>

	<a style="display: none" href="<?php echo esc_attr($tabBar->getDefaultTab()); ?>"></a>
</div><!-- /page -->

<?php
get_footer();