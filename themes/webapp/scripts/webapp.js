// iOS5
(function($, $$) {
	if ($.mobile) {
		$$($);
	}
	else {
		$(document).bind("mobileinit", function() {
			$$($);
		});
	}
})(jQuery, function($) {
	$.mobile.touchOverflowEnabled = true;
});

jQuery(document).delegate(".index:jqmData(role='page')", "pageshow", function() {
	var a = jQuery("a", this);

	if ( jQuery.mobile.path.parseUrl(window.location.href).search.indexOf("androidapp=1") >= 0 ){
		a.click();
	} else {
		setTimeout(function() {
			// Add the webapp=1 to mark what is the Android browser request and not the Android native app
			var href = a.attr("href");
			var webapp = "?webapp=1";
			var ind = href.indexOf('?');
			if ( ind > 0 ){
				webapp = "";
				if ( href.indexOf('webapp=1', ind + 1) < 0 ){
					webapp = '&webapp=1';
				}
			}
			href += webapp;

			a
			.attr("href", href)
			.click();
			}, 1000);
	}
});

jQuery(document).delegate(":jqmData(role='page')", "pageinit", pageShowEvent);
jQuery(document).delegate(".edit_favorites", "vclick", function(event){
	var $btn = jQuery(this);
	var $page = $btn.closest('.ui-page');

	if ( $page.is('.editing') ){
		// Leave edit mode
		$page.removeClass('editing');
		$btn.text($btn.attr('data-text'));
	} else {
		// Enter edit mode
		$page.addClass('editing');
		$btn.text($btn.attr('data-alternate-text'));
	}

	$btn = null;
});

function pageShowEvent(event){
	var $page = jQuery(this);

	if ( $page.is('.got_empty_state') ){
		if ( $page.find('.screen').find('li').length == 0 ){
			var cssClass = $page.attr('data-class');
			$page.find('.screen').addClass(cssClass + '_empty');
		}
	}

	jQuery.each($page.find('.valign_attribute:not(.processed_page_show)'), function(index, item){
		var $el = jQuery(item);
		var height = $el.css('height');

		if ( height != "auto" ){
			$el.css({
				'line-height': parseInt($el.css('height')) + 'px'
			});
		}

		$el.addClass('processed_page_show');
		$el = null;
	});

	jQuery.each($page.find('.imageURL:not(.processed_page_show)'), function(index, item){
		var $el = jQuery(item);
		var imageSrc = $el.attr('data-image-src');

		if ( imageSrc ){
			$el.find('img')
			.bind('error', function(){
				// Maybe we will add some logs to this
			})
			.bind('load', function(){
				var className = jQuery(this).attr('data-class');
				if ( className ){
					jQuery(this).addClass(className);
				}
				jQuery(this).removeClass("hidden");
			})
			.attr('src', imageSrc);

		}

		$el.addClass('processed_page_show');
		$el = null;
	});

	handleActionURL($page);

	$page
	.find('.audio_play:not(.processed_page_show)')
	.bind('click', function(event){
		var $el = jQuery(this);
		var audioElId = $el.attr('data-audio-id');
		var $audioEl = jQuery('#' + audioElId);

		if ( $audioEl.parents('.playing').length > 0 ){
			$el.removeClass('audio_button_play_selected');
			$audioEl[0].pause();
			$audioEl.parents('ul').find('.playing').removeClass('playing').removeClass('audio_selected');
		} else {
			$audioEl[0].play();
			$el.addClass('audio_button_play_selected');
			$audioEl.parents('ul').find('.playing').removeClass('playing').removeClass('audio_selected');
			$audioEl.parents('.audioCellItem').addClass('playing').addClass('audio_selected');
		}

		$el = $audioEl = null;
	})
	.addClass('processed_page_show');

	$page
	.find('.audio_stop:not(.processed_page_show)')
	.bind('click', function(event){
		var $el = jQuery(this);
		var audioElId = $el.attr('data-audio-id');
		var $audioEl = jQuery('#' + audioElId);

		$audioEl[0].pause();
		$audioEl[0].currentTime=0;// rewind

		$el.parent().find('.audio_button_play_selected').removeClass('audio_button_play_selected');

		$audioEl.parents('.audioCellItem').removeClass('playing').removeClass('audio_selected');

		$el = $audioEl = null;
	})
	.addClass('processed_page_show');

	$page
	.find('.cellItem:not(.ignore_hover):not(.processed_page_show)')
	.hover(function(){
		jQuery(this).addClass('default_selected');
		},
		function(){
			jQuery(this).removeClass('default_selected');
	})
	.addClass('processed_page_show');

	$page.bind("pagehide", function(){
		$page.find('.cellItem:not(.ignore_hover).processed_page_show.default_selected').removeClass('default_selected');
	});

	jQuery.each($page.find('[data-auto-height-calc=true]:not(.processed_page_show)'), function(){
		var $el = jQuery(this);
		var $byEl = $el.find($el.attr('data-height-by'));
		var height = parseInt($byEl.outerHeight());
		var totalHeight = parseInt($byEl.css('top')) + height;

		$el.css('height', totalHeight + 'px');

		$el.addClass('processed_page_show');
		$byEl = null;
		$el = null;
	});

	// editable
	$page.find('ul.editable li .ui-btn-inner').each(function(index, item){
		var $el = jQuery(this);
		var h = $el.parents('.cellItem:first').height();
		$el
		.prepend('<div class="delete delete_icon"></div>')
		.append('<div class="delete_button" data-delete-method="FavoritesPage.remove" data-delete-param="data-post-id">Delete</div>');

		$el
		.find('.delete_icon')
		.css('height', h);

		$el = null;
	});

	$page.find('.delete_icon').click(function(){
		var $btn = jQuery(this);
		var $deleteBtn = $btn.parents('.cellItem:first').find('.delete_button');

		if ( $btn.is(".btn_mode") ){
			$btn.removeClass('btn_mode');
			$deleteBtn.animate({
				'width': '0px'
				}, 'slow', function(){
					jQuery(this).hide();
			});
		} else {
			$btn.addClass('btn_mode');
			$deleteBtn.show().animate({
				'width': '50px'
				}, 'slow');
		}

		$deleteBtn = $btn = null;
		return false;
	});

	$page.find('.delete_button').click(function(event){
		event.preventDefault();

		var $btn = jQuery(this);
		var $parent = $btn.parents(".cellItem:first");
		var actionInfo = $btn.attr('data-delete-method').split('.');

		var id = $parent.attr($btn.attr('data-delete-param'));
		if ( id.indexOf('_') >= 0 ){
			var tmp = id.split('_');
			id = parseInt(tmp[tmp.length - 1]);
		}
		if ( actionInfo.length > 1 ){
			var obj = window[actionInfo[0]];
			obj[actionInfo[1]](id);
		} else {
			actionInfo[0](id);
		}

		$btn = $parent = null;
		return false;
	});

	// To avoid collision with "Contact Form 7", "FV Community News", "Formidable Forms" and "Events Manager" plugins
	$page
	.find("form.wpcf7-form, form.fvcn-post-form-new-post, #event-form, form[id^='gform'], form.frm-show-form[id^='form_']")
	.attr("data-ajax", "false");

	// Open X Ad
	if ( $page.attr("data-url").indexOf('output=html') > 0 && jQuery("#wiziapp_openxad_body div:jqmData(role='content') [id^='beacon_']").length > 0 ){
		setTimeout(function(){
			// The proper way "jQuery.mobile.changePage( "#open_x_ad", { role: "dialog" } );"
			// is not work fine, so need a trick
			jQuery("#wiziapp_openxad_open").click();

			// set Cookie to show the Ad next after some week.
			var exp_date = new Date();
			exp_date.setDate(exp_date.getDate() + 7);
			document.cookie = "wiziapp_openxad_shown=1; expires=" + exp_date.toUTCString();

			setTimeout(function(){
				jQuery("#wiziapp_openxad_close").click();
				}, 6000);
			}, 2000);
	}

	// Bind favorite button
	// Apply effects
	applyEffects($page);
}

