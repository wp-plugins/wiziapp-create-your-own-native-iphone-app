(function($){

	$(document)
	.on("click", "#download_from_store", function(event){
		// If user choose to see Appstore URL,
		// set Cookie to another expire date to show the message next after six months.
		var exp_date = new Date();
		exp_date.setDate(exp_date.getDate() + 30*6);
		document.cookie = "WIZI_SHOW_STORE_URL" + "=" + 1 + "; expires=" + exp_date.toUTCString();

		window.location = window.store_url;
	})
	.on("click", "#mobile_site", function(event){
		window.location = window.site_url;
	})
	.on("click", "#desktop_site", function(event){
		// Clear the Cookie
		document.cookie = "WIZI_SHOW_STORE_URL=; expires=Thu, 01 Jan 1970 00:00:01 GMT;";

		window.location = window.site_url + "?setsession=desktopsite";
	});

})(jQuery);