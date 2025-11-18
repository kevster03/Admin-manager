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

// Get all folders.
$folders = get_terms(
	array(
		'taxonomy'   => 'am_media_folder',
		'hide_empty' => false,
	)
);
?>

<div class="wrap am-admin-wrap">
	<h1><?php esc_html_e( 'Virtual Media Folders', 'admin-manager' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Organize your media library with virtual folders. Files stay in the same location - folders are for organization only.', 'admin-manager' ); ?>
	</p>

	<div class="am-notice am-notice-info">
		<p>
			<strong><?php esc_html_e( 'How it works:', 'admin-manager' ); ?></strong>
			<?php esc_html_e( 'Folders are virtual and taxonomy-based. File URLs never change. Go to Media Library to create folders and organize files.', 'admin-manager' ); ?>
		</p>
	</div>

	<h2><?php esc_html_e( 'Existing Folders', 'admin-manager' ); ?></h2>

	<?php if ( ! empty( $folders ) && ! is_wp_error( $folders ) ) : ?>
		<table class="widefat am-settings-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Folder Name', 'admin-manager' ); ?></th>
					<th><?php esc_html_e( 'Files', 'admin-manager' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'admin-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $folders as $folder ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $folder->name ); ?></strong></td>
						<td><?php echo esc_html( $folder->count ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'upload.php?am_media_folder=' . $folder->term_id ) ); ?>" class="button button-small">
								<?php esc_html_e( 'View Files', 'admin-manager' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><em><?php esc_html_e( 'No folders created yet.', 'admin-manager' ); ?></em></p>
	<?php endif; ?>

	<hr>

	<h2><?php esc_html_e( 'Getting Started', 'admin-manager' ); ?></h2>
	<ol style="line-height: 2;">
		<li><?php esc_html_e( 'Go to Media → Library', 'admin-manager' ); ?></li>
		<li><?php esc_html_e( 'Click "Add New Folder" button (appears above the media grid)', 'admin-manager' ); ?></li>
		<li><?php esc_html_e( 'Use the folder dropdown to filter media by folder', 'admin-manager' ); ?></li>
		<li><?php esc_html_e( 'Assign files to folders using the bulk actions or individual file settings', 'admin-manager' ); ?></li>
	</ol>

	<p>
		<a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>" class="button button-primary">
			<?php esc_html_e( 'Go to Media Library', 'admin-manager' ); ?>
		</a>
	</p>

	<hr>

	<h2><?php esc_html_e( 'Important Notes', 'admin-manager' ); ?></h2>
	<ul style="list-style: disc; padding-left: 25px;">
		<li><?php esc_html_e( 'Folders are virtual - they don\'t change file URLs or physical locations', 'admin-manager' ); ?></li>
		<li><?php esc_html_e( 'Deleting a folder does NOT delete the files inside', 'admin-manager' ); ?></li>
		<li><?php esc_html_e( 'Files can only be in one folder at a time', 'admin-manager' ); ?></li>
		<li><?php esc_html_e( 'Folder organization is for admin convenience only - doesn\'t affect frontend', 'admin-manager' ); ?></li>
	</ul>
</div>
