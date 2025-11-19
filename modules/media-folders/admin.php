<?php
/**
 * Media Folders Admin Page - Debug Dashboard
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get debug logs.
$debug_logs = AM_Media_Folders::get_debug_logs();
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Media Folders - Debug Dashboard', 'admin-manager' ); ?></h1>

	<div class="am-debug-dashboard">
		<h2><?php esc_html_e( 'System Information', 'admin-manager' ); ?></h2>

		<table class="widefat">
			<tr>
				<th><?php esc_html_e( 'Approach', 'admin-manager' ); ?></th>
				<td>REST API + Post Meta (Modern)</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Folder Storage', 'admin-manager' ); ?></th>
				<td>Custom Post Type (<?php echo esc_html( AM_Media_Folders::POST_TYPE ); ?>)</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Assignment Method', 'admin-manager' ); ?></th>
				<td>Post Meta (<?php echo esc_html( AM_Media_Folders::META_KEY ); ?>)</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Filtering Method', 'admin-manager' ); ?></th>
				<td>meta_query (AJAX) + SQL JOIN (List View)</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'REST API Endpoint', 'admin-manager' ); ?></th>
				<td><?php echo esc_url( rest_url( 'am/v1/folders' ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Total Folders', 'admin-manager' ); ?></th>
				<td>
					<?php
					$folder_count = wp_count_posts( AM_Media_Folders::POST_TYPE );
					echo esc_html( $folder_count->publish );
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Files in Folders', 'admin-manager' ); ?></th>
				<td>
					<?php
					global $wpdb;
					$assigned_count = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s",
							AM_Media_Folders::META_KEY
						)
					);
					echo esc_html( $assigned_count );
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Uncategorized Files', 'admin-manager' ); ?></th>
				<td>
					<?php
					$total_attachments = wp_count_posts( 'attachment' );
					$total = $total_attachments->inherit;
					$uncategorized = $total - $assigned_count;
					echo esc_html( $uncategorized );
					?>
				</td>
			</tr>
		</table>

		<h2 style="margin-top: 30px;"><?php esc_html_e( 'Debug Logs', 'admin-manager' ); ?></h2>

		<div style="margin-bottom: 15px;">
			<button type="button" id="am-refresh-debug-logs" class="button">
				<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Refresh Logs', 'admin-manager' ); ?>
			</button>
			<button type="button" id="am-copy-debug-logs" class="button">
				<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy All', 'admin-manager' ); ?>
			</button>
			<button type="button" id="am-clear-debug-logs" class="button">
				<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Clear Logs', 'admin-manager' ); ?>
			</button>
		</div>

		<div id="am-debug-logs-container">
			<?php if ( empty( $debug_logs ) ) : ?>
				<p><?php esc_html_e( 'No debug logs yet. Logs will appear here when you interact with media folders.', 'admin-manager' ); ?></p>
			<?php else : ?>
				<?php foreach ( array_reverse( $debug_logs ) as $log ) : ?>
					<div class="log-<?php echo esc_attr( $log['type'] ); ?>">
						<strong><?php echo esc_html( strtoupper( $log['type'] ) ); ?></strong>
						<span class="log-time"><?php echo esc_html( $log['time'] ); ?></span>
						<div><?php echo esc_html( $log['message'] ); ?></div>
						<?php if ( ! empty( $log['data'] ) ) : ?>
							<details>
								<summary><?php esc_html_e( 'View Data', 'admin-manager' ); ?></summary>
								<pre><?php echo esc_html( print_r( $log['data'], true ) ); ?></pre>
							</details>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Refresh logs
	$('#am-refresh-debug-logs').on('click', function() {
		location.reload();
	});

	// Copy all logs
	$('#am-copy-debug-logs').on('click', function() {
		let logs = '';
		$('#am-debug-logs-container > div').each(function() {
			const type = $(this).find('strong').first().text();
			const time = $(this).find('.log-time').first().text();
			const message = $(this).find('div').first().text();
			const details = $(this).find('pre').text();

			logs += type + ' | ' + time + ' | ' + message + '\n';
			if (details) {
				logs += 'DATA:\n' + details + '\n';
			}
			logs += '---\n';
		});

		navigator.clipboard.writeText(logs).then(function() {
			alert('Logs copied to clipboard!');
		});
	});

	// Clear logs
	$('#am-clear-debug-logs').on('click', function() {
		if (!confirm('Are you sure you want to clear all debug logs?')) {
			return;
		}

		$.post(ajaxurl, {
			action: 'am_clear_debug',
			nonce: '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
		}, function(response) {
			if (response.success) {
				location.reload();
			} else {
				alert('Failed to clear logs');
			}
		});
	});
});
</script>
