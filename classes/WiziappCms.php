<?php

class WiziappCms {

	public function activate() {
		$updatedApi = FALSE;
		$profile = $this->generateProfile();

		$blogUrl = get_bloginfo('url');
		$urlData = explode('://', $blogUrl);

		// Inform the admin server
		$r = new WiziappHTTPRequest();
		$response = $r->api($profile, '/cms/activate/' . $urlData[0] . '?version=' . WIZIAPP_VERSION . '&url=' . urlencode($urlData[1]), 'POST');
		WiziappLog::getInstance()->write('DEBUG', "The response is " . print_r($response, TRUE), "cms.activate");
		if (is_wp_error($response)) {
			return FALSE;
		}

		$tokenResponse = json_decode($response['body'], TRUE);
		if (empty($tokenResponse) || ! $tokenResponse['header']['status']) {
			return FALSE;
		}

		WiziappConfig::getInstance()->startBulkUpdate();

		/**
		* Do Common settings
		*/
		if (isset($tokenResponse['plugin_token'])){
			WiziappConfig::getInstance()->app_token = $tokenResponse['plugin_token'];
		}
		$common_settings = array(
			'app_id', 'main_tab_index', 'settings_done', 'app_live', 'app_description', 'appstore_url', 'app_icon', 'app_name', 'email_verified',
			'thumb_min_size', 'display_download_from_appstore', 'notify_on_new_post', 'notify_on_new_page', 'push_message',
		);
		$this->_apply_setting($common_settings, $tokenResponse);

		/**
		* Set Thumbnails config
		*/
		$thumbs = json_decode($tokenResponse['thumbs'], TRUE);

		WiziappConfig::getInstance()->full_image_height = $thumbs['full_image_height'];
		WiziappConfig::getInstance()->full_image_width = $thumbs['full_image_width'];

		WiziappConfig::getInstance()->images_thumb_height = $thumbs['images_thumb_height'];
		WiziappConfig::getInstance()->images_thumb_width = $thumbs['images_thumb_width'];

		WiziappConfig::getInstance()->posts_thumb_height = $thumbs['posts_thumb_height'];
		WiziappConfig::getInstance()->posts_thumb_width = $thumbs['posts_thumb_width'];

		WiziappConfig::getInstance()->featured_post_thumb_height = $thumbs['featured_post_thumb_height'];
		WiziappConfig::getInstance()->featured_post_thumb_width = $thumbs['featured_post_thumb_width'];

		WiziappConfig::getInstance()->mini_post_thumb_height = $thumbs['mini_post_thumb_height'];
		WiziappConfig::getInstance()->mini_post_thumb_width = $thumbs['mini_post_thumb_width'];

		WiziappConfig::getInstance()->comments_avatar_height = $thumbs['comments_avatar_height'];
		WiziappConfig::getInstance()->comments_avatar_width = $thumbs['comments_avatar_width'];

		WiziappConfig::getInstance()->album_thumb_width = $thumbs['album_thumb_width'];
		WiziappConfig::getInstance()->album_thumb_height = $thumbs['album_thumb_height'];

		WiziappConfig::getInstance()->video_album_thumb_width = $thumbs['video_album_thumb_width'];
		WiziappConfig::getInstance()->video_album_thumb_height = $thumbs['video_album_thumb_height'];

		WiziappConfig::getInstance()->audio_thumb_width = $thumbs['audio_thumb_width'];
		WiziappConfig::getInstance()->audio_thumb_height = $thumbs['audio_thumb_height'];

		/**
		* If the app is configured update the titles
		*/
		if ( ! empty($tokenResponse['screen_titles']) ) {
			$titles_settings = array('categories_title', 'tags_title', 'albums_title', 'videos_title', 'audio_title', 'links_title', 'pages_title', 'favorites_title', 'about_title', 'search_title', 'archive_title',);
			$this->_apply_setting($titles_settings, $tokenResponse['screen_titles']);
		}

		$screens = array();
		if ( isset($tokenResponse['screens']) ){
			$screens = json_decode(stripslashes($tokenResponse['screens']), TRUE);
		}
		$components = array();
		if ( isset($tokenResponse['components']) ){
			$components = json_decode(stripslashes($tokenResponse['components']), TRUE);
		}
		$pages = array();
		if ( isset($tokenResponse['pages']) ){
			$pages = json_decode(stripslashes($tokenResponse['pages']), TRUE);
		}
		update_option('wiziapp_pages', $pages, '', 'no');
		update_option('wiziapp_screens', $screens, '', 'no');
		update_option('wiziapp_components', $components, '', 'no');

		WiziappConfig::getInstance()->bulkSave();
		$updatedApi = TRUE;

		return TRUE;
	}

	public function deactivate(){
		// Inform the system control
		$blogUrl = get_bloginfo('url');
		$urlData = explode('://', $blogUrl);

		$r = new WiziappHTTPRequest();
		$response = $r->api(array(), '/cms/deactivate?app_id=' . WiziappConfig::getInstance()->app_id . '&url=' . urlencode($urlData[1]), 'POST');

		$this->deleteUser();
	}

	protected function deleteUser(){
		$userId = username_exists('wiziapp');

		if ( ! $userId){
			return;
		}

		if ( ! wp_delete_user($userId) ){
			WiziappLog::getInstance()->write('ERROR', "Error deleting user wiziapp", "install.delete_user_wiziapp");
		}
	}