function handleActionURL(page){
	WIZIAPP.doLoad(page);

	var elements = {
		'back_button' : page.find('a.navigation_back_button_wrapper[data-nav-processed!="processed"][href^="nav\\:\\/\\/"]'),
		'content' : page.find('.ui-content a[data-nav-processed!="processed"]'),
		'toolbar' : page.find("div.webview_bar ul li div.left_right_buttons_wrapper a[data-nav-processed!=\"processed\"]")
	};

	for (var key in elements){
		if ( elements[key].length > 0 ){
			prepareActionURL(elements[key], page.jqmData("url"));
		}
	}

	handleFavoritiesIcon(page);

	elements = page = null;
}

function prepareActionURL(elements, doc_href){
	var is_webapp = typeof doc_href === "string" && doc_href.indexOf('webapp=1') > 0;

	elements.filter("[href]").each(function(){
		var $el = jQuery(this);
		$el.attr("data-nav-processed", "processed");

		var href = $el.attr('href');
		if ( href != "#" ){
			var actionParams = href.split("://");
			var actionType = actionParams[0];
			var screenParams = '';
			var screenType = 'simple';
			var screenURL = href;
			if ( typeof(actionParams[1]) != 'undefined' ){
				screenParams = actionParams[1].split("/");
				screenType = screenParams[0];
				screenURL = unescape(screenParams[1]);
			}

			$el.data({
				'actionType': actionType,
				'screenType': screenType
			});

			var sep = '?output=html&androidapp=1&';
			var ind = screenURL.indexOf('?');
			if ( ind >= 0 ){
				sep = '&';
				if ( screenURL.indexOf('output=html', ind + 1) < 0 ){
					sep += 'output=html&';
				}
				if ( screenURL.indexOf('androidapp=1', ind + 1) < 0 ){
					sep += 'androidapp=1&';
				}
			}
			if (is_webapp){
				// Pass on an existing GET element "webapp=1"
				sep += 'webapp=1&';
			}

			if ( actionType == 'nav' ){
				screenURL += sep + 'ap=1&wizi_ver=' + window.WiziappPlatformVersion;

				if ( $el.is('.showMore') ){
					$el.bind('click', loadMoreInPage);
				}

				$el.attr('href', screenURL);
			} else {
				if ( actionType == 'cmd' ){
					if ( screenType == 'open' ){
						if ( screenParams[1] == 'favorites' ){
							// Open the posts favorites since the rest are not active yet.
							$el.attr('href', '#favorites');
						}
						else if ( screenParams[1] == 'image' ){
							var $im = $el.find("img[data-wiziapp-id]");
							if ($im.length){
								$im.attr("data-wiziapp-full-image", decodeURIComponent($el.attr('href').substr(17)));
								$el.attr('href', "#image-" + encodeURIComponent($im.attr("data-wiziapp-id")));
							}
						}
						else if ( screenParams[1] == 'video' ){
							$el.attr("href", decodeURIComponent($el.attr('href').substr(17)));
							$el.attr("rel", "external");
						}

					}
				} else {
					if ( actionType == 'http' || actionType == 'https' ){
						$el.attr("rel", "external");
//						$el.attr('href', "?wiziapp/external/" + encodeURIComponent(encodeURIComponent(href)) + '&output=html&ap=1&wizi_ver=' + window.WiziappPlatformVersion);
					}
				}
			}
		}

		$el = null;
	});
}

