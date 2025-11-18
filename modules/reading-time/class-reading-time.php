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

		// Custom colors: #d30038, #f2dec1, #c6e0f2, #e0c8ff, #2f010d.
		$html = sprintf(
			'<div class="am-reading-time" style="display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #f2dec1 0%%, #e0c8ff 100%%); border: 2px solid #d30038; padding: 10px 18px; margin: 20px 0; border-radius: 8px; box-shadow: 0 3px 10px rgba(47, 1, 13, 0.1); transition: transform 0.3s ease, box-shadow 0.3s ease;">' .
			'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="%3$s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>' .
			'<span style="color: #2f010d; font-size: 15px; font-weight: 600; letter-spacing: 0.3px;">%1$s</span>' .
			'</div>',
			esc_html( $reading_time ),
			'#d30038',
			'#d30038'
		);

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
		return sprintf( _n( '%d min read', '%d min read', $minutes, 'admin-manager' ), $minutes );
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
