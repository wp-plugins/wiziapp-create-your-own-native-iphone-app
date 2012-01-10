<?php
/**
 * @property integer $categories_list_limit
 * @property integer $posts_list_limit
 * @property integer $comments_list_limit
 * @property integer $links_list_limit
 * @property integer $pages_list_limit
 * @property integer $tags_list_limit
 * @property integer $authors_list_limit
 * @property integer $videos_list_limit
 * @property integer $audios_list_limit
 * @property integer $comments_avatar_height // Replace the comments_avatar_size
 * @property integer $post_processing_batch_size
 * @property integer $search_limit
 * @property string $sep_color
 * @property integer $main_tab_index
 * @property string $api_server
 * @property boolean $configured
 * @property integer $app_id
 * @property string $app_token
 * @property string $app_name
 * @property string $version
 * @property string $appstore_url
 * @property boolean $app_live
 * @property integer $appstore_url_timeout
 * @property boolean $allow_grouped_lists
 * @property boolean $zebra_lists
 * @property string $wiziapp_theme_name
 * @property integer $count_minimum_for_appear_in_albums
 * @property integer $multi_image_height
 * @property integer $multi_image_width
 * @property integer $max_thumb_check
 * @property boolean $settings_done
 * @property boolean $finished_processing
 * @property integer $last_version_checked_at
 * @property string $wiziapp_avail_version
 * @property boolean $show_need_upgrade_msg
 * @property string $last_version_shown
 * @property boolean $wiziapp_showed_config_once
 * @property boolean $email_verified
 * @property boolean $show_email_verified_msg
 * @property integer $last_recorded_save
 * @property integer $wiziapp_log_threshold
 * @property boolean $nofity_on_new_page
 * @property boolean $nofity_on_new_post
 */
// As long as we are supporting php < 5.3 we shouldn't extent the singleton class
//class WiziappConfig extends WiziappSingleton implements WiziappIInstallable{
class WiziappConfig implements WiziappIInstallable{
	private $options = array();

	private $saveAsBulk = FALSE;

	private $name = 'wiziapp_settings';
	private $internalVersion =  (int)(int)(int)(int)(int)${build_number};

	private static $_instance = null;

	/**
	 * @static
	 * @return WiziappConfig
	 */
	public static function getInstance() {
		if( is_null(self::$_instance) ) {
			self::$_instance = new WiziappConfig();
		}

		return self::$_instance;
	}

	private function  __clone() {
		// Prevent cloning
	}

	private function __construct(){
		$this->load();
	}

	private function load(){
		$this->options = get_option($this->name);
	}

	public function upgrade(){
		/**
		 * This is depended per version, each version might remove or add values...
		 */
        $resetOptions = array(); // Add here the keys to reset to the default value;
        $addOptions = array('wiziapp_log_threshold', 'authors_list_limit','nofity_on_new_page'); // Add here the keys add with the default value, if they don't already exists;
		$removeOptions = array('test1'); // Add here the keys to remove from the options array;

		$newDefaults = $this->getDefaultConfig();
        foreach($addOptions as $optionName){
            if ( !isset($this->options[$optionName]) ){
                $this->options[$optionName] = $newDefaults[$optionName];
            }
        }

		foreach($resetOptions as $optionName){
			$this->options[$optionName] = $newDefaults[$optionName];
		}

		foreach($removeOptions as $optionName){
		   unset($this->options[$optionName]);
		}

		// save the updated options
		$this->options['options_version'] = $this->internalVersion;

		$this->options['wiziapp_avail_version'] = WIZIAPP_P_VERSION;
		$this->options['show_need_upgrade_msg'] = TRUE;

		return $this->save();
	}

	public function needUpgrade(){
		return ( $this->internalVersion != $this->options['options_version'] );
	}

	public function uninstall(){
		delete_option( $this->name );
	}

