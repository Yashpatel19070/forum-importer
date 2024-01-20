<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/Yashpatel19070/
 * @since      1.0.0
 *
 * @package    Forum_Importer
 * @subpackage Forum_Importer/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Forum_Importer
 * @subpackage Forum_Importer/public
 * @author     Yash Patel <yash19070@gmail.com>
 */
class Forum_Importer_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Forum_Importer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Forum_Importer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		// wp_enqueue_style( $this->plugin_name . '-bootstrap', plugin_dir_url( __FILE__ ) . 'css/lib/bootstrap.min.css', array(), $this->version, '' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/forum-importer-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Forum_Importer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Forum_Importer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// wp_enqueue_script( $this->plugin_name . '-bootstrap', plugin_dir_url( __FILE__ ) . 'js/lib/bootstrap.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/forum-importer-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'FI,Obj_Public_JS', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}

	/**
	 * Function for include single post template
	 */
	public function fi_template_include_callback( $template ) {
		global $post;

		if ( 'forum' === $post->post_type || 'topic' === $post->post_type ) {
			$template = plugin_dir_path( __FILE__ ) . 'templates/bbpress.php';
		}

		return $template;
	}

	/**
	 * Function for add modal html in footer.
	 */
	public function fi_wp_footer_callback() {
		?>
		<!-- The Modal -->
		<div id="imageEnlargeModal" class="modal" style="display:none;">
			<span class="close">&times;</span>
			<img class="modal-content" id="enlargeImg">
			<div id="caption"></div>
		</div>
		<?php
	}

	/**
	 * Ajax callback fumction for enlarge images.
	 */
	public function fi_enlarge_images_callback() {
		$image_id = filter_input( INPUT_POST, 'image_id', FILTER_SANITIZE_STRING );
		$img_src  = filter_input( INPUT_POST, 'imgsrc', FILTER_SANITIZE_STRING );
		if ( ! empty( $image_id ) ) {
			$imageid = $image_id;
		} else {
			$imageid = attachment_url_to_postid( $img_src );
		}
		if ( $image_id > 0 ) {
			$image_array = wp_get_attachment_image_src( $imageid, 'full' );
			$main_img    = $image_array[0];
		} else {
			$main_img = $img_src;
		}

		wp_send_json_success(
			array(
				'code' => 'success',
				'html' => $main_img,
			)
		);
		wp_die();
	}
}
