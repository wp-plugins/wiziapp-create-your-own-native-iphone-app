(function($) {

		$(document).bind("ready wiziapp_upload_finished", function() {
				$("<span>Use as Post Thumbnail</apan>")
				.insertBefore('a[id^="wp-post-thumbnail-"]')
				.css({
						"cursor": "pointer",
						"color": "#0000AA",
						"text-decoration": "underline",
						"display": "inline-block",
						"width": "142px",
						"text-align": "center",
				})
				.click(function() {
						var id = $(this)
						.siblings('a[id^="wp-post-thumbnail-"]')
						.attr("id");
						var pattern = /^wp-post-thumbnail-(\d+)$/;
						var result = pattern.exec(id);
						var id = result[1];

						image_element = $("#thumbnail-head-" + id + " p a img");
						image_element.attr("width", 85);

						use_post_thumbnail(this, id);
				});

				$('a[id^="wp-post-thumbnail-"]').css("margin", "0");
				$("#media-items a").css("color", "#0000AA");
		});

		var element;
		var image_element;

		function use_post_thumbnail(html, id) {
			element = $(html);

			element
			.html('<img src="' + wiziapp_ajax_loader_source + '" />')
			.unbind("click")
			.css({
					"font-weight": "bold",
					"text-decoration": "none",
					"cursor": "default",
			});

			var data = {
				action: 'wiziapp_use_thumbnail',
				post_id: post_id,
				image_id: id,
			};
			$.post(ajaxurl, data, handle_use_thumbnail, 'json');
		}

		function handle_use_thumbnail(data) {
			element
			.text(data.message)
			.css("color", ((data.status) ? "green" : "red"));

			if (data.status) {
				window.parent.jQuery("#wiziapp_post_thumbnail_preview").html(image_element);
				window.parent.jQuery("#wiziapp_is_user_chosen").attr("checked", "checked");
				window.parent.jQuery("#wiziapp_is_no_thumbnail").removeAttr("checked");
				window.parent.jQuery("#wiziapp_upload_thumbnail + p *").show(0);
			}
		}

})(jQuery);