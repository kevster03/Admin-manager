<?php
/**
 * Admin class.
 *
 * Handles admin interface and settings.
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 */
class AM_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_module_toggle' ) );
		add_action( 'admin_init', array( $this, 'handle_safe_mode_toggle' ) );
		add_filter( 'plugin_action_links_' . AM_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Admin Manager', 'admin-manager' ),
			__( 'Admin Manager', 'admin-manager' ),
			'manage_options',
			'admin-manager',
			array( $this, 'render_modules_page' ),
			'dashicons-admin-tools',
			80
		);

		add_submenu_page(
			'admin-manager',
			__( 'Modules', 'admin-manager' ),
			__( 'Modules', 'admin-manager' ),
			'manage_options',
			'admin-manager',
			array( $this, 'render_modules_page' )
		);

		// Add submenus for each enabled module with settings.
		$this->add_module_submenus();
	}

	/**
	 * Add submenus for modules with settings pages.
	 */
	private function add_module_submenus() {
		$enabled_modules = am_get_settings( 'modules_enabled', array() );

		foreach ( $enabled_modules as $module_slug ) {
			$module_data = AM_Plugin::instance()->module_manager->get_module_data( $module_slug );

			if ( ! $module_data ) {
				continue;
			}

			// Check if module has admin file.
			$admin_file = AM_PLUGIN_DIR . 'modules/' . dirname( $module_data['file'] ) . '/admin.php';

			if ( file_exists( $admin_file ) ) {
				add_submenu_page(
					'admin-manager',
					$module_data['name'],
					$module_data['name'],
					'manage_options',
					'admin-manager-' . $module_slug,
					function () use ( $admin_file, $module_slug ) {
						include $admin_file;
					}
				);
			}
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on Admin Manager pages.
		if ( strpos( $hook, 'admin-manager' ) === false ) {
			return;
		}

		// Enqueue WordPress color picker.
		wp_enqueue_style( 'wp-color-picker' );

		// Enqueue admin CSS.
		wp_enqueue_style(
			'am-admin',
			AM_PLUGIN_URL . 'assets/css/admin.css',
			array( 'wp-color-picker' ),
			AM_VERSION
		);

		// Enqueue admin JS.
		wp_enqueue_script(
			'am-admin',
			AM_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			AM_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'am-admin',
			'amAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'am_admin_nonce' ),
			)
		);
	}

	/**
	 * Handle module toggle.
	 */
	public function handle_module_toggle() {
		if ( ! isset( $_POST['am_toggle_modules'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! am_verify_nonce( 'am_modules_nonce', 'am_toggle_modules' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'admin-manager' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'admin-manager' ) );
		}

		// Get enabled modules from POST.
		$enabled_modules = isset( $_POST['am_modules'] ) && is_array( $_POST['am_modules'] )
			? array_map( 'sanitize_key', $_POST['am_modules'] )
			: array();

		// Update settings.
		am_update_setting( 'modules_enabled', $enabled_modules );

		// Redirect with success message.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'admin-manager',
					'updated' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle safe mode toggle.
	 */
	public function handle_safe_mode_toggle() {
		if ( ! isset( $_POST['am_toggle_safe_mode'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! am_verify_nonce( 'am_safe_mode_nonce', 'am_toggle_safe_mode' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'admin-manager' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'admin-manager' ) );
		}

		// Toggle safe mode.
		$safe_mode = isset( $_POST['am_safe_mode'] ) ? (bool) $_POST['am_safe_mode'] : false;
		am_update_setting( 'safe_mode', $safe_mode );

		// Redirect with success message.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'admin-manager',
					'updated' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render modules page.
	 */
	public function render_modules_page() {
		$modules         = AM_Plugin::instance()->module_manager->get_modules();
		$enabled_modules = am_get_settings( 'modules_enabled', array() );
		$safe_mode       = am_is_safe_mode();
		?>
		<div class="wrap am-admin-wrap">
			<h1><?php esc_html_e( 'Admin Manager', 'admin-manager' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully.', 'admin-manager' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="am-admin-header">
				<p class="description">
					<?php esc_html_e( 'Enable or disable modules below. Only enabled modules will be loaded, keeping your site lightweight and fast.', 'admin-manager' ); ?>
				</p>
			</div>

			<!-- Safe Mode Toggle -->
			<div class="am-safe-mode-section">
				<form method="post" action="">
					<?php wp_nonce_field( 'am_toggle_safe_mode', 'am_safe_mode_nonce' ); ?>
					<h2><?php esc_html_e( 'Safe Mode', 'admin-manager' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'When enabled, changes from Script Manager and other modules only apply to logged-in administrators. This allows you to test settings safely before applying to all visitors.', 'admin-manager' ); ?>
					</p>
					<label class="am-toggle-label">
						<input type="checkbox" name="am_safe_mode" value="1" <?php checked( $safe_mode, true ); ?>>
						<span class="am-toggle-text">
							<?php esc_html_e( 'Enable Safe Mode (changes only apply to admins)', 'admin-manager' ); ?>
						</span>
					</label>
					<p>
						<button type="submit" name="am_toggle_safe_mode" class="button button-secondary">
							<?php esc_html_e( 'Update Safe Mode', 'admin-manager' ); ?>
						</button>
					</p>
				</form>
			</div>

			<hr>

			<!-- Modules List -->
			<form method="post" action="">
				<?php wp_nonce_field( 'am_toggle_modules', 'am_modules_nonce' ); ?>

				<h2><?php esc_html_e( 'Available Modules', 'admin-manager' ); ?></h2>

				<div class="am-modules-grid">
					<?php foreach ( $modules as $slug => $module ) : ?>
						<div class="am-module-card">
							<label class="am-module-label">
								<input
									type="checkbox"
									name="am_modules[]"
									value="<?php echo esc_attr( $slug ); ?>"
									<?php checked( in_array( $slug, $enabled_modules, true ) ); ?>
								>
								<div class="am-module-info">
									<h3><?php echo esc_html( $module['name'] ); ?></h3>
									<p class="description"><?php echo esc_html( $module['description'] ); ?></p>
								</div>
							</label>
						</div>
					<?php endforeach; ?>
				</div>

				<p class="submit">
					<button type="submit" name="am_toggle_modules" class="button button-primary">
						<?php esc_html_e( 'Save Module Settings', 'admin-manager' ); ?>
					</button>
				</p>
			</form>

			<hr>

			<!-- Debug Section -->
			<div class="am-debug-section">
				<h2><?php esc_html_e( 'Debug & Restore', 'admin-manager' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Use these tools to troubleshoot or reset settings.', 'admin-manager' ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Plugin Version:', 'admin-manager' ); ?></strong> <?php echo esc_html( AM_VERSION ); ?><br>
					<strong><?php esc_html_e( 'Active Modules:', 'admin-manager' ); ?></strong> <?php echo esc_html( count( $enabled_modules ) ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing links.
	 * @return array Modified links.
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=admin-manager' ),
			__( 'Settings', 'admin-manager' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}
}
