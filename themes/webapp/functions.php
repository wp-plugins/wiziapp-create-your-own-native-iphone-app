<?php

/** Theme functions file */
function wiziapp_back_button($url = false, $text = false){
	$style_add = '';
	if ($url === false){
		$url = '#';
		$style_add = ' style="display: none"';
	}
	/*if ($text === false)*/{
		$text = __('Back', 'wiziapp');
	}
?>
<a data-role="button" data-corners="false" data-theme="z" href="<?php echo esc_attr($url); ?>" class="navigation_back_button_wrapper ui-btn-left"<?php echo $style_add; ?> data-transition="slide" data-direction="reverse">
	<span class="navigation_back_button"><?php echo esc_html($text); ?></span>
	<span class="navigation_back_button_closer"></span>
</a>
<?php
}

function wiziapp_get_webapp_title(){
	global $page, $paged;
	$title = wp_title('|', FALSE, 'right');

	$title .= get_bloginfo('name');

	$site_description = get_bloginfo('description', 'display');
	if ( $site_description && ( is_home() || is_front_page() ) ){
		$title .= " | {$site_description}";
	}

	return $title;
}

function wiziapp_get_splash(){
	return WiziappContentHandler::getInstance()->get_blog_property('data_files_url').'/resources/default.png';
}

function wiziapp_get_app_icon(){
	$url = WiziappContentHandler::getInstance()->get_blog_property('data_files_url').'/resources/icons/';
	$name = basename(WiziappConfig::getInstance()->getAppIcon());

	return $url . $name;
}