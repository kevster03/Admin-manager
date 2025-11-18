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
			'layout'             => isset( $_POST['layout'] ) ? sanitize_text_field( $_POST['layout'] ) : 'horizontal',
			'show_avatar'        => isset( $_POST['show_avatar'] ) ? true : false,
			'show_social'        => isset( $_POST['show_social'] ) ? true : false,
			'show_badges'        => isset( $_POST['show_badges'] ) ? true : false,
			'size'               => isset( $_POST['size'] ) ? sanitize_text_field( $_POST['size'] ) : 'medium',
			'title_prefix'       => isset( $_POST['title_prefix'] ) ? sanitize_text_field( $_POST['title_prefix'] ) : __( 'About the Author', 'admin-manager' ),
			'bg_color'           => isset( $_POST['bg_color'] ) ? sanitize_hex_color( $_POST['bg_color'] ) : '#f9f9f9',
			'text_color'         => isset( $_POST['text_color'] ) ? sanitize_hex_color( $_POST['text_color'] ) : '#333333',
			'border_color'       => isset( $_POST['border_color'] ) ? sanitize_hex_color( $_POST['border_color'] ) : '#dddddd',
			'border_width'       => isset( $_POST['border_width'] ) ? absint( $_POST['border_width'] ) : 1,
			'border_radius'      => isset( $_POST['border_radius'] ) ? absint( $_POST['border_radius'] ) : 8,
			'padding'            => isset( $_POST['padding'] ) ? absint( $_POST['padding'] ) : 20,
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
$layout = isset( $settings['layout'] ) ? $settings['layout'] : 'horizontal';
$show_avatar = isset( $settings['show_avatar'] ) ? $settings['show_avatar'] : true;
$show_social = isset( $settings['show_social'] ) ? $settings['show_social'] : true;
$show_badges = isset( $settings['show_badges'] ) ? $settings['show_badges'] : true;
$size = isset( $settings['size'] ) ? $settings['size'] : 'medium';
$title_prefix = isset( $settings['title_prefix'] ) ? $settings['title_prefix'] : __( 'About the Author', 'admin-manager' );
$bg_color = isset( $settings['bg_color'] ) ? $settings['bg_color'] : '#f9f9f9';
$text_color = isset( $settings['text_color'] ) ? $settings['text_color'] : '#333333';
$border_color = isset( $settings['border_color'] ) ? $settings['border_color'] : '#dddddd';
$border_width = isset( $settings['border_width'] ) ? $settings['border_width'] : 1;
$border_radius = isset( $settings['border_radius'] ) ? $settings['border_radius'] : 8;
$padding = isset( $settings['padding'] ) ? $settings['padding'] : 20;

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

		<h2><?php esc_html_e( 'General Settings', 'admin-manager' ); ?></h2>

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
					<label for="layout"><?php esc_html_e( 'Layout Template', 'admin-manager' ); ?></label>
				</th>
				<td>
					<select name="layout" id="layout">
						<option value="horizontal" <?php selected( $layout, 'horizontal' ); ?>>
							<?php esc_html_e( 'Horizontal (Default)', 'admin-manager' ); ?>
						</option>
						<option value="vertical" <?php selected( $layout, 'vertical' ); ?>>
							<?php esc_html_e( 'Vertical (Avatar on Top)', 'admin-manager' ); ?>
						</option>
						<option value="card" <?php selected( $layout, 'card' ); ?>>
							<?php esc_html_e( 'Card (Centered)', 'admin-manager' ); ?>
						</option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Choose the visual layout style for the author box.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="title_prefix"><?php esc_html_e( 'Title/Prefix', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="title_prefix"
						id="title_prefix"
						value="<?php echo esc_attr( $title_prefix ); ?>"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'About the Author', 'admin-manager' ); ?>"
					>
					<p class="description">
						<?php esc_html_e( 'Text to display above the author name (e.g., "Written by", "Post Author", "About the Author"). Leave empty to hide.', 'admin-manager' ); ?>
					</p>
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
					<label style="display: block; margin-bottom: 8px;">
						<input type="checkbox" name="show_badges" value="1" <?php checked( $show_badges ); ?>>
						<?php esc_html_e( 'Show Expertise Badges', 'admin-manager' ); ?>
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
					<p class="description">
						<?php esc_html_e( 'Also used for social media buttons', 'admin-manager' ); ?>
					</p>
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
