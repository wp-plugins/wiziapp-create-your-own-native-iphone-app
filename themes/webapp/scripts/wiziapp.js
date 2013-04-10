window.WIZIAPP = (function($){
	var self = this;

	/**
	* Multi-image swipe support
	*/
	function swipeImage($el, direction){
		var $container = $el.closest(".wiziAppMultiImage");
		var images = $container.find("img");
		var index = $.inArray($el.get(0), images);
		var total = images.length;

		var modify_by;
		if (direction == 'left'){
			modify_by = 1;
		} else {
			modify_by = -1;
		}

		// var new_index = (index+modify_by) % total; - No need to cycle need to simulate the gallery
		var new_index = index + modify_by;
		var shouldSwipe = true;

		// Sometimes the user will try to swipe the un-swipable...
		if (new_index < 0){
			// Most right
			//new_index = total + new_index;
			$container
			.find('.wiziAppMultiImageScrolling').stop().animate({
				'left': '50px'
				}, "fast", "linear").animate({
				'left': '0'
				}, "fast", "linear").end();
			shouldSwipe = false;
		} else if (new_index >= total){
			// Most left
			//var last_image_left = 0 - parseInt($(".wiziAppMultiImageScrolling img:eq("+(total-1)+")")
			var last_image_left = 0 - parseInt($container.find(".wiziAppMultiImageScrolling a:eq("+(total-1)+")")
				.offset().left);
			last_image_left += $container.find(".wiziAppMultiImageScrolling").offset().left;
			$container
			.find('.wiziAppMultiImageScrolling').stop().animate({
				'left': last_image_left - 50
				}, "fast", "linear").animate({
				'left': last_image_left
				}, "fast", "linear").end();
			shouldSwipe = false;
		}

		// But if is swipe-able, do it
		if (shouldSwipe){
			//var $new_image = $el.parents(".wiziAppMultiImage").find("img:eq("+new_index+")");
			var $new_image = $container.find("img").eq(new_index).closest('a');
			/**var new_width = $new_image.width();
			var new_height = $new_image.height();
			$new_image.parent('a').css({
			'width': new_width,
			'height': new_height
			});*/
			var new_left = 0 - parseInt($new_image.offset().left);
			var new_top = 0 - parseInt($new_image.offset().top);
			new_top += $container.find(".wiziAppMultiImageScrolling").offset().top;
			new_left += $container.find(".wiziAppMultiImageScrolling").offset().left;
			if ( $container.find('.caption').legnth > 0 ){
				$container.find('.caption').text($new_image.attr('title')).end();
			}
			$container
			.find('.multiImageNav.active').removeClass("active").end()
			.find('.multiImageNav:eq(' + new_index + ')').addClass("active").end()
			.find('.wiziAppMultiImageScrolling').animate({
				'left': new_left
			}).end();
			/**.animate({'height': (new_height + 35)})
			.find('.wiziAppMultiImageScrolling'). css({'height': new_height}).end()
			.css({
			'width': new_width
			});*/
			$new_image = null;
		}

		$el = $container = null;
	}

	self.getCookie = function(c_name){
		var i, x, y, ARRcookies = document.cookie.split(";");

		for ( i=0; i<ARRcookies.length; i++ ){
			x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
			y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
			x = x.replace(/^\s+|\s+$/g, "");

			if ( x == c_name ){
				return unescape(y);
			}
		}
	};

	self.setCookie = function(c_name, value, exdays){
		var exdate = new Date();
		exdate.setDate(exdate.getDate() + exdays);
		var c_value = escape(value) + ( (exdays==null) ? "" : "; expires=" + exdate.toUTCString() );
		document.cookie = c_name + "=" + c_value;
	};

	return {
		reloadThemeCSS: function(){
			var $currentCSS = jQuery("#themeCss");
			var cssSrc = $currentCSS.attr('href');

			$currentCSS.remove();
			$currentCSS = null;

			$('<link />')
			.appendTo('head')
			.attr({
				id: "themeCss",
				rel: "stylesheet",
				type: "text/css",
				href: cssSrc + '&rnd=' + Math.floor(Math.random()*99999)
			});
		},

		changeFontSize: function(decrease){
			var steps = 2;
			var size = parseInt($("body").css("font-size"));

			if ( decrease ) {
				size = size - steps;
			} else {
				size = size + steps;
			}

			$("body").css("font-size", size+"px");
		},

		doLoad: function($page) {
			$.ready();
			if (!$page) {
				$page = $("body");
			}
			var clickSkip = false;
			$page.find(".wiziAppMultiImageScrolling img").bind("swipeleft", function(e) {
				clickSkip = true;
				setTimeout(function() {
					clickSkip = false;
				}, 200);
				e.stopPropagation();
				e.preventDefault();
				swipeImage($(this), "left");
				return false;
			}).bind("swiperight", function(e) {
				clickSkip = true;
				setTimeout(function() {
					clickSkip = false;
				}, 200);
				e.stopPropagation();
				e.preventDefault();
				swipeImage($(this), "right");
				return false;
			}).bind("click", function(e) {
				if (clickSkip) {
					e.stopPropagation();
					e.preventDefault();
					clickSkip = false;
					return false;
				}
				return true;
			}).bind("mousedown touchstart", function(e) {
				e.preventDefault();
			});
		},

		condition_for_intro_page: function(){
			var not_show_intro_page =
			typeof navigator !== "object" || typeof navigator.userAgent !== "string" ||
			window.location.href.indexOf("androidapp=1") > 0 ||
			wiziapp_name_space.ajaxurl == '' || wiziapp_name_space.home_url == '' ||
			Boolean( self.getCookie("WIZI_SHOW_STORE_URL") );
			if ( not_show_intro_page ){
				return;
			}

			var device_type = "";
			if ( navigator.userAgent.search(/(iPhone)|(iPod)/i) > -1 ){
				device_type = "iphone";
			} else if ( navigator.userAgent.search(/Android/i) > -1 ){
				device_type = "android";
			}
			if ( device_type === "" ){
				return;
			}

			// Show "Appstore URL" message, if "WIZI_SHOW_STORE_URL" cookie not exist
			$.ajax({
				type: "post",
				url: wiziapp_name_space.ajaxurl,
				data: {
					action: "intro_page_info",
					device_type: device_type
				},
				dataType : "text",
				timeout: 10*1000,
				success: function(response_text) {
					if ( response_text.indexOf("allow_show_intro_page") < 0 ){
						return;
					}

					self.setCookie("WIZI_SHOW_STORE_URL", 1, 7);
					if ( ! Boolean( self.getCookie("WIZI_SHOW_STORE_URL") ) ) {
						// We are not able to create the cookie, so it is not ok.
						return;
					}

					window.location.href = wiziapp_name_space.home_url + "/?wiziapp/intropage&device=" + device_type;
				}
			});
		}
	};
})(jQuery);

window.WIZIAPP.condition_for_intro_page();