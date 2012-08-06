<?php

class WiziappSettingMetabox {

	private $_plugin_dir_url;

	public function __construct() {
		$this->_plugin_dir_url = plugins_url( dirname( WP_WIZIAPP_BASE ) );
	}

	/**
	* Trigger of the hooks on the Site Front End.
	*
	* @return void
	*/
	public function site_init() {
		add_action( 'pre_get_posts', array( &$this, 'exclude_posts' ) );
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
		add_action( 'admin_print_scripts-media-upload-popup', array( &$this, 'set_loader_source' ) );
		if ( strpos($_SERVER['REQUEST_URI'], 'async-upload.php') !== FALSE && isset($_POST['attachment_id']) && isset($_POST['fetch']) ) {
			// Add Javascript with the trigger to insert "Use as Post Thumbnail" link to "Upload Image" WP iframe
			add_action( 'shutdown', array( &$this, 'trigger_link_adding' ) );
		}
		// Save the data entered in "Wiziapp Metabox" on save post
		add_action( 'edit_post', array( &$this, 'save_wiziapp_postdata' ), 10, 2 );
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

	/**
	* When the post is saved, saves our Wiziapp Data
	*/
	public function save_wiziapp_postdata( $post_id, $post ) {
		if ( ! ( is_object( $post ) && $post->post_type === 'post' && isset( $_POST['wiziapp_setting_metabox'] ) ) ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			// If the Post is a revision
			return;
		}

		WiziappDB::getInstance()->update_element_exclusion( array( 'ID' => $post_id ) );

		if ( isset( $_POST['is_featured_post'] ) && $post->post_status === 'publish' ) {
			if ( get_option( 'wiziapp_featured_post' ) ) {
				update_option( 'wiziapp_featured_post', $post_id );
			} else {
				add_option( 'wiziapp_featured_post', $post_id, '', 'no' );
			}
		} else {
			delete_option( 'wiziapp_featured_post' );
		}

		if ( isset( $_POST['wiziapp_is_no_thumbnail']) ) {
			$update_values = array(
				'post_thumbnail'  => 0,
				'is_user_chosen'  => 1,
				'is_no_thumbnail' => 1,
			);
		} else {
			$update_values = array(
				'is_user_chosen'  => intval( isset( $_POST['wiziapp_is_user_chosen'] ) ),
				'is_no_thumbnail' => 0
			);
		}

		$this->_uptate_metabox_setting( $post_id, $update_values );
	}

	public function exclude_posts($query_request) {
		$excluded_posts_ids = WiziappDB::getInstance()->set_exclude_query();
                $currentExcluded = $query_request->get('post__not_in');
                $excluded_posts_ids = array_merge($excluded_posts_ids,$currentExcluded);
		$query_request->set( 'post__not_in', $excluded_posts_ids );

		return $query_request;
	}

	public function save_push_message() {
		$error_message = FALSE;
		if ( ! isset( $_POST['post_id'] ) || ! intval($_POST['post_id']) > 0 ) {
			$error_message = 'Internal not clear error.';
		}
		if ( ! isset( $_POST['push_message'] ) || ! preg_match('/^[a-z\d\!\.\,\s]{10,105}$/i', $_POST['push_message']) ) {
			$error_message = 'Improper character inserted. Allowed alphanumeric characters only.';
		}
		if ($error_message) {
			echo json_encode(array(
					'status' => FALSE,
					'message' => $error_message,
				));
			exit;
		}

		if ( $this->_uptate_metabox_setting( $_POST['post_id'], array( 'push_message' => $_POST['push_message'] ) ) ) {
			echo json_encode(array(
					'status' => TRUE,
					'message' => 'Saved successfuly.',
				));
		} else {
			echo json_encode(array(
					'status' => FALSE,
					'message' => 'Updating Post Meta error.',
				));
		}
		exit;
	}

	public function use_post_thumbnail() {
		$update_values = array(
			'post_thumbnail'  => intval($_POST['image_id']),
			'is_user_chosen'  => 1,
			'is_no_thumbnail' => 0,
		);

		if ( $this->_uptate_metabox_setting( $_POST['post_id'], $update_values ) ) {
			// Erase old Thumbnail file from Cache in Admin server, to see proper Thumbnail in Simulator
			$r = new WiziappHTTPRequest();
			$r->api( array( 'src' => sha1(site_url().'/?wiziapp/getthumb/'.intval($_POST['post_id']).'&type=posts_thumb'), ), '/simulator/eraseCache' );

			echo json_encode(array(
					'status' 	=> TRUE,
					'message' 	=> 'Saved successfuly.',
				));
		} else {
			echo json_encode(array(
					'status'  => FALSE,
					'message' => 'Updating Post Meta error.',
				));
		}

		exit;
	}

	public static function get_push_message($post_id) {
		$meta_values = get_post_meta($post_id, 'wiziapp_metabox_setting', TRUE);
		if ( isset( $meta_values['push_message'] ) && $meta_values['push_message'] != '' ) {
			return $meta_values['push_message'];
		} else {
			return WiziappConfig::getInstance()->push_message;
		}
	}

	public static function get_wiziapp_featured_post() {
		$wiziapp_featured_post = get_option( 'wiziapp_featured_post' );
		if ( $wiziapp_featured_post ) {
			if ( is_object ( $post = get_post( intval($wiziapp_featured_post ) ) ) && $post->post_status === 'publish' ) {
				$post_id = $wiziapp_featured_post;
			} else {
				delete_option( 'wiziapp_featured_post' );
				$post_id = '';
			}
		} else {
			$post_id = '';
		}

		return $post_id;
	}

	public function styles_javascripts($hook) {
		$is_request_edit_post =	isset($_GET['post']) && ctype_digit($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'edit';

		if ( ( $hook === 'post.php' && $is_request_edit_post ) || ( $hook === 'post-new.php' ) ) {
			wp_enqueue_script( 'jquery.tools', 'http://cdn.jquerytools.org/1.2.5/all/jquery.tools.min.js' );
			wp_enqueue_style(  'wiziapp_metabox', $this->_plugin_dir_url . '/themes/admin/wiziapp_metabox.css' );
			wp_enqueue_script( 'wiziapp_metabox', $this->_plugin_dir_url . '/themes/admin/wiziapp_metabox.js', array('jquery.tools') );
		} elseif ($hook === 'media-upload-popup') {
			wp_enqueue_script( 'wiziapp_metabox', $this->_plugin_dir_url . '/themes/admin/media_upload_popup.js', array('jquery') );
		}
	}

	public function set_loader_source() {
	?>
	<script type="text/javascript">
		wiziapp_ajax_loader_source = "<?php echo $this->_plugin_dir_url; ?>/themes/admin/ajax_loader.gif";
	</script>
	<?php
	}

	public function trigger_link_adding() {
	?>
	<script type="text/javascript">
		/* <![CDATA[ */
		jQuery(document).trigger('wiziapp_upload_finished');
		/* ]]> */
	</script>
	<?php
	}

	/**
	* Prints the box content
	* @param Object $post
	*/
	public function setting_box_view($post) {
		if ( ! ( is_object($post) && property_exists($post, 'post_type') && $post->post_type === 'post' ) ) {
			return;
		}

		$metabox_setting = get_post_meta( $post->ID, 'wiziapp_metabox_setting', TRUE);
		$is_new_post = ( $post->post_status === 'auto-draft' ) && ( strpos($_SERVER['REQUEST_URI'], 'post-new.php') !== FALSE );

		$thumbnail_image = $thumbnail_div_style = '';
		if ( isset( $metabox_setting['post_thumbnail'] ) && $metabox_setting['post_thumbnail'] ) {
			$thumbnail_src = wp_get_attachment_url( intval( $metabox_setting['post_thumbnail'] ) );
			if ( $thumbnail_src && $thumbnail_src != '' ) {
				$thumbnail_image = '<img src="' . $thumbnail_src . '" width="85" />' . PHP_EOL;
			}
		} else {
			$thumbnail_image = '<img src="' . site_url() . '/?wiziapp/getthumb/' . $post->ID . '&type=posts_thumb" width="85" />' . PHP_EOL;
		}

		$push_message = self::get_push_message($post->ID);

		$checked_array = array(
			'wizi_included_site'  => ( intval( $post->wizi_included_site )  || $is_new_post )  ? ' checked="checked"' : '',
			'wizi_included_app'   => ( intval( $post->wizi_included_app )   || $is_new_post )  ? ' checked="checked"' : '',
			'wizi_published_push' => ( intval( $post->wizi_published_push ) || $is_new_post )  ? ' checked="checked"' : '',
			//'wizi_updated_push'   => ( intval( $post->wizi_updated_push )   || $is_new_post )  ? ' checked="checked"' : '',

			'wiziapp_featured_post' => ( self::get_wiziapp_featured_post() === $post->ID ) ? ' checked="checked"' : '',

			'wiziapp_is_user_chosen'  => ( isset( $metabox_setting['is_user_chosen']  ) && intval( $metabox_setting['is_user_chosen']  ) ) ? ' checked="checked"' : '',
			'wiziapp_is_no_thumbnail' => ( isset( $metabox_setting['is_no_thumbnail'] ) && intval( $metabox_setting['is_no_thumbnail'] ) ) ? ' checked="checked"' : '',
		);
		$path_to_view = realpath( dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'admin' );
		require $path_to_view.DIRECTORY_SEPARATOR.'setting_metabox.php';
	}

	private function _uptate_metabox_setting($post_id, array $data ) {
		$post_id = intval($post_id);
		$metabox_setting = get_post_meta( $post_id, 'wiziapp_metabox_setting', TRUE);

		foreach ( $data as $key => $value ) {
			if ( isset($metabox_setting[$key]) && $metabox_setting[$key] === $value ) {
				continue;
			}

			$metabox_setting[$key] = $value;
		}

		return update_post_meta( $post_id, 'wiziapp_metabox_setting', $metabox_setting );
	}

}