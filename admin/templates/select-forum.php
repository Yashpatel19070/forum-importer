<?php
/**
 * This file is used for templating the forum importing form drupal database
 *
 * @package    Forum_Importer
 * @subpackage Forum_Importer/admin/templates
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$wp_forums = get_posts(
	array(
		'post_type'      => 'forum',
		'post_status'    => 'publish',
		'posts_per_page' => -1
	)
);
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Import Forums', 'forum-importer' ); ?></h1>
	<section class="forum-importer-wrapper main-forum-section">
		<div class="card importing-card">
			<h2 class="heading"><?php esc_html_e( 'Select Forum', 'forum-importer' ); ?></h2>
			<form id="importForum" action="<?php echo admin_url( 'admin.php?page=import-forums-from-drupal-database' ); ?>" method="post">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="blogname">Select Forum For Import Topic From Drupal Database</label></th>
							<td>
								<select name="fi_select_forum" required>
									<option>Select</option>
									<?php
									if ( ! empty( $wp_forums ) ) {
										foreach( $wp_forums as $wp_forum ) {
											echo "<option value='{$wp_forum->ID}'>{$wp_forum->post_title}</option>";
										}
									}
									?>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Start Import"></p>
			</form>
		</div>
	</section>
</div>
