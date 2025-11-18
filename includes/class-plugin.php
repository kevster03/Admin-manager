<?php
/**
 * Main plugin class.
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class AM_Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var AM_Plugin
	 */
	private static $instance = null;

	/**
	 * Module manager instance.
	 *
	 * @var AM_Module_Manager
	 */
	public $module_manager;

	/**
	 * Admin instance.
	 *
	 * @var AM_Admin
	 */
	public $admin;

	/**
	 * Get plugin instance.
	 *
	 * @return AM_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin.
	 */
	private function init() {
		// Initialize module manager.
		$this->module_manager = new AM_Module_Manager();

		// Initialize admin if in admin area.
		if ( is_admin() ) {
			$this->admin = new AM_Admin();
		}

		// Load modules.
		add_action( 'init', array( $this, 'load_modules' ), 5 );

		// Register shortcodes.
		add_action( 'init', array( $this, 'register_shortcodes' ) );
	}

	/**
	 * Load enabled modules.
	 */
	public function load_modules() {
		$this->module_manager->load_enabled_modules();
	}

	/**
	 * Register all shortcodes.
	 */
	public function register_shortcodes() {
		// Author box shortcode.
		if ( am_is_module_enabled( 'author-box' ) ) {
			add_shortcode( 'am-author', array( $this, 'author_shortcode' ) );
		}

		// Reading time shortcode.
		if ( am_is_module_enabled( 'reading-time' ) ) {
			add_shortcode( 'am-reading-time', array( $this, 'reading_time_shortcode' ) );
		}

		// Form shortcode.
		if ( am_is_module_enabled( 'form-builder' ) ) {
			add_shortcode( 'am-form', array( $this, 'form_shortcode' ) );
		}
	}

	/**
	 * Author box shortcode callback.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function author_shortcode( $atts ) {
		$module = $this->module_manager->get_module( 'author-box' );
		if ( $module && method_exists( $module, 'render_author_box' ) ) {
			return $module->render_author_box( $atts );
		}
		return '';
	}

	/**
	 * Reading time shortcode callback.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function reading_time_shortcode( $atts ) {
		$module = $this->module_manager->get_module( 'reading-time' );
		if ( $module && method_exists( $module, 'render_reading_time' ) ) {
			return $module->render_reading_time( $atts );
		}
		return '';
	}

	/**
	 * Form shortcode callback.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function form_shortcode( $atts ) {
		$module = $this->module_manager->get_module( 'form-builder' );
		if ( $module && method_exists( $module, 'render_form' ) ) {
			return $module->render_form( $atts );
		}
		return '';
	}
}
