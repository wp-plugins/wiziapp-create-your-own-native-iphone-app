<?php
if ( $screen_content['screen']['update'] === TRUE ){
	echo $components;
} else {
	$is_comments = ( $this->name === 'comments' );

	get_header();

	$loaded_event_trigger = '';
	if ( $is_comments && isset($screen_content['post_id']) ){
		$loaded_event_trigger = ' class="comments_loaded_event"';
	} elseif ( isset($this->type) && $this->type === 'list' && isset($this->name) && $this->name === 'posts' ){
		$loaded_event_trigger = ' class="recent_loaded_event"';
	}
	?>

	<div data-role="page" data-theme="c"<?php echo $loaded_event_trigger; ?>>
		<div data-role="header" data-id="header" data-position="fixed" class="navigation">
			<?php
				if ($back_content === false || $tabBar->getTabFromURL() !== false){
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
					<a data-role="button" data-corners="false" data-theme="z" href="javascript:void(0)" class="navigation_back_button_wrapper ui-btn-right" id="webapp_send_comments_form">
						<span class="navigation_back_button_opener"></span>
						<span class="navigation_back_button">Post</span>
						<span class="navigation_back_button_closer"></span>
					</a>
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