<?php

class WiziappSettingMetabox {

	private $_plugin_dir_url;

	public function __construct() {
		$this->_plugin_dir_url = plugins_url( dirname( WP_WIZIAPP_BASE ) );
	}

	/**
	* Trigger of the hooks on the Site Back End.
	*
	* @return void
	*/
	public function admin_init() {
		// Define the Wiziapp side metabox
		add_action( 'add_meta_boxes', array( &$this, 'add_setting_box' ) );
		// Add Javascripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'styles_javascripts' ) );
		// Save the Push Notification message entered in "Wiziapp Metabox" on the Publish post
		add_action( 'save_post', array( &$this, 'save_push_message' ), 10, 2 );
	}

	/**
	* Adds a box to the main column on the Post edit screens
	*/
	public function add_setting_box() {
		add_meta_box(
			'wiziapp_setting_box',
			'WiziApp',
			array( &$this, 'setting_box_view' ),
			'post',
			'side'
		);
	}

	public function save_push_message( $post_id, $post ) {
		$push_message = isset($_POST['wiziapp_push_message']) ? $_POST['wiziapp_push_message'] : '';
		if ( function_exists('mb_strlen') ) {
			if ( mb_strlen($push_message) < 5 || mb_strlen($push_message) > 105 ) {
				return;
			}
		} else {
			if ( strlen($push_message) < 5 || strlen($push_message) > 105 ) {
				return;
			}
		}

		$not_proper_condition =
		! ( is_object( $post ) && $post->post_type === 'post' ) ||
		// If the Post is a revision
		wp_is_post_revision( $post_id ) ||
		self::get_push_message($post_id) === $push_message;
		if ( $not_proper_condition ){
			return;
		}

		update_post_meta( $post_id, 'wiziapp_push_message', $push_message );
	}

	public static function get_push_message($post_id) {
		$wiziapp_push_message = get_post_meta($post_id, 'wiziapp_push_message', TRUE);

		if ( $wiziapp_push_message && $wiziapp_push_message != '' ) {
			return $wiziapp_push_message;
		} else {
			return WiziappConfig::getInstance()->push_message;
		}
	}

	public function styles_javascripts($hook) {
		$is_request_edit_post =	isset($_GET['post']) && ctype_digit($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'edit';

		if ( ( $hook === 'post.php' && $is_request_edit_post ) || ( $hook === 'post-new.php' ) ) {
			wp_enqueue_style(  'wiziapp_metabox', $this->_plugin_dir_url . '/themes/admin/styles/wiziapp_metabox.css' );
			wp_enqueue_script( 'wiziapp_metabox', $this->_plugin_dir_url . '/themes/admin/scripts/wiziapp_metabox.js' );
		}
	}

	/**
	* Prints the box content
	* @param Object $post
	*/
	public function setting_box_view($post) {
		if ( ! ( is_object($post) && property_exists($post, 'post_type') && $post->post_type === 'post' ) ) {
			return;
		}

		$push_message = self::get_push_message($post->ID);

		$path_to_view = realpath( dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'admin' );
		require $path_to_view.DIRECTORY_SEPARATOR.'setting_metabox.php';
	}
}