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

		// Custom login URL.
		add_action( 'plugins_loaded', array( $this, 'custom_login_url' ) );
		add_filter( 'site_url', array( $this, 'change_login_url' ), 10, 4 );
		add_filter( 'wp_redirect', array( $this, 'change_redirect_url' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media_uploader' ) );
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

	/**
	 * Custom login URL handler.
	 */
	public function custom_login_url() {
		$settings = am_get_module_setting( 'custom-login' );
		$custom_slug = isset( $settings['custom_login_slug'] ) ? $settings['custom_login_slug'] : '';

		// Only proceed if custom slug is set and not empty.
		if ( empty( $custom_slug ) ) {
			return;
		}

		// Sanitize the slug.
		$custom_slug = sanitize_title( $custom_slug );

		// Prevent access to default login URLs.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		// Block direct access to wp-login.php.
		if ( strpos( $request_uri, 'wp-login.php' ) !== false && ! isset( $_GET['action'] ) ) {
			if ( ! is_user_logged_in() ) {
				// Check if it's our custom URL.
				if ( strpos( $request_uri, $custom_slug ) === false ) {
					wp_safe_redirect( home_url( '/404' ) );
					exit;
				}
			}
		}

		// Handle custom login URL.
		if ( strpos( $request_uri, '/' . $custom_slug ) !== false ) {
			// Allow access to login page via custom URL.
			if ( ! is_user_logged_in() ) {
				// Show login form.
				require_once ABSPATH . 'wp-login.php';
				exit;
			} else {
				// Already logged in, redirect to admin.
				wp_safe_redirect( admin_url() );
				exit;
			}
		}
	}

	/**
	 * Change login URL in site_url.
	 *
	 * @param string $url URL.
	 * @param string $path Path.
	 * @param string $scheme Scheme.
	 * @param int    $blog_id Blog ID.
	 * @return string Modified URL.
	 */
	public function change_login_url( $url, $path, $scheme, $blog_id ) {
		$settings = am_get_module_setting( 'custom-login' );
		$custom_slug = isset( $settings['custom_login_slug'] ) ? $settings['custom_login_slug'] : '';

		if ( empty( $custom_slug ) ) {
			return $url;
		}

		// Replace wp-login.php with custom slug.
		if ( strpos( $url, 'wp-login.php' ) !== false ) {
			$url = str_replace( 'wp-login.php', $custom_slug, $url );
		}

		return $url;
	}

	/**
	 * Change redirect URL after login.
	 *
	 * @param string $location Redirect location.
	 * @param int    $status Status code.
	 * @return string Modified location.
	 */
	public function change_redirect_url( $location, $status ) {
		$settings = am_get_module_setting( 'custom-login' );
		$custom_slug = isset( $settings['custom_login_slug'] ) ? $settings['custom_login_slug'] : '';

		if ( empty( $custom_slug ) ) {
			return $location;
		}

		// Replace wp-login.php in redirects.
		if ( strpos( $location, 'wp-login.php' ) !== false ) {
			$location = str_replace( 'wp-login.php', $custom_slug, $location );
		}

		return $location;
	}
}