	public function install(){
		if ( ! $this->isInstalled() ){
			$this->loadDefaultOptions();
			$this->options['options_version'] = $this->internalVersion;
			$this->save();
		}

		return $this->isInstalled();
	}

	public function isInstalled(){
		// Make sure we are loaded
		$this->load();
		return ( ! empty($this->options) && isset($this->options['options_version']) );
	}

	private function loadDefaultOptions(){
		$this->options =  $this->getDefaultConfig();
	}

	public function startBulkUpdate(){
		$this->saveAsBulk = TRUE;
	}

	public function bulkSave(){
		$this->saveAsBulk = FALSE;
		return $this->save();
	}

	private function save(){
		return update_option($this->name, $this->options, '', 'no');
	}

	public function __get($option){
		$value = null;

		if ( isset($this->options[$option]) ){
			$value = $this->options[$option];
		}

		return $value;
	}

	public function saveUpdate($option, $value){
		$saved = FALSE;

		if ( isset($this->options[$option]) ){
			$this->options[$option] = $value;
			$this->save();
			// If the value is the same it will not be updated but thats still ok.
			$saved = TRUE;
		}

		return $saved;
	}

	public function __isset($option){
		return isset($this->options[$option]);
	}

	public function __set($option, $value){
		$saved = FALSE;

		//if ( isset($this->options[$option]) ){
			$this->options[$option] = $value;
			if ( !$this->saveAsBulk ){
				$saved = $this->save();
			}
		//}

		return $saved;
	}

	public function usePostsPreloading(){
		if ( isset($_GET['ap']) && $_GET['ap']==1 ){
			return FALSE;
		} else {
			return $this->options['use_post_preloading'];
		}
	}

   public function getImageSize($type){
		if ( !isset($this->options[$type . '_width']) || !isset($this->options[$type . '_height']) ){
			throw new WiziappUnknownType('Clone is not allowed.');
	   }

		$size = array(
			'width' => $this->options[$type . '_width'],
			'height' => $this->options[$type . '_height'],
		);
		return $size;
   }

	public function getScreenTitle($screen){
		$title = '';
		if ( isset($this->options[$screen.'_title']) ){
			$title = stripslashes($this->options[$screen.'_title']);
		}
		return $title;
	}

	public function getCdnServer(){
		$cdn = $this->options['cdn_server'];
		$protocol = 'http://';

		if ( isset($_GET['secure']) && $_GET['secure']==1 ){
			$cdn = $this->options['secure_cdn_server'];
			$protocol = 'https://';
		}
		return $protocol.$cdn;
	}

	public function getCommonApiHeaders(){
		$app_token = $this->options['app_token'];

		$headers = array(
			'Application' => $app_token,
			'wiziapp_version' => WIZIAPP_P_VERSION
		);

		if ( !empty($this->options['api_key']) ){
			$headers['Authorization'] = 'Basic '.$this->options['api_key'];
		}

		return $headers;
	}

	public function getAppIcon(){
		$url = $this->options['app_icon'];
		if ($url == '') {
			$url = $this->options['icon_url'];
		}

		if ( strpos($url, 'http') !== 0){
			$url = 'https://'.$this->options['api_server'].$url;
		}
		return $url;
	}

	public function getAppDescription(){
		$patterns = array("/(<br>|<br \/>|<br\/>)\s*/i","/(\r\n|\r|\n)/");
		$replacements = array(PHP_EOL, PHP_EOL);
		return preg_replace($patterns, $replacements, stripslashes($this->options['app_description']));
	}

