<?php
/**
 * Media Folders - Admin Page
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get folder statistics.
$folder_count = wp_count_terms( array( 'taxonomy' => 'media_folder' ) );
$uncategorized_count = 0;

$uncategorized_query = new WP_Query(
	array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => 'media_folder',
				'operator' => 'NOT EXISTS',
			),
		),
	)
);

if ( $uncategorized_query->have_posts() ) {
	$uncategorized_count = $uncategorized_query->post_count;
}
wp_reset_postdata();
?>

<div class="wrap am-admin-wrap">
	<h1><?php esc_html_e( 'Media Folders', 'admin-manager' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Organize your WordPress media library with virtual folders. Drag and drop media files between folders directly in the media library.', 'admin-manager' ); ?>
	</p>

	<div class="am-media-folders-info" style="margin-top: 30px;">
		<h2><?php esc_html_e( 'How to Use', 'admin-manager' ); ?></h2>

		<ol style="line-height: 1.8; font-size: 14px;">
			<li><?php esc_html_e( 'Go to Media > Library to see the folder sidebar', 'admin-manager' ); ?></li>
			<li><?php esc_html_e( 'Click the "+ Create Folder" button to create a new folder', 'admin-manager' ); ?></li>
			<li><?php esc_html_e( 'Drag and drop media files onto folders to organize them', 'admin-manager' ); ?></li>
			<li><?php esc_html_e( 'Create subfolders by clicking the "+" icon next to a folder (up to 4 levels)', 'admin-manager' ); ?></li>
			<li><?php esc_html_e( 'Use the rename and delete icons to manage your folders', 'admin-manager' ); ?></li>
		</ol>

		<div class="notice notice-info inline" style="margin-top: 20px;">
			<p><strong><?php esc_html_e( 'Note:', 'admin-manager' ); ?></strong> <?php esc_html_e( 'Folders are virtual - they organize your media without moving files. Deleting a folder will not delete your media files.', 'admin-manager' ); ?></p>
		</div>
	</div>

	<hr>

	<h2><?php esc_html_e( 'Statistics', 'admin-manager' ); ?></h2>

	<div class="am-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
		<div class="am-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
			<div style="font-size: 32px; font-weight: 700; color: #2196f3; margin-bottom: 8px;"><?php echo esc_html( $folder_count ); ?></div>
			<div style="font-size: 14px; color: #666;"><?php esc_html_e( 'Total Folders', 'admin-manager' ); ?></div>
		</div>

		<div class="am-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
			<div style="font-size: 32px; font-weight: 700; color: #ff9800; margin-bottom: 8px;"><?php echo esc_html( $uncategorized_count ); ?></div>
			<div style="font-size: 14px; color: #666;"><?php esc_html_e( 'Uncategorized Media', 'admin-manager' ); ?></div>
		</div>

		<div class="am-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
			<div style="font-size: 32px; font-weight: 700; color: #4caf50; margin-bottom: 8px;">4</div>
			<div style="font-size: 14px; color: #666;"><?php esc_html_e( 'Maximum Depth Levels', 'admin-manager' ); ?></div>
		</div>
	</div>

	<hr>

	<h2><?php esc_html_e( 'Features', 'admin-manager' ); ?></h2>

	<ul class="ul-disc" style="line-height: 1.8; font-size: 14px; margin-left: 20px;">
		<li><strong><?php esc_html_e( 'Virtual Organization:', 'admin-manager' ); ?></strong> <?php esc_html_e( 'Folders are metadata-based, not physical directories', 'admin-manager' ); ?></li>
		<li><strong><?php esc_html_e( 'Hierarchical Structure:', 'admin-manager' ); ?></strong> <?php esc_html_e( 'Create nested folders up to 4 levels deep', 'admin-manager' ); ?></li>
		<li><strong><?php esc_html_e( 'Drag & Drop:', 'admin-manager' ); ?></strong> <?php esc_html_e( 'Move media files by dragging them onto folders', 'admin-manager' ); ?></li>
		<li><strong><?php esc_html_e( 'Collapsible Tree:', 'admin-manager' ); ?></strong> <?php esc_html_e( 'Expand/collapse folder branches for better navigation', 'admin-manager' ); ?></li>
		<li><strong><?php esc_html_e( 'Non-Destructive:', 'admin-manager' ); ?></strong> <?php esc_html_e( 'Deleting folders never deletes your media files', 'admin-manager' ); ?></li>
		<li><strong><?php esc_html_e( 'Bulk Operations:', 'admin-manager' ); ?></strong> <?php esc_html_e( 'Select multiple media items and move them together', 'admin-manager' ); ?></li>
		<li><strong><?php esc_html_e( 'Filter by Folder:', 'admin-manager' ); ?></strong> <?php esc_html_e( 'Click a folder to show only media in that folder', 'admin-manager' ); ?></li>
		<li><strong><?php esc_html_e( 'Performance Optimized:', 'admin-manager' ); ?></strong> <?php esc_html_e( 'Uses WordPress object caching and lazy loading', 'admin-manager' ); ?></li>
	</ul>

	<hr>

	<h2><?php esc_html_e( 'Folder Settings', 'admin-manager' ); ?></h2>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'am_media_folders_settings' );
		do_settings_sections( 'am_media_folders_settings' );
		?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="am_folders_max_depth"><?php esc_html_e( 'Maximum Folder Depth', 'admin-manager' ); ?></label>
					</th>
					<td>
						<select name="am_folders_max_depth" id="am_folders_max_depth">
							<?php
							$max_depth = get_option( 'am_folders_max_depth', 4 );
							for ( $i = 1; $i <= 10; $i++ ) :
								?>
								<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $max_depth, $i ); ?>>
									<?php echo esc_html( $i ); ?> <?php echo esc_html( _n( 'Level', 'Levels', $i, 'admin-manager' ) ); ?>
								</option>
							<?php endfor; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Maximum number of nested folder levels (default: 4)', 'admin-manager' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="am_folders_enable_drag_drop"><?php esc_html_e( 'Drag & Drop', 'admin-manager' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="am_folders_enable_drag_drop" id="am_folders_enable_drag_drop" value="1" <?php checked( get_option( 'am_folders_enable_drag_drop', 1 ) ); ?>>
							<?php esc_html_e( 'Enable drag and drop for media files', 'admin-manager' ); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="am_folders_show_count"><?php esc_html_e( 'Show File Count', 'admin-manager' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="am_folders_show_count" id="am_folders_show_count" value="1" <?php checked( get_option( 'am_folders_show_count', 1 ) ); ?>>
							<?php esc_html_e( 'Display file count badges on folders', 'admin-manager' ); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="am_folders_default_collapsed"><?php esc_html_e( 'Default Folder State', 'admin-manager' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="am_folders_default_collapsed" id="am_folders_default_collapsed" value="1" <?php checked( get_option( 'am_folders_default_collapsed', 1 ) ); ?>>
							<?php esc_html_e( 'Collapse folders by default (expand manually)', 'admin-manager' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'When enabled, all folders will be collapsed on load unless previously expanded', 'admin-manager' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="am_folders_remember_state"><?php esc_html_e( 'Remember Folder State', 'admin-manager' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="am_folders_remember_state" id="am_folders_remember_state" value="1" <?php checked( get_option( 'am_folders_remember_state', 1 ) ); ?>>
							<?php esc_html_e( 'Remember current folder and expanded state between sessions', 'admin-manager' ); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="am_folders_enable_bulk_move"><?php esc_html_e( 'Bulk Move Actions', 'admin-manager' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="am_folders_enable_bulk_move" id="am_folders_enable_bulk_move" value="1" <?php checked( get_option( 'am_folders_enable_bulk_move', 1 ) ); ?>>
							<?php esc_html_e( 'Enable "Move to Folder" in bulk actions dropdown', 'admin-manager' ); ?>
						</label>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( __( 'Save Settings', 'admin-manager' ) ); ?>
	</form>

	<hr>

	<div class="am-quick-links">
		<h2><?php esc_html_e( 'Quick Links', 'admin-manager' ); ?></h2>
		<p>
			<a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Go to Media Library', 'admin-manager' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'media-new.php' ) ); ?>" class="button">
				<?php esc_html_e( 'Upload New Media', 'admin-manager' ); ?>
			</a>
		</p>
	</div>
</div>