function handleFavoritiesIcon($page){
	$page.find('.nav_fav_btn').each(function() {
		var $favIcon = jQuery(this);
		var postID = $favIcon.attr("data-post-id")<<0;

		var favSelectedClass = 'navigation_button_favorite';
		var favNotSelectedClass = 'navigation_button_favorite_disabled';
		if ( FavoritesPage.havePost(postID) ){
			$favIcon.removeClass(favNotSelectedClass).addClass(favSelectedClass);
		}

		$favIcon.click(function(){
			var $btn = jQuery(this);
			var msg = '';

			if ( FavoritesPage.havePost(postID) ){
				msg = 'The post was removed from favorites';
				FavoritesPage.remove(postID);
				$btn.removeClass(favSelectedClass).addClass(favNotSelectedClass);
			} else {
				FavoritesPage.add(postID);
				$btn.removeClass(favNotSelectedClass).addClass(favSelectedClass);
				msg = 'The post was saved as favorite';
			}
			$btn.simpledialog({
				'mode' : 'empty',
				'prompt' : msg,
				'useModal' : false,
				'buttons' : {
					'OK': {
						click: function () {

						}
					}
				}
			});
			setTimeout(function(){
				jQuery('.nav_fav_btn:visible').trigger('simpledialog', {'method': 'close'});
				},1000);
			$btn = null;
		});

		$favIcon = null;
	});
}

function loadMoreInPage(event){
	event.preventDefault();
	var $showMore = jQuery(this);
	$showMore.addClass('pending');
	$showMore.closest("li").addClass("ui-btn-active");
	var href = $showMore.attr('href');

	jQuery.get(href, function(data){
		var $data = jQuery(data).children();
		var $page = $showMore.closest('.ui-page');
		var $list = $showMore.closest('ul');

		$showMore
		.closest('.cellItem')
		.after($data)
		.remove();

		$list.listview("refresh");

		// Before adding data, it needs to go through our own "enhancement" steps
		prepareActionURL($data.find("a"));

		jQuery.each($data.find('.valign_attribute:not(.processed_page_show)'), function(index, item){
			var $el = jQuery(item);
			var height = $el.css('height');

			if ( height != "auto" ){
				$el.css({
					'line-height': parseInt($el.css('height')) + 'px'
				});
			}

			$el.addClass('processed_page_show');
			$el = null;
		});

		jQuery.each($page.find('.imageURL:not(.processed_page_show)'), function(index, item){
			var $el = jQuery(item);
			var imageSrc = $el.attr('data-image-src');

			if ( imageSrc ){
				$el.find('img')
				.bind('error', function(){
					// Maybe we will add some logs to this
				})
				.bind('load', function(){
					var className = jQuery(this).attr('data-class');
					if ( className ){
						jQuery(this).addClass(className);
					}
					jQuery(this).removeClass("hidden");
				})
				.attr('src', imageSrc);

			}

			$el.addClass('processed_page_show');
			$el = null;
		});

		$data
		.filter('.cellItem:not(.ignore_hover):not(.processed_page_show)')
		.hover(function(){
			jQuery(this).addClass('default_selected');
		},
		function(){
			jQuery(this).removeClass('default_selected');
		})
		.addClass('processed_page_show');

		jQuery.each($data.find('[data-auto-height-calc=true]:not(.processed_page_show)'), function(){
			var $el = jQuery(this);
			var $byEl = $el.find($el.attr('data-height-by'));
			var height = parseInt($byEl.outerHeight());
			var totalHeight = parseInt($byEl.css('top')) + height;

			$el.css('height', totalHeight + 'px');

			$el.addClass('processed_page_show');
			$byEl = null;
			$el = null;
		});

		applyEffects($data);

		$page = $showMore = $data = $list = null;
	});

	return false;
}

var actions = {
	processActionURL: function(href){
		var $sandbox = jQuery("#sandbox");
		var $a = jQuery("<a></a>");

		$a.appendTo($sandbox);
		$a.attr('href', href);

		handleActionURL($sandbox);

		$a.trigger('click');

		$a.remove();

		$a = $sandbox = null;
	}
};

function applyEffects($wantedContainer){
	// See if we have something that needs special treatment
	if ( typeof jsInstructions != 'undefined' && jsInstructions.length > 0 ){
		/**
		* See if we can find any of the classes,
		* each special treatment should be a "jQuery plugin"
		*/
		var jsInstruction;
		for (jsInstruction in jsInstructions){
			if ( jsInstructions.hasOwnProperty(jsInstruction) && jsInstruction != "tag" ){
				for (var i=0; i<jsInstructions[jsInstruction].length;++i){
					var func = jsInstructions[jsInstruction][i]['func'];
					var params = jsInstructions[jsInstruction][i]['params'];
					var $el = $wantedContainer.find("." + jsInstruction + ":not(.ignore_effect)");

					if ( $el.length > 0 ){
						$el.data('effectSelector', '.ui-page[data-url="' + $wantedContainer.attr('data-url') + '"] .' + jsInstruction);
						$el.data('effectFor', jsInstruction);
						$el[func](params);
					}

					$el = null;
				}
			}
		}
	}
}

