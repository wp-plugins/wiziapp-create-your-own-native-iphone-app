<?php
	get_header();
?>

<div data-role="page">
	<div data-role="header">
<?php
	if ($back_content === false){
		$tabBar->getBackButton();
	} else {
		wiziapp_back_button($back_content['url'], $back_content['text']);
	}
?>
		<h1><?php echo WiziappConfig::getInstance()->app_name; ?></h1>
	</div>

	<div data-role="content">
		<div class="page_content">
			<div class="post">
				<h2>
					<?php echo $screen_content['screen']['title']; ?>
				</h2>

				<?php echo $screen_content['screen']['items']['content']; ?>

				<div class="video_page_description">
					<?php echo $screen_content['screen']['items']['description']; ?>
				</div>
			</div>
		</div><!-- page_content -->
	</div><!-- /content -->
</div><!-- /page -->

<?php
	get_footer();