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
			// Logo settings.
			'logo_url'             => isset( $_POST['logo_url'] ) ? esc_url_raw( $_POST['logo_url'] ) : '',
			'logo_link'            => isset( $_POST['logo_link'] ) ? esc_url_raw( $_POST['logo_link'] ) : home_url(),
			'logo_title'           => isset( $_POST['logo_title'] ) ? sanitize_text_field( $_POST['logo_title'] ) : get_bloginfo( 'name' ),

			// Background settings.
			'bg_image'             => isset( $_POST['bg_image'] ) ? esc_url_raw( $_POST['bg_image'] ) : '',
			'bg_color'             => isset( $_POST['bg_color'] ) ? sanitize_hex_color( $_POST['bg_color'] ) : '',

			// Button colors.
			'button_bg_color'      => isset( $_POST['button_bg_color'] ) ? sanitize_hex_color( $_POST['button_bg_color'] ) : '#0073aa',
			'button_text_color'    => isset( $_POST['button_text_color'] ) ? sanitize_hex_color( $_POST['button_text_color'] ) : '#ffffff',

			// Input field colors.
			'input_border_color'   => isset( $_POST['input_border_color'] ) ? sanitize_hex_color( $_POST['input_border_color'] ) : '#dddddd',
			'input_focus_color'    => isset( $_POST['input_focus_color'] ) ? sanitize_hex_color( $_POST['input_focus_color'] ) : '#0073aa',

			// Custom message.
			'custom_message'       => isset( $_POST['custom_message'] ) ? wp_kses_post( $_POST['custom_message'] ) : '',

			// Security options.
			'hide_login_errors'    => isset( $_POST['hide_login_errors'] ) ? true : false,
			'hide_lost_password'   => isset( $_POST['hide_lost_password'] ) ? true : false,
			'hide_back_link'       => isset( $_POST['hide_back_link'] ) ? true : false,

			// Custom login URL.
			'enable_custom_url'    => isset( $_POST['enable_custom_url'] ) ? true : false,
			'custom_login_slug'    => isset( $_POST['custom_login_slug'] ) ? sanitize_title( $_POST['custom_login_slug'] ) : '',

			// Custom CSS.
			'custom_css'           => isset( $_POST['custom_css'] ) ? wp_strip_all_tags( $_POST['custom_css'] ) : '',
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
$bg_image = isset( $settings['bg_image'] ) ? $settings['bg_image'] : '';
$bg_color = isset( $settings['bg_color'] ) ? $settings['bg_color'] : '';
$button_bg_color = isset( $settings['button_bg_color'] ) ? $settings['button_bg_color'] : '#0073aa';
$button_text_color = isset( $settings['button_text_color'] ) ? $settings['button_text_color'] : '#ffffff';
$input_border_color = isset( $settings['input_border_color'] ) ? $settings['input_border_color'] : '#dddddd';
$input_focus_color = isset( $settings['input_focus_color'] ) ? $settings['input_focus_color'] : '#0073aa';
$custom_message = isset( $settings['custom_message'] ) ? $settings['custom_message'] : '';
$hide_login_errors = isset( $settings['hide_login_errors'] ) ? $settings['hide_login_errors'] : false;
$hide_lost_password = isset( $settings['hide_lost_password'] ) ? $settings['hide_lost_password'] : false;
$hide_back_link = isset( $settings['hide_back_link'] ) ? $settings['hide_back_link'] : false;
$enable_custom_url = isset( $settings['enable_custom_url'] ) ? $settings['enable_custom_url'] : false;
$custom_login_slug = isset( $settings['custom_login_slug'] ) ? $settings['custom_login_slug'] : '';
$custom_css = isset( $settings['custom_css'] ) ? $settings['custom_css'] : '';
?>

