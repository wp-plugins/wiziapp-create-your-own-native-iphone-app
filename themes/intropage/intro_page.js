function wiziapp_intro_page_load(){
	document.getElementById("download_from_store").onclick = function(event){
		// If user choose to see Appstore URL,
		// set Cookie to another expire date to show the message next after some months.
		var exp_date = new Date();
		exp_date.setDate(exp_date.getDate() + window.intro_page_parameters.delay_period);
		document.cookie = "WIZI_SHOW_STORE_URL=1; expires=" + exp_date.toUTCString();

		event.currentTarget.className								= "display_none";
		document.getElementById("download_button_title").className	= "display_none";
		document.getElementById("no_thanks_notation").className		= "display_none";
		document.getElementById("title").className					= "display_none";

		document.getElementById("intro_page_postclick").className	= "display_block";
		document.getElementById("arrow_up").className				= "display_block";

		window.location = window.intro_page_parameters.store_url;
	};

	document.getElementById("mobile_site").onclick = function(event){
		window.location = window.intro_page_parameters.site_url;
	};

	var desktop_site = document.getElementById("desktop_site");
	if ( ! desktop_site ) {
		return;
	}

	desktop_site.onclick = function(event){
		// Clear the Cookie
		document.cookie = "WIZI_SHOW_STORE_URL=1; expires=Thu, 01 Jan 1970 00:00:01 GMT;";

		window.location = window.intro_page_parameters.desktop_site_url;
	};
}