	function getDefaultConfig(){
		$envSettings = array();
		require_once('conf/' . WIZIAPP_ENV . '_config.inc.php');

		$settings = array(
			// Push notifications
			'show_badge_number' => 1,
			'trigger_sound' => 1,
			'show_notification_text' => 1,
			'notify_on_new_post' => 1,
			'aggregate_notifications' => 0,
			'aggregate_sum' => 1,
			'notify_periods' => 'day',
            'nofity_on_new_page' => 0,

			// Rendering
			'main_tab_index' => 't1',
			'sep_color' => '#bbbbbbff',
	/**        'med_thumb_height' => 84,
			'med_thumb_width' => 112,
			'small_thumb_height' => 55,
			'small_thumb_width' => 73,
			'comments_avatar_size' => 50,*/

			'full_image_height' => 480,
			'full_image_width' => 320,
			'multi_image_height' => 320, // 350-30 pixels for the scroller and sorrounding space
			'multi_image_width' => 298, //300-2 pixels for the rounded border
			'images_thumb_height' => 55,
			'images_thumb_width' => 73,
			'posts_thumb_height' => 55,
			'posts_thumb_width' => 73,
			'featured_post_thumb_height' => 55,
			'featured_post_thumb_width' => 73,
			'limit_post_thumb_height' => 135,
			'limit_post_thumb_width' => 135,
			'comments_avatar_height' => 58,
			'comments_avatar_width' => 58,
			'album_thumb_width' => 64,
			'album_thumb_height' => 51,
			'video_album_thumb_width' => 64,
			'video_album_thumb_height' => 51,
			'audio_thumb_width' => 60,
			'audio_thumb_height' => 60,


			'thumb_size' => 80,
			'use_post_preloading' => TRUE,
			'comments_list_limit' => 20,
			'links_list_limit' => 20,
			'pages_list_limit' => 20,
			'posts_list_limit' => 10,
			'categories_list_limit' => 20,
			'tags_list_limit' => 20,
			'authors_list_limit' => 20,
			'videos_list_limit' => 20,
			'audios_list_limit' => 20,

			'max_thumb_check' => 6,
			'count_minimum_for_appear_in_albums' => 5,
			//'minimum_width_for_appear_in_albums' => 90,
	//        'minimum_height_for_appear_in_albums' => 90,

			// API
			'app_token' => '',
			'app_id' => 0,

			// Theme
			'allow_grouped_lists' => FALSE,
			'zebra_lists' => TRUE,
			'theme_name' => 'iphone',
			'wiziapp_theme_name' => 'default',

			// app
			'app_description' => 'Here you will see the description about your app. You will be able to provide the description in the app store information form (step 3).',
			'app_name' => get_bloginfo('name'),
			'app_icon' => '',
			'version' => '',
			'icon_url' => '/images/app/themes/default/about-placeholder.png',

			// Screens titles
			'categories_title' => 'Categories',
			'tags_title' => 'Tags',
			'albums_title' => 'Albums',
			'videos_title' => 'Videos',
			'audio_title' => 'Audio',
			'links_title' => 'Links',
			'pages_title' => 'Pages',
			'favorites_title' => 'Favorites',
			'about_title' => 'About',
			'search_title' => 'Search Results',
			'archive_title' => 'Archives',

			// General
			'last_recorded_save' => time(),
			'reset_settings_on_uninstall' => TRUE,
			'search_limit' => 50,
			'search_limit_pages' => 20,
			'post_processing_batch_size' => 3,
			'finished_processing' => FALSE,
			'configured' => FALSE,
			'app_live' => FALSE,
			'appstore_url' => '',
			'appstore_url_timeout' => 1, //How many days will pass before we will show the user the "download app from appstore" confirmation alert again, 0 will make it not display at all
			'email_verified' => FALSE,
			'show_email_verified_msg' => TRUE,
			'wiziapp_showed_config_once' => FALSE,
			'wiziapp_log_threshold' => 2, // Initial default level

			// Wiziapp QR Code Widget
			'wiziapp_qrcode_widget_id_base' => 'wiziapp_qr_code',
			'wiziapp_qrcode_widget_name' => 'Wiziapp QR Code',
			'wiziapp_qrcode_widget_decription' => '',
		);

		return array_merge($settings, $envSettings);
	}
}
