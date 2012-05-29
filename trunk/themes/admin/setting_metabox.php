<div>
	<p>Display post on:</p>
	<p>
		iPhone App
		<input type="checkbox" name="wizi_not_exclude_app"<?php echo $checked_array['wizi_included_app']; ?> />
	</p>
	<p>
		Web Site
		<input type="checkbox" name="wizi_not_exclude_site"<?php echo $checked_array['wizi_included_site']; ?> />
	</p>
</div>
<div>
	<input type="checkbox" name="is_featured_post"<?php echo $checked_array['wiziapp_featured_post']; ?> />
	Set as featured Post
	<input type="hidden" name="wiziapp_setting_metabox" value="1" />
</div>
<div class="wiziapp_push_notification">
	<p>Send Post Push Notification:</p>
	<div>
		<p>
			Publish
			<input type="checkbox" name="wizi_published_push"<?php echo $checked_array['wizi_published_push']; ?> />
		</p>
		<p>
			Update
			<input type="checkbox" name="wizi_updated_push"<?php echo $checked_array['wizi_updated_push']; ?> />
		</p>
	</div>
	<div id="wiziapp_push_message_edit">
		<img src="<?php echo $this->_plugin_dir_url; ?>/themes/admin/edit.png" />
		Edit message
	</div>
	<div style="clear: both;"></div>
	<div id="wiziapp_push_message_insert">
		<h3>Push Notification Alert message</h3>
		<textarea id="wiziapp_push_message" data-push_message="<?php echo $push_message; ?>"></textarea>
		<div id="wiziapp_push_save_result" data-ajax_loader="<?php echo $this->_plugin_dir_url; ?>/themes/admin/ajax_loader.gif"></div>
		<div id="wiziapp_push_message_send" data-post_id="<?php echo $post->ID; ?>">Save</div>
		<div style="clear: both;"></div>
	</div>
</div>
<div class="wiziapp_post_thumbnail">
	<p>Post Thumbnail</p>
	<div id="wiziapp_post_thumbnail_preview"<?php echo $thumbnail_div_style; ?>>
		<?php echo $thumbnail_image; ?>
	</div>
	<div class="wiziapp_manage_thumbnail">
		<p id="wiziapp_upload_thumbnail">
			<img src="<?php echo $this->_plugin_dir_url; ?>/themes/admin/upload.png" />
			Upload new Thumbnail
		</p>
		<p>
			<input type="checkbox" id="wiziapp_is_user_chosen" name="wiziapp_is_user_chosen"<?php echo $checked_array['wiziapp_is_user_chosen']; ?> />
			<span>Use Uploaded Thumbnail</span>
		</p>
		<p>
			<input type="checkbox" id="wiziapp_is_no_thumbnail" name="wiziapp_is_no_thumbnail"<?php echo $checked_array['wiziapp_is_no_thumbnail']; ?> />
			No thumbnail
		</p>
	</div>
	<div style="clear: both;"></div>
</div>