(function($){

	$.fn.applyEffectImage = function(params){
		return this.each(function(){
			var src = params[0];
			if ( src.indexOf('url(') == 0 ){
				src = src.replace('url(', 'url(' + jsInstructionsBase);
			}

			var $el = jQuery(this);
			var $styleEl = $el;

			if ( $el.parent('div').is('.image_ph')){
				$styleEl = $el.parent('div');
			}

			if ( $el.parent().find('.' + $el.data('effectFor') + '_effect').length == 0 ){
				var $effect = jQuery('<div class="effect"></div>');

				$styleEl.after($effect);
				$effect
				.data('buttonSelector', $el.data('effectSelector'))
				.css({
					'backgroundImage': src,
					'width'     : (parseInt($styleEl.width())-0) + 'px',
					'height'    : (parseInt($styleEl.height())-0) + 'px',
					'position'  : 'absolute',
					'top'       : $styleEl.css('top'),
					'left'      : $styleEl.css('left')
				})
				.addClass($el.data('effectFor') + '_effect')
				.click(function(event){
					event.preventDefault();
					$( $(this).data('buttonSelector') ).click();

					return false;
				});

				$effect = null;
			}

			$el = null;
		});
	};

	$.fn.applyDecorImage = function(params){
		return this.each(function(){
			var src = params[0];
			if ( src.indexOf('url(') == 0 ){
				src = src.replace('url(', 'url(' + jsInstructionsBase);
			}

			var $el = jQuery(this);
			var classes = $el.data('effectFor') + '_decor ';
			if ( $el.data('effectFor') === 'featured_post' ){
				classes = 'featured_webapp_decor ';
			}
			var $effect = jQuery('<div class="' + classes + '"></div>');

			// Make sure it's not there already
			if ( $el.find('.' + classes).length == 0 ){
				if ( $el.find('.imageURL').length > 0 ){
					if (  $el.find('.imageURL').parent('div').is('.image_ph')){
						$el
						.find('.image_ph')
						.after($effect);
					} else {
						$el
						.find('.imageURL')
						.after($effect);
					}
				} else {
					$el.append($effect);
				}

				if ( classes == 'featured_webapp_decor ' ){
					$effect.css({
						'backgroundImage': src,
						'background-repeat': 'repeat repeat'
					});
				} else {
					$effect.css({
						'backgroundImage': src,
						'background-position': 'center center',
						'background-repeat': 'repeat',
						'position': 'absolute'
					});
				}
			}

			$effect = $el = null;
		});
	};

	$(document)
	.on("pagebeforeshow", "#sharing_menu", function(event, data){
		var sharing_options = {
			"facebook":	"http://www.facebook.com/share.php?u=",
			"twitter":	"https://twitter.com/share?url=",
			"email":	"mailto:?subject=The%20page%20to%20share&body=",
			"google+":	"https://plus.google.com/share?url="
		}

		var share_buttons = $(event.currentTarget).find("div:jqmData(role='content') p a");
		var share_buttons_amount = share_buttons.length;
		if (share_buttons_amount == 0 ){
			return;
		}

		var sharing_url = data.prevPage.attr("data-url");
		if ( ! $.mobile.path.isAbsoluteUrl(sharing_url) ){
			sharing_url = window.location.protocol + "//" + window.location.host + sharing_url;
		}
		sharing_url = encodeURIComponent( $.mobile.path.parseUrl(sharing_url).hrefNoHash );

		for (var i=0; i<share_buttons_amount; i++){
			var current_button = $(share_buttons[i]);
			var sharing_name = current_button.text().toLowerCase();

			if ( typeof sharing_options[sharing_name] === "undefined" ){
				continue;
			}

			current_button.attr("href", sharing_options[sharing_name] + sharing_url);
		}
	})
	.on("pageinit", ".post_loaded_event", function(event){
		var process_video_behavior = function(event){
			var iframe_protect_screen = $(event.currentTarget);
			var not_moved = true;

			iframe_protect_screen
			.on("mousemove", function(event){
				not_moved = false;
			})
			.mouseup(function(event) {
				if (not_moved){
					$.mobile.changePage( iframe_protect_screen.attr("data-video-url") );
				}

				iframe_protect_screen
				.off("mouseup")
				.off("mousemove");

				not_moved = true;
			});
		}

		var toggleFontSizePanel = function(event){
			var element = $(this);

			element
			.children(".font_panel")
			.toggle();

			element = null;
		};

		var changeWebViewFontSize = function(event){
			event.stopPropagation();

			var element = $(this);

			WIZIAPP.changeFontSize( element.data('fontStep') == -1 );

			element = null;
		};

		var post_page_wrapper = $(event.currentTarget);
		var post_tool_bar = post_page_wrapper.find("div:jqmData(role='footer') div:jqmData(role='navbar') ul");

		post_tool_bar
		.find('li div.post_button_fontsize')
		.click(toggleFontSizePanel)
		.find('div.font_panel')
		.find('div.plus_button')
		.data('fontStep', 1)
		.click(changeWebViewFontSize)
		.end()
		.find('div.minus_button')
		.data('fontStep', -1)
		.click(changeWebViewFontSize);

		var video_iframes = post_page_wrapper.find("div:jqmData(role='content') div.data-wiziapp-iphone-support iframe");
		var video_iframes_amount = video_iframes.length;

		if ( video_iframes_amount > 0 ){
			for (var i=0; i<video_iframes_amount; i++){
				var current_video_iframe = $(video_iframes[i]);
				var iframe_protect_screen = current_video_iframe.prev("div");

				if ( iframe_protect_screen.lenght == 0 ){
					continue;
				}

				iframe_protect_screen
				.width(current_video_iframe.width())
				.height(current_video_iframe.height())
				.mousedown(process_video_behavior);
			}
		}
	})
	.on("pageinit", "div.comments_loaded_event", function(event){
		var comments_page_wrapper = $(event.currentTarget);

		comments_page_wrapper.on( "expand", "div:jqmData(role='collapsible')", function(event){
			event.stopPropagation();

			var collapsible_element = $(event.currentTarget);
			var inner_comments_url = collapsible_element.attr("data-inner-comments-url");

			if ( inner_comments_url.length == 0 ){
				return;
			}

			$.mobile
			.loadPage(inner_comments_url, {showLoadMsg : true})
			.done(function(absUrl, options, page, dupCachedPage){
				var inner_comments_element = page.find("ul:jqmData(role='listview')");

				collapsible_element
				.attr("data-inner-comments-url", "")
				.children("div.ui-collapsible-content")
				.append(inner_comments_element);

				page.remove();
			});
		});

		DoComments.init(comments_page_wrapper);
	})
	.on("pageinit", ".recent_loaded_event", function(event){
		$(event.currentTarget)
		.find("div:jqmData(role='content') ul:jqmData(role='listview') li.postDescriptionCellItem a")
		.click(function(){
			$.mobile.showPageLoadingMsg();

			var post_link = $(this);

			setTimeout(function(){
				$.mobile.hidePageLoadingMsg();
				window.location = post_link.attr("href");
				}, 1000);

				return false;
		});
	});

	var DoComments = function(){
		var
		self = this,
		data_role_content,
		comment_parent_element,
		comment_reply_button,
		webapp_send_comments_button,
		collapsible_wrapper,
		comments_form,
		root_ul_element,
		title_wrapper,
		permanent_title = '',
		temporary_title = 'New comment',
		comment_parent;

		var init = function(comments_page_wrapper) {
			data_role_content = comments_page_wrapper.children("div:jqmData(role='content')");
			title_wrapper = comments_page_wrapper.find("div:jqmData(role='header') h1.ui-title");
			permanent_title = title_wrapper.text();
			comment_reply_button = $("#comment_reply_root");
			webapp_send_comments_button = $("#webapp_send_comments_form");

			comments_page_wrapper
			.on( "click", "div.comment_replyButton", self.show_form )
			.on( "click", "#comment_reply_root", 	 self.show_form )
			.on( "click", 'input[type="button"][name="submit"]', self.send_form )
			.on( "click", "#webapp_send_comments_form", 		 self.send_form );
		}

		this.show_form = function(event){
			event.preventDefault();

			comment_parent_element = $(event.currentTarget).parent("li[data-comment-id]");
			root_ul_element = data_role_content.find("ul").first();
			comments_form = root_ul_element.next();

			if ( comment_parent_element.length === 0 ){
				comment_parent = 0;
			} else {
				comment_parent = comment_parent_element.attr("data-comment-id");
				collapsible_wrapper = comment_parent_element.children("div:jqmData(role='collapsible')");
			}

			$(document).on("pagebeforechange", function(event, data){
				event.preventDefault();
				self.return_from_form();
			});

			title_wrapper.text(temporary_title);
			comments_form.show();
			root_ul_element.hide();
			webapp_send_comments_button.show();
			comment_reply_button.hide();

			return false;
		}

		this.send_form = function(event){
			event.preventDefault();

			$(event.currentTarget).off("click");

			var form_fields = {
				"author" : $("#comment_reply_author").val(),
				"email" : $("#comment_reply_email").val(),
				"url" : $("#comment_reply_url").val(),
				"comment" : $("#comment_reply_content").val(),
				"comment_post_ID" : $("#comment_post_ID").val(),
				"comment_parent" : comment_parent,
				"_wp_unfiltered_html_comment" : $("#_wp_unfiltered_html_comment_disabled").val()
			};

			$.mobile.loadPage($("#comment_form_action").val(), {
				data : form_fields,
				showLoadMsg : true,
				type : "post"
			})
			.done(self.prepare_response);

			return false;
		}

		this.prepare_response = function(absUrl, options, page, dupCachedPage){
			var parent_comment_ul = $( page.find("div:jqmData(role='content') ul:jqmData(role='listview')")[0] );
			var new_comment_ul = $( page.find("div:jqmData(role='content') ul:jqmData(role='listview')")[1] );
			var new_comment_li = new_comment_ul.children("li")

			if ( new_comment_li.attr("data-comment-id") === '0' ){
				alert( new_comment_li.find("div.comment_body").text() );
				page.remove();
				return;
			}

			if ( comment_parent_element.length === 0 ){
				root_ul_element
				.children("li.comment_welcome_main")
				.remove()
				.end()
				.append(new_comment_li);
			} else {
				if ( collapsible_wrapper.length === 0 ){
					var new_collapsible_wrapper = parent_comment_ul.find("div:jqmData(role='collapsible')");

					new_collapsible_wrapper
					.children("div.ui-collapsible-content")
					.append(new_comment_ul);

					new_collapsible_wrapper
					.attr({
						"data-inner-comments-url" : ""
					})
					.appendTo(comment_parent_element)
					.trigger("expand");
				} else {
					collapsible_wrapper
					.find("ul:jqmData(role='listview')")
					.append(new_comment_li);
				}
			}

			self.return_from_form();
			page.remove();
		}

		this.return_from_form = function(){
			$(document).off("pagebeforechange");

			title_wrapper.text(permanent_title);
			comments_form.hide();
			root_ul_element.show();
			webapp_send_comments_button.hide();
			comment_reply_button.show();
		}

		return {
			init : init
		};
	}();

})(jQuery);

