<?php
/**
 * Helper functions for Admin Manager plugin.
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get plugin settings.
 *
 * @param string $key Optional. Specific setting key to retrieve.
 * @param mixed  $default Default value if setting doesn't exist.
 * @return mixed Settings array or specific value.
 */
function am_get_settings( $key = '', $default = false ) {
	$settings = get_option( 'am_settings', array() );

	if ( empty( $key ) ) {
		return $settings;
	}

	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
}

/**
 * Update plugin settings.
 *
 * @param string $key Setting key.
 * @param mixed  $value Setting value.
 * @return bool Whether update was successful.
 */
function am_update_setting( $key, $value ) {
	$settings         = am_get_settings();
	$settings[ $key ] = $value;
	return update_option( 'am_settings', $settings );
}

/**
 * Check if a module is enabled.
 *
 * @param string $module_slug Module slug.
 * @return bool Whether module is enabled.
 */
function am_is_module_enabled( $module_slug ) {
	$enabled_modules = am_get_settings( 'modules_enabled', array() );
	return in_array( $module_slug, $enabled_modules, true );
}

/**
 * Check if safe mode is enabled.
 *
 * @return bool Whether safe mode is active.
 */
function am_is_safe_mode() {
	return (bool) am_get_settings( 'safe_mode', false );
}

/**
 * Check if current user should see safe mode changes.
 *
 * In safe mode, changes only apply to logged-in administrators.
 *
 * @return bool Whether to apply changes.
 */
function am_should_apply_changes() {
	if ( ! am_is_safe_mode() ) {
		return true; // Not in safe mode, apply to everyone.
	}

	// In safe mode, only apply to logged-in admins.
	return current_user_can( 'manage_options' );
}

/**
 * Sanitize module settings array.
 *
 * @param array $settings Settings to sanitize.
 * @return array Sanitized settings.
 */
function am_sanitize_module_settings( $settings ) {
	if ( ! is_array( $settings ) ) {
		return array();
	}

	$sanitized = array();

	foreach ( $settings as $key => $value ) {
		$key = sanitize_key( $key );

		if ( is_array( $value ) ) {
			$sanitized[ $key ] = am_sanitize_module_settings( $value );
		} elseif ( is_bool( $value ) ) {
			$sanitized[ $key ] = (bool) $value;
		} elseif ( is_numeric( $value ) ) {
			$sanitized[ $key ] = $value;
		} else {
			$sanitized[ $key ] = sanitize_text_field( $value );
		}
	}

	return $sanitized;
}

/**
 * Get module setting.
 *
 * @param string $module_slug Module slug.
 * @param string $key Setting key.
 * @param mixed  $default Default value.
 * @return mixed Setting value.
 */
function am_get_module_setting( $module_slug, $key = '', $default = false ) {
	$option_name = 'am_' . $module_slug . '_settings';
	$settings    = get_option( $option_name, array() );

	if ( empty( $key ) ) {
		return $settings;
	}

	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
}

/**
 * Update module setting.
 *
 * @param string $module_slug Module slug.
 * @param string $key Setting key.
 * @param mixed  $value Setting value.
 * @return bool Whether update was successful.
 */
function am_update_module_setting( $module_slug, $key, $value ) {
	$option_name          = 'am_' . $module_slug . '_settings';
	$settings             = get_option( $option_name, array() );
	$settings[ $key ]     = $value;
	return update_option( $option_name, $settings );
}

/**
 * Format file size.
 *
 * @param int $bytes File size in bytes.
 * @return string Formatted file size.
 */
function am_format_bytes( $bytes ) {
	if ( $bytes >= 1073741824 ) {
		return number_format( $bytes / 1073741824, 2 ) . ' GB';
	} elseif ( $bytes >= 1048576 ) {
		return number_format( $bytes / 1048576, 2 ) . ' MB';
	} elseif ( $bytes >= 1024 ) {
		return number_format( $bytes / 1024, 2 ) . ' KB';
	} else {
		return $bytes . ' bytes';
	}
}

/**
 * Get file size from URL.
 *
 * @param string $url File URL.
 * @return int|false File size in bytes or false on failure.
 */
function am_get_file_size( $url ) {
	// Convert to file path if local.
	if ( strpos( $url, home_url() ) === 0 ) {
		$file_path = str_replace( home_url(), ABSPATH, $url );
		$file_path = str_replace( '/', DIRECTORY_SEPARATOR, $file_path );

		if ( file_exists( $file_path ) ) {
			return filesize( $file_path );
		}
	}

	return false;
}

/**
 * Verify nonce with better error handling.
 *
 * @param string $nonce_field Nonce field name.
 * @param string $action Nonce action.
 * @return bool Whether nonce is valid.
 */
function am_verify_nonce( $nonce_field, $action ) {
	if ( ! isset( $_REQUEST[ $nonce_field ] ) ) {
		return false;
	}

	return wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_field ] ) ), $action );
}

/**
 * Count words in content.
 *
 * @param string $content Content to count.
 * @return int Word count.
 */
function am_count_words( $content ) {
	// Remove shortcodes.
	$content = strip_shortcodes( $content );

	// Remove HTML tags.
	$content = wp_strip_all_tags( $content );

	// Count words.
	$words = preg_split( '/\s+/', $content, -1, PREG_SPLIT_NO_EMPTY );

	return count( $words );
}

/**
 * Get current screen post type.
 *
 * @return string|false Post type or false.
 */
function am_get_current_screen_post_type() {
	global $post, $typenow, $current_screen;

	if ( $post && $post->post_type ) {
		return $post->post_type;
	} elseif ( $typenow ) {
		return $typenow;
	} elseif ( $current_screen && isset( $current_screen->post_type ) ) {
		return $current_screen->post_type;
	} elseif ( isset( $_REQUEST['post_type'] ) ) {
		return sanitize_key( $_REQUEST['post_type'] );
	}

	return false;
}

/**
 * Debug log function.
 *
 * @param mixed $message Message to log.
 */
function am_log( $message ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		if ( is_array( $message ) || is_object( $message ) ) {
			error_log( 'Admin Manager: ' . print_r( $message, true ) );
		} else {
			error_log( 'Admin Manager: ' . $message );
		}
	}
}
