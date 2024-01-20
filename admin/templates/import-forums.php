<?php
/**
 * This file is used for templating the forum importing form drupal database
 *
 * @package    Forum_Importer
 * @subpackage Forum_Importer/admin/templates
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
delete_transient( 'sonopath_forum_items' );
// Fetch the products data from the transient.
$wp_forum_id = filter_input( INPUT_POST, 'fi_select_forum', FILTER_SANITIZE_NUMBER_INT );
$wp_forum_id = ( ! empty( $wp_forum_id ) ) ? $wp_forum_id : '';
$forums = get_transient( 'sonopath_forum_items' );

// See if there are products in the transient.
if ( false === $forums || empty( $forums ) ) {
	$forums = sonopath_fetch_forums(); // Shoot the API to get products.

	/**
	 * Store the response data in a cookie.
	 * This cookie data will be used to import the products in the database.
	 */
	if ( false !== $forums ) {
		set_transient( 'sonopath_forum_items', wp_json_encode( $forums ), ( 60 * 60 * 12 ) );
	}
} else {
	// If you're here, the data is already in transients.
	$forums = json_decode( $forums, true );
}

// Get the count of the products.
$total_forums = count( $forums );

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Import Forums', 'forum-importer' ); ?></h1>
	<section class="forum-importer-wrapper import_progress_section">
		<div class="card importing-card">
			<h2 class="heading"><?php esc_html_e( 'Importing', 'forum-importer' ); ?></h2>
			<input type="hidden" id="wp_forum_id" value="<?php echo esc_attr( $wp_forum_id ); ?>">
			<p class="importing-notice">
				<?php
				if ( empty( $wp_forum_id ) ) {
					echo wp_kses_post(
						sprintf(
							/* translators: 1: %s: span tag, 2: %s: span tag, 3: %s: span tag closed, 4: %d: total products count */
							__(
								'You are not selected any forum. Please go back and select forum.',
								'forum-importer'
							),
						)
					);
					?>
					<p class="submit"><a href="<?php echo admin_url( 'admin.php?page=select-forums-for-import' ); ?>" class="button button-primary">Go Back</a></p>
					<?php
				} else {
					echo wp_kses_post(
						sprintf(
							/* translators: 1: %s: span tag, 2: %s: span tag, 3: %s: span tag closed, 4: %d: total products count */
							__(
								'Your forums are now being imported... %1$s0%3$s of %2$s%4$s%3$s imported',
								'forum-importer'
							),
							'<span class="imported-count">',
							'<span class="total-forums-count">',
							'</span>',
							$total_forums
						)
					);
				}
				?>
			</p>
			<?php
			if ( ! empty( $wp_forum_id ) ) {
			?>
			<div class="progress-bar-wrapper">
				<progress class="importer-progress" max="100" value="0"></progress>
				<span class="value">0%</span>
			</div>
			<p class="importing-notice"><?php esc_html_e( 'DO NOT close the window until the import is completed.', 'forum-importer' ); ?></p>
			<?php } ?>
		</div>

		<div class="card finish-card" style="display: none;">
			<h2 class="heading"><?php esc_html_e( 'Import Complete!', 'forum-importer' ); ?></h2>
			<div class="importer-done">
				<span class="dashicons dashicons-yes-alt icon"></span>
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: 1: %s: total forums count, 2: %s: strong tag open, 3: %s: strong tag closed, 4: %s: span tag, 5: %s: span tag, 6: %s: span tag, 7: %s: span tag closed */
							__(
								'%1$s forums imported. New forums: %2$s%4$s%7$s%3$s Updated forums: %2$s%5$s%7$s%3$s Failed forums: %2$s%6$s%7$s%3$s',
								'forum-importer'
							),
							$total_forums,
							'<strong>',
							'</strong>',
							'<span class="new-forums-count">',
							'<span class="old-forums-updated-count">',
							'<span class="failed-forums-count">',
							'</span>'
						)
					);
					?>
				</p>
			</div>
			<div class="wc-actions text-right">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=forum' ) ); ?>"><?php esc_html_e( 'View Forums', 'forum-importer' ); ?></a>
			</div>
		</div>
	</section>
</div>
