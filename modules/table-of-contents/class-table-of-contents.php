<?php
/**
 * Table of Contents Module
 *
 * Generates customizable table of contents from headings.
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Table of Contents class.
 */
class AM_Table_Of_Contents {

	/**
	 * Global counter for heading IDs to prevent duplicates.
	 *
	 * @var array
	 */
	private $heading_ids = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add IDs to headings FIRST (priority 5).
		add_filter( 'the_content', array( $this, 'add_heading_ids' ), 5 );

		// Add TOC to content SECOND (priority 10).
		add_filter( 'the_content', array( $this, 'auto_insert_toc' ), 10 );

		// Register shortcode.
		add_shortcode( 'am-toc', array( $this, 'render_toc_shortcode' ) );
		add_shortcode( 'am_toc', array( $this, 'render_toc_shortcode' ) );

		// Clear cache when post is updated.
		add_action( 'save_post', array( $this, 'clear_cache_on_save' ) );
		add_action( 'delete_post', array( $this, 'clear_cache_on_delete' ) );
	}

	/**
	 * Add IDs to headings in content.
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function add_heading_ids( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$settings = am_get_module_setting( 'table-of-contents' );
		$heading_levels = isset( $settings['heading_levels'] ) && is_array( $settings['heading_levels'] )
			? $settings['heading_levels']
			: array( 'h2', 'h3', 'h4' );

		if ( empty( $heading_levels ) ) {
			return $content;
		}

		// Reset heading IDs for each post.
		$this->heading_ids = array();

		return $this->process_heading_ids( $content, $heading_levels );
	}

	/**
	 * Process content to add heading IDs.
	 *
	 * @param string $content Post content.
	 * @param array  $heading_levels Heading levels to process.
	 * @return string Modified content.
	 */
	private function process_heading_ids( $content, $heading_levels ) {
		$levels_pattern = implode( '|', array_map( 'preg_quote', $heading_levels ) );
		$pattern = '/<(' . $levels_pattern . ')([^>]*)>(.*?)<\/\1>/i';

		$content = preg_replace_callback(
			$pattern,
			function( $matches ) {
				$tag = $matches[1];
				$attrs = $matches[2];
				$text = $matches[3];

				// Check if ID already exists.
				if ( stripos( $attrs, 'id=' ) !== false ) {
					// Extract existing ID and add to tracking.
					if ( preg_match( '/id=["\']([^"\']+)["\']/', $attrs, $id_match ) ) {
						$this->heading_ids[] = $id_match[1];
					}
					return $matches[0];
				}

				// Generate unique ID.
				$id = $this->generate_heading_id( strip_tags( $text ) );

				return '<' . $tag . $attrs . ' id="' . esc_attr( $id ) . '">' . $text . '</' . $tag . '>';
			},
			$content
		);

		return $content;
	}

	/**
	 * Auto-insert TOC in content.
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function auto_insert_toc( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$settings = am_get_module_setting( 'table-of-contents' );
		$enabled_post_types = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array();
		$position = isset( $settings['position'] ) ? $settings['position'] : 'before';

		if ( ! in_array( get_post_type(), $enabled_post_types, true ) ) {
			return $content;
		}

		$toc_html = $this->generate_toc_html( $content );

		if ( empty( $toc_html ) ) {
			return $content;
		}

		if ( 'before' === $position ) {
			return $toc_html . $content;
		} elseif ( 'after' === $position ) {
			return $content . $toc_html;
		}

		return $content;
	}

	/**
	 * Generate TOC HTML.
	 *
	 * @param string $content Post content (with IDs already added).
	 * @return string TOC HTML or empty string.
	 */
	public function generate_toc_html( $content ) {
		// Check cache first (safe for caching plugins - uses unique prefix).
		global $post;
		if ( $post ) {
			$cache_key = 'am_toc_' . $post->ID;
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$settings = am_get_module_setting( 'table-of-contents' );

		// Get heading levels to include.
		$heading_levels = isset( $settings['heading_levels'] ) && is_array( $settings['heading_levels'] )
			? $settings['heading_levels']
			: array( 'h2', 'h3', 'h4' );

		if ( empty( $heading_levels ) ) {
			return '';
		}

		// Parse headings from content.
		$headings = $this->parse_headings( $content, $heading_levels );

		if ( empty( $headings ) ) {
			return '';
		}

		// Get styling options with proper defaults.
		$bg_color       = isset( $settings['bg_color'] ) ? sanitize_hex_color( $settings['bg_color'] ) : '#f9f9f9';
		$text_color     = isset( $settings['text_color'] ) ? sanitize_hex_color( $settings['text_color'] ) : '#333333';
		$border_color   = isset( $settings['border_color'] ) ? sanitize_hex_color( $settings['border_color'] ) : '#dddddd';
		$h2_color       = isset( $settings['h2_color'] ) ? sanitize_hex_color( $settings['h2_color'] ) : '#0073aa';
		$other_color    = isset( $settings['other_color'] ) ? sanitize_hex_color( $settings['other_color'] ) : '#555555';
		$border_width   = isset( $settings['border_width'] ) ? absint( $settings['border_width'] ) : 1;
		$border_radius  = isset( $settings['border_radius'] ) ? absint( $settings['border_radius'] ) : 4;
		$padding        = isset( $settings['padding'] ) ? absint( $settings['padding'] ) : 20;
		$title          = isset( $settings['title'] ) ? sanitize_text_field( $settings['title'] ) : __( 'Table of Contents', 'admin-manager' );
		$show_numbers   = ! empty( $settings['show_numbers'] );
		$collapsible    = ! empty( $settings['collapsible'] );
		$collapsed      = ! empty( $settings['collapsed'] );

		// Build styles.
		$container_styles = sprintf(
			'background-color: %s; color: %s; border: %dpx solid %s; border-radius: %dpx; padding: %dpx; margin: 20px 0;',
			$bg_color,
			$text_color,
			$border_width,
			$border_color,
			$border_radius,
			$padding
		);

		// Build TOC HTML.
		$html = '<div id="am-toc" class="am-toc" style="' . esc_attr( $container_styles ) . '">';

		// Title and toggle.
		$html .= '<div class="am-toc-header" style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">';
		$html .= '<h3 class="am-toc-title" style="margin: 0; font-size: 18px;">' . esc_html( $title ) . '</h3>';

		if ( $collapsible ) {
			$toggle_text = $collapsed ? __( '[Show]', 'admin-manager' ) : __( '[Hide]', 'admin-manager' );
			$html .= '<a href="#" class="am-toc-toggle" style="color: ' . esc_attr( $h2_color ) . '; text-decoration: none; font-size: 14px;">' . $toggle_text . '</a>';
		}

		$html .= '</div>';

		// TOC list - Remove all list bullets.
		$display_style = $collapsed ? 'display: none;' : '';
		$html .= '<div class="am-toc-list" style="' . $display_style . '">';
		$html .= '<ul style="margin: 0; padding-left: 0; list-style: none !important;">';

		$h2_counter = 1;
		$min_level = min( array_column( $headings, 'level' ) );

		foreach ( $headings as $heading ) {
			$is_h2 = ( 2 === $heading['level'] );
			$indent = ( $heading['level'] - $min_level ) * 20;

			$li_style = sprintf(
				'margin: 5px 0; padding-left: %dpx; list-style: none !important;',
				$indent
			);

			// Color based on heading level.
			$link_color = $is_h2 ? $h2_color : $other_color;
			$font_weight = $is_h2 ? 'bold' : 'normal';

			$link_style = sprintf(
				'color: %s; text-decoration: none; display: block; padding: 3px 0; font-weight: %s;',
				$link_color,
				$font_weight
			);

			// Show marker ONLY on H2s.
			$marker = '';
			if ( $is_h2 ) {
				if ( $show_numbers ) {
					$marker = $h2_counter . '. ';
				} else {
					$marker = '→ ';
				}
				++$h2_counter;
			}

			$html .= '<li style="' . esc_attr( $li_style ) . '">';
			$html .= '<a href="#' . esc_attr( $heading['id'] ) . '" class="am-toc-link" style="' . esc_attr( $link_style ) . '">';
			$html .= $marker . esc_html( $heading['text'] );
			$html .= '</a>';
			$html .= '</li>';
		}

		$html .= '</ul>';
		$html .= '</div>';
		$html .= '</div>';

		// Add JavaScript for toggle and smooth scroll.
		$html .= "
		<script>
		(function() {
			var tocElement = document.getElementById('am-toc');
			if (!tocElement) return;

			var toggle = tocElement.querySelector('.am-toc-toggle');
			var list = tocElement.querySelector('.am-toc-list');

			if (toggle && list) {
				toggle.addEventListener('click', function(e) {
					e.preventDefault();
					if (list.style.display === 'none') {
						list.style.display = 'block';
						this.textContent = '[Hide]';
					} else {
						list.style.display = 'none';
						this.textContent = '[Show]';
					}
				});
			}

			// Smooth scroll for TOC links.
			var links = tocElement.querySelectorAll('.am-toc-link');
			links.forEach(function(link) {
				link.addEventListener('click', function(e) {
					e.preventDefault();
					var targetId = this.getAttribute('href').substring(1);
					var target = document.getElementById(targetId);

					if (target) {
						var offsetTop = target.getBoundingClientRect().top + window.pageYOffset - 100;
						window.scrollTo({
							top: offsetTop,
							behavior: 'smooth'
						});
					}
				});
			});
		})();
		</script>
		";

		// Add BreadcrumbList schema for SEO.
		$html .= $this->get_breadcrumb_schema( $headings );

		$html = apply_filters( 'am_toc_html', $html, $headings );

		// Cache for 1 week (cleared on post update).
		if ( $post ) {
			set_transient( $cache_key, $html, WEEK_IN_SECONDS );
		}

		return $html;
	}

	/**
	 * Parse headings from content.
	 *
	 * @param string $content Post content.
	 * @param array  $levels Heading levels to include.
	 * @return array Array of headings with id, level, and text.
	 */
	private function parse_headings( $content, $levels ) {
		$headings = array();

		// Create regex pattern from levels.
		$levels_pattern = implode( '|', array_map( 'preg_quote', $levels ) );
		$pattern = '/<(' . $levels_pattern . ')([^>]*)>(.*?)<\/\1>/i';

		if ( preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$level = strtolower( $match[1] );
				$attrs = $match[2];
				$text = strip_tags( $match[3] );

				// Extract ID if it exists.
				$id = '';
				if ( preg_match( '/id=["\']([^"\']+)["\']/', $attrs, $id_match ) ) {
					$id = $id_match[1];
				} else {
					// Generate ID if missing.
					$id = $this->generate_heading_id( $text );
				}

				if ( ! empty( $id ) && ! empty( $text ) ) {
					$headings[] = array(
						'id'    => $id,
						'level' => intval( substr( $level, 1 ) ), // h2 -> 2.
						'text'  => $text,
					);
				}
			}
		}

		return $headings;
	}

	/**
	 * Generate heading ID from text.
	 *
	 * @param string $text Heading text.
	 * @return string Unique ID.
	 */
	private function generate_heading_id( $text ) {
		// Sanitize text to create ID.
		$id = sanitize_title_with_dashes( $text );

		// Handle empty IDs.
		if ( empty( $id ) ) {
			$id = 'heading';
		}

		// Ensure uniqueness.
		$original_id = $id;
		$counter = 1;

		while ( in_array( $id, $this->heading_ids, true ) ) {
			$id = $original_id . '-' . $counter;
			++$counter;
		}

		$this->heading_ids[] = $id;

		return $id;
	}

	/**
	 * Render TOC shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string TOC HTML.
	 */
	public function render_toc_shortcode( $atts ) {
		global $post;

		if ( ! $post || empty( $post->post_content ) ) {
			return '';
		}

		// Get heading levels from settings.
		$settings = am_get_module_setting( 'table-of-contents' );
		$heading_levels = isset( $settings['heading_levels'] ) && is_array( $settings['heading_levels'] )
			? $settings['heading_levels']
			: array( 'h2', 'h3', 'h4' );

		// Reset IDs for shortcode.
		$this->heading_ids = array();

		// Process content to add IDs.
		$processed_content = $this->process_heading_ids( $post->post_content, $heading_levels );

		// Generate TOC from processed content.
		return $this->generate_toc_html( $processed_content );
	}

	/**
	 * Get BreadcrumbList schema for TOC navigation.
	 *
	 * @param array $headings Array of headings.
	 * @return string JSON-LD schema.
	 */
	private function get_breadcrumb_schema( $headings ) {
		if ( empty( $headings ) ) {
			return '';
		}

		global $post;
		if ( ! $post ) {
			return '';
		}

		$items = array();
		$position = 1;

		// Add current page as first item.
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position++,
			'name'     => get_the_title(),
			'item'     => get_permalink(),
		);

		// Add each H2 heading as breadcrumb item.
		foreach ( $headings as $heading ) {
			if ( 2 === $heading['level'] ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => $heading['text'],
					'item'     => get_permalink() . '#' . $heading['id'],
				);
			}
		}

		// Only output schema if we have more than 1 item.
		if ( count( $items ) <= 1 ) {
			return '';
		}

		$schema = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);

		return '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>';
	}

	/**
	 * Clear cache when post is saved.
	 *
	 * @param int $post_id Post ID.
	 */
	public function clear_cache_on_save( $post_id ) {
		delete_transient( 'am_toc_' . $post_id );
	}

	/**
	 * Clear cache when post is deleted.
	 *
	 * @param int $post_id Post ID.
	 */
	public function clear_cache_on_delete( $post_id ) {
		delete_transient( 'am_toc_' . $post_id );
	}
}
