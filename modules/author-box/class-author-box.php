<?php
/**
 * Author Box Module
 *
 * Display author information box with bio and social links.
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Author Box class.
 */
class AM_Author_Box {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'the_content', array( $this, 'auto_insert_author_box' ), 20 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ) );
		add_action( 'show_user_profile', array( $this, 'add_user_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'add_user_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'save_user_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_profile_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Clear cache when user profile is updated.
		add_action( 'profile_update', array( $this, 'clear_cache_on_user_update' ) );
		add_action( 'delete_user', array( $this, 'clear_cache_on_user_delete' ) );
	}

	/**
	 * Auto-insert author box in content.
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function auto_insert_author_box( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		global $post;

		$settings = am_get_module_setting( 'author-box' );
		$enabled_post_types = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array();
		$position = isset( $settings['position'] ) ? $settings['position'] : 'after';

		if ( ! in_array( get_post_type( $post ), $enabled_post_types, true ) ) {
			return $content;
		}

		$author_box_html = $this->get_author_box_html( $post->post_author, $post->ID );

		if ( 'before' === $position ) {
			return $author_box_html . $content;
		} elseif ( 'after' === $position ) {
			return $content . $author_box_html;
		}

		return $content;
	}

	/**
	 * Get author box HTML.
	 *
	 * @param int $author_id Author ID.
	 * @param int $post_id Post ID.
	 * @return string Author box HTML.
	 */
	public function get_author_box_html( $author_id, $post_id = null ) {
		$settings = am_get_module_setting( 'author-box' );
		$show_avatar = isset( $settings['show_avatar'] ) ? $settings['show_avatar'] : true;
		$show_social = isset( $settings['show_social'] ) ? $settings['show_social'] : true;
		$show_badges = isset( $settings['show_badges'] ) ? $settings['show_badges'] : true;
		$layout = isset( $settings['layout'] ) ? $settings['layout'] : 'horizontal';
		$size = isset( $settings['size'] ) ? $settings['size'] : 'medium';

		// Get styling options with proper defaults.
		$title_prefix = isset( $settings['title_prefix'] ) ? sanitize_text_field( $settings['title_prefix'] ) : __( 'About the Author', 'admin-manager' );
		$bg_color = isset( $settings['bg_color'] ) ? sanitize_hex_color( $settings['bg_color'] ) : '#f9f9f9';
		$text_color = isset( $settings['text_color'] ) ? sanitize_hex_color( $settings['text_color'] ) : '#333333';
		$border_color = isset( $settings['border_color'] ) ? sanitize_hex_color( $settings['border_color'] ) : '#dddddd';
		$border_width = isset( $settings['border_width'] ) ? absint( $settings['border_width'] ) : 1;
		$border_radius = isset( $settings['border_radius'] ) ? absint( $settings['border_radius'] ) : 8;
		$padding = isset( $settings['padding'] ) ? absint( $settings['padding'] ) : 20;
		$badge_bg_color = isset( $settings['badge_bg_color'] ) ? sanitize_hex_color( $settings['badge_bg_color'] ) : $border_color;
		$badge_text_color = isset( $settings['badge_text_color'] ) ? sanitize_hex_color( $settings['badge_text_color'] ) : '#ffffff';

		// Check cache (includes settings hash so color changes create new cache).
		$settings_hash = md5( serialize( array( $bg_color, $text_color, $border_color, $border_width, $border_radius, $padding, $badge_bg_color, $badge_text_color, $layout, $size, $show_avatar, $show_social, $show_badges ) ) );
		$cache_key = 'am_ab_' . $author_id . '_' . ( $post_id ? $post_id : '0' ) . '_' . substr( $settings_hash, 0, 8 );
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		// Get custom bio if set for this post.
		$custom_bio = '';
		if ( $post_id ) {
			$custom_bio = get_post_meta( $post_id, '_am_author_bio', true );
		}

		$bio = ! empty( $custom_bio ) ? $custom_bio : get_the_author_meta( 'description', $author_id );
		$name = get_the_author_meta( 'display_name', $author_id );

		// Optimize: Get all user meta at once (1 query instead of 6).
		$all_user_meta = get_user_meta( $author_id );

		$twitter = isset( $all_user_meta['am_twitter'][0] ) ? $all_user_meta['am_twitter'][0] : '';
		$linkedin = isset( $all_user_meta['am_linkedin'][0] ) ? $all_user_meta['am_linkedin'][0] : '';
		$facebook = isset( $all_user_meta['am_facebook'][0] ) ? $all_user_meta['am_facebook'][0] : '';
		$instagram = isset( $all_user_meta['am_instagram'][0] ) ? $all_user_meta['am_instagram'][0] : '';
		$expertise_badges = isset( $all_user_meta['am_expertise_badges'][0] ) ? $all_user_meta['am_expertise_badges'][0] : '';
		$custom_avatar_id = isset( $all_user_meta['am_custom_avatar'][0] ) ? $all_user_meta['am_custom_avatar'][0] : '';

		$website = get_the_author_meta( 'url', $author_id );

		// Expertise badges.
		$badges_array = array();
		if ( ! empty( $expertise_badges ) ) {
			$badges_array = array_map( 'trim', explode( ',', $expertise_badges ) );
		}

		// Prepare data array for layout methods.
		$data = array(
			'author_id'         => $author_id,
			'name'              => $name,
			'bio'               => $bio,
			'show_avatar'       => $show_avatar,
			'show_social'       => $show_social,
			'show_badges'       => $show_badges,
			'size'              => $size,
			'title_prefix'      => $title_prefix,
			'bg_color'          => $bg_color,
			'text_color'        => $text_color,
			'border_color'      => $border_color,
			'border_width'      => $border_width,
			'border_radius'     => $border_radius,
			'padding'           => $padding,
			'badge_bg_color'    => $badge_bg_color,
			'badge_text_color'  => $badge_text_color,
			'twitter'           => $twitter,
			'linkedin'          => $linkedin,
			'facebook'          => $facebook,
			'instagram'         => $instagram,
			'website'           => $website,
			'badges_array'      => $badges_array,
			'custom_avatar_id'  => $custom_avatar_id,
		);

		// Generate HTML based on layout.
		switch ( $layout ) {
			case 'vertical':
				$html = $this->render_vertical_layout( $data );
				break;
			case 'card':
				$html = $this->render_card_layout( $data );
				break;
			case 'horizontal':
			default:
				$html = $this->render_horizontal_layout( $data );
				break;
		}

		// Add Person schema for SEO.
		$html .= $this->get_person_schema( $author_id, $data );

		// Add mobile responsive styles.
		$html .= $this->get_responsive_styles();

		$html = apply_filters( 'am_author_box_html', $html, $author_id, $post_id );

		// Cache for 1 day (cleared on user profile update).
		set_transient( $cache_key, $html, DAY_IN_SECONDS );

		return $html;
	}

	/**
	 * Render horizontal layout (default).
	 *
	 * @param array $data Author data and settings.
	 * @return string HTML output.
	 */
	private function render_horizontal_layout( $data ) {
		extract( $data );

		$container_styles = sprintf(
			'background-color: %s; color: %s; border: %dpx solid %s; border-radius: %dpx; padding: %dpx; margin: 30px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.05);',
			$bg_color,
			$text_color,
			$border_width,
			$border_color,
			$border_radius,
			$padding
		);

		$box_class = 'am-author-box am-author-box-' . esc_attr( $size ) . ' am-author-box-horizontal';
		$html = '<div class="' . esc_attr( $box_class ) . '" style="' . esc_attr( $container_styles ) . '">';

		// Title/Prefix
		if ( ! empty( $title_prefix ) ) {
			$html .= '<h4 class="am-author-title" style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: ' . esc_attr( $border_color ) . '; opacity: 0.8;">' . esc_html( $title_prefix ) . '</h4>';
		}

		$html .= '<div class="am-author-content" style="display: flex; gap: 25px; align-items: start;">';

		// Avatar
		if ( $show_avatar ) {
			$html .= $this->render_avatar( $author_id, $name, $size, $border_color, false, $custom_avatar_id );
		}

		$html .= '<div class="am-author-info" style="flex: 1;">';
		$html .= '<h3 class="am-author-name" style="margin: 0 0 12px 0; font-size: 22px; font-weight: 700; letter-spacing: 0.3px; color: ' . esc_attr( $text_color ) . ';">' . esc_html( $name ) . '</h3>';

		// Expertise badges
		if ( $show_badges && ! empty( $badges_array ) ) {
			$html .= $this->render_badges( $badges_array, $badge_bg_color, $badge_text_color );
		}

		if ( ! empty( $bio ) ) {
			$html .= '<div class="am-author-bio" style="margin-bottom: 15px; color: ' . esc_attr( $text_color ) . '; line-height: 1.6; opacity: 0.9;">' . wp_kses_post( wpautop( $bio ) ) . '</div>';
		}

		// Social links
		if ( $show_social && ( $twitter || $linkedin || $facebook || $instagram || $website ) ) {
			$html .= $this->render_social_links( $data );
		}

		$html .= '</div>'; // .am-author-info
		$html .= '</div>'; // .am-author-content
		$html .= '</div>'; // .am-author-box

		return $html;
	}

	/**
	 * Render vertical layout (avatar on top).
	 *
	 * @param array $data Author data and settings.
	 * @return string HTML output.
	 */
	private function render_vertical_layout( $data ) {
		extract( $data );

		$container_styles = sprintf(
			'background-color: %s; color: %s; border: %dpx solid %s; border-radius: %dpx; padding: %dpx; margin: 30px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.05);',
			$bg_color,
			$text_color,
			$border_width,
			$border_color,
			$border_radius,
			$padding
		);

		$box_class = 'am-author-box am-author-box-' . esc_attr( $size ) . ' am-author-box-vertical';
		$html = '<div class="' . esc_attr( $box_class ) . '" style="' . esc_attr( $container_styles ) . '">';

		// Title/Prefix
		if ( ! empty( $title_prefix ) ) {
			$html .= '<h4 class="am-author-title" style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: ' . esc_attr( $border_color ) . '; opacity: 0.8; text-align: center;">' . esc_html( $title_prefix ) . '</h4>';
		}

		$html .= '<div class="am-author-content" style="display: flex; flex-direction: column; align-items: center; text-align: center;">';

		// Avatar
		if ( $show_avatar ) {
			$html .= '<div style="margin-bottom: 20px;">' . $this->render_avatar( $author_id, $name, $size, $border_color, false, $custom_avatar_id ) . '</div>';
		}

		$html .= '<div class="am-author-info" style="width: 100%;">';
		$html .= '<h3 class="am-author-name" style="margin: 0 0 12px 0; font-size: 22px; font-weight: 700; letter-spacing: 0.3px; color: ' . esc_attr( $text_color ) . ';">' . esc_html( $name ) . '</h3>';

		// Expertise badges
		if ( $show_badges && ! empty( $badges_array ) ) {
			$html .= '<div style="display: flex; justify-content: center; margin-bottom: 15px;">' . $this->render_badges( $badges_array, $badge_bg_color, $badge_text_color ) . '</div>';
		}

		if ( ! empty( $bio ) ) {
			$html .= '<div class="am-author-bio" style="margin-bottom: 15px; color: ' . esc_attr( $text_color ) . '; line-height: 1.6; opacity: 0.9;">' . wp_kses_post( wpautop( $bio ) ) . '</div>';
		}

		// Social links
		if ( $show_social && ( $twitter || $linkedin || $facebook || $instagram || $website ) ) {
			$html .= '<div style="display: flex; justify-content: center;">' . $this->render_social_links( $data ) . '</div>';
		}

		$html .= '</div>'; // .am-author-info
		$html .= '</div>'; // .am-author-content
		$html .= '</div>'; // .am-author-box

		return $html;
	}

	/**
	 * Render card layout (centered, compact).
	 *
	 * @param array $data Author data and settings.
	 * @return string HTML output.
	 */
	private function render_card_layout( $data ) {
		extract( $data );

		$container_styles = sprintf(
			'background-color: %s; color: %s; border: %dpx solid %s; border-radius: %dpx; padding: %dpx; margin: 30px auto; max-width: 600px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);',
			$bg_color,
			$text_color,
			$border_width,
			$border_color,
			$border_radius,
			$padding
		);

		$box_class = 'am-author-box am-author-box-' . esc_attr( $size ) . ' am-author-box-card';
		$html = '<div class="' . esc_attr( $box_class ) . '" style="' . esc_attr( $container_styles ) . '">';

		// Title/Prefix
		if ( ! empty( $title_prefix ) ) {
			$html .= '<h4 class="am-author-title" style="margin: 0 0 20px 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: ' . esc_attr( $border_color ) . '; opacity: 0.8; text-align: center;">' . esc_html( $title_prefix ) . '</h4>';
		}

		$html .= '<div class="am-author-content" style="display: flex; flex-direction: column; align-items: center; text-align: center;">';

		// Avatar
		if ( $show_avatar ) {
			$html .= '<div style="margin-bottom: 20px;">' . $this->render_avatar( $author_id, $name, $size, $border_color, true, $custom_avatar_id ) . '</div>';
		}

		$html .= '<div class="am-author-info" style="width: 100%;">';
		$html .= '<h3 class="am-author-name" style="margin: 0 0 12px 0; font-size: 24px; font-weight: 700; letter-spacing: 0.3px; color: ' . esc_attr( $text_color ) . ';">' . esc_html( $name ) . '</h3>';

		// Expertise badges
		if ( $show_badges && ! empty( $badges_array ) ) {
			$html .= '<div style="display: flex; justify-content: center; margin-bottom: 15px;">' . $this->render_badges( $badges_array, $badge_bg_color, $badge_text_color ) . '</div>';
		}

		if ( ! empty( $bio ) ) {
			$html .= '<div class="am-author-bio" style="margin-bottom: 20px; color: ' . esc_attr( $text_color ) . '; line-height: 1.6; opacity: 0.9;">' . wp_kses_post( wpautop( $bio ) ) . '</div>';
		}

		// Social links
		if ( $show_social && ( $twitter || $linkedin || $facebook || $instagram || $website ) ) {
			$html .= '<div style="display: flex; justify-content: center;">' . $this->render_social_links( $data ) . '</div>';
		}

		$html .= '</div>'; // .am-author-info
		$html .= '</div>'; // .am-author-content
		$html .= '</div>'; // .am-author-box

		return $html;
	}

	/**
	 * Render avatar HTML.
	 *
	 * @param int    $author_id Author ID.
	 * @param string $name Author name.
	 * @param string $size Size (small, medium, large).
	 * @param string $border_color Border color.
	 * @param bool   $larger Make avatar larger for card layout.
	 * @param string $custom_avatar_id Custom avatar attachment ID.
	 * @return string Avatar HTML.
	 */
	private function render_avatar( $author_id, $name, $size, $border_color, $larger = false, $custom_avatar_id = '' ) {
		$avatar_size = 'large' === $size ? 128 : ( 'small' === $size ? 48 : 80 );
		if ( $larger ) {
			$avatar_size = (int) ( $avatar_size * 1.25 );
		}

		$avatar_border = sprintf( 'border-radius: 50%%; border: 3px solid %s; box-shadow: 0 2px 6px rgba(0,0,0,0.08);', $border_color );

		$html = '<div class="am-author-avatar" style="flex-shrink: 0;">';

		// Check for custom avatar from media library.
		if ( $custom_avatar_id ) {
			$avatar_url = wp_get_attachment_image_url( $custom_avatar_id, array( $avatar_size, $avatar_size ) );
			if ( $avatar_url ) {
				$html .= '<img src="' . esc_url( $avatar_url ) . '" alt="' . esc_attr( $name ) . '" width="' . esc_attr( $avatar_size ) . '" height="' . esc_attr( $avatar_size ) . '" loading="lazy" style="' . esc_attr( $avatar_border ) . '">';
			} else {
				$html .= get_avatar( $author_id, $avatar_size, '', $name, array( 'style' => $avatar_border ) );
			}
		} else {
			$html .= get_avatar( $author_id, $avatar_size, '', $name, array( 'style' => $avatar_border ) );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render expertise badges HTML.
	 *
	 * @param array  $badges_array Array of badge labels.
	 * @param string $badge_bg_color Badge background color.
	 * @param string $badge_text_color Badge text color.
	 * @return string Badges HTML.
	 */
	private function render_badges( $badges_array, $badge_bg_color, $badge_text_color ) {
		$html = '<div class="am-author-badges" style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">';

		foreach ( $badges_array as $badge ) {
			if ( empty( $badge ) ) {
				continue;
			}
			$html .= '<span style="background-color: ' . esc_attr( $badge_bg_color ) . '; color: ' . esc_attr( $badge_text_color ) . '; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; letter-spacing: 0.3px;">' . esc_html( $badge ) . '</span>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render social links HTML.
	 *
	 * @param array $data Author data and settings.
	 * @return string Social links HTML.
	 */
	private function render_social_links( $data ) {
		extract( $data );

		$button_color = $border_color;
		$button_hover = $this->darken_color( $border_color, 15 );

		$button_style = sprintf(
			'color: #fff; background: %s; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.3s ease; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.1);',
			$button_color
		);

		$html = '<div class="am-author-social" style="display: flex; gap: 12px; flex-wrap: wrap;">';

		if ( $website ) {
			$html .= '<a href="' . esc_url( $website ) . '" target="_blank" rel="noopener me" style="' . esc_attr( $button_style ) . '" onmouseover="this.style.background=\'' . esc_attr( $button_hover ) . '\'; this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 8px rgba(0,0,0,0.15)\'" onmouseout="this.style.background=\'' . esc_attr( $button_color ) . '\'; this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 5px rgba(0,0,0,0.1)\'">' . esc_html__( 'Website', 'admin-manager' ) . '</a>';
		}

		if ( $twitter ) {
			$html .= '<a href="https://twitter.com/' . esc_attr( $twitter ) . '" target="_blank" rel="noopener me" style="' . esc_attr( $button_style ) . '" onmouseover="this.style.background=\'' . esc_attr( $button_hover ) . '\'; this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 8px rgba(0,0,0,0.15)\'" onmouseout="this.style.background=\'' . esc_attr( $button_color ) . '\'; this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 5px rgba(0,0,0,0.1)\'">' . esc_html__( 'Twitter', 'admin-manager' ) . '</a>';
		}

		if ( $linkedin ) {
			$html .= '<a href="' . esc_url( $linkedin ) . '" target="_blank" rel="noopener me" style="' . esc_attr( $button_style ) . '" onmouseover="this.style.background=\'' . esc_attr( $button_hover ) . '\'; this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 8px rgba(0,0,0,0.15)\'" onmouseout="this.style.background=\'' . esc_attr( $button_color ) . '\'; this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 5px rgba(0,0,0,0.1)\'">' . esc_html__( 'LinkedIn', 'admin-manager' ) . '</a>';
		}

		if ( $facebook ) {
			$html .= '<a href="' . esc_url( $facebook ) . '" target="_blank" rel="noopener me" style="' . esc_attr( $button_style ) . '" onmouseover="this.style.background=\'' . esc_attr( $button_hover ) . '\'; this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 8px rgba(0,0,0,0.15)\'" onmouseout="this.style.background=\'' . esc_attr( $button_color ) . '\'; this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 5px rgba(0,0,0,0.1)\'">' . esc_html__( 'Facebook', 'admin-manager' ) . '</a>';
		}

		if ( $instagram ) {
			$html .= '<a href="' . esc_url( $instagram ) . '" target="_blank" rel="noopener me" style="' . esc_attr( $button_style ) . '" onmouseover="this.style.background=\'' . esc_attr( $button_hover ) . '\'; this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 8px rgba(0,0,0,0.15)\'" onmouseout="this.style.background=\'' . esc_attr( $button_color ) . '\'; this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 5px rgba(0,0,0,0.1)\'">' . esc_html__( 'Instagram', 'admin-manager' ) . '</a>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Darken a hex color by percentage.
	 *
	 * @param string $hex Hex color code.
	 * @param int $percent Percentage to darken (0-100).
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
	 * Add meta box for custom author bio.
	 */
	public function add_meta_box() {
		$settings = am_get_module_setting( 'author-box' );
		$enabled_post_types = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array();

		foreach ( $enabled_post_types as $post_type ) {
			add_meta_box(
				'am_author_bio',
				__( 'Custom Author Bio', 'admin-manager' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'normal',
				'low'
			);
		}
	}

	/**
	 * Render meta box content.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'am_author_bio_meta', 'am_author_bio_nonce' );

		$custom_bio = get_post_meta( $post->ID, '_am_author_bio', true );
		?>
		<p class="description">
			<?php esc_html_e( 'Override the author bio for this specific post.', 'admin-manager' ); ?>
		</p>
		<textarea name="am_author_bio" rows="5" style="width: 100%;"><?php echo esc_textarea( $custom_bio ); ?></textarea>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['am_author_bio_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['am_author_bio_nonce'] ) ), 'am_author_bio_meta' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['am_author_bio'] ) ) {
			update_post_meta( $post_id, '_am_author_bio', wp_kses_post( $_POST['am_author_bio'] ) );
		}
	}

	/**
	 * Render author box (for shortcode).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Author box HTML.
	 */
	public function render_author_box( $atts ) {
		global $post;

		$atts = shortcode_atts(
			array(
				'author_id' => $post ? $post->post_author : 0,
				'post_id'   => $post ? $post->ID : 0,
			),
			$atts,
			'am-author'
		);

		return $this->get_author_box_html( intval( $atts['author_id'] ), intval( $atts['post_id'] ) );
	}

	/**
	 * Enqueue admin scripts for media uploader.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'profile.php' !== $hook && 'user-edit.php' !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'am-author-avatar-uploader',
			AM_PLUGIN_URL . 'assets/js/author-avatar-uploader.js',
			array(),
			AM_VERSION,
			true
		);
	}

	/**
	 * Add user profile fields for custom avatar and social links.
	 *
	 * @param WP_User $user User object.
	 */
	public function add_user_profile_fields( $user ) {
		$custom_avatar_id = get_user_meta( $user->ID, 'am_custom_avatar', true );
		$custom_avatar_url = $custom_avatar_id ? wp_get_attachment_image_url( $custom_avatar_id, 'thumbnail' ) : '';
		$twitter = get_user_meta( $user->ID, 'am_twitter', true );
		$linkedin = get_user_meta( $user->ID, 'am_linkedin', true );
		$facebook = get_user_meta( $user->ID, 'am_facebook', true );
		$instagram = get_user_meta( $user->ID, 'am_instagram', true );
		?>
		<h2><?php esc_html_e( 'Author Box Settings', 'admin-manager' ); ?></h2>

		<table class="form-table">
			<tr>
				<th>
					<label for="am_custom_avatar"><?php esc_html_e( 'Custom Avatar', 'admin-manager' ); ?></label>
				</th>
				<td>
					<div id="am-avatar-preview" style="margin-bottom: 10px;">
						<?php if ( $custom_avatar_url ) : ?>
							<img src="<?php echo esc_url( $custom_avatar_url ); ?>" alt="<?php esc_attr_e( 'Custom Avatar', 'admin-manager' ); ?>" style="max-width: 150px; border-radius: 50%; border: 3px solid #d30038; box-shadow: 0 3px 10px rgba(47, 1, 13, 0.15);">
						<?php endif; ?>
					</div>
					<input type="hidden" name="am_custom_avatar" id="am_custom_avatar" value="<?php echo esc_attr( $custom_avatar_id ); ?>">
					<button type="button" class="button" id="am-upload-avatar-btn">
						<?php esc_html_e( 'Upload/Select Avatar', 'admin-manager' ); ?>
					</button>
					<button type="button" class="button" id="am-remove-avatar-btn" <?php echo ! $custom_avatar_url ? 'style="display:none;"' : ''; ?>>
						<?php esc_html_e( 'Remove Avatar', 'admin-manager' ); ?>
					</button>
					<p class="description">
						<?php esc_html_e( 'Upload a custom avatar from your media library. This will override your Gravatar. Recommended size: 200x200px.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="am_twitter"><?php esc_html_e( 'Twitter Username', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="am_twitter" id="am_twitter" value="<?php echo esc_attr( $twitter ); ?>" class="regular-text" placeholder="username">
					<p class="description"><?php esc_html_e( 'Enter your Twitter username (without @).', 'admin-manager' ); ?></p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="am_linkedin"><?php esc_html_e( 'LinkedIn URL', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="url" name="am_linkedin" id="am_linkedin" value="<?php echo esc_url( $linkedin ); ?>" class="regular-text" placeholder="https://linkedin.com/in/username">
					<p class="description"><?php esc_html_e( 'Enter your full LinkedIn profile URL.', 'admin-manager' ); ?></p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="am_facebook"><?php esc_html_e( 'Facebook URL', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="url" name="am_facebook" id="am_facebook" value="<?php echo esc_url( $facebook ); ?>" class="regular-text" placeholder="https://facebook.com/username">
					<p class="description"><?php esc_html_e( 'Enter your Facebook profile or page URL.', 'admin-manager' ); ?></p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="am_instagram"><?php esc_html_e( 'Instagram URL', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="url" name="am_instagram" id="am_instagram" value="<?php echo esc_url( $instagram ); ?>" class="regular-text" placeholder="https://instagram.com/username">
					<p class="description"><?php esc_html_e( 'Enter your Instagram profile URL.', 'admin-manager' ); ?></p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="am_expertise_badges"><?php esc_html_e( 'Expertise Badges', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="am_expertise_badges" id="am_expertise_badges" value="<?php echo esc_attr( get_user_meta( $user->ID, 'am_expertise_badges', true ) ); ?>" class="regular-text" placeholder="WordPress Expert, Designer, Developer">
					<p class="description"><?php esc_html_e( 'Enter comma-separated expertise tags (e.g., "WordPress Expert, Designer, SEO Specialist").', 'admin-manager' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save user profile fields.
	 *
	 * @param int $user_id User ID.
	 */
	public function save_user_profile_fields( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( isset( $_POST['am_custom_avatar'] ) ) {
			$avatar_id = intval( $_POST['am_custom_avatar'] );
			if ( $avatar_id > 0 ) {
				update_user_meta( $user_id, 'am_custom_avatar', $avatar_id );
			} else {
				delete_user_meta( $user_id, 'am_custom_avatar' );
			}
		}

		if ( isset( $_POST['am_twitter'] ) ) {
			update_user_meta( $user_id, 'am_twitter', sanitize_text_field( $_POST['am_twitter'] ) );
		}

		if ( isset( $_POST['am_linkedin'] ) ) {
			update_user_meta( $user_id, 'am_linkedin', esc_url_raw( $_POST['am_linkedin'] ) );
		}

		if ( isset( $_POST['am_facebook'] ) ) {
			update_user_meta( $user_id, 'am_facebook', esc_url_raw( $_POST['am_facebook'] ) );
		}

		if ( isset( $_POST['am_instagram'] ) ) {
			update_user_meta( $user_id, 'am_instagram', esc_url_raw( $_POST['am_instagram'] ) );
		}

		if ( isset( $_POST['am_expertise_badges'] ) ) {
			update_user_meta( $user_id, 'am_expertise_badges', sanitize_text_field( $_POST['am_expertise_badges'] ) );
		}
	}

	/**
	 * Get Person schema for author.
	 *
	 * @param int   $author_id Author ID.
	 * @param array $data Author data.
	 * @return string JSON-LD schema.
	 */
	private function get_person_schema( $author_id, $data ) {
		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Person',
			'name'     => $data['name'],
			'url'      => get_author_posts_url( $author_id ),
		);

		// Add description if available.
		if ( ! empty( $data['bio'] ) ) {
			$schema['description'] = wp_strip_all_tags( $data['bio'] );
		}

		// Add social media profiles.
		$same_as = array();
		if ( ! empty( $data['twitter'] ) ) {
			$same_as[] = 'https://twitter.com/' . $data['twitter'];
		}
		if ( ! empty( $data['linkedin'] ) ) {
			$same_as[] = $data['linkedin'];
		}
		if ( ! empty( $data['facebook'] ) ) {
			$same_as[] = $data['facebook'];
		}
		if ( ! empty( $data['instagram'] ) ) {
			$same_as[] = $data['instagram'];
		}
		if ( ! empty( $data['website'] ) ) {
			$same_as[] = $data['website'];
		}

		if ( ! empty( $same_as ) ) {
			$schema['sameAs'] = $same_as;
		}

		// Add image if custom avatar exists.
		if ( ! empty( $data['custom_avatar_id'] ) ) {
			$avatar_url = wp_get_attachment_image_url( $data['custom_avatar_id'], 'medium' );
			if ( $avatar_url ) {
				$schema['image'] = $avatar_url;
			}
		}

		return '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>';
	}

	/**
	 * Get responsive CSS styles.
	 *
	 * @return string Responsive CSS.
	 */
	private function get_responsive_styles() {
		return '<style>
		@media (max-width: 768px) {
			.am-author-box-horizontal .am-author-content {
				flex-direction: column !important;
				gap: 15px !important;
				text-align: center;
			}
			.am-author-box-horizontal .am-author-avatar {
				margin: 0 auto !important;
			}
			.am-author-box-horizontal .am-author-info {
				text-align: center !important;
			}
			.am-author-box .am-author-name {
				font-size: 20px !important;
			}
			.am-author-box .am-author-social {
				justify-content: center !important;
			}
			.am-author-box .am-author-social a {
				min-width: 44px;
				min-height: 44px;
				display: inline-flex;
				align-items: center;
				justify-content: center;
			}
		}
		</style>';
	}

	/**
	 * Clear cache when user is updated.
	 *
	 * @param int $user_id User ID.
	 */
	public function clear_cache_on_user_update( $user_id ) {
		// Clear all cached author boxes for this user.
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_am_ab_' . $user_id . '_%'
			)
		);
	}

	/**
	 * Clear cache when user is deleted.
	 *
	 * @param int $user_id User ID.
	 */
	public function clear_cache_on_user_delete( $user_id ) {
		$this->clear_cache_on_user_update( $user_id );
	}
}