<div class="wrap am-admin-wrap">
	<h1><?php esc_html_e( 'Custom Login', 'admin-manager' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Customize the appearance and security of your WordPress login page. Add your logo, change colors, and enhance security.', 'admin-manager' ); ?>
	</p>

	<form method="post" action="">
		<?php wp_nonce_field( 'am_save_custom_login', 'am_custom_login_nonce' ); ?>

		<!-- Logo Settings -->
		<h2><?php esc_html_e( 'Logo Settings', 'admin-manager' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="logo_url"><?php esc_html_e( 'Custom Logo URL', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="logo_url" id="logo_url" value="<?php echo esc_attr( $logo_url ); ?>" class="regular-text">
					<button type="button" class="button am-upload-image" data-target="logo_url">
						<?php esc_html_e( 'Select Image', 'admin-manager' ); ?>
					</button>
					<p class="description">
						<?php esc_html_e( 'Upload a custom logo for the login page. Recommended size: 320x100px.', 'admin-manager' ); ?>
					</p>
					<?php if ( $logo_url ) : ?>
						<p>
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="Preview" style="max-width: 320px; max-height: 100px; margin-top: 10px; border: 1px solid #ddd; padding: 5px;">
						</p>
					<?php endif; ?>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="logo_link"><?php esc_html_e( 'Logo Link URL', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="url" name="logo_link" id="logo_link" value="<?php echo esc_attr( $logo_link ); ?>" class="regular-text" placeholder="<?php echo esc_attr( home_url() ); ?>">
					<p class="description">
						<?php esc_html_e( 'The URL the logo links to. Defaults to your homepage.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="logo_title"><?php esc_html_e( 'Logo Title Text', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="logo_title" id="logo_title" value="<?php echo esc_attr( $logo_title ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					<p class="description">
						<?php esc_html_e( 'The title attribute for the logo link (appears on hover).', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<!-- Background Settings -->
		<h2><?php esc_html_e( 'Background Settings', 'admin-manager' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="bg_image"><?php esc_html_e( 'Background Image', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="bg_image" id="bg_image" value="<?php echo esc_attr( $bg_image ); ?>" class="regular-text">
					<button type="button" class="button am-upload-image" data-target="bg_image">
						<?php esc_html_e( 'Select Image', 'admin-manager' ); ?>
					</button>
					<p class="description">
						<?php esc_html_e( 'Upload a background image for the login page. Will cover the entire screen.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="bg_color"><?php esc_html_e( 'Background Color', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="bg_color" id="bg_color" value="<?php echo esc_attr( $bg_color ); ?>" class="am-color-picker">
					<p class="description">
						<?php esc_html_e( 'Background color for the login page. Used if no image is set.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<!-- Color Settings -->
		<h2><?php esc_html_e( 'Color Settings', 'admin-manager' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="button_bg_color"><?php esc_html_e( 'Button Background Color', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="button_bg_color" id="button_bg_color" value="<?php echo esc_attr( $button_bg_color ); ?>" class="am-color-picker">
					<p class="description">
						<?php esc_html_e( 'Background color for the login button.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="button_text_color"><?php esc_html_e( 'Button Text Color', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="button_text_color" id="button_text_color" value="<?php echo esc_attr( $button_text_color ); ?>" class="am-color-picker">
					<p class="description">
						<?php esc_html_e( 'Text color for the login button.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="input_border_color"><?php esc_html_e( 'Input Border Color', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="input_border_color" id="input_border_color" value="<?php echo esc_attr( $input_border_color ); ?>" class="am-color-picker">
					<p class="description">
						<?php esc_html_e( 'Border color for input fields (username, password).', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="input_focus_color"><?php esc_html_e( 'Input Focus Color', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="input_focus_color" id="input_focus_color" value="<?php echo esc_attr( $input_focus_color ); ?>" class="am-color-picker">
					<p class="description">
						<?php esc_html_e( 'Border color for input fields when focused.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<!-- Messages & Security -->
		<h2><?php esc_html_e( 'Messages & Security', 'admin-manager' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="custom_message"><?php esc_html_e( 'Custom Welcome Message', 'admin-manager' ); ?></label>
				</th>
				<td>
					<textarea name="custom_message" id="custom_message" rows="3" class="large-text"><?php echo esc_textarea( $custom_message ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Display a custom message above the login form. Leave blank for no message.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Security Options', 'admin-manager' ); ?>
				</th>
				<td>
					<fieldset>
						<label>
							<input type="checkbox" name="hide_login_errors" value="1" <?php checked( $hide_login_errors, true ); ?>>
							<?php esc_html_e( 'Hide Specific Login Errors', 'admin-manager' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Display a generic error message instead of specific errors. Prevents username enumeration.', 'admin-manager' ); ?>
						</p>

						<label>
							<input type="checkbox" name="hide_lost_password" value="1" <?php checked( $hide_lost_password, true ); ?>>
							<?php esc_html_e( 'Hide "Lost your password?" Link', 'admin-manager' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Remove the password recovery link from the login page.', 'admin-manager' ); ?>
						</p>

						<label>
							<input type="checkbox" name="hide_back_link" value="1" <?php checked( $hide_back_link, true ); ?>>
							<?php esc_html_e( 'Hide "Back to Site" Link', 'admin-manager' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Remove the link that goes back to your homepage.', 'admin-manager' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>
		</table>

		<!-- Custom Login URL -->
		<h2><?php esc_html_e( 'Custom Login URL (Advanced)', 'admin-manager' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Enable Custom Login URL', 'admin-manager' ); ?>
				</th>
				<td>
					<fieldset>
						<label>
							<input type="checkbox" name="enable_custom_url" value="1" <?php checked( $enable_custom_url, true ); ?>>
							<?php esc_html_e( 'Enable custom login URL', 'admin-manager' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Change your login URL from wp-login.php to a custom slug. This adds a layer of security by hiding the default login page.', 'admin-manager' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="custom_login_slug"><?php esc_html_e( 'Custom Login Slug', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="custom_login_slug" id="custom_login_slug" value="<?php echo esc_attr( $custom_login_slug ); ?>" class="regular-text" placeholder="my-login">
					<p class="description">
						<?php esc_html_e( 'Enter a custom slug for your login page (e.g., "my-login", "secure-login"). Only letters, numbers, and hyphens allowed.', 'admin-manager' ); ?>
						<br>
						<strong><?php esc_html_e( 'WARNING:', 'admin-manager' ); ?></strong> <?php esc_html_e( 'Save this URL! If you forget it, you may be locked out. Recovery: Disable the plugin via FTP.', 'admin-manager' ); ?>
					</p>
					<?php if ( $enable_custom_url && ! empty( $custom_login_slug ) ) : ?>
						<p>
							<strong><?php esc_html_e( 'Your custom login URL:', 'admin-manager' ); ?></strong>
							<code style="background: #fffbcc; padding: 5px 10px; font-size: 14px; display: inline-block; margin-top: 5px;">
								<?php echo esc_html( home_url( $custom_login_slug ) ); ?>
							</code>
						</p>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<!-- Custom CSS -->
		<h2><?php esc_html_e( 'Advanced Customization', 'admin-manager' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="custom_css"><?php esc_html_e( 'Custom CSS', 'admin-manager' ); ?></label>
				</th>
				<td>
					<textarea name="custom_css" id="custom_css" rows="10" class="large-text code"><?php echo esc_textarea( $custom_css ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Add custom CSS to further customize the login page appearance. Use with caution.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" name="am_save_custom_login" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'admin-manager' ); ?>
			</button>
		</p>
	</form>
</div>

<script>
jQuery(document).ready(function($) {
	// Image upload.
	$('.am-upload-image').on('click', function(e) {
		e.preventDefault();

		var button = $(this);
		var targetInput = $('#' + button.data('target'));

		var mediaUploader = wp.media({
			title: '<?php esc_html_e( 'Select Image', 'admin-manager' ); ?>',
			button: {
				text: '<?php esc_html_e( 'Use this image', 'admin-manager' ); ?>'
			},
			multiple: false
		});

		mediaUploader.on('select', function() {
			var attachment = mediaUploader.state().get('selection').first().toJSON();
			targetInput.val(attachment.url);
		});

		mediaUploader.open();
	});
});
</script>