// Image Viewer Begin
(function($){
	function recheck_scale($this) {
		$this.css("width", "");
		$this.css("height", "");
		var w = $this.width(),
		h = $this.height(),
		hh = $(".ui-header").filter(":visible").last().outerHeight() || 0,
		fh = $(".ui-footer").filter(":visible").last().outerHeight() || 0,
		cn = $this.closest(".ui-content"),
		cw = window.innerWidth-parseFloat(cn.css("padding-left"))-parseFloat(cn.css("padding-right")),
		ch = window.innerHeight-parseFloat(cn.css("padding-top"))-parseFloat(cn.css("padding-bottom"))-hh-fh;
		if (w <= 0 || h <= 0 || cw <= 0 || ch <= 0) {
			return;
		}
		var p = $this.parent();
		if (w*ch > h*cw) {
			var r = ((h*cw/w) << 0);
			$this.width(cw + "px");
			$this.height(((h*cw/w) << 0) + "px");
			p.css("margin-top", ((ch-r) >> 1) + "px");
		}
		else {
			$this.width(((w*ch/h) << 0) + "px");
			$this.height(ch + "px");
			p.css("margin-top", "0px");
		}
		p.css("top", "0px");
		p.css("height", "auto");
	}

	var slideTime = 5000;

	var first = !0;
	var re = /^#image(-|_viewer$)/, res = /#image(-|_viewer$)/;
	$(document).bind("pagebeforechange", function(e, data) {
		if (typeof data.toPage !== "string") {
			if (!first) {
				return;
			}
			first = !1;
			if (window.location.hash) {
				if (re.test(window.location.hash)) {
					e.preventDefault();
					$.mobile.changePage(window.location.hash, data.options);
				}
				else if (window.location.hash.substr(0, 1) == "#" && res.test(window.location.hash)) {
					var l = window.location.hash.substr(1).split("#", 2);
					e.preventDefault();
					$.mobile.loadPage(l[0], data.options).done(function(url, options, page) {
						options.fromPageForce = page;
						options.transition = "none";
						$.mobile.changePage("#" + l[1], options);
					});
				}
				return;
			}
			return;
		}
		var u = $.mobile.path.parseUrl(data.toPage);
		if (!re.test(u.hash)) {
			return;
		}
		var t = $("#image_viewer_template");
		if (!t.length) {
			return;
		}
		var o = data.options;
		var f = o.fromPageForce || o.fromPage;
		if (!f || !f.length) {
			f = $(":jqmData(role=page):first");
		}
		if (!f.length) {
			return;
		}
		var fh = $.mobile.path.parseUrl(f.attr("data-url")).hrefNoHash;
		if (o.fromHashChange && u.hrefNoHash && u.hrefNoHash != fh && u.hrefNoHash != u.domain + fh) {
			e.preventDefault();
			$.mobile.loadPage(u.hrefNoHash, o).done(function(url, options, page) {
				options.fromPageForce = page;
				$.mobile.changePage(u.hash, options);
			});
			return;
		}
		o.dataUrl = fh + u.hash;
		var id = decodeURIComponent((u.hash == "#image_viewer")?"":u.hash.substr(7));
		var h = jQuery(t.html());
		h.bind("pagehide", function() {
			h.remove();
		});
		h.attr("id", u.hash.substr(1));
		h.attr("data-url", o.dataUrl);
		jQuery("body").append(h);
		var inslide = !!$(".image_viewer_tabbar .image-viewer-play.image-viewer-play-active", f).length;
		var images = [];
		// Process image viewer images
		$(".all-wrapper > .single-wrapper .thumbnail", f).each(function() {
			images.push({id: $(this).data("thumbnail-id"), full: $(this).data("thumbnail-as-full"), thumb: $(this).attr("src"), post: $(this).data("thumbnail-post"), back: $(this).data("thumbnail-back")});
		});
		// Process gallery images
		$("a[href^=\\#image-] > span[data-image-src] > img[data-full]", f).each(function() {
			images.push({id: $(this).parent().parent().attr("href").substr(7), full: $(this).attr("data-full"), thumb: $(this).parent().attr("data-image-src"), post: $(this).closest("li").attr("data-related-posts"), back: f.attr("data-url")});
		});
		// Process post images
		$("img[data-wiziapp-id]", f).each(function() {
			var p;
			if ($(this).is("[data-wiziapp-full-image]")) {
				images.push({id: $(this).attr("data-wiziapp-id"), full: $(this).attr("data-wiziapp-full-image"), thumb: $(this).attr("src"), post: $("#post_title", f).attr("href"), back: $("#post_title", f).attr("href")});
			}
			else if ((p = $(this).closest("a[href^=cmd\\:\\/\\/open\\/image\\/]")).length){
				images.push({id: $(this).attr("data-wiziapp-id"), full: decodeURIComponent(p.attr("href").substr(17)), thumb: $(this).attr("src"), post: $("#post_title", f).attr("href"), back: $("#post_title", f).attr("href")});
			}
			else {
				images.push({id: $(this).attr("data-wiziapp-id"), full: $(this).attr("src"), thumb: $(this).attr("src"), post: $("#post_title", f).attr("href"), back: $("#post_title", f).attr("href")});
			}
		});
		var tm = $(".template", h);
		var i;
		for (i = 0; i < images.length; i++) {
			var el = tm.children().clone();
			el.find("img").attr("src", images[i].thumb).data("thumbnail-id", images[i].id).data("thumbnail-as-full", images[i].full).data("thumbnail-post", images[i].post).data("thumbnail-back", images[i].back);
			tm.before(el);
		}
		f = t = u = tm = images = null;
		var b = !1, c = !1, a = !1, aa = !1;
		$(".all-wrapper > .single-wrapper .thumbnail", h).each(function() {
			if (a === !1) {
				a = $(this);
			}
			else if (aa === !1) {
				aa = $(this);
			}
			if (c !== !1 && id !== !1) {
				id = !1;
				a = $(this);
				return (b === !1);
			}
			if ($(this).data("thumbnail-id") === id) {
				c = $(this);
				return !0;
			}
			b = $(this);
			return !0;
		}).one("load", function() {
			var $this = $(this);
			recheck_scale($this);
		});
		if (a === !1) {
			return;
		}
		if (c === !1) {
			c = a;
			if (aa !== !1) {
				a = aa;
			}
		}
		if (b === !1) {
			b = a;
		}
		$(".image-viewer-back", h).attr("href", "#image-" + encodeURIComponent(b.data("thumbnail-id")));
		$(".image-viewer-play", h).attr("href", "#image-" + encodeURIComponent(c.data("thumbnail-id")));
		$(".image-viewer-forward", h).attr("href", "#image-" + encodeURIComponent(a.data("thumbnail-id")));
		if (b.is(c)){
			$(".image-viewer-back", h).addClass("ui-disabled");
		}
		if (a.is(c)){
			$(".image-viewer-play, .image-viewer-forward", h).addClass("ui-disabled");
		}
		var actionParams, actionType, screenParams, screenURL = c.data("thumbnail-post"), sep, ind;
		if (screenURL) {
			actionParams = screenURL.split("://");
			actionType = actionParams[0];
			if ( actionType == 'nav' ){
				if ( typeof(actionParams[1]) != 'undefined' ){
					screenParams = actionParams[1].split("/");
					screenURL = unescape(screenParams[1]);
				}

				sep = '?output=html&androidapp=1&';
				ind = screenURL.indexOf('?');
				if ( ind >= 0 ){
					sep = '&';
					if ( screenURL.indexOf('output=html', ind + 1) < 0 ){
						sep += 'output=html&';
					}
					if ( screenURL.indexOf('androidapp=1', ind + 1) < 0 ){
						sep += 'androidapp=1&';
					}
				}

				screenURL += sep + 'ap=1&wizi_ver=' + window.WiziappPlatformVersion;
			}
			$(".image-viewer-post", h).attr("href", screenURL);
		}
		screenURL = c.data("thumbnail-back");
		if (screenURL) {
			actionParams = screenURL.split("://");
			actionType = actionParams[0];
			if ( actionType == 'nav' ){
				if ( typeof(actionParams[1]) != 'undefined' ){
					screenParams = actionParams[1].split("/");
					screenURL = unescape(screenParams[1]);
				}

				sep = '?output=html&androidapp=1&';
				ind = screenURL.indexOf('?');
				if ( ind >= 0 ){
					sep = '&';
					if ( screenURL.indexOf('output=html', ind + 1) < 0 ){
						sep += 'output=html&';
					}
					if ( screenURL.indexOf('androidapp=1', ind + 1) < 0 ){
						sep += 'androidapp=1&';
					}
				}

				screenURL += sep + 'ap=1&wizi_ver=' + window.WiziappPlatformVersion;
			}
			$(".navigation_back_button_wrapper", h).attr("href", screenURL).css("display", "");
		}
		else {
			$(".navigation_back_button_wrapper", h).css("display", "none");
		}
		c.parent().parent().removeClass("single-wrapper-displaced");
		$(".fullsize", h).attr("src", "").attr("src", c.data("thumbnail-as-full")).one("load", function() {
			var $this = $(this);
			$this.closest(".single-wrapper").removeClass("single-wrapper-displaced");
			recheck_scale($this);
			$(".all-wrapper > .single-wrapper:has(.thumbnail)", h).addClass("single-wrapper-displaced");
		});
		h.bind("pagebeforeshow pageshow", function() {
			$(".all-wrapper > .single-wrapper img", h).each(function() {
				recheck_scale($(this));
			});
		});
		$(".content", h).bind("swipeleft", function() {
			$(".image-viewer-forward:not(.ui-disabled)", h).click();
		}).bind("swiperight", function() {
			$(".image-viewer-back:not(.ui-disabled)", h).click();
		});
		var slide_timeout = 0;
		$(".image-viewer-play:not(.ui-disabled)", h).bind("vclick", function(e) {
			e.preventDefault();
			e.stopImmediatePropagation();
			if (slide_timeout) {
				$(".image-viewer-play", h).removeClass("image-viewer-play-active");
				clearTimeout(slide_timeout);
				slide_timeout = 0;
			}
			else
			{
				$(".image-viewer-play", h).addClass("image-viewer-play-active");
				slide_timeout = setTimeout(function() {
					$(".image-viewer-forward:visible", h).click();
					}, slideTime);
			}
		});
		if (inslide) {
			$(".image-viewer-play", h).addClass("image-viewer-play-active");
			slide_timeout = setTimeout(function() {
				$(".image-viewer-forward:visible", h).click();
				}, slideTime);
		}
		h.page();
		e.preventDefault();
		$.mobile.changePage(h, o);
		o = data = e = null;
	});
	$(window).bind("resize orientationchange", function() {
		$(".image_viewer:jqmData(role='page') .all-wrapper > .single-wrapper img").each(function() {
			recheck_scale($(this));
		});
	});
})(jQuery);
// Image Viewer End

