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
		$size = isset( $settings['size'] ) ? $settings['size'] : 'medium';

		// Get custom bio if set for this post.
		$custom_bio = '';
		if ( $post_id ) {
			$custom_bio = get_post_meta( $post_id, '_am_author_bio', true );
		}

		$bio = ! empty( $custom_bio ) ? $custom_bio : get_the_author_meta( 'description', $author_id );
		$name = get_the_author_meta( 'display_name', $author_id );
		$url = get_author_posts_url( $author_id );

		// Social links - using custom user meta.
		$twitter = get_user_meta( $author_id, 'am_twitter', true );
		$linkedin = get_user_meta( $author_id, 'am_linkedin', true );
		$facebook = get_user_meta( $author_id, 'am_facebook', true );
		$instagram = get_user_meta( $author_id, 'am_instagram', true );
		$website = get_the_author_meta( 'url', $author_id );

		// Custom colors: #d30038, #f2dec1, #c6e0f2, #e0c8ff, #2f010d.
		$box_class = 'am-author-box am-author-box-' . esc_attr( $size );
		$html = '<div class="' . esc_attr( $box_class ) . '" style="background: linear-gradient(135deg, #f2dec1 0%, #e0c8ff 100%); border: 2px solid #d30038; padding: 25px; margin: 30px 0; border-radius: 12px; box-shadow: 0 4px 15px rgba(47, 1, 13, 0.1); display: flex; gap: 25px; align-items: start; transition: transform 0.3s ease, box-shadow 0.3s ease;">';

		if ( $show_avatar ) {
			$avatar_size = 'large' === $size ? 128 : ( 'small' === $size ? 48 : 80 );
			$html .= '<div class="am-author-avatar" style="flex-shrink: 0;">';

			// Check for custom avatar from media library.
			$custom_avatar_id = get_user_meta( $author_id, 'am_custom_avatar', true );
			if ( $custom_avatar_id ) {
				$avatar_url = wp_get_attachment_image_url( $custom_avatar_id, array( $avatar_size, $avatar_size ) );
				if ( $avatar_url ) {
					$html .= '<img src="' . esc_url( $avatar_url ) . '" alt="' . esc_attr( $name ) . '" width="' . esc_attr( $avatar_size ) . '" height="' . esc_attr( $avatar_size ) . '" style="border-radius: 50%; border: 3px solid #d30038; box-shadow: 0 3px 10px rgba(47, 1, 13, 0.15);">';
				} else {
					$html .= get_avatar( $author_id, $avatar_size, '', $name, array( 'style' => 'border-radius: 50%; border: 3px solid #d30038; box-shadow: 0 3px 10px rgba(47, 1, 13, 0.15);' ) );
				}
			} else {
				$html .= get_avatar( $author_id, $avatar_size, '', $name, array( 'style' => 'border-radius: 50%; border: 3px solid #d30038; box-shadow: 0 3px 10px rgba(47, 1, 13, 0.15);' ) );
			}

			$html .= '</div>';
		}

		$html .= '<div class="am-author-info" style="flex: 1;">';
		$html .= '<h3 class="am-author-name" style="margin: 0 0 12px 0; font-size: 22px; font-weight: 700; letter-spacing: 0.3px;"><a href="' . esc_url( $url ) . '" style="text-decoration: none; color: #2f010d; transition: color 0.3s ease;" onmouseover="this.style.color=\'#d30038\'" onmouseout="this.style.color=\'#2f010d\'">' . esc_html( $name ) . '</a></h3>';

		if ( ! empty( $bio ) ) {
			$html .= '<div class="am-author-bio" style="margin-bottom: 15px; color: #2f010d; line-height: 1.6; opacity: 0.9;">' . wp_kses_post( wpautop( $bio ) ) . '</div>';
		}

		if ( $show_social && ( $twitter || $linkedin || $facebook || $instagram || $website ) ) {
			$html .= '<div class="am-author-social" style="display: flex; gap: 12px; flex-wrap: wrap;">';

			if ( $website ) {
				$html .= '<a href="' . esc_url( $website ) . '" target="_blank" rel="noopener" style="color: #fff; background: #d30038; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.3s ease; display: inline-block; box-shadow: 0 2px 5px rgba(211, 0, 56, 0.2);" onmouseover="this.style.background=\'#a80028\'; this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 8px rgba(211, 0, 56, 0.3)\'" onmouseout="this.style.background=\'#d30038\'; this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 5px rgba(211, 0, 56, 0.2)\'">' . esc_html__( 'Website', 'admin-manager' ) . '</a>';
			}

			if ( $twitter ) {
				$html .= '<a href="https://twitter.com/' . esc_attr( $twitter ) . '" target="_blank" rel="noopener" style="color: #fff; background: #d30038; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.3s ease; display: inline-block; box-shadow: 0 2px 5px rgba(211, 0, 56, 0.2);" onmouseover="this.style.background=\'#a80028\'; this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 8px rgba(211, 0, 56, 0.3)\'" onmouseout="this.style.background=\'#d30038\'; this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 5px rgba(211, 0, 56, 0.2)\'">' . esc_html__( 'Twitter', 'admin-manager' ) . '</a>';
			}

			if ( $linkedin ) {
				$html .= '<a href="' . esc_url( $linkedin ) . '" target="_blank" rel="noopener" style="color: #fff; background: #d30038; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.3s ease; display: inline-block; box-shadow: 0 2px 5px rgba(211, 0, 56, 0.2);" onmouseover="this.style.background=\'#a80028\'; this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 8px rgba(211, 0, 56, 0.3)\'" onmouseout="this.style.background=\'#d30038\'; this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 5px rgba(211, 0, 56, 0.2)\'">' . esc_html__( 'LinkedIn', 'admin-manager' ) . '</a>';
			}

			if ( $facebook ) {
				$html .= '<a href="' . esc_url( $facebook ) . '" target="_blank" rel="noopener" style="color: #fff; background: #d30038; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.3s ease; display: inline-block; box-shadow: 0 2px 5px rgba(211, 0, 56, 0.2);" onmouseover="this.style.background=\'#a80028\'; this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 8px rgba(211, 0, 56, 0.3)\'" onmouseout="this.style.background=\'#d30038\'; this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 5px rgba(211, 0, 56, 0.2)\'">' . esc_html__( 'Facebook', 'admin-manager' ) . '</a>';
			}

			if ( $instagram ) {
				$html .= '<a href="' . esc_url( $instagram ) . '" target="_blank" rel="noopener" style="color: #fff; background: #d30038; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.3s ease; display: inline-block; box-shadow: 0 2px 5px rgba(211, 0, 56, 0.2);" onmouseover="this.style.background=\'#a80028\'; this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 8px rgba(211, 0, 56, 0.3)\'" onmouseout="this.style.background=\'#d30038\'; this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 5px rgba(211, 0, 56, 0.2)\'">' . esc_html__( 'Instagram', 'admin-manager' ) . '</a>';
			}

			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return apply_filters( 'am_author_box_html', $html, $author_id, $post_id );
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
	}
}
