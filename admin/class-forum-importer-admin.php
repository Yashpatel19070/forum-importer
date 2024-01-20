<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/Yashpatel19070/
 * @since      1.0.0
 *
 * @package    Forum_Importer
 * @subpackage Forum_Importer/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Forum_Importer
 * @subpackage Forum_Importer/admin
 * @author     Yash Patel <yash19070@gmail.com>
 */
class Forum_Importer_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/forum-importer-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/forum-importer-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script(
			$this->plugin_name,
			'FiAdminJsObj',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Add custom settings page for the plugin.
	 *
	 * @version 1.0.0
	 */
	public function fi_admin_menu_callback() {
		/**
		 * Add submenu to the bbpress menu.
		 * This is for showing the template for the forums select.
		 */
		add_submenu_page(
			null,
			__( 'Select Forums', 'forum-importer' ),
			__( 'Select Forums', 'forum-importer' ),
			'manage_options',
			'select-forums-for-import',
			array( $this, 'fi_select_forums_for_import_callback' )
		);

		/**
		 * Add submenu to the bbpress menu.
		 * This is for showing the template for the forums import.
		 */
		add_submenu_page(
			null,
			__( 'Import Forums', 'forum-importer' ),
			__( 'Import Forums from Drupal Database', 'forum-importer' ),
			'manage_options',
			'import-forums-from-drupal-database',
			array( $this, 'fi_import_forums_from_drupal_database_callback' )
		);
	}

	/**
	 * Forums import with progressbar template.
	 *
	 * @since 1.0.0
	 */
	public function fi_import_forums_from_drupal_database_callback() {
		include 'templates/import-forums.php'; // Include the template for importing products - progressbar.
	}

	/**
	 * Forums import with progressbar template.
	 *
	 * @since 1.0.0
	 */
	public function fi_select_forums_for_import_callback() {
		include 'templates/select-forum.php'; // Include the template for importing products - progressbar.
	}

	/**
	 * Admin head callback function for add button after add new in forum post type.
	 */
	public function admin_head_callback() {
		$import_forum_page_url = admin_url( 'admin.php?page=select-forums-for-import' );
		?>
		<script>
		jQuery(function(){
			jQuery( '<a href="<?php echo esc_url( $import_forum_page_url ); ?>" class="page-title-action">Import from Drupal Database</a>' ).insertAfter('body.post-type-forum .wrap .page-title-action');
		});
		</script>
		<?php
	}

	/**
	 * AJAX to kickoff the forums import.
	 *
	 * @since 1.0.0
	 */
	public function fi_kickoff_forums_import_callback() {
		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );

		// Exit, if the action mismatches.
		if ( empty( $action ) || 'kickoff_forums_import' !== $action ) {
			echo 0;
			wp_die();
		}

		// Posted data.
		$page                 = (int) filter_input( INPUT_POST, 'page', FILTER_SANITIZE_NUMBER_INT );
		$wp_forum_id          = (int) filter_input( INPUT_POST, 'wp_forum_id', FILTER_SANITIZE_NUMBER_INT );
		$new_forums_added     = (int) filter_input( INPUT_POST, 'new_forums_added', FILTER_SANITIZE_NUMBER_INT );
		$old_forums_updated   = (int) filter_input( INPUT_POST, 'old_forums_updated', FILTER_SANITIZE_NUMBER_INT );
		$forums_import_failed = (int) filter_input( INPUT_POST, 'forums_import_failed', FILTER_SANITIZE_NUMBER_INT );
		$chunk_length         = 1;
		
		// Start the import log if the request is for the first time.
		if ( 1 === $page ) {
			// Get the current product count.
			$forums_count  = array_filter( (array) wp_count_posts( 'forum' ) );
			$count_log_arr = array();

			// Prepare a string from the array.
			if ( ! empty( $forums_count ) && is_array( $forums_count ) ) {
				// Iterate through the counts.
				foreach ( $forums_count as $status => $count ) {
					$status          = ucfirst( $status );
					$count_log_arr[] = "{$status}: {$count}";
				}
			}

			/* translators: 1: %d: existing products count */
			$message  = sprintf( __( 'Previous forums count: %1$s', 'forum-importer' ), implode( ', ', $count_log_arr ) );
			$filename = 'import-log-' . gmdate( 'Y-m-d' ) . '-' . md5( time() ) . '.log';
			$log_file = FI_LOG_DIR_PATH . $filename;

			// Set the filename in the cookie to use it during the email.
			setcookie( 'forum_import_log_filename', $filename, time() + ( 10 * 365 * 24 * 60 * 60 ), '/' );

			// Write the log.
			fi_write_import_log( $message, $log_file );
		}

		// Fetch forums.
		$forums       = get_transient( 'sonopath_forum_items' );
		$forums       = json_decode( $forums, true );
		$forums_count = ( ! empty( $forums ) && is_array( $forums ) ) ? count( $forums ) : 0;
		$forums       = ( ! empty( $forums ) && is_array( $forums ) ) ? array_chunk( $forums, $chunk_length, true ) : array(); // Divide the complete data into chunk length products.
		$chunk_index  = $page - 1;
		$chunk        = ( array_key_exists( $chunk_index, $forums ) ) ? $forums[ $chunk_index ] : array();
	
		// Return, if the chunk is empty, means all the products are imported.
		if ( empty( $chunk ) || ! is_array( $chunk ) ) {
			// Get the filename.
			if ( ! empty( $_COOKIE['forum_import_log_filename'] ) ) {
				$filename = sanitize_text_field( wp_unslash( $_COOKIE['forum_import_log_filename'] ) );
				$log_file = FI_LOG_DIR_PATH . $filename;
				/* translators: 1: %d: count of old products updated */
				$message = sprintf( __( 'Updated forums: %1$s', 'forum-importer' ), $old_forums_updated );
				// Update the log for updated products.
				fi_write_import_log( $message, $log_file );

				/* translators: 1: %d: count of new products added */
				$message = sprintf( __( 'Uploaded forums: %1$s', 'forum-importer' ), $new_forums_added );
				// Update the log for newly uploaded products.
				fi_write_import_log( $message, $log_file );
			}

			/**
			 * This hook fires on the admin portal.
			 *
			 * This actions fires when the import process from sonopath is complete.
			 *
			 * @since 1.0.0
			 */
			do_action( 'forum_import_complete' );

			// Sent the final response.
			$response = array(
				'code'                 => 'forums-imported',
				'forums_import_failed' => $forums_import_failed, // Count of the products that failed to import.
				'new_forums_added'     => $new_forums_added, // Count of the products that failed to import.
				'old_forums_updated'   => $old_forums_updated, // Count of the products that failed to import.
			);
			wp_send_json_success( $response );
			wp_die();
		}

		// Iterate through the loop to import the products.
		foreach ( $chunk as $part ) {
			$forum_title = ( ! empty( $part['title'] ) ) ? $part['title'] : '';
			$forum_id    = ( ! empty( $part['nid'] ) ) ? $part['nid'] : '';

			// Skip the update if the product title or part id is missing.
			if ( empty( $forum_title ) || empty( $forum_id ) ) {
				$products_import_failed++; // Increase the count of products import.
				continue;
			}

			// Check if the product exists with the name.
			$forum_exists = fi_forum_exists( $forum_title );

			// If the product doesn't exist.
			if ( false === $forum_exists ) {
				$forum_id = fi_create_forum( $part, $wp_forum_id ); // Create product with forum details.
				$new_forums_added++; // Increase the counter of new product created.
				// Add the import log.
				if ( ! empty( $_COOKIE['forum_import_log_filename'] ) ) {
					$filename = sanitize_text_field( wp_unslash( $_COOKIE['forum_import_log_filename'] ) );
					/* translators: 1: %s: product sku */
					$message = sprintf( __( 'Uploaded Forum: %1$s', 'forum-importer' ), $forum_id );
					fi_write_import_log( $message, FI_LOG_DIR_PATH . $filename );
				}
			} else {
				$forum_id = $forum_exists;
				$old_forums_updated++; // Increase the counter of old product updated.

				// Add the import log.
				if ( ! empty( $_COOKIE['forum_import_log_filename'] ) ) {
					$filename = sanitize_text_field( wp_unslash( $_COOKIE['forum_import_log_filename'] ) );
					/* translators: 1: %s: product sku */
					$message = sprintf( __( 'Updated Forum: %1$s', 'forum-importer' ), $forum_id );
					fi_write_import_log( $message, FI_LOG_DIR_PATH . $filename );
				}
			}
		}

		// Send the AJAX response now.
		$response = array(
			'code'                 => 'forums-import-in-progress',
			'percent'              => ( ( $page * $chunk_length ) / $forums_count ) * 100, // Percent of the forums imported.
			'total'                => $forums_count, // Count of the total forums.
			'imported'             => ( $page * $chunk_length ), // These are the count of forums that are imported.
			'forums_import_failed' => $forums_import_failed, // Count of the forums that failed to import.
			'new_forums_added'     => $new_forums_added, // Count of the forums that failed to import.
			'old_forums_updated'   => $old_forums_updated, // Count of the forums that failed to import.
		);
		wp_send_json_success( $response );
		wp_die();
	}

	/**
	 * This callback is triggered when all the items from forums are imported.
	 *
	 * @since 1.0.0
	 */
	public function fi_forum_import_complete_callback() {
		// Delete the import transient.
		delete_transient( 'sonopath_forum_items' );

		// Nullify the cookies.
		setcookie( 'forum_import_log_filename', null, -1, '/' );
	}

}
