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
	}

	/**
	 * Handle Google Fonts (remove or self-host).
	 */
	public function handle_google_fonts() {
		$settings = am_get_module_setting( 'performance' );
		$google_fonts_action = isset( $settings['google_fonts_action'] ) ? $settings['google_fonts_action'] : 'none';

		if ( 'remove' === $google_fonts_action ) {
			// Remove Google Fonts from frontend and admin.
			add_filter( 'style_loader_tag', array( $this, 'remove_google_fonts_style' ), 10, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_google_fonts' ), PHP_INT_MAX );
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

		// Add crossorigin for fonts.
		if ( 'font' === $type ) {
			$link .= ' crossorigin="anonymous"';
		}

		$link .= '>';

		echo $link . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
