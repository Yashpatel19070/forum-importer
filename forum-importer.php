<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/Yashpatel19070/
 * @since             1.0.0
 * @package           Forum_Importer
 *
 * @wordpress-plugin
 * Plugin Name:       Forum Importer
 * Plugin URI:        https://github.com/Yashpatel19070/
 * Description:       This plugin helps you to import forums in WordPress site from Drupal site.
 * Version:           1.0.0
 * Author:            Yash Patel
 * Author URI:        https://github.com/Yashpatel19070/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       forum-importer
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'FORUM_IMPORTER_VERSION', '1.0.0' );

error_reporting(E_ALL);
ini_set('display_errors', '1'); 

// Log file path.
if ( ! defined( 'FI_LOG_DIR_PATH' ) ) {
	$uploads_dir = wp_upload_dir();
	define( 'FI_LOG_DIR_PATH', $uploads_dir['basedir'] . '/forum-import-log/' );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-forum-importer-activator.php
 */
function activate_forum_importer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-forum-importer-activator.php';
	Forum_Importer_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-forum-importer-deactivator.php
 */
function deactivate_forum_importer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-forum-importer-deactivator.php';
	Forum_Importer_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_forum_importer' );
register_deactivation_hook( __FILE__, 'deactivate_forum_importer' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-forum-importer.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_forum_importer() {

	$plugin = new Forum_Importer();
	$plugin->run();

}
run_forum_importer();

/**
 * Debugger function which shall be removed in production.
 */
if ( ! function_exists( 'debug' ) ) {
	/**
	 * Debug function definition.
	 *
	 * @param string $params Holds the variable name.
	 */
	function debug( $params ) {
		echo '<pre>';
		// phpcs:disable WordPress.PHP.DevelopmentFunctions
		print_r( $params );
		// phpcs:enable
		echo '</pre>';
	}
}
// debug($_SERVER);

add_action( 'wp', function() {
	return;
	if ( 'yash_del' !== $_SERVER['REMOTE_ADDR'] ) {
		return;
	}

	// Return, if it's the admin screen.
	if ( is_admin() ) {
		return;
	}
	//delete_replies();
	//delete_topics();
	delete_posts_and_media();

	
} );

function delete_replies() {
	$replies = get_posts(
		array(
			'post_type'      => 'reply',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	if ( empty( $replies ) || ! is_array( $replies ) ) {
		return;
	}

	foreach ( $replies as $reply_id ) {
		$reply_images = get_attached_media( 'image', $reply_id );
		$reply_video  = get_attached_media( 'video', $reply_id );
		if ( ! empty( $reply_images ) ) {
			foreach( $reply_images as $image ) {
				wp_delete_attachment( $image->ID, true );
			}
		}
		if ( ! empty( $reply_video ) ) {
			foreach( $reply_video as $video ) {
				wp_delete_attachment( $video->ID, true );
			}
		}
		wp_delete_post( $reply_id, true );
	}

	wp_die( "all replies deleted" );
}

function delete_topics() {
	$topics = get_posts(
		array(
			'post_type'      => 'topic',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	if ( empty( $topics ) || ! is_array( $topics ) ) {
		return;
	}

	foreach ( $topics as $topic_id ) {
		$topic_images = get_attached_media( 'image', $topic_id );
		$topic_video  = get_attached_media( 'video', $topic_id );

		if ( ! empty( $topic_images ) ) {
			foreach( $topic_images as $image ) {
				wp_delete_attachment( $image->ID, true );
			}
		}
		if ( ! empty( $topic_video ) ) {
			foreach( $topic_video as $video ) {
				wp_delete_attachment( $video->ID, true );
			}
		}
		wp_delete_post( $topic_id, true );
	}

	wp_die( "all topics deleted" );
}
function delete_posts_and_media() {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	if ( empty( $posts ) || ! is_array( $posts ) ) {
		return;
	}

	foreach ( $posts as $post_id ) {
		$post_images = get_attached_media( 'image', $post_id );
		$post_videos = get_attached_media( 'video', $post_id );

		if ( ! empty( $post_images ) ) {
			foreach ( $post_images as $image ) {
				wp_delete_attachment( $image->ID, true );
			}
		}

		if ( ! empty( $post_videos ) ) {
			foreach ( $post_videos as $video ) {
				wp_delete_attachment( $video->ID, true );
			}
		}

		wp_delete_post( $post_id, true );
	}

	wp_die( 'All posts and related media deleted.' );
}

