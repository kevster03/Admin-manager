<?php
/**
 * Author Box - Admin Page
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle settings save.
if ( isset( $_POST['am_save_author_box'] ) && am_verify_nonce( 'am_author_box_nonce', 'am_save_author_box' ) ) {
	if ( current_user_can( 'manage_options' ) ) {
		$settings = array(
			'enabled_post_types' => isset( $_POST['enabled_post_types'] ) && is_array( $_POST['enabled_post_types'] )
				? array_map( 'sanitize_text_field', $_POST['enabled_post_types'] )
				: array(),
			'position'           => isset( $_POST['position'] ) ? sanitize_text_field( $_POST['position'] ) : 'after',
			'show_avatar'        => isset( $_POST['show_avatar'] ) ? true : false,
			'show_social'        => isset( $_POST['show_social'] ) ? true : false,
			'size'               => isset( $_POST['size'] ) ? sanitize_text_field( $_POST['size'] ) : 'medium',
		);

		foreach ( $settings as $key => $value ) {
			am_update_module_setting( 'author-box', $key, $value );
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'admin-manager' ) . '</p></div>';
	}
}

// Get current settings.
$settings = am_get_module_setting( 'author-box' );
$enabled_post_types = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array();
$position = isset( $settings['position'] ) ? $settings['position'] : 'after';
$show_avatar = isset( $settings['show_avatar'] ) ? $settings['show_avatar'] : true;
$show_social = isset( $settings['show_social'] ) ? $settings['show_social'] : true;
$size = isset( $settings['size'] ) ? $settings['size'] : 'medium';

// Get all post types.
$post_types = get_post_types( array( 'public' => true ), 'objects' );
?>

<div class="wrap am-admin-wrap">
	<h1><?php esc_html_e( 'Author Box', 'admin-manager' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Display author information box with bio, avatar, and social links. Use the shortcode [am-author] to insert author box anywhere.', 'admin-manager' ); ?>
	</p>

	<form method="post" action="">
		<?php wp_nonce_field( 'am_save_author_box', 'am_author_box_nonce' ); ?>

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
						<?php esc_html_e( 'Automatically display author box on selected post types.', 'admin-manager' ); ?>
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
							<?php esc_html_e( 'Manual (Shortcode Only)', 'admin-manager' ); ?>
						</option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Display Options', 'admin-manager' ); ?>
				</th>
				<td>
					<label style="display: block; margin-bottom: 8px;">
						<input type="checkbox" name="show_avatar" value="1" <?php checked( $show_avatar ); ?>>
						<?php esc_html_e( 'Show Avatar', 'admin-manager' ); ?>
					</label>
					<label style="display: block; margin-bottom: 8px;">
						<input type="checkbox" name="show_social" value="1" <?php checked( $show_social ); ?>>
						<?php esc_html_e( 'Show Social Links', 'admin-manager' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="size"><?php esc_html_e( 'Size', 'admin-manager' ); ?></label>
				</th>
				<td>
					<select name="size" id="size">
						<option value="small" <?php selected( $size, 'small' ); ?>>
							<?php esc_html_e( 'Small', 'admin-manager' ); ?>
						</option>
						<option value="medium" <?php selected( $size, 'medium' ); ?>>
							<?php esc_html_e( 'Medium', 'admin-manager' ); ?>
						</option>
						<option value="large" <?php selected( $size, 'large' ); ?>>
							<?php esc_html_e( 'Large', 'admin-manager' ); ?>
						</option>
					</select>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" name="am_save_author_box" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'admin-manager' ); ?>
			</button>
		</p>
	</form>

	<hr>

	<h2><?php esc_html_e( 'Usage', 'admin-manager' ); ?></h2>
	<p><code>[am-author]</code> - <?php esc_html_e( 'Display author box for current post author', 'admin-manager' ); ?></p>
	<p><code>[am-author author_id="1"]</code> - <?php esc_html_e( 'Display author box for specific author', 'admin-manager' ); ?></p>

	<h2><?php esc_html_e( 'Social Links Setup', 'admin-manager' ); ?></h2>
	<p>
		<?php
		printf(
			/* translators: %s: URL to user profile page */
			esc_html__( 'Authors can add their social links in their %s.', 'admin-manager' ),
			'<a href="' . esc_url( admin_url( 'profile.php' ) ) . '">' . esc_html__( 'profile settings', 'admin-manager' ) . '</a>'
		);
		?>
	</p>
	<p class="description">
		<?php esc_html_e( 'Note: Twitter and LinkedIn fields may need to be added via custom user meta fields or a plugin.', 'admin-manager' ); ?>
	</p>
</div>
