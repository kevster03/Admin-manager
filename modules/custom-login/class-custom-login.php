<?php
/**
 * Custom Login Module
 *
 * Customize WordPress login page appearance and security.
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Login class.
 */
class AM_Custom_Login {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Appearance customization.
		add_action( 'login_enqueue_scripts', array( $this, 'customize_login_page' ) );
		add_filter( 'login_headerurl', array( $this, 'custom_logo_url' ) );
		add_filter( 'login_headertext', array( $this, 'custom_logo_title' ) );
		add_filter( 'login_head', array( $this, 'add_custom_css' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media_uploader' ) );

		// Enhanced security features.
		add_filter( 'login_message', array( $this, 'custom_login_message' ) );
		add_filter( 'login_errors', array( $this, 'custom_login_errors' ) );

		// Hide elements if configured.
		add_filter( 'lostpassword_url', array( $this, 'maybe_hide_lost_password' ), 10, 2 );
		add_action( 'login_footer', array( $this, 'hide_login_elements' ) );

		// Custom login URL feature (secure implementation).
		$this->init_custom_login_url();
	}

	/**
	 * Initialize custom login URL functionality.
	 */
	private function init_custom_login_url() {
		$settings = am_get_module_setting( 'custom-login' );
		$enable_custom_url = isset( $settings['enable_custom_url'] ) ? $settings['enable_custom_url'] : false;
		$custom_slug = isset( $settings['custom_login_slug'] ) ? sanitize_title( $settings['custom_login_slug'] ) : '';

		// Only proceed if feature is enabled and slug is set.
		if ( ! $enable_custom_url || empty( $custom_slug ) || 'wp-login' === $custom_slug ) {
			// Clean up if disabled.
			$last_slug = get_option( 'am_custom_login_last_slug' );
			if ( $last_slug ) {
				flush_rewrite_rules();
				delete_option( 'am_custom_login_last_slug' );
			}
			return;
		}

		// Add custom rewrite rule.
		add_action( 'init', array( $this, 'add_custom_login_rewrite' ) );

		// Filter login URL.
		add_filter( 'login_url', array( $this, 'custom_login_url_filter' ), 10, 3 );
		add_filter( 'site_url', array( $this, 'custom_login_url_filter_site_url' ), 10, 4 );
		add_filter( 'wp_redirect', array( $this, 'custom_login_url_filter_redirect' ), 10, 2 );

		// Block direct access to wp-login.php.
		add_action( 'login_init', array( $this, 'block_default_login_url' ) );

		// Parse request for custom login slug.
		add_action( 'parse_request', array( $this, 'parse_custom_login_request' ) );

		// Add admin notice.
		add_action( 'admin_notices', array( $this, 'admin_notice_custom_url' ) );
	}

	/**
	 * Add custom login rewrite rule.
	 */
	public function add_custom_login_rewrite() {
		$settings = am_get_module_setting( 'custom-login' );
		$custom_slug = isset( $settings['custom_login_slug'] ) ? sanitize_title( $settings['custom_login_slug'] ) : '';

		// Get the last slug we used.
		$last_slug = get_option( 'am_custom_login_last_slug' );

		if ( empty( $custom_slug ) ) {
			// Slug was removed, clean up.
			if ( $last_slug ) {
				flush_rewrite_rules();
				delete_option( 'am_custom_login_last_slug' );
			}
			return;
		}

		add_rewrite_rule( '^' . $custom_slug . '/?$', 'index.php?am_custom_login=1', 'top' );
		add_rewrite_tag( '%am_custom_login%', '([^&]+)' );

		// Flush if slug changed or doesn't exist in rules.
		if ( $last_slug !== $custom_slug ) {
			flush_rewrite_rules();
			update_option( 'am_custom_login_last_slug', $custom_slug );
		}
	}

	/**
	 * Parse custom login request.
	 *
	 * @param WP $wp WordPress environment object.
	 */
	public function parse_custom_login_request( $wp ) {
		if ( ! isset( $wp->query_vars['am_custom_login'] ) ) {
			return;
		}

		// Show login page.
		if ( is_user_logged_in() ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		// Set $pagenow global for wp-login.php.
		global $pagenow;
		$pagenow = 'wp-login.php';

		// Include wp-login.php.
		require_once ABSPATH . 'wp-login.php';
		exit;
	}

	/**
	 * Filter login URL.
	 *
	 * @param string $login_url Login URL.
	 * @param string $redirect Redirect URL.
	 * @param bool   $force_reauth Force reauth.
	 * @return string Modified login URL.
	 */
	public function custom_login_url_filter( $login_url, $redirect = '', $force_reauth = false ) {
		$settings = am_get_module_setting( 'custom-login' );
		$custom_slug = isset( $settings['custom_login_slug'] ) ? sanitize_title( $settings['custom_login_slug'] ) : '';

		if ( empty( $custom_slug ) ) {
			return $login_url;
		}

		$login_url = home_url( '/' . $custom_slug . '/' );

		if ( ! empty( $redirect ) ) {
			$login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url );
		}

		if ( $force_reauth ) {
			$login_url = add_query_arg( 'reauth', '1', $login_url );
		}

		return $login_url;
	}

	/**
	 * Filter site URL for login.
	 *
	 * @param string $url URL.
	 * @param string $path Path.
	 * @param string $scheme Scheme.
	 * @param int    $blog_id Blog ID.
	 * @return string Modified URL.
	 */
	public function custom_login_url_filter_site_url( $url, $path, $scheme, $blog_id ) {
		// Only filter wp-login.php URLs.
		if ( false === strpos( $url, 'wp-login.php' ) ) {
			return $url;
		}

		$settings = am_get_module_setting( 'custom-login' );
		$custom_slug = isset( $settings['custom_login_slug'] ) ? sanitize_title( $settings['custom_login_slug'] ) : '';

		if ( empty( $custom_slug ) ) {
			return $url;
		}

		// Replace wp-login.php with custom slug.
		return str_replace( 'wp-login.php', $custom_slug, $url );
	}

	/**
	 * Filter redirect URLs.
	 *
	 * @param string $location Redirect location.
	 * @param int    $status Status code.
	 * @return string Modified location.
	 */
	public function custom_login_url_filter_redirect( $location, $status ) {
		// Only filter wp-login.php URLs.
		if ( false === strpos( $location, 'wp-login.php' ) ) {
			return $location;
		}

		$settings = am_get_module_setting( 'custom-login' );
		$custom_slug = isset( $settings['custom_login_slug'] ) ? sanitize_title( $settings['custom_login_slug'] ) : '';

		if ( empty( $custom_slug ) ) {
			return $location;
		}

		// Replace wp-login.php with custom slug.
		return str_replace( 'wp-login.php', $custom_slug, $location );
	}

	/**
	 * Block direct access to default wp-login.php.
	 */
	public function block_default_login_url() {
		$settings = am_get_module_setting( 'custom-login' );
		$enable_custom_url = isset( $settings['enable_custom_url'] ) ? $settings['enable_custom_url'] : false;
		$custom_slug = isset( $settings['custom_login_slug'] ) ? sanitize_title( $settings['custom_login_slug'] ) : '';

		if ( ! $enable_custom_url || empty( $custom_slug ) ) {
			return;
		}

		// Allow if user is already logged in.
		if ( is_user_logged_in() ) {
			return;
		}

		// Allow AJAX requests.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Allow if coming from custom login URL.
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		if ( ! empty( $referer ) && false !== strpos( $referer, $custom_slug ) ) {
			return;
		}

		// Allow specific actions (logout, lostpassword, etc.).
		$allowed_actions = array( 'logout', 'lostpassword', 'retrievepassword', 'resetpass', 'rp', 'postpass' );
		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
		if ( in_array( $action, $allowed_actions, true ) ) {
			return;
		}

		// Block access to default wp-login.php.
		global $pagenow;
		if ( 'wp-login.php' === $pagenow && empty( $_GET['am_custom_login'] ) ) {
			wp_safe_redirect( home_url( '/404/' ) );
			exit;
		}
	}

	/**
	 * Display admin notice about custom login URL.
	 */
	public function admin_notice_custom_url() {
		$settings = am_get_module_setting( 'custom-login' );
		$enable_custom_url = isset( $settings['enable_custom_url'] ) ? $settings['enable_custom_url'] : false;
		$custom_slug = isset( $settings['custom_login_slug'] ) ? sanitize_title( $settings['custom_login_slug'] ) : '';

		if ( ! $enable_custom_url || empty( $custom_slug ) ) {
			return;
		}

		$current_screen = get_current_screen();
		if ( $current_screen && 'admin-manager_page_admin-manager-custom-login' === $current_screen->id ) {
			echo '<div class="notice notice-warning">';
			echo '<p><strong>' . esc_html__( 'Important: Custom Login URL', 'admin-manager' ) . '</strong></p>';
			echo '<p>';
			printf(
				/* translators: %s: Custom login URL */
				esc_html__( 'Your login URL has been changed to: %s', 'admin-manager' ),
				'<code>' . esc_html( home_url( $custom_slug ) ) . '</code>'
			);
			echo '</p>';
			echo '<p>' . esc_html__( 'Please save this URL! You will need it to log in. If you forget it, you may be locked out.', 'admin-manager' ) . '</p>';
			echo '<p><strong>' . esc_html__( 'Recovery:', 'admin-manager' ) . '</strong> ' . esc_html__( 'If you get locked out, access your site via FTP and disable the Admin Manager plugin.', 'admin-manager' ) . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Customize login page appearance.
	 */
	public function customize_login_page() {
		$settings = am_get_module_setting( 'custom-login' );
		$logo_url = isset( $settings['logo_url'] ) ? $settings['logo_url'] : '';
		$button_bg_color = isset( $settings['button_bg_color'] ) ? $settings['button_bg_color'] : '#0073aa';
		$button_text_color = isset( $settings['button_text_color'] ) ? $settings['button_text_color'] : '#ffffff';
		$input_border_color = isset( $settings['input_border_color'] ) ? $settings['input_border_color'] : '#dddddd';
		$input_focus_color = isset( $settings['input_focus_color'] ) ? $settings['input_focus_color'] : '#0073aa';

		?>
		<style>
			<?php if ( ! empty( $logo_url ) ) : ?>
				#login h1 a {
					background-image: url('<?php echo esc_url( $logo_url ); ?>');
					background-size: contain;
					background-position: center;
					width: 100%;
					max-width: 320px;
					height: 100px;
				}
			<?php endif; ?>

			/* Custom button colors */
			.wp-core-ui .button-primary {
				background: <?php echo esc_attr( $button_bg_color ); ?> !important;
				border-color: <?php echo esc_attr( $button_bg_color ); ?> !important;
				color: <?php echo esc_attr( $button_text_color ); ?> !important;
				box-shadow: none !important;
				text-shadow: none !important;
			}

			.wp-core-ui .button-primary:hover,
			.wp-core-ui .button-primary:focus {
				background: <?php echo esc_attr( $this->darken_color( $button_bg_color, 10 ) ); ?> !important;
				border-color: <?php echo esc_attr( $this->darken_color( $button_bg_color, 10 ) ); ?> !important;
			}

			/* Custom input field styles */
			input[type="text"],
			input[type="password"],
			input[type="email"] {
				border-color: <?php echo esc_attr( $input_border_color ); ?>;
			}

			input[type="text"]:focus,
			input[type="password"]:focus,
			input[type="email"]:focus {
				border-color: <?php echo esc_attr( $input_focus_color ); ?>;
				box-shadow: 0 0 0 1px <?php echo esc_attr( $input_focus_color ); ?>;
			}

			/* Improved login form styling */
			#login {
				padding: 8% 0 0;
			}

			#loginform {
				box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
				border-radius: 8px;
			}
		</style>
		<?php
	}

