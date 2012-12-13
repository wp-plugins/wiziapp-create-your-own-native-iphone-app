<?php
if ( $screen_content['screen']['update'] === TRUE ){
	echo $components;
} else {
	/*
	// Prepare the classes
	global $pageScripts;
	$pageScripts = array('wiziapp_favorites');
	*/

	$is_comments = ( $this->name === 'comments' );

	get_header();
	?>

	<div data-role="page" data-theme="c"<?php echo ( $is_comments && isset($screen_content['post_id']) ) ? ' class="comments_loaded_event"' : ''; ?>>
		<div data-role="header" data-id="header" data-position="fixed" class="navigation">
<?php
	if ($back_content === false){
		$tabBar->getBackButton();
	} else {
		wiziapp_back_button($back_content['url'], $back_content['text']);
	}
?>
			<h1><?php echo $screen_content['screen']['title']; ?></h1>

			<?php
				/*
				<a href="#login" class="login_nav_button ui-btn-right" data-alternate-text="<?php echo __('Done', 'wiziapp');?>" data-text="<?php echo __('Login', 'wiziapp');?>"><?php echo __('Login', 'wiziapp');?></a>
				*/

				if ( $is_comments ) {
				?>
				<div id="comment_reply_root"></div>
				<?php
				}
			?>
		</div><!-- /header -->

		<div data-role="content" data-scroll="true" class="<?php echo $screen_content['screen']['class']; ?>" data-footer-id="tabbar">
			<?php
			echo $components;

			if ( $is_comments && isset($screen_content['post_id']) ){
				$add_comment_form = WiziappCommentCellItem::get_comment_form( $screen_content['post_id'] );
				echo $add_comment_form;
			}
			?>
		</div><!-- /content -->

		<?php echo $tabBar->getBar(); ?>
	</div><!-- /page -->

	<?php
	get_footer();
}