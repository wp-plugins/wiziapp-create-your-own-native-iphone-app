jQuery(document).ready(function() {

		jQuery("#wiziapp_active_notice div:first-child").click(function() {
				window.location="admin.php?page=wiziapp";
		});

		jQuery("#wiziapp_active_notice div:last-child").click(function() {
				jQuery("#wiziapp_active_notice").hide("slow");
		});

});