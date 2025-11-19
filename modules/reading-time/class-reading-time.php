<?php
/**
 * Reading Time Module
 *
 * Calculate and display reading time for posts.
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reading Time class.
 */
class AM_Reading_Time {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'the_content', array( $this, 'auto_insert_reading_time' ), 1 );

		// Clear cache when post is updated.
		add_action( 'save_post', array( $this, 'clear_cache_on_save' ) );
		add_action( 'delete_post', array( $this, 'clear_cache_on_delete' ) );
	}

	/**
	 * Auto-insert reading time in content.
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function auto_insert_reading_time( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		global $post;

		$settings = am_get_module_setting( 'reading-time' );
		$enabled_post_types = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array();
		$position = isset( $settings['position'] ) ? $settings['position'] : 'before';

		if ( ! in_array( get_post_type( $post ), $enabled_post_types, true ) ) {
			return $content;
		}

		$reading_time_html = $this->get_reading_time_html( $post->ID );

		if ( 'before' === $position ) {
			return $reading_time_html . $content;
		} elseif ( 'after' === $position ) {
			return $content . $reading_time_html;
		}

		return $content;
	}

	/**
	 * Get reading time HTML.
	 *
	 * @param int $post_id Post ID.
	 * @return string Reading time HTML.
	 */
	public function get_reading_time_html( $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$reading_time = $this->calculate_reading_time( $post_id );
		$settings = am_get_module_setting( 'reading-time' );

		// Get styling options with proper defaults.
		$bg_color       = isset( $settings['bg_color'] ) ? sanitize_hex_color( $settings['bg_color'] ) : '#f5f5f5';
		$text_color     = isset( $settings['text_color'] ) ? sanitize_hex_color( $settings['text_color'] ) : '#333333';
		$border_color   = isset( $settings['border_color'] ) ? sanitize_hex_color( $settings['border_color'] ) : '#dddddd';
		$border_width   = isset( $settings['border_width'] ) ? absint( $settings['border_width'] ) : 1;
		$border_radius  = isset( $settings['border_radius'] ) ? absint( $settings['border_radius'] ) : 4;
		$padding        = isset( $settings['padding'] ) ? absint( $settings['padding'] ) : 10;
		$show_icon      = ! empty( $settings['show_icon'] );

		$container_style = sprintf(
			'display: inline-flex; align-items: center; gap: 8px; background-color: %s; border: %dpx solid %s; padding: %dpx 18px; margin: 20px 0; border-radius: %dpx; box-shadow: 0 2px 6px rgba(0,0,0,0.05);',
			$bg_color,
			$border_width,
			$border_color,
			$padding,
			$border_radius
		);

		$html = '<div class="am-reading-time" style="' . esc_attr( $container_style ) . '">';

		if ( $show_icon ) {
			$html .= sprintf(
				'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
				esc_attr( $border_color )
			);
		}

		$html .= sprintf(
			'<span style="color: %s; font-size: 15px; font-weight: 600; letter-spacing: 0.3px;">%s</span>',
			esc_attr( $text_color ),
			esc_html( $reading_time )
		);

		$html .= '</div>';

		return apply_filters( 'am_reading_time_html', $html, $post_id );
	}

	/**
	 * Calculate reading time for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string Reading time string.
	 */
	public function calculate_reading_time( $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		// Check cache first (safe for caching plugins - uses unique prefix).
		$cache_key = 'am_rt_' . $post_id;
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return '';
		}

		$settings = am_get_module_setting( 'reading-time' );
		$wpm = isset( $settings['words_per_minute'] ) ? intval( $settings['words_per_minute'] ) : 200;

		$word_count = am_count_words( $post->post_content );
		$minutes = ceil( $word_count / $wpm );

		if ( $minutes < 1 ) {
			$minutes = 1;
		}

		/* translators: %d: Reading time in minutes */
		$result = sprintf( _n( '%d min read', '%d min read', $minutes, 'admin-manager' ), $minutes );

		// Cache for 1 week (cleared on post update).
		set_transient( $cache_key, $result, WEEK_IN_SECONDS );

		return $result;
	}

	/**
	 * Clear cache when post is saved.
	 *
	 * @param int $post_id Post ID.
	 */
	public function clear_cache_on_save( $post_id ) {
		delete_transient( 'am_rt_' . $post_id );
	}

	/**
	 * Clear cache when post is deleted.
	 *
	 * @param int $post_id Post ID.
	 */
	public function clear_cache_on_delete( $post_id ) {
		delete_transient( 'am_rt_' . $post_id );
	}

	/**
	 * Render reading time (for shortcode).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Reading time HTML.
	 */
	public function render_reading_time( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id' => get_the_ID(),
			),
			$atts,
			'am-reading-time'
		);

		return $this->get_reading_time_html( intval( $atts['post_id'] ) );
	}
}

/**
 * Get reading time for a post (public API function).
 *
 * @param int $post_id Post ID.
 * @return string Reading time string.
 */
function am_get_reading_time( $post_id = null ) {
	$module = AM_Plugin::instance()->module_manager->get_module( 'reading-time' );

	if ( $module ) {
		return $module->calculate_reading_time( $post_id );
	}

	return '';
}