// Back Button Stack Begin
(function($){
	var stack = [];
	$(document).delegate(":jqmData(role='page')", "pagebeforeshow", function() {
		var $page = $(this);
		var $header = $(":jqmData(role='header')", $page);
		if ($header.length < 1){
			$header = $("body > :jqmData(role='header')");
		}
		var $title = $("h1", $header);
		var $post_title = $("#post_title", $page);
		if ($post_title.length > 0){
			$title = $post_title;
		}
		var $back = $(".navigation_back_button_wrapper", $header);
		if ($title.length < 1){
			return;
		}
		if ($back.length < 1){
			stack = [];
		}
		var type = 0;
		if ($page.is(".post_loaded_event")){
			type = 1;
		}
		else if ($page.is(".image_viewer")){
			type = 2;
		}
		if (type > 0){
			while (stack.length > 0 && stack[stack.length-1].type >= type){
				stack.pop();
			}
		}
		var url = $page.jqmData("url");
		if (url == $page.attr("id")) {
			url = "#" + url;
		}
		stack.push({url: url, title: $title.text(), type: type});
		if ($back.length < 1 || stack.length < 2){
			var data = $back.data("wiziapp-back-button-original");
			if (data){
//				$back.find(".navigation_back_button").text(data.text);
				$back.attr("href", data.href);
				$back.css("display", data.display);
			}
			return;
		}
		if (!$back.data("wiziapp-back-button-original")){
			$back.data("wiziapp-back-button-original", {
				text: $back.find(".navigation_back_button").text(),
				href: $back.attr("href"),
				display: $back.css("display")
			});
		}
//		$back.find(".navigation_back_button").text(stack[stack.length-2].title);
		$back.attr("href", stack[stack.length-2].url);
		$back.css("display", "");
	});
	$(document).delegate(".navigation_back_button_wrapper", "vclick", function() {
		if (stack.length > 0){
			stack.pop();
		}
		if (stack.length > 0){
			stack.pop();
		}
	});
})(jQuery);
// Back Button Stack End

