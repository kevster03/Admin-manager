<?php
/**
 * Performance Module
 *
 * Optimize site performance: Google Fonts, preconnect, preload assets.
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Performance class.
 */
class AM_Performance {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Google Fonts handling.
		add_action( 'init', array( $this, 'handle_google_fonts' ), 1 );

		// Resource hints (preconnect, dns-prefetch).
		add_filter( 'wp_resource_hints', array( $this, 'add_resource_hints' ), 10, 2 );

		// Preload assets.
		add_action( 'wp_head', array( $this, 'add_preload_assets' ), 1 );

		// Remove Google Fonts from resource hints if set to remove.
		add_filter( 'wp_resource_hints', array( $this, 'remove_google_fonts_hints' ), 999, 2 );
	}

	/**
	 * Handle Google Fonts (remove or self-host).
	 */
	public function handle_google_fonts() {
		$settings = am_get_module_setting( 'performance' );
		$google_fonts_action = isset( $settings['google_fonts_action'] ) ? $settings['google_fonts_action'] : 'none';

		if ( 'remove' === $google_fonts_action ) {
			// Remove Google Fonts from frontend and admin - high priority to override other plugins.
			add_filter( 'style_loader_tag', array( $this, 'remove_google_fonts_style' ), PHP_INT_MAX, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_google_fonts' ), PHP_INT_MAX );
			add_action( 'wp_print_styles', array( $this, 'dequeue_google_fonts' ), PHP_INT_MAX );

			// Start output buffering to remove Google Fonts from HTML - earliest possible.
			add_action( 'template_redirect', array( $this, 'start_buffer' ), -9999 );

			// Also handle admin side.
			add_action( 'admin_enqueue_scripts', array( $this, 'dequeue_google_fonts' ), PHP_INT_MAX );
			add_action( 'admin_print_styles', array( $this, 'dequeue_google_fonts' ), PHP_INT_MAX );
		}
	}

	/**
	 * Remove Google Fonts from style tags.
	 *
	 * @param string $html Style tag HTML.
	 * @param string $handle Style handle.
	 * @return string Modified HTML.
	 */
	public function remove_google_fonts_style( $html, $handle ) {
		// Check if this is a Google Fonts stylesheet.
		if ( false !== strpos( $html, 'fonts.googleapis.com' ) || false !== strpos( $html, 'fonts.gstatic.com' ) ) {
			return '';
		}

		return $html;
	}

	/**
	 * Dequeue registered Google Fonts.
	 */
	public function dequeue_google_fonts() {
		global $wp_styles;

		if ( ! isset( $wp_styles->registered ) || ! is_array( $wp_styles->registered ) ) {
			return;
		}

		foreach ( $wp_styles->registered as $handle => $data ) {
			if ( isset( $data->src ) && ( false !== strpos( $data->src, 'fonts.googleapis.com' ) || false !== strpos( $data->src, 'fonts.gstatic.com' ) ) ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
	}

	/**
	 * Start output buffering to remove Google Fonts from HTML.
	 */
	public function start_buffer() {
		ob_start( array( $this, 'end_buffer' ) );
	}

	/**
	 * Process buffer and remove Google Fonts references.
	 *
	 * @param string $html HTML content.
	 * @return string Modified HTML.
	 */
	public function end_buffer( $html ) {
		// Remove Google Fonts link tags.
		$html = preg_replace(
			'/<link[^>]*href=["\'][^"\']*fonts\.googleapis\.com[^"\']*["\'][^>]*>/i',
			'',
			$html
		);

		// Remove Google Fonts @import from inline styles.
		$html = preg_replace(
			'/@import\s+url\(["\']?https?:\/\/fonts\.googleapis\.com[^)]+\)[^;]*;?/i',
			'',
			$html
		);

		// Remove fonts.gstatic.com preconnect hints.
		$html = preg_replace(
			'/<link[^>]*rel=["\'](?:preconnect|dns-prefetch)["\'][^>]*href=["\'][^"\']*fonts\.(?:googleapis|gstatic)\.com[^"\']*["\'][^>]*>/i',
			'',
			$html
		);
		$html = preg_replace(
			'/<link[^>]*href=["\'][^"\']*fonts\.(?:googleapis|gstatic)\.com[^"\']*["\'][^>]*rel=["\'](?:preconnect|dns-prefetch)["\'][^>]*>/i',
			'',
			$html
		);

		// Remove Google Fonts from CSS content (inline and external).
		$html = preg_replace(
			'/url\(["\']?https?:\/\/fonts\.(?:googleapis|gstatic)\.com[^)]+\)/i',
			'',
			$html
		);

		return $html;
	}

	/**
	 * Remove Google Fonts from resource hints.
	 *
	 * @param array  $urls URLs for resource hints.
	 * @param string $relation_type Type of relation.
	 * @return array Modified URLs.
	 */
	public function remove_google_fonts_hints( $urls, $relation_type ) {
		$settings = am_get_module_setting( 'performance' );
		$google_fonts_action = isset( $settings['google_fonts_action'] ) ? $settings['google_fonts_action'] : 'none';

		if ( 'remove' !== $google_fonts_action ) {
			return $urls;
		}

		// Remove Google Fonts URLs.
		$filtered_urls = array();
		foreach ( $urls as $url ) {
			$href = is_array( $url ) ? $url['href'] : $url;

			// Skip Google Fonts domains.
			if ( false !== strpos( $href, 'fonts.googleapis.com' ) || false !== strpos( $href, 'fonts.gstatic.com' ) ) {
				continue;
			}

			$filtered_urls[] = $url;
		}

		return $filtered_urls;
	}

	/**
	 * Add resource hints (preconnect, dns-prefetch).
	 *
	 * @param array  $urls URLs for resource hints.
	 * @param string $relation_type Type of relation (preconnect, dns-prefetch).
	 * @return array Modified URLs.
	 */
	public function add_resource_hints( $urls, $relation_type ) {
		// Only add preconnect hints.
		if ( 'preconnect' !== $relation_type ) {
			return $urls;
		}

		$settings = am_get_module_setting( 'performance' );

		// Get current post type.
		$post_type = $this->get_current_post_type();

		if ( ! $post_type ) {
			return $urls;
		}

		// Get preconnect URLs for this post type.
		$preconnect_key = 'preconnect_' . $post_type;
		$preconnect_urls = isset( $settings[ $preconnect_key ] ) ? $settings[ $preconnect_key ] : '';

		if ( empty( $preconnect_urls ) ) {
			return $urls;
		}

		// Parse URLs (one per line).
		$lines = explode( "\n", $preconnect_urls );

		foreach ( $lines as $line ) {
			$url = trim( $line );

			// Validate URL.
			if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				continue;
			}

			// Add to hints.
			$urls[] = array(
				'href' => esc_url( $url ),
				'crossorigin' => 'anonymous',
			);
		}

		return $urls;
	}

	/**
	 * Add preload assets.
	 */
	public function add_preload_assets() {
		$settings = am_get_module_setting( 'performance' );

		// Get current post type.
		$post_type = $this->get_current_post_type();

		if ( ! $post_type ) {
			return;
		}

		// Get preload assets for this post type.
		$preload_key = 'preload_' . $post_type;
		$preload_assets = isset( $settings[ $preload_key ] ) ? $settings[ $preload_key ] : '';

		if ( empty( $preload_assets ) ) {
			return;
		}

		// Parse assets (format: url|type, one per line).
		$lines = explode( "\n", $preload_assets );

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( empty( $line ) ) {
				continue;
			}

			// Parse line: url|type (e.g., https://example.com/style.css|style).
			$parts = array_map( 'trim', explode( '|', $line ) );

			if ( count( $parts ) !== 2 ) {
				continue;
			}

			list( $url, $type ) = $parts;

			// Validate URL.
			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				continue;
			}

			// Validate type.
			$valid_types = array( 'style', 'script', 'font', 'image' );
			if ( ! in_array( $type, $valid_types, true ) ) {
				continue;
			}

			// Output preload link.
			$this->output_preload_link( $url, $type );
		}
	}

	/**
	 * Output preload link tag.
	 *
	 * @param string $url Asset URL.
	 * @param string $type Asset type (style, script, font, image).
	 */
	private function output_preload_link( $url, $type ) {
		$as_attr = $type;

		// Map types to 'as' attribute values.
		if ( 'style' === $type ) {
			$as_attr = 'style';
		} elseif ( 'script' === $type ) {
			$as_attr = 'script';
		} elseif ( 'font' === $type ) {
			$as_attr = 'font';
		} elseif ( 'image' === $type ) {
			$as_attr = 'image';
		}

		// Build preload link.
		$link = '<link rel="preload" href="' . esc_url( $url ) . '" as="' . esc_attr( $as_attr ) . '"';

		// Add type and crossorigin for fonts.
		if ( 'font' === $type ) {
			// Detect font MIME type from extension.
			$mime_type = $this->get_font_mime_type( $url );
			if ( $mime_type ) {
				$link .= ' type="' . esc_attr( $mime_type ) . '"';
			}
			$link .= ' crossorigin="anonymous"';
		}

		$link .= '>';

		echo $link . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get MIME type for font based on extension.
	 *
	 * @param string $url Font URL.
	 * @return string|false MIME type or false if not detected.
	 */
	private function get_font_mime_type( $url ) {
		$extension = pathinfo( $url, PATHINFO_EXTENSION );

		$mime_types = array(
			'woff2' => 'font/woff2',
			'woff'  => 'font/woff',
			'ttf'   => 'font/ttf',
			'otf'   => 'font/otf',
			'eot'   => 'application/vnd.ms-fontobject',
			'svg'   => 'image/svg+xml',
		);

		return isset( $mime_types[ $extension ] ) ? $mime_types[ $extension ] : false;
	}

	/**
	 * Get current post type.
	 *
	 * @return string|false Post type or false if not singular.
	 */
	private function get_current_post_type() {
		if ( is_singular() ) {
			return get_post_type();
		}

		if ( is_home() || is_front_page() ) {
			return 'page';
		}

		return false;
	}
}
