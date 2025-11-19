<?php
/**
 * Performance - Admin Page
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle settings save.
if ( isset( $_POST['am_save_performance'] ) && am_verify_nonce( 'am_performance_nonce', 'am_save_performance' ) ) {
	if ( current_user_can( 'manage_options' ) ) {
		$settings = array(
			'google_fonts_action' => isset( $_POST['google_fonts_action'] ) ? sanitize_text_field( $_POST['google_fonts_action'] ) : 'none',
		);

		// Get all public post types.
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		// Save preconnect and preload settings for each post type.
		foreach ( $post_types as $post_type ) {
			// Preconnect URLs.
			$preconnect_key = 'preconnect_' . $post_type;
			if ( isset( $_POST[ $preconnect_key ] ) ) {
				$settings[ $preconnect_key ] = sanitize_textarea_field( $_POST[ $preconnect_key ] );
			}

			// Preload assets.
			$preload_key = 'preload_' . $post_type;
			if ( isset( $_POST[ $preload_key ] ) ) {
				$settings[ $preload_key ] = sanitize_textarea_field( $_POST[ $preload_key ] );
			}
		}

		foreach ( $settings as $key => $value ) {
			am_update_module_setting( 'performance', $key, $value );
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'admin-manager' ) . '</p></div>';
	}
}

// Get current settings.
$settings = am_get_module_setting( 'performance' );
$google_fonts_action = isset( $settings['google_fonts_action'] ) ? $settings['google_fonts_action'] : 'none';

// Get all post types.
$post_types = get_post_types( array( 'public' => true ), 'objects' );
?>

<div class="wrap am-admin-wrap">
	<h1><?php esc_html_e( 'Performance', 'admin-manager' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Optimize site performance by managing Google Fonts and adding resource hints for faster loading. Configure preconnect and preload assets per post type to improve PageSpeed scores.', 'admin-manager' ); ?>
	</p>

	<form method="post" action="">
		<?php wp_nonce_field( 'am_save_performance', 'am_performance_nonce' ); ?>

		<!-- Google Fonts Settings -->
		<h2><?php esc_html_e( 'Google Fonts', 'admin-manager' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="google_fonts_action"><?php esc_html_e( 'Google Fonts Action', 'admin-manager' ); ?></label>
				</th>
				<td>
					<select name="google_fonts_action" id="google_fonts_action" class="regular-text">
						<option value="none" <?php selected( $google_fonts_action, 'none' ); ?>>
							<?php esc_html_e( 'None (Keep Google Fonts)', 'admin-manager' ); ?>
						</option>
						<option value="remove" <?php selected( $google_fonts_action, 'remove' ); ?>>
							<?php esc_html_e( 'Remove All Google Fonts', 'admin-manager' ); ?>
						</option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Remove all Google Fonts from your site (fonts.googleapis.com and fonts.gstatic.com). Useful if you want to use system fonts or self-hosted fonts.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<!-- Preconnect Settings -->
		<h2><?php esc_html_e( 'Preconnect Resources (Per Post Type)', 'admin-manager' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Add preconnect resource hints to establish early connections to external domains. This reduces DNS lookup, TCP handshake, and TLS negotiation time. Enter one URL per line.', 'admin-manager' ); ?>
		</p>

		<table class="form-table">
			<?php foreach ( $post_types as $post_type ) : ?>
				<?php
				$preconnect_key = 'preconnect_' . $post_type->name;
				$preconnect_value = isset( $settings[ $preconnect_key ] ) ? $settings[ $preconnect_key ] : '';
				?>
				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr( $preconnect_key ); ?>">
							<?php
							/* translators: %s: Post type label */
							echo esc_html( sprintf( __( '%s - Preconnect URLs', 'admin-manager' ), $post_type->label ) );
							?>
						</label>
					</th>
					<td>
						<textarea
							name="<?php echo esc_attr( $preconnect_key ); ?>"
							id="<?php echo esc_attr( $preconnect_key ); ?>"
							rows="4"
							class="large-text code"
							placeholder="https://fonts.gstatic.com&#10;https://cdn.example.com"
						><?php echo esc_textarea( $preconnect_value ); ?></textarea>
						<p class="description">
							<?php
							/* translators: %s: Post type label */
							echo esc_html( sprintf( __( 'Preconnect URLs for %s (one per line). Example: https://fonts.gstatic.com', 'admin-manager' ), strtolower( $post_type->label ) ) );
							?>
						</p>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>

		<!-- Preload Settings -->
		<h2><?php esc_html_e( 'Preload Assets (Per Post Type)', 'admin-manager' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Preload critical resources that are discovered late by the browser. Use format: url|type (one per line). Supported types: style, script, font, image.', 'admin-manager' ); ?>
		</p>

		<table class="form-table">
			<?php foreach ( $post_types as $post_type ) : ?>
				<?php
				$preload_key = 'preload_' . $post_type->name;
				$preload_value = isset( $settings[ $preload_key ] ) ? $settings[ $preload_key ] : '';
				?>
				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr( $preload_key ); ?>">
							<?php
							/* translators: %s: Post type label */
							echo esc_html( sprintf( __( '%s - Preload Assets', 'admin-manager' ), $post_type->label ) );
							?>
						</label>
					</th>
					<td>
						<textarea
							name="<?php echo esc_attr( $preload_key ); ?>"
							id="<?php echo esc_attr( $preload_key ); ?>"
							rows="6"
							class="large-text code"
							placeholder="https://yoursite.com/wp-content/themes/theme/critical.css|style&#10;https://yoursite.com/wp-content/themes/theme/main.js|script&#10;https://yoursite.com/fonts/roboto.woff2|font"
						><?php echo esc_textarea( $preload_value ); ?></textarea>
						<p class="description">
							<?php
							/* translators: %s: Post type label */
							echo esc_html( sprintf( __( 'Preload assets for %s in format url|type (one per line).', 'admin-manager' ), strtolower( $post_type->label ) ) );
							?>
							<br>
							<?php esc_html_e( 'Example: https://yoursite.com/critical.css|style', 'admin-manager' ); ?>
							<br>
							<strong><?php esc_html_e( 'Types:', 'admin-manager' ); ?></strong> style, script, font, image
						</p>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>

		<p class="submit">
			<button type="submit" name="am_save_performance" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'admin-manager' ); ?>
			</button>
		</p>
	</form>

	<!-- Help Section -->
	<hr>
	<div class="am-help-section">
		<h2><?php esc_html_e( 'PageSpeed Integration Guide', 'admin-manager' ); ?></h2>

		<h3><?php esc_html_e( 'What is Preconnect?', 'admin-manager' ); ?></h3>
		<p>
			<?php esc_html_e( 'Preconnect establishes early connections to important third-party origins. This resolves the "Preconnect to required origins" recommendation in PageSpeed Insights.', 'admin-manager' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Common preconnect URLs:', 'admin-manager' ); ?></strong><br>
			• <code>https://fonts.gstatic.com</code> - Google Fonts<br>
			• <code>https://fonts.googleapis.com</code> - Google Fonts API<br>
			• <code>https://cdn.jsdelivr.net</code> - jsDelivr CDN<br>
			• <code>https://cdnjs.cloudflare.com</code> - Cloudflare CDN
		</p>

		<h3><?php esc_html_e( 'What is Preload?', 'admin-manager' ); ?></h3>
		<p>
			<?php esc_html_e( 'Preload tells the browser to download critical resources early. This resolves the "Preload key requests" recommendation in PageSpeed Insights.', 'admin-manager' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'What to preload:', 'admin-manager' ); ?></strong><br>
			• <strong>style</strong> - Critical CSS files above the fold<br>
			• <strong>font</strong> - Web fonts that cause layout shift<br>
			• <strong>image</strong> - Hero images or LCP (Largest Contentful Paint) images<br>
			• <strong>script</strong> - Critical JavaScript needed for initial render
		</p>

		<h3><?php esc_html_e( 'Finding URLs from PageSpeed', 'admin-manager' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Run PageSpeed Insights on your site', 'admin-manager' ); ?></li>
			<li><?php esc_html_e( 'Look for "Preconnect to required origins" - copy the domain URLs', 'admin-manager' ); ?></li>
			<li><?php esc_html_e( 'Look for "Preload key requests" - copy the resource URLs with their types', 'admin-manager' ); ?></li>
			<li><?php esc_html_e( 'Paste them into the appropriate post type fields above', 'admin-manager' ); ?></li>
		</ol>

		<h3><?php esc_html_e( 'Performance Tips', 'admin-manager' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Only preload resources that are truly critical for initial render', 'admin-manager' ); ?></li>
			<li><?php esc_html_e( 'Preloading too many resources can hurt performance', 'admin-manager' ); ?></li>
			<li><?php esc_html_e( 'Configure separately for different post types (posts vs pages may need different resources)', 'admin-manager' ); ?></li>
			<li><?php esc_html_e( 'This module complements caching plugins - it works at the browser resource hint level', 'admin-manager' ); ?></li>
		</ul>
	</div>
</div>