	/**
	 * Custom logo URL.
	 *
	 * @return string Logo URL.
	 */
	public function custom_logo_url() {
		$settings = am_get_module_setting( 'custom-login' );
		$logo_link = isset( $settings['logo_link'] ) ? $settings['logo_link'] : home_url();

		return $logo_link ? $logo_link : home_url();
	}

	/**
	 * Custom logo title.
	 *
	 * @return string Logo title.
	 */
	public function custom_logo_title() {
		$settings = am_get_module_setting( 'custom-login' );
		$logo_title = isset( $settings['logo_title'] ) ? $settings['logo_title'] : get_bloginfo( 'name' );

		return $logo_title ? $logo_title : get_bloginfo( 'name' );
	}

	/**
	 * Add custom CSS to login page.
	 */
	public function add_custom_css() {
		$settings = am_get_module_setting( 'custom-login' );
		$custom_css = isset( $settings['custom_css'] ) ? $settings['custom_css'] : '';
		$bg_image = isset( $settings['bg_image'] ) ? $settings['bg_image'] : '';
		$bg_color = isset( $settings['bg_color'] ) ? $settings['bg_color'] : '';

		?>
		<style>
			<?php if ( ! empty( $bg_color ) ) : ?>
				body.login {
					background-color: <?php echo esc_attr( $bg_color ); ?>;
				}
			<?php endif; ?>

			<?php if ( ! empty( $bg_image ) ) : ?>
				body.login {
					background-image: url('<?php echo esc_url( $bg_image ); ?>');
					background-size: cover;
					background-position: center;
					background-repeat: no-repeat;
					background-attachment: fixed;
				}
			<?php endif; ?>

			<?php if ( ! empty( $custom_css ) ) : ?>
				<?php
				// Sanitize CSS - only allow safe properties.
				echo wp_strip_all_tags( $custom_css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			<?php endif; ?>
		</style>
		<?php
	}

	/**
	 * Custom login message.
	 *
	 * @param string $message Login message.
	 * @return string Modified message.
	 */
	public function custom_login_message( $message ) {
		$settings = am_get_module_setting( 'custom-login' );
		$custom_message = isset( $settings['custom_message'] ) ? $settings['custom_message'] : '';

		if ( ! empty( $custom_message ) && ! isset( $_GET['checkemail'] ) && ! isset( $_GET['loggedout'] ) ) {
			return '<p class="message">' . wp_kses_post( $custom_message ) . '</p>';
		}

		return $message;
	}

	/**
	 * Custom login errors.
	 *
	 * @param string $error Error message.
	 * @return string Modified error.
	 */
	public function custom_login_errors( $error ) {
		$settings = am_get_module_setting( 'custom-login' );
		$hide_errors = isset( $settings['hide_login_errors'] ) ? $settings['hide_login_errors'] : false;

		if ( $hide_errors ) {
			return __( 'Invalid login credentials.', 'admin-manager' );
		}

		return $error;
	}

	/**
	 * Maybe hide lost password link.
	 *
	 * @param string $url Lost password URL.
	 * @param string $redirect Redirect URL.
	 * @return string Modified URL.
	 */
	public function maybe_hide_lost_password( $url, $redirect ) {
		$settings = am_get_module_setting( 'custom-login' );
		$hide_lost_password = isset( $settings['hide_lost_password'] ) ? $settings['hide_lost_password'] : false;

		if ( $hide_lost_password ) {
			return '';
		}

		return $url;
	}

	/**
	 * Hide login page elements.
	 */
	public function hide_login_elements() {
		$settings = am_get_module_setting( 'custom-login' );
		$hide_back_link = isset( $settings['hide_back_link'] ) ? $settings['hide_back_link'] : false;
		$hide_lost_password = isset( $settings['hide_lost_password'] ) ? $settings['hide_lost_password'] : false;
		$hide_register = isset( $settings['hide_register'] ) ? $settings['hide_register'] : false;

		?>
		<style>
			<?php if ( $hide_back_link ) : ?>
				#backtoblog {
					display: none !important;
				}
			<?php endif; ?>

			<?php if ( $hide_lost_password ) : ?>
				#nav {
					display: none !important;
				}
			<?php endif; ?>

