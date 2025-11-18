<?php
/**
 * Reading Time - Admin Page
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle settings save.
if ( isset( $_POST['am_save_reading_time'] ) && am_verify_nonce( 'am_reading_time_nonce', 'am_save_reading_time' ) ) {
	if ( current_user_can( 'manage_options' ) ) {
		$settings = array(
			'enabled_post_types' => isset( $_POST['enabled_post_types'] ) && is_array( $_POST['enabled_post_types'] )
				? array_map( 'sanitize_text_field', $_POST['enabled_post_types'] )
				: array(),
			'words_per_minute'   => isset( $_POST['words_per_minute'] ) ? intval( $_POST['words_per_minute'] ) : 200,
			'position'           => isset( $_POST['position'] ) ? sanitize_text_field( $_POST['position'] ) : 'before',
			'bg_color'           => isset( $_POST['bg_color'] ) ? sanitize_hex_color( $_POST['bg_color'] ) : '#f5f5f5',
			'text_color'         => isset( $_POST['text_color'] ) ? sanitize_hex_color( $_POST['text_color'] ) : '#333333',
			'border_color'       => isset( $_POST['border_color'] ) ? sanitize_hex_color( $_POST['border_color'] ) : '#dddddd',
			'border_width'       => isset( $_POST['border_width'] ) ? absint( $_POST['border_width'] ) : 1,
			'border_radius'      => isset( $_POST['border_radius'] ) ? absint( $_POST['border_radius'] ) : 4,
			'padding'            => isset( $_POST['padding'] ) ? absint( $_POST['padding'] ) : 10,
			'show_icon'          => ! empty( $_POST['show_icon'] ),
		);

		foreach ( $settings as $key => $value ) {
			am_update_module_setting( 'reading-time', $key, $value );
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'admin-manager' ) . '</p></div>';
	}
}

// Get current settings.
$settings = am_get_module_setting( 'reading-time' );
$enabled_post_types = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array();
$wpm = isset( $settings['words_per_minute'] ) ? intval( $settings['words_per_minute'] ) : 200;
$position = isset( $settings['position'] ) ? $settings['position'] : 'before';
$bg_color = isset( $settings['bg_color'] ) ? $settings['bg_color'] : '#f5f5f5';
$text_color = isset( $settings['text_color'] ) ? $settings['text_color'] : '#333333';
$border_color = isset( $settings['border_color'] ) ? $settings['border_color'] : '#dddddd';
$border_width = isset( $settings['border_width'] ) ? $settings['border_width'] : 1;
$border_radius = isset( $settings['border_radius'] ) ? $settings['border_radius'] : 4;
$padding = isset( $settings['padding'] ) ? $settings['padding'] : 10;
$show_icon = ! empty( $settings['show_icon'] );

// Get all post types.
$post_types = get_post_types( array( 'public' => true ), 'objects' );
?>

<div class="wrap am-admin-wrap">
	<h1><?php esc_html_e( 'Reading Time', 'admin-manager' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Display estimated reading time for posts. Use the shortcode [am-reading-time] to display reading time anywhere.', 'admin-manager' ); ?>
	</p>

	<form method="post" action="">
		<?php wp_nonce_field( 'am_save_reading_time', 'am_reading_time_nonce' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Enable For Post Types', 'admin-manager' ); ?>
				</th>
				<td>
					<?php foreach ( $post_types as $post_type ) : ?>
						<label style="display: block; margin-bottom: 8px;">
							<input
								type="checkbox"
								name="enabled_post_types[]"
								value="<?php echo esc_attr( $post_type->name ); ?>"
								<?php checked( in_array( $post_type->name, $enabled_post_types, true ) ); ?>
							>
							<?php echo esc_html( $post_type->label ); ?>
						</label>
					<?php endforeach; ?>
					<p class="description">
						<?php esc_html_e( 'Automatically display reading time on selected post types.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="words_per_minute"><?php esc_html_e( 'Words Per Minute', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						name="words_per_minute"
						id="words_per_minute"
						value="<?php echo esc_attr( $wpm ); ?>"
						min="100"
						max="500"
						class="small-text"
					>
					<p class="description">
						<?php esc_html_e( 'Average reading speed. Default is 200 words per minute.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="position"><?php esc_html_e( 'Position', 'admin-manager' ); ?></label>
				</th>
				<td>
					<select name="position" id="position">
						<option value="before" <?php selected( $position, 'before' ); ?>>
							<?php esc_html_e( 'Before Content', 'admin-manager' ); ?>
						</option>
						<option value="after" <?php selected( $position, 'after' ); ?>>
							<?php esc_html_e( 'After Content', 'admin-manager' ); ?>
						</option>
						<option value="manual" <?php selected( $position, 'manual' ); ?>>
							<?php esc_html_e( 'Manual (shortcode only)', 'admin-manager' ); ?>
						</option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Where to display the reading time automatically.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Appearance', 'admin-manager' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="bg_color"><?php esc_html_e( 'Background Color', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="bg_color"
						id="bg_color"
						value="<?php echo esc_attr( $bg_color ); ?>"
						class="am-color-picker"
					>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="text_color"><?php esc_html_e( 'Text Color', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="text_color"
						id="text_color"
						value="<?php echo esc_attr( $text_color ); ?>"
						class="am-color-picker"
					>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="border_color"><?php esc_html_e( 'Border Color', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="border_color"
						id="border_color"
						value="<?php echo esc_attr( $border_color ); ?>"
						class="am-color-picker"
					>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="border_width"><?php esc_html_e( 'Border Width (px)', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						name="border_width"
						id="border_width"
						value="<?php echo esc_attr( $border_width ); ?>"
						min="0"
						max="10"
						class="small-text"
					>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="border_radius"><?php esc_html_e( 'Border Radius (px)', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						name="border_radius"
						id="border_radius"
						value="<?php echo esc_attr( $border_radius ); ?>"
						min="0"
						max="50"
						class="small-text"
					>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="padding"><?php esc_html_e( 'Padding (px)', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						name="padding"
						id="padding"
						value="<?php echo esc_attr( $padding ); ?>"
						min="0"
						max="50"
						class="small-text"
					>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Display Options', 'admin-manager' ); ?>
				</th>
				<td>
					<label>
						<input type="checkbox" name="show_icon" value="1" <?php checked( $show_icon ); ?>>
						<?php esc_html_e( 'Show clock icon', 'admin-manager' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" name="am_save_reading_time" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'admin-manager' ); ?>
			</button>
		</p>
	</form>

	<hr>

	<h2><?php esc_html_e( 'Usage', 'admin-manager' ); ?></h2>
	<h3><?php esc_html_e( 'Shortcode', 'admin-manager' ); ?></h3>
	<p><code>[am-reading-time]</code> - <?php esc_html_e( 'Display reading time for current post', 'admin-manager' ); ?></p>
	<p><code>[am-reading-time post_id="123"]</code> - <?php esc_html_e( 'Display reading time for specific post', 'admin-manager' ); ?></p>

	<h3><?php esc_html_e( 'PHP Function', 'admin-manager' ); ?></h3>
	<pre><code>&lt;?php echo am_get_reading_time( $post_id ); ?&gt;</code></pre>
</div>
