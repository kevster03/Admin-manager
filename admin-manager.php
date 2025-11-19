<?php
/**
 * Plugin Name: Admin Manager
 * Plugin URI: https://github.com/kevster03/Admin-manager
 * Description: Lightweight, modular WordPress plugin for content features. Includes reading time with progress bar, table of contents, author box with layouts and badges, virtual media folders with file explorer-like interface, and custom login page styling.
 * Version: 4.0.0
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Author: Admin Manager Team
 * Author URI: https://github.com/kevster03
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: admin-manager
 * Domain Path: /languages
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'AM_VERSION', '4.0.0' );
define( 'AM_PLUGIN_FILE', __FILE__ );
define( 'AM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Minimum requirements.
define( 'AM_MIN_PHP_VERSION', '7.4' );
define( 'AM_MIN_WP_VERSION', '5.9' );

/**
 * Check minimum requirements before loading plugin.
 *
 * @return bool Whether requirements are met.
 */
function am_requirements_met() {
	global $wp_version;

	if ( version_compare( PHP_VERSION, AM_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'am_php_version_notice' );
		return false;
	}

	if ( version_compare( $wp_version, AM_MIN_WP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'am_wp_version_notice' );
		return false;
	}

	return true;
}

/**
 * Display PHP version notice.
 */
function am_php_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required PHP version, 2: Current PHP version */
				esc_html__( 'Admin Manager requires PHP version %1$s or higher. You are running version %2$s. Please upgrade PHP.', 'admin-manager' ),
				esc_html( AM_MIN_PHP_VERSION ),
				esc_html( PHP_VERSION )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Display WordPress version notice.
 */
function am_wp_version_notice() {
	global $wp_version;
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required WP version, 2: Current WP version */
				esc_html__( 'Admin Manager requires WordPress version %1$s or higher. You are running version %2$s. Please upgrade WordPress.', 'admin-manager' ),
				esc_html( AM_MIN_WP_VERSION ),
				esc_html( $wp_version )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Load plugin textdomain for translations.
 */
function am_load_textdomain() {
	load_plugin_textdomain( 'admin-manager', false, dirname( AM_PLUGIN_BASENAME ) . '/languages' );
}
add_action( 'plugins_loaded', 'am_load_textdomain' );

// Check requirements before loading.
if ( ! am_requirements_met() ) {
	return;
}

// Load core files.
require_once AM_PLUGIN_DIR . 'includes/helpers.php';
require_once AM_PLUGIN_DIR . 'includes/class-module-manager.php';
require_once AM_PLUGIN_DIR . 'includes/class-admin.php';
require_once AM_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Initialize the plugin.
 */
function am_init_plugin() {
	AM_Plugin::instance();
}
add_action( 'plugins_loaded', 'am_init_plugin' );

/**
 * Activation hook.
 */
function am_activate() {
	// Set default options on activation.
	$default_settings = array(
		'modules_enabled' => array(),
		'safe_mode' => false,
	);

	if ( ! get_option( 'am_settings' ) ) {
		update_option( 'am_settings', $default_settings );
	}

	// Flush rewrite rules for custom post types.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'am_activate' );

/**
 * Deactivation hook.
 */
function am_deactivate() {
	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'am_deactivate' );