			<?php if ( $hide_register && ! $hide_lost_password ) : ?>
				/* Hide only register link, keep lost password */
				#nav a[href*="wp-login.php?action=register"],
				#nav a[href*="action=register"] {
					display: none !important;
				}
			<?php endif; ?>
		</style>
		<?php if ( $hide_register ) : ?>
		<script>
		(function() {
			// Hide register link via JavaScript as well for better compatibility
			document.addEventListener('DOMContentLoaded', function() {
				var navLinks = document.querySelectorAll('#nav a');
				navLinks.forEach(function(link) {
					if (link.href && (link.href.indexOf('action=register') !== -1 || link.href.indexOf('wp-signup.php') !== -1)) {
						link.style.display = 'none';
					}
				});
			});
		})();
		</script>
		<?php endif; ?>
		<?php
	}

	/**
	 * Darken a hex color by percentage.
	 *
	 * @param string $hex Hex color code.
	 * @param int    $percent Percentage to darken (0-100).
	 * @return string Darkened hex color.
	 */
	private function darken_color( $hex, $percent ) {
		// Remove # if present.
		$hex = ltrim( $hex, '#' );

		// Convert to RGB.
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );

		// Darken.
		$r = max( 0, min( 255, $r - ( $r * $percent / 100 ) ) );
		$g = max( 0, min( 255, $g - ( $g * $percent / 100 ) ) );
		$b = max( 0, min( 255, $b - ( $b * $percent / 100 ) ) );

		// Convert back to hex.
		return sprintf( '#%02x%02x%02x', $r, $g, $b );
	}

	/**
	 * Enqueue media uploader on settings page.
	 *
	 * @param string $hook Current page hook.
	 */
	public function enqueue_media_uploader( $hook ) {
		// Only enqueue on our settings page.
		if ( strpos( $hook, 'admin-manager' ) !== false ) {
			wp_enqueue_media();
		}
	}
}
