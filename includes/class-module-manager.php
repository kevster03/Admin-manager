<?php
/**
 * Module Manager class.
 *
 * Handles loading and management of plugin modules.
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module Manager class.
 */
class AM_Module_Manager {

	/**
	 * Available modules.
	 *
	 * @var array
	 */
	private $modules = array();

	/**
	 * Loaded module instances.
	 *
	 * @var array
	 */
	private $loaded_modules = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_modules();
	}

	/**
	 * Register all available modules.
	 */
	private function register_modules() {
		$this->modules = array(
			'author-box'          => array(
				'name'        => __( 'Author Box', 'admin-manager' ),
				'description' => __( 'Display author information box with bio, avatar, and social links.', 'admin-manager' ),
				'file'        => 'author-box/class-author-box.php',
				'class'       => 'AM_Author_Box',
			),
			'reading-time'        => array(
				'name'        => __( 'Reading Time', 'admin-manager' ),
				'description' => __( 'Calculate and display estimated reading time for posts.', 'admin-manager' ),
				'file'        => 'reading-time/class-reading-time.php',
				'class'       => 'AM_Reading_Time',
			),
			'table-of-contents'   => array(
				'name'        => __( 'Table of Contents', 'admin-manager' ),
				'description' => __( 'Automatically generate a table of contents from post headings with anchor links.', 'admin-manager' ),
				'file'        => 'table-of-contents/class-table-of-contents.php',
				'class'       => 'AM_Table_Of_Contents',
			),
			'custom-login'        => array(
				'name'        => __( 'Custom Login', 'admin-manager' ),
				'description' => __( 'Customize WordPress login page with logo, colors, and branding.', 'admin-manager' ),
				'file'        => 'custom-login/class-custom-login.php',
				'class'       => 'AM_Custom_Login',
			),
		);
	}

	/**
	 * Get all registered modules.
	 *
	 * @return array Modules array.
	 */
	public function get_modules() {
		return $this->modules;
	}

	/**
	 * Get specific module data.
	 *
	 * @param string $slug Module slug.
	 * @return array|false Module data or false if not found.
	 */
	public function get_module_data( $slug ) {
		return isset( $this->modules[ $slug ] ) ? $this->modules[ $slug ] : false;
	}

	/**
	 * Get loaded module instance.
	 *
	 * @param string $slug Module slug.
	 * @return object|false Module instance or false if not loaded.
	 */
	public function get_module( $slug ) {
		return isset( $this->loaded_modules[ $slug ] ) ? $this->loaded_modules[ $slug ] : false;
	}

	/**
	 * Load all enabled modules.
	 */
	public function load_enabled_modules() {
		$enabled_modules = am_get_settings( 'modules_enabled', array() );

		if ( empty( $enabled_modules ) || ! is_array( $enabled_modules ) ) {
			return;
		}

		foreach ( $enabled_modules as $module_slug ) {
			$this->load_module( $module_slug );
		}
	}

	/**
	 * Load a specific module.
	 *
	 * @param string $slug Module slug.
	 * @return bool Whether module was loaded successfully.
	 */
	public function load_module( $slug ) {
		// Check if module exists.
		if ( ! isset( $this->modules[ $slug ] ) ) {
			am_log( "Module '{$slug}' not found." );
			return false;
		}

		// Check if already loaded.
		if ( isset( $this->loaded_modules[ $slug ] ) ) {
			return true;
		}

		$module = $this->modules[ $slug ];
		$file   = AM_PLUGIN_DIR . 'modules/' . $module['file'];

		// Check if file exists.
		if ( ! file_exists( $file ) ) {
			am_log( "Module file not found: {$file}" );
			return false;
		}

		// Load module file.
		require_once $file;

		// Instantiate module class.
		if ( class_exists( $module['class'] ) ) {
			$this->loaded_modules[ $slug ] = new $module['class']();
			return true;
		}

		am_log( "Module class '{$module['class']}' not found." );
		return false;
	}

	/**
	 * Check if module is loaded.
	 *
	 * @param string $slug Module slug.
	 * @return bool Whether module is loaded.
	 */
	public function is_module_loaded( $slug ) {
		return isset( $this->loaded_modules[ $slug ] );
	}
}
