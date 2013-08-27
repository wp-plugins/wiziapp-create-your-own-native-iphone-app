var intro_page_parameters = intro_page_parameters || {};

intro_page_parameters.action = function(action, url) {
	var expiration = '';
	if ( document.getElementById("remember").checked ) {
		var exp_date = new Date();
		exp_date.setDate( exp_date.getDate() + this.delay_period );
		expiration = " expires=" + exp_date.toUTCString();
	}
	document.cookie = "WIZI_SHOW_STORE_URL=1;" + expiration;

	if ( typeof _gaq !== "object" ) {
		window.location = this[url];
		return;
	}

	_gaq.push(['_trackEvent', "AndroidIntroScreen", action, this.app_id]);

	setTimeout(function() {
		window.location = intro_page_parameters[url];
		}, 1000);
};

window.onload = function() {
	document.getElementById("download_from_store").onclick = function(event) {
		if ( intro_page_parameters.playstore_condition === "0" ) {
			var objects_array = [
				event.currentTarget,
				document.getElementById("download_button_title"),
				document.getElementById("title"),
				document.getElementById("remember").parentNode,
				document.getElementById("continue_to").parentNode,
				document.getElementById("desktop_site")
			];
			for ( var i=0; i<objects_array.length; i++ ) {
				if ( objects_array[i] == null ) {
					continue;
				}

				objects_array[i].className = "display_none";
			}

			document.getElementById("android_app_download").className = "display_block";
		}

		intro_page_parameters.action("DownloadAndroidApp", "store_url");
	};

	document.getElementById("continue_to").onclick = function(event) {
		intro_page_parameters.delay_period = 7;

		intro_page_parameters.action("AndroidIntroScreenSkipped", "site_url");
	};

	var desktop_site = document.getElementById("desktop_site");
	if ( ! desktop_site ) {
		return;
	}

	desktop_site.onclick = function(event) {
		// Clear the Cookie
		intro_page_parameters.delay_period = -365;

		intro_page_parameters.action("AndroidIntroScreenSkipped", "desktop_site_url");
	};
};