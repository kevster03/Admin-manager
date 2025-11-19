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
