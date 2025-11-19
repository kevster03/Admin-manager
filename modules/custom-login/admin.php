<?php
/**
 * Custom Login - Admin Page
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle settings save.
if ( isset( $_POST['am_save_custom_login'] ) && am_verify_nonce( 'am_custom_login_nonce', 'am_save_custom_login' ) ) {
	if ( current_user_can( 'manage_options' ) ) {
		$settings = array(
			'logo_url'           => isset( $_POST['logo_url'] ) ? esc_url_raw( $_POST['logo_url'] ) : '',
			'logo_link'          => isset( $_POST['logo_link'] ) ? esc_url_raw( $_POST['logo_link'] ) : '',
			'logo_title'         => isset( $_POST['logo_title'] ) ? sanitize_text_field( $_POST['logo_title'] ) : '',
			'bg_color'           => isset( $_POST['bg_color'] ) ? sanitize_hex_color( $_POST['bg_color'] ) : '',
			'bg_image'           => isset( $_POST['bg_image'] ) ? esc_url_raw( $_POST['bg_image'] ) : '',
			'custom_css'         => isset( $_POST['custom_css'] ) ? wp_strip_all_tags( $_POST['custom_css'] ) : '',
			'custom_login_slug'  => isset( $_POST['custom_login_slug'] ) ? sanitize_title( $_POST['custom_login_slug'] ) : '',
		);

		foreach ( $settings as $key => $value ) {
			am_update_module_setting( 'custom-login', $key, $value );
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'admin-manager' ) . '</p></div>';
	}
}

// Get current settings.
$settings = am_get_module_setting( 'custom-login' );
$logo_url = isset( $settings['logo_url'] ) ? $settings['logo_url'] : '';
$logo_link = isset( $settings['logo_link'] ) ? $settings['logo_link'] : home_url();
$logo_title = isset( $settings['logo_title'] ) ? $settings['logo_title'] : get_bloginfo( 'name' );
$bg_color = isset( $settings['bg_color'] ) ? $settings['bg_color'] : '';
$bg_image = isset( $settings['bg_image'] ) ? $settings['bg_image'] : '';
$custom_css = isset( $settings['custom_css'] ) ? $settings['custom_css'] : '';
$custom_login_slug = isset( $settings['custom_login_slug'] ) ? $settings['custom_login_slug'] : '';
?>

<div class="wrap am-admin-wrap">
	<h1><?php esc_html_e( 'Custom Login', 'admin-manager' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Customize your WordPress login page with a custom logo, background, and CSS.', 'admin-manager' ); ?>
	</p>

	<form method="post" action="">
		<?php wp_nonce_field( 'am_save_custom_login', 'am_custom_login_nonce' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="logo_url"><?php esc_html_e( 'Logo URL', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="url"
						name="logo_url"
						id="logo_url"
						value="<?php echo esc_url( $logo_url ); ?>"
						class="regular-text"
					>
					<button type="button" class="button button-secondary" onclick="amSelectMedia('logo_url')">
						<?php esc_html_e( 'Select Image', 'admin-manager' ); ?>
					</button>
					<p class="description">
						<?php esc_html_e( 'URL to your custom login logo image. Recommended size: 320x100px.', 'admin-manager' ); ?>
					</p>
					<?php if ( ! empty( $logo_url ) ) : ?>
						<p><img src="<?php echo esc_url( $logo_url ); ?>" style="max-width: 320px; height: auto; border: 1px solid #ddd; padding: 5px; margin-top: 10px;"></p>
					<?php endif; ?>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="logo_link"><?php esc_html_e( 'Logo Link URL', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="url"
						name="logo_link"
						id="logo_link"
						value="<?php echo esc_url( $logo_link ); ?>"
						class="regular-text"
					>
					<p class="description">
						<?php esc_html_e( 'Where should the logo link to? Default is your homepage.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="logo_title"><?php esc_html_e( 'Logo Title', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="logo_title"
						id="logo_title"
						value="<?php echo esc_attr( $logo_title ); ?>"
						class="regular-text"
					>
					<p class="description">
						<?php esc_html_e( 'Logo title attribute (for accessibility).', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="bg_color"><?php esc_html_e( 'Background Color', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="color"
						name="bg_color"
						id="bg_color"
						value="<?php echo esc_attr( $bg_color ); ?>"
					>
					<p class="description">
						<?php esc_html_e( 'Login page background color.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="bg_image"><?php esc_html_e( 'Background Image URL', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="url"
						name="bg_image"
						id="bg_image"
						value="<?php echo esc_url( $bg_image ); ?>"
						class="regular-text"
					>
					<button type="button" class="button button-secondary" onclick="amSelectMedia('bg_image')">
						<?php esc_html_e( 'Select Image', 'admin-manager' ); ?>
					</button>
					<p class="description">
						<?php esc_html_e( 'Login page background image (overrides background color).', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="custom_css"><?php esc_html_e( 'Custom CSS', 'admin-manager' ); ?></label>
				</th>
				<td>
					<textarea
						name="custom_css"
						id="custom_css"
						rows="10"
						cols="50"
						class="large-text code"
						placeholder="<?php esc_attr_e( 'Add your custom CSS here...', 'admin-manager' ); ?>"
					><?php echo esc_textarea( $custom_css ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Add custom CSS to further customize the login page. Use the namespace ".login" to target login page elements.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="custom_login_slug"><?php esc_html_e( 'Custom Login URL (Advanced)', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="custom_login_slug"
						id="custom_login_slug"
						value="<?php echo esc_attr( $custom_login_slug ); ?>"
						class="regular-text"
						placeholder="my-login"
					>
					<p class="description" style="color: #d30038; font-weight: 600;">
						⚠️ <?php esc_html_e( 'IMPORTANT: Use with caution!', 'admin-manager' ); ?>
					</p>
					<p class="description">
						<?php esc_html_e( 'Change your login URL from /wp-login.php to a custom slug (e.g., "my-login"). This adds security through obscurity.', 'admin-manager' ); ?>
					</p>
					<p class="description">
						<?php esc_html_e( 'Your new login URL will be:', 'admin-manager' ); ?>
						<strong><?php echo esc_url( home_url( '/' ) ); ?><span id="login-slug-preview"><?php echo esc_html( $custom_login_slug ? $custom_login_slug : 'your-slug-here' ); ?></span></strong>
					</p>
					<p class="description" style="background: #fff3cd; padding: 10px; border-left: 4px solid #d30038; margin-top: 10px;">
						<strong><?php esc_html_e( 'Safety Tips:', 'admin-manager' ); ?></strong><br>
						• <?php esc_html_e( 'Bookmark your custom login URL before saving!', 'admin-manager' ); ?><br>
						• <?php esc_html_e( 'Test it in an incognito window before logging out.', 'admin-manager' ); ?><br>
						• <?php esc_html_e( 'If you get locked out, deactivate this plugin via FTP/cPanel.', 'admin-manager' ); ?><br>
						• <?php esc_html_e( 'Leave empty to use standard wp-login.php', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<script>
		document.getElementById('custom_login_slug').addEventListener('input', function() {
			var slug = this.value || 'your-slug-here';
			document.getElementById('login-slug-preview').textContent = slug;
		});
		</script>

		<p class="submit">
			<button type="submit" name="am_save_custom_login" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'admin-manager' ); ?>
			</button>
			<a href="<?php echo esc_url( wp_login_url() ); ?>" target="_blank" class="button button-secondary">
				<?php esc_html_e( 'Preview Login Page', 'admin-manager' ); ?>
			</a>
		</p>
	</form>
</div>

<script>
function amSelectMedia(fieldId) {
	if (typeof wp === 'undefined' || !wp.media) {
		alert('<?php esc_html_e( 'Media library not available.', 'admin-manager' ); ?>');
		return;
	}

	var frame = wp.media({
		title: '<?php esc_html_e( 'Select Image', 'admin-manager' ); ?>',
		button: {
			text: '<?php esc_html_e( 'Use this image', 'admin-manager' ); ?>'
		},
		multiple: false
	});

	frame.on('select', function() {
		var attachment = frame.state().get('selection').first().toJSON();
		document.getElementById(fieldId).value = attachment.url;
	});

	frame.open();
}
</script>
