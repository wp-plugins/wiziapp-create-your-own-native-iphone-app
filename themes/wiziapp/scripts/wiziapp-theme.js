/**
 * WiziApp - Smooth Touch
 *
 * License: Distributed under the GPL
 * Copyright: Wiziapp Solutions Ltd, http://www.wiziapp.com
 */

(function($, w, d, undef) {
	$(d).delegate(".wiziapp-show-more-link a[href]", "click", function(e) {
		var me = $(this);
		e.preventDefault();
		$.mobile.loadPage(me.attr("href")).done(function(url, opt, page) {
			me.closest("li").replaceWith(page.find(".wiziapp-content-list").html());
		});
		return false;
	});

	$(d).delegate(".wiziapp-collapsible:not(.wiziapp-collapsible-expanded) > a.wiziapp-collapsible-header", "click", function(e) {
		var me = $(this);
		e.preventDefault();
		var l = me.closest(".wiziapp-collapsible");
		l.addClass("wiziapp-collapsible-expanded");
		l.children(".wiziapp-collapsible-header").hide();
		l.children(".wiziapp-collapsible-content").show();
		var c = l.children(".wiziapp-collapsible-content[data-wiziapp-url]");
		if (c.length > 0) {
			var url = c.attr("data-wiziapp-url");
			c.removeAttr("data-wiziapp-url");
			$.mobile.loadPage(url).done(function(url, opt, page) {
				c.html("").append(page.find(".wiziapp-content-list").clone());
			});
		}
		return false;
	});

	$(d).delegate(".wiziapp-tabs > .wiziapp-tabs-header a[href]", "click", function(e) {
		var me = $(this);
		e.preventDefault();
		var h = me.attr("href");
		var l = me.closest(".wiziapp-tabs");
		l.children(".wiziapp-tabs-header").find("a").not(me).removeClass("wiziapp-tabs-selected");
		me.addClass("wiziapp-tabs-selected");
		var c = l.children(".wiziapp-tabs-content");
		var t = c.children("[data-wiziapp-url=\""+h.replace(/([^a-zA-Z0-9/ \-_])/g, "\\$1")+"\"]");
		if (!t.length)
		{
			t = c.children(".wiziapp-tabs-content-loading");
		}
		else
		{
			h = false;
		}
		c.children().not(t).hide();
		t.show();
		if (h)
		{
			$.mobile.loadPage(h, {reloadPage: true}).done(function(url, opt, page) {
				c.append(t = $("<div>").addClass("wiziapp-tabs-content-tab").attr("data-wiziapp-url", h).append(page.find(":jqmData(role=content)").clone()));
				c.children().not(t).hide();
				t.show();
			});
		}
		return false;
	});

	$(w).bind("load", function(e) {
		$(".wiziapp-header").trigger("updatelayout");
	});

	$(w).bind("orientationchange", function(){
		// Disable "set position by left" hoot in change orientation case.
		$("div.wiziapp-header a.ui-btn-right").css("left", "auto");
	});
	$(d).bind("pageshow", function(e) {
		// In the Android 4.1 position absolute right is not work, so, set position by "left".
		$("div.wiziapp-header a.ui-btn-right").css("left", ($(d).width() - 30) + "px");
	});

	$(d).bind("pagecreate", function(e) {
		$(".wiziapp-tabs > .wiziapp-tabs-header a[href]", e.target).first().trigger("click");

		$(".gallery br:not(:last)", e.target).remove();

		$("iframe", e.target).addClass("wiziapp-video").wrap("<div class=\"wiziapp-video-wrapper\">");
	});

	$(d).bind("pageload", function(e, data) {
		if (data.page.children().length > 0) {
			return;
		}
		var mime = data.xhr.getResponseHeader('Content-Type');
		if (/^image\//i.test(mime)) {
			var img = $("<img>");
			img.attr("src", data.absUrl);
			data.page.append(img);
		}
	});

	$(d).delegate("#left-panel li", "click", function() {
		$("#left-panel").panel("close");
	});

// Back Button Stack Begin
	var stack = [];
	$(d).delegate("#left-panel li a", "vclick", function() {
		stack = [];
	});
	$(d).delegate(":jqmData(role='page')", "pagebeforeshow", function() {
		var $page = $(this);
		var $header = $(":jqmData(role='header')", $page);
		if ($header.length < 1){
			$header = $("body > :jqmData(role='header')");
		}
		var $back = $(".wiziapp-back-button", $header);
		if ($back.length < 1){
			stack = [];
		}
		var type = 0;
		if ($page.is(":has(:jqmData(role='content') > .wiziapp-content-post)")){
			type = 1;
		}
		var url = $page.jqmData("url");
		if (url === $page.attr("id")) {
			url = "#" + url;
		}
		stack.push({url: url, type: type});
		if ($back.length < 1 || stack.length < 2){
			var data = $back.data("wiziapp-back-button-original");
			if (data){
				$back.attr("href", data.href);
				if (data.display) {
					$header.addClass("wiziapp-header-has-back");
				}
				else {
					$header.removeClass("wiziapp-header-has-back");
				}
			}
			return;
		}
		if (!$back.data("wiziapp-back-button-original")){
			$back.data("wiziapp-back-button-original", {
				href: $back.attr("href"),
				display: $header.hasClass("wiziapp-header-has-back")
			});
		}
		$back.attr("href", stack[stack.length-2].url);
		$header.addClass("wiziapp-header-has-back");
	});
	$(d).delegate(".wiziapp-back-button", "vclick", function() {
		if (stack.length > 0){
			stack.pop();
		}
		if (stack.length > 0){
			stack.pop();
		}
	});
// Back Button Stack End
})(jQuery, window, document);
