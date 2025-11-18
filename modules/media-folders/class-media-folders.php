<?php
/**
 * Virtual Media Folders Module
 *
 * Organize media library with virtual folders (taxonomy-based).
 *
 * @package AdminManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Media Folders class.
 */
class AM_Media_Folders {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_media_by_folder' ) );
		add_action( 'wp_ajax_am_create_folder', array( $this, 'ajax_create_folder' ) );
		add_action( 'wp_ajax_am_rename_folder', array( $this, 'ajax_rename_folder' ) );
		add_action( 'wp_ajax_am_delete_folder', array( $this, 'ajax_delete_folder' ) );
		add_action( 'wp_ajax_am_assign_to_folder', array( $this, 'ajax_assign_to_folder' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_folder_filter' ) );
	}

	/**
	 * Register media folder taxonomy.
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'          => __( 'Media Folders', 'admin-manager' ),
			'singular_name' => __( 'Media Folder', 'admin-manager' ),
			'search_items'  => __( 'Search Folders', 'admin-manager' ),
			'all_items'     => __( 'All Folders', 'admin-manager' ),
			'edit_item'     => __( 'Edit Folder', 'admin-manager' ),
			'update_item'   => __( 'Update Folder', 'admin-manager' ),
			'add_new_item'  => __( 'Add New Folder', 'admin-manager' ),
			'new_item_name' => __( 'New Folder Name', 'admin-manager' ),
			'menu_name'     => __( 'Folders', 'admin-manager' ),
		);

		register_taxonomy(
			'am_media_folder',
			'attachment',
			array(
				'labels'            => $labels,
				'hierarchical'      => true,
				'public'            => false,
				'show_ui'           => false,
				'show_admin_column' => false,
				'query_var'         => true,
				'rewrite'           => false,
			)
		);
	}

	/**
	 * Filter media library by folder.
	 *
	 * @param array $query Query args.
	 * @return array Modified query args.
	 */
	public function filter_media_by_folder( $query ) {
		if ( isset( $_REQUEST['am_media_folder'] ) && ! empty( $_REQUEST['am_media_folder'] ) ) {
			$folder_id = intval( $_REQUEST['am_media_folder'] );

			if ( $folder_id > 0 ) {
				$query['tax_query'] = array(
					array(
						'taxonomy' => 'am_media_folder',
						'field'    => 'term_id',
						'terms'    => $folder_id,
					),
				);
			}
		}

		return $query;
	}

	/**
	 * Add folder filter dropdown to media library.
	 */
	public function add_folder_filter() {
		$screen = get_current_screen();

		if ( ! $screen || 'upload' !== $screen->id ) {
			return;
		}

		$folders = get_terms(
			array(
				'taxonomy'   => 'am_media_folder',
				'hide_empty' => false,
			)
		);

		if ( empty( $folders ) || is_wp_error( $folders ) ) {
			return;
		}

		$selected = isset( $_GET['am_media_folder'] ) ? intval( $_GET['am_media_folder'] ) : 0;

		?>
		<select name="am_media_folder" id="am_media_folder">
			<option value=""><?php esc_html_e( 'All Folders', 'admin-manager' ); ?></option>
			<?php foreach ( $folders as $folder ) : ?>
				<option value="<?php echo esc_attr( $folder->term_id ); ?>" <?php selected( $selected, $folder->term_id ); ?>>
					<?php echo esc_html( $folder->name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'upload.php' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'am-media-folders',
			AM_PLUGIN_URL . 'modules/media-folders/assets/js/media-folders.js',
			array(),
			AM_VERSION,
			true
		);

		wp_localize_script(
			'am-media-folders',
			'amMediaFolders',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'am_media_folders' ),
				'strings' => array(
					'createFolder'  => __( 'Enter folder name:', 'admin-manager' ),
					'renameFolder'  => __( 'Enter new folder name:', 'admin-manager' ),
					'deleteConfirm' => __( 'Delete this folder? Files will not be deleted.', 'admin-manager' ),
				),
			)
		);

		wp_enqueue_style(
			'am-media-folders',
			AM_PLUGIN_URL . 'modules/media-folders/assets/css/media-folders.css',
			array(),
			AM_VERSION
		);
	}

	/**
	 * AJAX: Create folder.
	 */
	public function ajax_create_folder() {
		check_ajax_referer( 'am_media_folders', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'admin-manager' ) ) );
		}

		$folder_name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';

		if ( empty( $folder_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Folder name is required.', 'admin-manager' ) ) );
		}

		$parent_id = isset( $_POST['parent'] ) ? intval( $_POST['parent'] ) : 0;

		$folder = wp_insert_term( $folder_name, 'am_media_folder', array( 'parent' => $parent_id ) );

		if ( is_wp_error( $folder ) ) {
			wp_send_json_error( array( 'message' => $folder->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'folder' => array(
					'id'   => $folder['term_id'],
					'name' => $folder_name,
				),
			)
		);
	}

	/**
	 * AJAX: Rename folder.
	 */
	public function ajax_rename_folder() {
		check_ajax_referer( 'am_media_folders', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'admin-manager' ) ) );
		}

		$folder_id = isset( $_POST['folder_id'] ) ? intval( $_POST['folder_id'] ) : 0;
		$new_name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';

		if ( empty( $folder_id ) || empty( $new_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'admin-manager' ) ) );
		}

		$result = wp_update_term( $folder_id, 'am_media_folder', array( 'name' => $new_name ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Folder renamed successfully.', 'admin-manager' ) ) );
	}

	/**
	 * AJAX: Delete folder.
	 */
	public function ajax_delete_folder() {
		check_ajax_referer( 'am_media_folders', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'admin-manager' ) ) );
		}

		$folder_id = isset( $_POST['folder_id'] ) ? intval( $_POST['folder_id'] ) : 0;

		if ( empty( $folder_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid folder ID.', 'admin-manager' ) ) );
		}

		$result = wp_delete_term( $folder_id, 'am_media_folder' );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) ) ;
		}

		wp_send_json_success( array( 'message' => __( 'Folder deleted successfully.', 'admin-manager' ) ) );
	}

	/**
	 * AJAX: Assign attachment to folder.
	 */
	public function ajax_assign_to_folder() {
		check_ajax_referer( 'am_media_folders', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'admin-manager' ) ) );
		}

		$attachment_id = isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0;
		$folder_id = isset( $_POST['folder_id'] ) ? intval( $_POST['folder_id'] ) : 0;

		if ( empty( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid attachment ID.', 'admin-manager' ) ) );
		}

		if ( $folder_id > 0 ) {
			wp_set_object_terms( $attachment_id, $folder_id, 'am_media_folder' );
		} else {
			// Remove from all folders.
			wp_set_object_terms( $attachment_id, array(), 'am_media_folder' );
		}

		wp_send_json_success( array( 'message' => __( 'File moved successfully.', 'admin-manager' ) ) );
	}
}