	// If the blog allows to create users, we register our user to be able to give to apple for appstore approval
	protected function registerUser() {
		$userData = array();
		$blogAllowRegistration = intval( get_option('users_can_register') );
		$userName = 'wiziapp';
		$password = 'ERROR';

		if ($blogAllowRegistration) {
			$blogName = get_bloginfo('name');
			$userId = username_exists($userName);

			$password = substr(str_replace(" " , "", $blogName), 0, 5) . '1324'; // wp_generate_password(12, false);
			if (!$userId) {
				$userId = wp_create_user($userName, $password); //wp_create_user($userName, $password, $user_email)
				if (!$userId) {
					WiziappLog::getInstance()->write('ERROR', "Error creating user " . $userName, "install.register_user_wiziapp");
				} else {
					WiziappLog::getInstance()->write('INFO', "User " . $userName . " created successfully.", "install.register_user_wiziapp");
				}
			} else {
				// Might be our user... should see if we can login with our password
				$user = wp_authenticate($userName, $password);
				if ( is_wp_error($user) ){
					$password = 'ERROR';
					WiziappLog::getInstance()->write('ERROR', "User " . $userName . " already exists and was NOT created.", "install.register_user_wiziapp");
				}
			}
		}

		$userData['blog_allows_registration'] = $blogAllowRegistration;
		$userData['blog_username'] = $userName;
		$userData['blog_password'] = $password;

		return $userData;
	}

	protected function generateProfile(){
		$version = get_bloginfo('version');
		$admin_email = get_option('admin_email');
		/**
		* @todo check if wp_touch is installed and try to get it's configuration
		* for blog name and description
		*/
		$checker = new WiziappCompatibilitiesChecker();

		$profile = array(
			'cms' => 'wordpress',
			'cms_version' => floatval($version),
			'name' => get_bloginfo('name'),
			'tag_line' => get_bloginfo('description'),
			'profile_data' => json_encode(array(
				'plugins' => $this->getActivePlugins(),
				'pages' => $this->getPagesList(),
				'stats' => $this->getCMSProfileStats(),
				'os' => $checker->testOperatingSystem(),
				'write_permissions' => $checker->testWritingPermissions(false),
				'gd_image_magick' => $checker->testPhpGraphicRequirements(false),
				'allow_url_fopen' => $checker->testAllowUrlFopen(false),
				'web_server' => $checker->testWebServer(false),
			)),
			'comment_registration' => get_option('comment_registration') ? 1 : 0,
			'admin_email' => empty($admin_email) ? '' : $admin_email,
			'installation_source' => WiziappConfig::getInstance()->installation_source,
		);

		$profile = array_merge($profile, $this->registerUser());

		return $profile;
	}

	/**
	* Gets the list of the active plugins installed in the blogs
	*
	* @return array|bool $listActivePlugins or false on error
	*/
	protected function getActivePlugins() {
		/**
		* Uses the wordpress function - get_plugins($plugin_folder = [null])
		* to retrieve all plugins installed in the defined directory
		* filters out the none active plugins and then stores the name and version
		* of the active plugins in $listPlugins array and returns it.
		*/
		$listActivePlugins = array();
		if ($folder_plugins = get_plugins()) {
			foreach($folder_plugins as $plugin_file => $data) {
				if(is_plugin_active($plugin_file)) {
					$listActivePlugins[$data['Name']] = $data['Version'];
				}
			}
			return $listActivePlugins;
		}
		else return false;
	}

	/**
	* Gets the pages in the blog
	*
	* @return array $list of pages
	*/
	protected  function getPagesList() {
		$pages = get_pages(array(
			'number' => 15,
		));
		$list = array();

		foreach ($pages as $p) {
			$list[] = $p->post_title;
		}

		return $list;
	}

	/**
	* Gets the statistics for the CMS profile
	* @return array $stats
	*/
	protected  function getCMSProfileStats() {
		WiziappLog::getInstance()->write('INFO', "Getting the CMS profile", "wiziapp_getCMSProfileStats");

		$audiosAlbums = array();
		$playlists = array();

		ob_start();
		//    $imagesAlbums = apply_filters('wiziapp_images_albums_request', $imagesAlbums);
		$imagesAlbums = WiziappDB::getInstance()->get_albums_count();
		$audioAlbums = apply_filters('wiziapp_audios_albums_request', $audiosAlbums);
		$playlists = apply_filters('wiziapp_playlists_request', $playlists);
		ob_end_clean();

		$numOfCategories = count(get_categories(array(
			'number' => 15,
		)));

		$numOfTags = count(get_tags(array(
			'number' => 15,
		)));
		$postImagesAlbums = WiziappDB::getInstance()->get_images_post_albums_count(5);
		$videosCount = WiziappDB::getInstance()->get_videos_count();
		$postAudiosAlbums = WiziappDB::getInstance()->get_audios_post_albums_count(2);
		$linksCount = count(get_bookmarks(array(
			'limit' => 15,
		)));

		$stats = array(
			'numOfCategories' => $numOfCategories,
			'numOfTags' => $numOfTags,
			'postImagesAlbums' => $postImagesAlbums,
			'pluginImagesAlbums' => count($imagesAlbums),
			'videosCount' => $videosCount,
			'postAudiosAlbums' => $postAudiosAlbums,
			'pluginAudioAlbums' => count($audioAlbums),
			'pluginPlaylists' => count($playlists),
			'linksCount' => $linksCount,
		);
		WiziappLog::getInstance()->write('DEBUG', "About to return the CMS profile: " . print_r($stats, TRUE), "wiziapp_getCMSProfileStats");

		return $stats;
	}

	private function _apply_setting($variables, $incoming) {
		foreach ($variables as $variable) {
			if (isset($incoming[$variable])){
				WiziappConfig::getInstance()->$variable = $incoming[$variable];
			}
		}
	}
}