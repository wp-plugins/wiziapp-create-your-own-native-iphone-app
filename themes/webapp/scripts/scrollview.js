function resizePageContentHeight(page) {
	var $page = jQuery(page),
	$content = $page.children( ".ui-content" ),
	hh = $page.children( ".ui-header" ).outerHeight() || 0,
	fh = $page.children( ".ui-footer" ).outerHeight() || 0,
	pt = parseFloat($content.css( "padding-top" )),
	pb = parseFloat($content.css( "padding-bottom" )),
	wh = window.innerHeight;

	if ( fh == 0 ){
		// Check if we still have a footer
		var footerId = $content.attr('data-footer-id');
		if ( footerId ){
			fh = jQuery('[data-id='+footerId+']:visible:first').outerHeight();
		}
	}
	$content.height(wh - (hh + fh) - (pt + pb));
	$page.find( ":jqmData(scroll)" ).each(function() {
		if (jQuery.data(this, "scrollview"))
		{
			jQuery(this).scrollview("scrollFix");
		}
	});
}

jQuery(document).delegate(":jqmData(role='page')", "pageshow", function(event) {
	var $page = jQuery( this );

	// For the demos that use this script, we want the content area of each
	// page to be scrollable in the 'y' direction.

	$page.find( ".ui-content" ).attr( "data-" + jQuery.mobile.ns + "scroll", "y" );

	// This code that looks for [data-scroll] will eventually be folded
	// into the jqm page processing code when scrollview support is "official"
	// instead of "experimental".

	$page.find( ":jqmData(scroll):not(.ui-scrollview-clip)" ).each(function () {
		var $this = jQuery( this );
		// XXX: Remove this check for ui-scrolllistview once we've
		//      integrated list divider support into the main scrollview class.
		if ( $this.hasClass( "ui-scrolllistview" ) ) {
			$this.scrolllistview();
		} else {
			var st = $this.jqmData( "scroll" ) + "",
			paging = st && st.search(/^[xy]p$/) != -1,
			dir = st && st.search(/^[xy]/) != -1 ? st.charAt(0) : null,

			opts = {
				direction: dir || undefined,
				paging: paging || undefined,
				scrollMethod: $this.jqmData("scroll-method") || undefined
			};

			$this.scrollview( opts );
		}

		$this.mousewheel(function(event, delta) {
			this.scrollTop -= (delta * 30);

			event.preventDefault();
		});
	});

	// For the demos, we want to make sure the page being shown has a content
	// area that is sized to fit completely within the viewport. This should
	// also handle the case where pages are loaded dynamically.

	resizePageContentHeight( event.target );
});

jQuery(window).bind( "resize orientationchange", function( event ) {
	resizePageContentHeight( jQuery( ".ui-page:visible" ) );
});