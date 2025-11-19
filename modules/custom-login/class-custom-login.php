<?php
/**
 * Custom Login Module
 *
 * Customize WordPress login page.
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
		add_action( 'login_enqueue_scripts', array( $this, 'customize_login_page' ) );
		add_filter( 'login_headerurl', array( $this, 'custom_logo_url' ) );
		add_filter( 'login_headertext', array( $this, 'custom_logo_title' ) );
		add_filter( 'login_head', array( $this, 'add_custom_css' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media_uploader' ) );

		// Custom login URL (secure implementation).
		$this->init_custom_login_url();
	}

	/**
	 * Initialize custom login URL functionality.
	 */
	private function init_custom_login_url() {
		$settings = am_get_module_setting( 'custom-login' );
		$custom_slug = isset( $settings['custom_login_slug'] ) ? sanitize_title( $settings['custom_login_slug'] ) : '';

		// Only proceed if custom slug is set.
		if ( empty( $custom_slug ) || 'wp-login' === $custom_slug ) {
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
		$custom_slug = isset( $settings['custom_login_slug'] ) ? sanitize_title( $settings['custom_login_slug'] ) : '';

		if ( empty( $custom_slug ) ) {
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
		$custom_slug = isset( $settings['custom_login_slug'] ) ? sanitize_title( $settings['custom_login_slug'] ) : '';

		if ( empty( $custom_slug ) ) {
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
	 * Customize login page.
	 */
	public function customize_login_page() {
		$settings = am_get_module_setting( 'custom-login' );
		$logo_url = isset( $settings['logo_url'] ) ? $settings['logo_url'] : '';

		if ( ! empty( $logo_url ) ) {
			?>
			<style>
				#login h1 a {
					background-image: url('<?php echo esc_url( $logo_url ); ?>');
					background-size: contain;
					background-position: center;
					width: 100%;
					max-width: 320px;
					height: 100px;
				}
			</style>
			<?php
		}
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
				}
			<?php endif; ?>

			<?php if ( ! empty( $custom_css ) ) : ?>
				<?php echo wp_strip_all_tags( $custom_css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</style>
		<?php
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
