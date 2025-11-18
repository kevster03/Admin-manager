<?php
/**
 * Table of Contents - Admin Page
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle settings save.
if ( isset( $_POST['am_save_toc'] ) && am_verify_nonce( 'am_toc_nonce', 'am_save_toc' ) ) {
	if ( current_user_can( 'manage_options' ) ) {
		$settings = array(
			'enabled_post_types' => isset( $_POST['enabled_post_types'] ) && is_array( $_POST['enabled_post_types'] )
				? array_map( 'sanitize_text_field', $_POST['enabled_post_types'] )
				: array(),
			'position'           => isset( $_POST['position'] ) ? sanitize_text_field( $_POST['position'] ) : 'before',
			'heading_levels'     => isset( $_POST['heading_levels'] ) && is_array( $_POST['heading_levels'] )
				? array_map( 'sanitize_text_field', $_POST['heading_levels'] )
				: array( 'h2', 'h3', 'h4' ),
			'title'              => isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : 'Table of Contents',
			'bg_color'           => isset( $_POST['bg_color'] ) ? sanitize_hex_color( $_POST['bg_color'] ) : '#f9f9f9',
			'text_color'         => isset( $_POST['text_color'] ) ? sanitize_hex_color( $_POST['text_color'] ) : '#333333',
			'border_color'       => isset( $_POST['border_color'] ) ? sanitize_hex_color( $_POST['border_color'] ) : '#dddddd',
			'h2_color'           => isset( $_POST['h2_color'] ) ? sanitize_hex_color( $_POST['h2_color'] ) : '#0073aa',
			'other_color'        => isset( $_POST['other_color'] ) ? sanitize_hex_color( $_POST['other_color'] ) : '#555555',
			'border_width'       => isset( $_POST['border_width'] ) ? absint( $_POST['border_width'] ) : 1,
			'border_radius'      => isset( $_POST['border_radius'] ) ? absint( $_POST['border_radius'] ) : 4,
			'padding'            => isset( $_POST['padding'] ) ? absint( $_POST['padding'] ) : 20,
			'show_numbers'       => ! empty( $_POST['show_numbers'] ),
			'collapsible'        => ! empty( $_POST['collapsible'] ),
			'collapsed'          => ! empty( $_POST['collapsed'] ),
		);

		foreach ( $settings as $key => $value ) {
			am_update_module_setting( 'table-of-contents', $key, $value );
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'admin-manager' ) . '</p></div>';
	}
}

// Get current settings.
$settings = am_get_module_setting( 'table-of-contents' );
$enabled_post_types = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array();
$position = isset( $settings['position'] ) ? $settings['position'] : 'before';
$heading_levels = isset( $settings['heading_levels'] ) ? $settings['heading_levels'] : array( 'h2', 'h3', 'h4' );
$title = isset( $settings['title'] ) ? $settings['title'] : 'Table of Contents';
$bg_color = isset( $settings['bg_color'] ) ? $settings['bg_color'] : '#f9f9f9';
$text_color = isset( $settings['text_color'] ) ? $settings['text_color'] : '#333333';
$border_color = isset( $settings['border_color'] ) ? $settings['border_color'] : '#dddddd';
$h2_color = isset( $settings['h2_color'] ) ? $settings['h2_color'] : '#0073aa';
$other_color = isset( $settings['other_color'] ) ? $settings['other_color'] : '#555555';
$border_width = isset( $settings['border_width'] ) ? $settings['border_width'] : 1;
$border_radius = isset( $settings['border_radius'] ) ? $settings['border_radius'] : 4;
$padding = isset( $settings['padding'] ) ? $settings['padding'] : 20;
$show_numbers = ! empty( $settings['show_numbers'] );
$collapsible = ! empty( $settings['collapsible'] );
$collapsed = ! empty( $settings['collapsed'] );

// Get all post types.
$post_types = get_post_types( array( 'public' => true ), 'objects' );
?>

<div class="wrap am-admin-wrap">
	<h1><?php esc_html_e( 'Table of Contents', 'admin-manager' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Automatically generate a table of contents from post headings. Use the shortcode [am-toc] to display it anywhere.', 'admin-manager' ); ?>
	</p>

	<form method="post" action="">
		<?php wp_nonce_field( 'am_save_toc', 'am_toc_nonce' ); ?>

		<h2><?php esc_html_e( 'General Settings', 'admin-manager' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Enable For Post Types', 'admin-manager' ); ?>
				</th>
				<td>
					<?php foreach ( $post_types as $post_type ) : ?>
						<label style="display: block; margin-bottom: 8px;">
							<input
								type="checkbox"
								name="enabled_post_types[]"
								value="<?php echo esc_attr( $post_type->name ); ?>"
								<?php checked( in_array( $post_type->name, $enabled_post_types, true ) ); ?>
							>
							<?php echo esc_html( $post_type->label ); ?>
						</label>
					<?php endforeach; ?>
					<p class="description">
						<?php esc_html_e( 'Automatically display table of contents on selected post types.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="position"><?php esc_html_e( 'Position', 'admin-manager' ); ?></label>
				</th>
				<td>
					<select name="position" id="position">
						<option value="before" <?php selected( $position, 'before' ); ?>>
							<?php esc_html_e( 'Before Content', 'admin-manager' ); ?>
						</option>
						<option value="after" <?php selected( $position, 'after' ); ?>>
							<?php esc_html_e( 'After Content', 'admin-manager' ); ?>
						</option>
						<option value="manual" <?php selected( $position, 'manual' ); ?>>
							<?php esc_html_e( 'Manual (shortcode only)', 'admin-manager' ); ?>
						</option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Where to display the table of contents automatically.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Heading Levels', 'admin-manager' ); ?>
				</th>
				<td>
					<?php
					$all_levels = array( 'h2', 'h3', 'h4', 'h5', 'h6' );
					foreach ( $all_levels as $level ) :
						$level_label = strtoupper( $level );
						?>
						<label style="display: inline-block; margin-right: 15px;">
							<input
								type="checkbox"
								name="heading_levels[]"
								value="<?php echo esc_attr( $level ); ?>"
								<?php checked( in_array( $level, $heading_levels, true ) ); ?>
							>
							<?php echo esc_html( $level_label ); ?>
						</label>
					<?php endforeach; ?>
					<p class="description">
						<?php esc_html_e( 'Select which heading levels to include in the table of contents.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="title"><?php esc_html_e( 'Title', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="title"
						id="title"
						value="<?php echo esc_attr( $title ); ?>"
						class="regular-text"
					>
					<p class="description">
						<?php esc_html_e( 'The title shown at the top of the TOC.', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Appearance', 'admin-manager' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="bg_color"><?php esc_html_e( 'Background Color', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="bg_color"
						id="bg_color"
						value="<?php echo esc_attr( $bg_color ); ?>"
						class="am-color-picker"
					>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="text_color"><?php esc_html_e( 'Text Color', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="text_color"
						id="text_color"
						value="<?php echo esc_attr( $text_color ); ?>"
						class="am-color-picker"
					>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="border_color"><?php esc_html_e( 'Border Color', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="border_color"
						id="border_color"
						value="<?php echo esc_attr( $border_color ); ?>"
						class="am-color-picker"
					>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="h2_color"><?php esc_html_e( 'H2 Link Color', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="h2_color"
						id="h2_color"
						value="<?php echo esc_attr( $h2_color ); ?>"
						class="am-color-picker"
					>
					<p class="description">
						<?php esc_html_e( 'Color for H2 headings (main sections).', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="other_color"><?php esc_html_e( 'Other Headings Color', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="other_color"
						id="other_color"
						value="<?php echo esc_attr( $other_color ); ?>"
						class="am-color-picker"
					>
					<p class="description">
						<?php esc_html_e( 'Color for H3-H6 headings (sub-sections).', 'admin-manager' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="border_width"><?php esc_html_e( 'Border Width (px)', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						name="border_width"
						id="border_width"
						value="<?php echo esc_attr( $border_width ); ?>"
						min="0"
						max="10"
						class="small-text"
					>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="border_radius"><?php esc_html_e( 'Border Radius (px)', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						name="border_radius"
						id="border_radius"
						value="<?php echo esc_attr( $border_radius ); ?>"
						min="0"
						max="50"
						class="small-text"
					>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="padding"><?php esc_html_e( 'Padding (px)', 'admin-manager' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						name="padding"
						id="padding"
						value="<?php echo esc_attr( $padding ); ?>"
						min="0"
						max="50"
						class="small-text"
					>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Features', 'admin-manager' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Options', 'admin-manager' ); ?>
				</th>
				<td>
					<label style="display: block; margin-bottom: 8px;">
						<input type="checkbox" name="show_numbers" value="1" <?php checked( $show_numbers ); ?>>
						<?php esc_html_e( 'Show numbering (1. 2. 3.)', 'admin-manager' ); ?>
					</label>
					<label style="display: block; margin-bottom: 8px;">
						<input type="checkbox" name="collapsible" value="1" <?php checked( $collapsible ); ?>>
						<?php esc_html_e( 'Make collapsible (show/hide toggle)', 'admin-manager' ); ?>
					</label>
					<label style="display: block; margin-bottom: 8px;">
						<input type="checkbox" name="collapsed" value="1" <?php checked( $collapsed ); ?>>
						<?php esc_html_e( 'Start collapsed', 'admin-manager' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" name="am_save_toc" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'admin-manager' ); ?>
			</button>
		</p>
	</form>

	<hr>

	<h2><?php esc_html_e( 'Usage', 'admin-manager' ); ?></h2>
	<h3><?php esc_html_e( 'Shortcode', 'admin-manager' ); ?></h3>
	<p><code>[am-toc]</code> - <?php esc_html_e( 'Display table of contents for current post', 'admin-manager' ); ?></p>
	<p><code>[am_toc]</code> - <?php esc_html_e( 'Alternative shortcode format', 'admin-manager' ); ?></p>

	<h3><?php esc_html_e( 'Features', 'admin-manager' ); ?></h3>
	<ul>
		<li><?php esc_html_e( 'Automatically extracts headings from your content', 'admin-manager' ); ?></li>
		<li><?php esc_html_e( 'Generates unique IDs for each heading (if not present)', 'admin-manager' ); ?></li>
		<li><?php esc_html_e( 'Creates clickable anchor links that scroll smoothly to sections', 'admin-manager' ); ?></li>
		<li><?php esc_html_e( 'Fully customizable colors, borders, and spacing', 'admin-manager' ); ?></li>
		<li><?php esc_html_e( 'Optional numbering and collapsible functionality', 'admin-manager' ); ?></li>
	</ul>
</div>