// Page LRU Cache Begin
(function($){
	var cache_max_length = 3;
	var cache = [];
	$(document).delegate(":jqmData(role='page'):jqmData(external-page='true')", "pagebeforeshow", function() {
		var $this = $( this );
		var i;
		for (i = 0; i < cache.length; ) {
			if (cache[i].is($this) || cache[i].closest("body").length <= 0) {
				cache.splice(i, 1);
			}
			else {
				i++;
			}
		}
		cache.push($this);
		if (cache.length > cache_max_length) {
			cache.shift().removeWithDependents();
		}
	});
	$(document).delegate(":jqmData(role='page')", "pageremove", function(e) {
		var $this = $( this );
		var i;
		for (i = 0; i < cache.length; i++) {
			if (cache[i].is($this)) {
				e.preventDefault();
				return false;
			}
		}
		return true;
	});
})(jQuery);
// Page LRU Cache End

// Dynamic Library Compatibility Begin
(function() {
	var queue = [];
	function push_function(path) {
		return function() {
			var args = arguments;
			queue.push(function(subset) {
				var o = window;
				var p = o;
				var i;
				for (i = 0; i < path.length && i < subset.length; i++) {
					if (path[i] != subset[i]) {
						return false;
					}
					p = o;
					o = o[path[i]];
				}
				for (; i < path.length; i++) {
					p = o;
					o = o[path[i]];
				}
				o.apply(p, args);
				return true;
			});
		};
	}
	function push_class(path, proto) {
		proto = proto || {};
		return function() {
			var args = arguments;
			var me = this;
			var i;
			for (i in proto) {
				if (proto.hasOwnProperty(i)) {
					me[i] = proto[i];
				}
			}
			queue.push(function(subset) {
				var o = window;
				var i;
				for (i = 0; i < path.length && i < subset.length; i++) {
					if (path[i] != subset[i]) {
						return false;
					}
					o = o[path[i]];
				}
				for (; i < path.length; i++) {
					o = o[path[i]];
				}
				me.__proto__ = o.prototype;
				o.apply(me, args);
				return true;
			});
			return me;
		};
	}
	function push_method(path, method) {
		return function() {
			var args = arguments;
			var me = this;
			queue.push(function(subset) {
				var i;
				for (i = 0; i < path.length && i < subset.length; i++) {
					if (path[i] != subset[i]) {
						return false;
					}
				}
				me[method].apply(me, args);
				return true;
			});
		};
	}
	function onload(subset) {
		subset = subset || [];
		var q = queue;
		queue = [];
		var i;
		for (i = 0; i < q.length; i++) {
			if (!q[i](subset)) {
				queue.push(q[i]);
			}
		}
	}

	window.IN=window.IN||{
		Event: {
			on: push_function(["IN", "Event", "on"])
		}
	};
	window.google=window.google||{
		maps: {
			Geocoder: push_class(["google", "maps", "Geocoder"], {
				geocode: push_method(["google", "maps", "Geocoder"], "geocode")
			}),
			LatLng: push_class(["google", "maps", "LatLng"]),
			Map: push_class(["google", "maps", "Map"], {
				setCenter: push_method(["google", "maps", "Map"], "setCenter"),
				setZoom: push_method(["google", "maps", "Map"], "setZoom")
			}),
			Marker: push_class(["google", "maps", "Marker"], {
				setIcon: push_method(["google", "maps", "Marker"], "setIcon"),
				setMap: push_method(["google", "maps", "Marker"], "setMap")
			}),
			MarkerImage: push_class(["google", "maps", "MarkerImage"]),
			Point: push_class(["google", "maps", "Point"]),
			Size: push_class(["google", "maps", "Size"]),
			event: {
				addListener: push_function(["google", "maps", "event", "addListener"]),
				trigger: push_function(["google", "maps", "event", "trigger"])
			},
			ControlPosition: {
				BOTTOM: 11,
				BOTTOM_CENTER: 11,
				BOTTOM_LEFT: 10,
				BOTTOM_RIGHT: 12,
				CENTER: 13,
				LEFT: 5,
				LEFT_BOTTOM: 6,
				LEFT_CENTER: 4,
				LEFT_TOP: 5,
				RIGHT: 7,
				RIGHT_BOTTOM: 9,
				RIGHT_CENTER: 8,
				RIGHT_TOP: 7,
				TOP: 2,
				TOP_CENTER: 2,
				TOP_LEFT: 1,
				TOP_RIGHT: 3
			},
			MapTypeId: {
				HYBRID: "hybrid",
				ROADMAP: "roadmap",
				SATELLITE: "satellite",
				TERRAIN: "terrain"
			},
			NavigationControlStyle: {
				ANDROID: 2,
				DEFAULT: 0,
				SMALL: 1,
				Wl: 5,
				ZOOM_PAN: 3,
				xm: 4
			}
		}
	};

	jQuery(document).delegate(":jqmData(role='page')", "pageinit pageshow", function() {
		if (window.IN.ENV) {
			onload(["IN"]);
		}
		if (window.google.maps.version) {
			onload(["google", "maps"]);
		}
	});

	// Compatibility with scripts that try to "realtime write"
	jQuery(document).ready(function() {
		document.write = function(content) {
			jQuery("head").append(content);
		}
	});
})();
// Dynamic Library Compatibility End
