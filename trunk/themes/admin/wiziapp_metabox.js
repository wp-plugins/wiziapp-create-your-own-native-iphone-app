(function($) {

		$(document).ready(function() {
				wiziapp_push_save_result  = $("#wiziapp_push_save_result");
				wiziapp_push_message_send = $("#wiziapp_push_message_send")
				wiziapp_push_message 	  = $('#wiziapp_push_message');
				wiziapp_push_message.val( wiziapp_push_message.attr('data-push_message') );

				$("#wiziapp_push_message_edit").overlay(overlay_options);

				wiziapp_push_message_send.click(save_push_message);

				wiziapp_push_message
				.after(jqEasyCounterMsg)
				.bind('keydown keyup keypress', doCount)
				.bind('focus paste', function() { setTimeout(doCount, 10); })
				.bind('blur', countStop);

				$("#wiziapp_upload_thumbnail").click(function() {
						$("#wp-content-media-buttons a.thickbox").trigger("click");
				});

				if ( $("#wiziapp_post_thumbnail_preview img").height() > 10 ) {
					$("#wiziapp_upload_thumbnail + p *").show(0);
				}
		});

		var wiziapp_push_save_result;
		var wiziapp_push_message_send;
		var wiziapp_push_message;
		var jqEasyCounterMsg = $('<div>&nbsp;</div>');;

		var counter_options = {
			maxChars: 105,
			maxCharsWarning: 90,
		};

		var overlay_options = {
			target: "#wiziapp_push_message_insert",
			top: '20%',
			mask: {
				color: '#444',
				opacity: 0.9,
			},
		};

		$.ajaxSetup({
				timeout: 60*1000,
				error: handle_ajax_error,
		});

		function handle_ajax_error(jqXHR, textStatus, errorThrown) {
			var data = {
				status: false,
				message: "Connection problem. Status: " + jqXHR.status + ", " + jqXHR.statusText + ".",
			}

			handle_save_result(data);
		}

		function countStop() {
			jqEasyCounterMsg
			.stop()
			.fadeTo( 'fast', 0);

			return false;
		}

		function doCount() {
			var val = wiziapp_push_message.val();
			var message_length = val.length;

			if (message_length > counter_options.maxChars) {
				wiziapp_push_message
				.val(val.substring(0, counter_options.maxChars))
				.scrollTop(wiziapp_push_message.scrollTop());
			};

			if (message_length > counter_options.maxCharsWarning) {
				jqEasyCounterMsg.css({"color" : "#F00"});
			} else {
				jqEasyCounterMsg.css({"color" : "#000"});
			};

			jqEasyCounterMsg
			.text('Maximum 105 Characters. Printed: ' + message_length + "/" + counter_options.maxChars)
			.stop()
			.fadeTo('fast', 1);
		}

		function save_push_message() {
			wiziapp_push_save_result.html('<img src="' + wiziapp_push_save_result.attr("data-ajax_loader") + '" />');

			wiziapp_push_message_send.unbind("click", save_push_message);

			var data = {
				action: 'wiziapp_push_message',
				post_id: wiziapp_push_message_send.attr("data-post_id"),
				push_message: wiziapp_push_message.val(),
			};
			$.post(ajaxurl, data, handle_save_result, 'json');
		}

		function handle_save_result(data) {
			wiziapp_push_save_result
			.text(data.message)
			.css("color", ((data.status) ? "green" : "red"));

			wiziapp_push_message_send.click(save_push_message);
			wiziapp_push_save_result.show(1);
			setTimeout(function() { wiziapp_push_save_result.text(''); }, 3000);
		}

})(jQuery);