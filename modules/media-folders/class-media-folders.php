<?php
/**
 * Media Folders Module
 *
 * Virtual folder organization for WordPress media library.
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
	 * Maximum folder depth level.
	 */
	const MAX_DEPTH = 4;

	/**
	 * Taxonomy name.
	 */
	const TAXONOMY = 'media_folder';

	/**
	 * Debug log option name.
	 */
	const DEBUG_LOG = 'am_media_folders_debug_log';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_media_by_folder' ) );
		add_filter( 'pre_get_posts', array( $this, 'filter_media_grid_by_folder' ) );
		add_action( 'wp_ajax_am_create_folder', array( $this, 'ajax_create_folder' ) );
		add_action( 'wp_ajax_am_rename_folder', array( $this, 'ajax_rename_folder' ) );
		add_action( 'wp_ajax_am_delete_folder', array( $this, 'ajax_delete_folder' ) );
		add_action( 'wp_ajax_am_move_media', array( $this, 'ajax_move_media' ) );
		add_action( 'wp_ajax_am_bulk_move_media', array( $this, 'ajax_bulk_move_media' ) );
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_folder_field_to_attachment' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'save_folder_field_for_attachment' ), 10, 2 );

		// Bulk actions
		add_filter( 'bulk_actions-upload', array( $this, 'add_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-upload', array( $this, 'handle_bulk_actions' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'bulk_action_admin_notice' ) );

		// Settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Debug
		add_action( 'wp_ajax_am_log_debug', array( $this, 'ajax_log_debug' ) );
		add_action( 'wp_ajax_am_clear_debug', array( $this, 'ajax_clear_debug' ) );
	}

	/**
	 * Register media folder taxonomy.
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'              => __( 'Media Folders', 'admin-manager' ),
			'singular_name'     => __( 'Media Folder', 'admin-manager' ),
			'search_items'      => __( 'Search Folders', 'admin-manager' ),
			'all_items'         => __( 'All Folders', 'admin-manager' ),
			'parent_item'       => __( 'Parent Folder', 'admin-manager' ),
			'parent_item_colon' => __( 'Parent Folder:', 'admin-manager' ),
			'edit_item'         => __( 'Edit Folder', 'admin-manager' ),
			'update_item'       => __( 'Update Folder', 'admin-manager' ),
			'add_new_item'      => __( 'Add New Folder', 'admin-manager' ),
			'new_item_name'     => __( 'New Folder Name', 'admin-manager' ),
			'menu_name'         => __( 'Folders', 'admin-manager' ),
		);

		register_taxonomy(
			self::TAXONOMY,
			'attachment',
			array(
				'hierarchical'      => true,
				'labels'            => $labels,
				'show_ui'           => false, // We'll build custom UI
				'show_admin_column' => false,
				'query_var'         => true,
				'public'            => false,
				'show_in_nav_menus' => false,
				'show_in_rest'      => true,
				'rewrite'           => false,
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'upload.php' !== $hook && 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'am-media-folders',
			AM_PLUGIN_URL . 'modules/media-folders/media-folders.js',
			array( 'jquery', 'media-views' ),
			AM_VERSION,
			true
		);

		wp_localize_script(
			'am-media-folders',
			'amMediaFolders',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'am_media_folders' ),
				'maxDepth'      => self::MAX_DEPTH,
				'folders'       => $this->get_folder_tree(),
				'currentFolder' => isset( $_GET['media_folder'] ) ? intval( $_GET['media_folder'] ) : 0,
				'strings'       => array(
					'createFolder'   => __( 'Create Folder', 'admin-manager' ),
					'folderName'     => __( 'Folder Name', 'admin-manager' ),
					'rename'         => __( 'Rename', 'admin-manager' ),
					'delete'         => __( 'Delete', 'admin-manager' ),
					'addFiles'       => __( 'Add Files', 'admin-manager' ),
					'confirmDelete'  => __( 'Are you sure you want to delete this folder? Media files will not be deleted.', 'admin-manager' ),
					'maxDepthError'  => __( 'Maximum folder depth reached (4 levels).', 'admin-manager' ),
					'allMedia'       => __( 'All Media', 'admin-manager' ),
					'uncategorized'  => __( 'Uncategorized', 'admin-manager' ),
				),
			)
		);

		wp_enqueue_style(
			'am-media-folders',
			AM_PLUGIN_URL . 'modules/media-folders/media-folders.css',
			array(),
			AM_VERSION
		);
	}

	/**
	 * Get folder tree structure.
	 *
	 * @return array Folder tree.
	 */
	public function get_folder_tree() {
		$cache_key = 'am_media_folders_tree';
		$tree = wp_cache_get( $cache_key );

		if ( false !== $tree ) {
			return $tree;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$tree = $this->build_tree( $terms );

		wp_cache_set( $cache_key, $tree, '', 3600 ); // Cache for 1 hour

		return $tree;
	}

	/**
	 * Build hierarchical tree from flat term list.
	 *
	 * @param array $terms Flat list of terms.
	 * @param int   $parent_id Parent term ID.
	 * @param int   $level Current level depth.
	 * @return array Hierarchical tree.
	 */
	private function build_tree( $terms, $parent_id = 0, $level = 1 ) {
		$branch = array();

		foreach ( $terms as $term ) {
			if ( $term->parent == $parent_id ) {
				$count = $this->get_folder_media_count( $term->term_id );
				$children = $this->build_tree( $terms, $term->term_id, $level + 1 );

				$branch[] = array(
					'id'       => $term->term_id,
					'name'     => $term->name,
					'slug'     => $term->slug,
					'parent'   => $term->parent,
					'count'    => $count,
					'level'    => $level,
					'children' => $children,
				);
			}
		}

		return $branch;
	}

	/**
	 * Get media count for a folder.
	 *
	 * @param int $folder_id Folder term ID.
	 * @return int Media count.
	 */
	private function get_folder_media_count( $folder_id ) {
		$count = wp_count_terms(
			array(
				'taxonomy' => self::TAXONOMY,
				'include'  => array( $folder_id ),
			)
		);

		if ( is_wp_error( $count ) ) {
			return 0;
		}

		return is_array( $count ) && isset( $count[0] ) ? $count[0]->count : 0;
	}

	/**
	 * Get folder depth level.
	 *
	 * @param int $folder_id Folder term ID.
	 * @return int Depth level.
	 */
	private function get_folder_depth( $folder_id ) {
		$depth = 1;
		$current_id = $folder_id;

		while ( $current_id > 0 && $depth < self::MAX_DEPTH + 1 ) {
			$term = get_term( $current_id, self::TAXONOMY );
			if ( is_wp_error( $term ) || ! $term ) {
				break;
			}
			$current_id = $term->parent;
			if ( $current_id > 0 ) {
				$depth++;
			}
		}

		return $depth;
	}

	/**
	 * Filter media library by folder (AJAX/Modal view).
	 *
	 * @param array $query Query args.
	 * @return array Modified query args.
	 */
	public function filter_media_by_folder( $query ) {
		if ( isset( $_REQUEST['query']['media_folder'] ) ) {
			$folder_id = intval( $_REQUEST['query']['media_folder'] );

			if ( $folder_id > 0 ) {
				$query['tax_query'] = array(
					array(
						'taxonomy' => self::TAXONOMY,
						'field'    => 'term_id',
						'terms'    => $folder_id,
					),
				);
			} elseif ( -1 === $folder_id ) {
				// Uncategorized - media without any folder
				$query['tax_query'] = array(
					array(
						'taxonomy' => self::TAXONOMY,
						'operator' => 'NOT EXISTS',
					),
				);
			}
		}

		return $query;
	}

	/**
	 * Filter media library by folder (Grid view).
	 *
	 * @param WP_Query $query The WP_Query instance.
	 */
	public function filter_media_grid_by_folder( $query ) {
		global $pagenow;

		// Only run on upload.php in admin
		if ( ! is_admin() || 'upload.php' !== $pagenow ) {
			return;
		}

		// Check if media_folder parameter is set
		if ( ! isset( $_GET['media_folder'] ) ) {
			return;
		}

		// Check if this is an attachment query (but don't require it to be set yet)
		$post_type = $query->get( 'post_type' );
		if ( $post_type && 'attachment' !== $post_type ) {
			return;
		}

		// Force post_type to attachment if not set
		if ( ! $post_type ) {
			$query->set( 'post_type', 'attachment' );
		}

		$folder_id = intval( $_GET['media_folder'] );

		// Get folder details for logging
		$folder = get_term( $folder_id, self::TAXONOMY );
		$folder_name = $folder && ! is_wp_error( $folder ) ? $folder->name : 'Unknown';

		// Get all attachments in this folder
		$attachments_in_folder = get_objects_in_term( $folder_id, self::TAXONOMY );

		self::debug_log( "filter_media_grid_by_folder: FILTERING BY FOLDER \"{$folder_name}\" (ID: {$folder_id})", 'info', array(
			'folder_id'             => $folder_id,
			'folder_name'           => $folder_name,
			'pagenow'               => $pagenow,
			'URL'                   => isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '',
			'attachments_in_folder' => is_array( $attachments_in_folder ) ? $attachments_in_folder : array(),
			'attachment_count'      => is_array( $attachments_in_folder ) ? count( $attachments_in_folder ) : 0,
			'query_vars'            => $query->query_vars,
		) );

		if ( $folder_id > 0 ) {
			// Show media in specific folder
			$tax_query = array(
				array(
					'taxonomy' => self::TAXONOMY,
					'field'    => 'term_id',
					'terms'    => $folder_id,
				),
			);

			$query->set( 'tax_query', $tax_query );

			self::debug_log( "filter_media_grid_by_folder: Applied tax_query filter", 'success', array(
				'tax_query'        => $tax_query,
				'expected_results' => is_array( $attachments_in_folder ) ? count( $attachments_in_folder ) : 0,
			) );
		} elseif ( -1 === $folder_id ) {
			// Show uncategorized media (not in any folder)
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => self::TAXONOMY,
						'operator' => 'NOT EXISTS',
					),
				)
			);
			self::debug_log( "filter_media_grid_by_folder: Applied uncategorized filter", 'success' );
		} elseif ( 0 === $folder_id ) {
			// Show all media - no filter needed
			self::debug_log( "filter_media_grid_by_folder: Showing all media (no filter)", 'info' );
		}
	}

	/**
	 * AJAX: Create new folder.
	 */
	public function ajax_create_folder() {
		check_ajax_referer( 'am_media_folders', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'admin-manager' ) ) );
		}

		$name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
		$parent_id = isset( $_POST['parent'] ) ? intval( $_POST['parent'] ) : 0;

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Folder name is required.', 'admin-manager' ) ) );
		}

		// Check depth limit
		if ( $parent_id > 0 ) {
			$parent_depth = $this->get_folder_depth( $parent_id );
			if ( $parent_depth >= self::MAX_DEPTH ) {
				wp_send_json_error( array( 'message' => __( 'Maximum folder depth reached (4 levels).', 'admin-manager' ) ) );
			}
		}

		$result = wp_insert_term(
			$name,
			self::TAXONOMY,
			array( 'parent' => $parent_id )
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Clear cache
		wp_cache_delete( 'am_media_folders_tree' );

		wp_send_json_success(
			array(
				'id'     => $result['term_id'],
				'name'   => $name,
				'parent' => $parent_id,
				'tree'   => $this->get_folder_tree(),
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

		$folder_id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		$name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';

		if ( empty( $name ) || $folder_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'admin-manager' ) ) );
		}

		$result = wp_update_term( $folder_id, self::TAXONOMY, array( 'name' => $name ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Clear cache
		wp_cache_delete( 'am_media_folders_tree' );

		wp_send_json_success( array( 'tree' => $this->get_folder_tree() ) );
	}

	/**
	 * AJAX: Delete folder.
	 */
	public function ajax_delete_folder() {
		check_ajax_referer( 'am_media_folders', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'admin-manager' ) ) );
		}

		$folder_id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

		if ( $folder_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid folder.', 'admin-manager' ) ) );
		}

		// Delete folder (media will remain unassigned)
		$result = wp_delete_term( $folder_id, self::TAXONOMY );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Clear cache
		wp_cache_delete( 'am_media_folders_tree' );

		wp_send_json_success( array( 'tree' => $this->get_folder_tree() ) );
	}

	/**
	 * AJAX: Move single media item to folder.
	 */
	public function ajax_move_media() {
		check_ajax_referer( 'am_media_folders', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'admin-manager' ) ) );
		}

		$attachment_id = isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0;
		$folder_id = isset( $_POST['folder_id'] ) ? intval( $_POST['folder_id'] ) : 0;

		if ( $attachment_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid media item.', 'admin-manager' ) ) );
		}

		if ( $folder_id > 0 ) {
			// Use false as 4th parameter to REPLACE (not append) - ensures file is only in one folder
			wp_set_object_terms( $attachment_id, $folder_id, self::TAXONOMY, false );
		} else {
			// Remove from all folders
			wp_delete_object_term_relationships( $attachment_id, self::TAXONOMY );
		}

		// Clear cache
		wp_cache_delete( 'am_media_folders_tree' );

		wp_send_json_success();
	}

	/**
	 * AJAX: Bulk move media items.
	 */
	public function ajax_bulk_move_media() {
		self::debug_log( 'AJAX bulk move started', 'info', array(
			'POST' => $_POST,
		) );

		check_ajax_referer( 'am_media_folders', 'nonce' );
		self::debug_log( 'Nonce verified', 'success' );

		if ( ! current_user_can( 'upload_files' ) ) {
			self::debug_log( 'Permission denied', 'error' );
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'admin-manager' ) ) );
		}

		$attachment_ids = isset( $_POST['attachment_ids'] ) ? array_map( 'intval', $_POST['attachment_ids'] ) : array();
		$folder_id = isset( $_POST['folder_id'] ) ? intval( $_POST['folder_id'] ) : 0;

		self::debug_log( 'Processing bulk move', 'info', array(
			'attachment_ids' => $attachment_ids,
			'folder_id'      => $folder_id,
		) );

		if ( empty( $attachment_ids ) ) {
			self::debug_log( 'No media items selected', 'error' );
			wp_send_json_error( array( 'message' => __( 'No media items selected.', 'admin-manager' ) ) );
		}

		// Verify folder exists
		if ( $folder_id > 0 ) {
			$folder_term = get_term( $folder_id, self::TAXONOMY );
			if ( is_wp_error( $folder_term ) || ! $folder_term ) {
				self::debug_log( "CRITICAL ERROR: Folder {$folder_id} does NOT exist!", 'error', array(
					'folder_id'    => $folder_id,
					'error'        => is_wp_error( $folder_term ) ? $folder_term->get_error_message() : 'Term not found',
					'taxonomy'     => self::TAXONOMY,
				) );
				wp_send_json_error( array( 'message' => 'Invalid folder ID' ) );
			}

			self::debug_log( "Folder verified: {$folder_term->name} (ID: {$folder_id})", 'success', array(
				'folder_id'   => $folder_id,
				'folder_name' => $folder_term->name,
				'taxonomy'    => self::TAXONOMY,
			) );
		}

		$moved_count = 0;
		foreach ( $attachment_ids as $attachment_id ) {
			// Verify attachment exists
			$attachment = get_post( $attachment_id );
			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				self::debug_log( "SKIP: Attachment {$attachment_id} does not exist or is not an attachment", 'warning', array(
					'attachment_id' => $attachment_id,
					'post_type'     => $attachment ? $attachment->post_type : 'null',
				) );
				continue;
			}

			// Get current terms BEFORE move
			$terms_before = wp_get_object_terms( $attachment_id, self::TAXONOMY, array( 'fields' => 'ids' ) );

			if ( $folder_id > 0 ) {
				// Use false as 4th parameter to REPLACE (not append) terms - ensures file is only in one folder
				$result = wp_set_object_terms( $attachment_id, $folder_id, self::TAXONOMY, false );

				// Get terms AFTER move to verify
				$terms_after = wp_get_object_terms( $attachment_id, self::TAXONOMY, array( 'fields' => 'ids' ) );

				if ( ! is_wp_error( $result ) && ! empty( $terms_after ) && in_array( $folder_id, $terms_after, true ) ) {
					$moved_count++;
					self::debug_log( "✓ VERIFIED: Attachment {$attachment_id} successfully assigned to folder {$folder_id}", 'success', array(
						'attachment_id'  => $attachment_id,
						'folder_id'      => $folder_id,
						'terms_before'   => $terms_before,
						'terms_after'    => $terms_after,
						'wp_set_result'  => $result,
					) );
				} else {
					self::debug_log( "✗ FAILED: Attachment {$attachment_id} was NOT assigned to folder!", 'error', array(
						'attachment_id'  => $attachment_id,
						'folder_id'      => $folder_id,
						'terms_before'   => $terms_before,
						'terms_after'    => $terms_after,
						'wp_set_result'  => $result,
						'is_error'       => is_wp_error( $result ),
						'error_message'  => is_wp_error( $result ) ? $result->get_error_message() : 'No error but terms not set',
					) );
				}
			} else {
				wp_delete_object_term_relationships( $attachment_id, self::TAXONOMY );
				$moved_count++;
				self::debug_log( "Removed attachment {$attachment_id} from all folders", 'success' );
			}
		}

		// Clear cache after bulk move
		wp_cache_delete( 'am_media_folders_tree' );

		self::debug_log( "Bulk move completed: {$moved_count} files moved", 'success' );

		wp_send_json_success( array(
			'count' => $moved_count,
			'message' => sprintf(
				/* translators: %d: Number of files moved */
				_n( '%d file moved successfully', '%d files moved successfully', $moved_count, 'admin-manager' ),
				$moved_count
			)
		) );
	}

	/**
	 * Add folder field to attachment edit screen.
	 *
	 * @param array  $fields Form fields.
	 * @param object $post Attachment post object.
	 * @return array Modified fields.
	 */
	public function add_folder_field_to_attachment( $fields, $post ) {
		$terms = wp_get_object_terms( $post->ID, self::TAXONOMY );
		$current_folder = ! empty( $terms ) && ! is_wp_error( $terms ) ? $terms[0]->term_id : 0;

		$folders = $this->get_folder_tree();
		$options = '<option value="0">' . __( 'No Folder', 'admin-manager' ) . '</option>';
		$options .= $this->render_folder_options( $folders, $current_folder );

		$fields['media_folder'] = array(
			'label' => __( 'Folder', 'admin-manager' ),
			'input' => 'html',
			'html'  => '<select name="attachments[' . $post->ID . '][media_folder]" id="attachments-' . $post->ID . '-media_folder">' . $options . '</select>',
		);

		return $fields;
	}

	/**
	 * Render folder options for select dropdown.
	 *
	 * @param array $folders Folder tree.
	 * @param int   $current_folder Current selected folder.
	 * @param int   $level Current level for indentation.
	 * @return string HTML options.
	 */
	private function render_folder_options( $folders, $current_folder, $level = 0 ) {
		$options = '';
		$indent = str_repeat( '&nbsp;&nbsp;', $level );

		foreach ( $folders as $folder ) {
			$selected = $folder['id'] == $current_folder ? ' selected' : '';
			$options .= sprintf(
				'<option value="%d"%s>%s%s</option>',
				$folder['id'],
				$selected,
				$indent,
				esc_html( $folder['name'] )
			);

			if ( ! empty( $folder['children'] ) ) {
				$options .= $this->render_folder_options( $folder['children'], $current_folder, $level + 1 );
			}
		}

		return $options;
	}

	/**
	 * Save folder field for attachment.
	 *
	 * @param array $post Post data.
	 * @param array $attachment Attachment data.
	 * @return array Post data.
	 */
	public function save_folder_field_for_attachment( $post, $attachment ) {
		if ( isset( $attachment['media_folder'] ) ) {
			$folder_id = intval( $attachment['media_folder'] );

			if ( $folder_id > 0 ) {
				// Use false as 4th parameter to REPLACE (not append) - ensures file is only in one folder
				wp_set_object_terms( $post['ID'], $folder_id, self::TAXONOMY, false );
			} else {
				wp_delete_object_term_relationships( $post['ID'], self::TAXONOMY );
			}

			// Clear cache
			wp_cache_delete( 'am_media_folders_tree' );
		}

		return $post;
	}

	/**
	 * Add custom bulk actions to media library.
	 *
	 * @param array $bulk_actions Existing bulk actions.
	 * @return array Modified bulk actions.
	 */
	public function add_bulk_actions( $bulk_actions ) {
		// Add separator
		$bulk_actions['am_separator'] = '--- ' . __( 'Media Folders', 'admin-manager' ) . ' ---';

		// Get all folders for dropdown
		$folders = $this->get_all_folders_flat();

		foreach ( $folders as $folder ) {
			$indent = str_repeat( '— ', $folder['level'] - 1 );
			$bulk_actions[ 'am_move_to_folder_' . $folder['id'] ] = sprintf(
				__( 'Move to: %s%s', 'admin-manager' ),
				$indent,
				$folder['name']
			);
		}

		// Add "Remove from folder" option
		$bulk_actions['am_remove_from_folder'] = __( 'Remove from Folder', 'admin-manager' );

		return $bulk_actions;
	}

	/**
	 * Get all folders as flat array.
	 *
	 * @return array Flat folder list.
	 */
	private function get_all_folders_flat() {
		$tree = $this->get_folder_tree();
		$flat = array();
		$this->flatten_tree( $tree, $flat );
		return $flat;
	}

	/**
	 * Flatten folder tree recursively.
	 *
	 * @param array $tree Folder tree.
	 * @param array &$flat Flat array reference.
	 */
	private function flatten_tree( $tree, &$flat ) {
		foreach ( $tree as $folder ) {
			$flat[] = $folder;
			if ( ! empty( $folder['children'] ) ) {
				$this->flatten_tree( $folder['children'], $flat );
			}
		}
	}

	/**
	 * Handle bulk actions.
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $doaction Action name.
	 * @param array  $post_ids Selected post IDs.
	 * @return string Modified redirect URL.
	 */
	public function handle_bulk_actions( $redirect_to, $doaction, $post_ids ) {
		// Check if it's our action
		if ( strpos( $doaction, 'am_move_to_folder_' ) === 0 || 'am_remove_from_folder' === $doaction ) {
			$count = 0;

			if ( 'am_remove_from_folder' === $doaction ) {
				// Remove from all folders
				foreach ( $post_ids as $post_id ) {
					wp_delete_object_term_relationships( $post_id, self::TAXONOMY );
					$count++;
				}
			} else {
				// Move to specific folder
				$folder_id = intval( str_replace( 'am_move_to_folder_', '', $doaction ) );

				if ( $folder_id > 0 ) {
					foreach ( $post_ids as $post_id ) {
						// Use false as 4th parameter to REPLACE (not append) - ensures file is only in one folder
						wp_set_object_terms( $post_id, $folder_id, self::TAXONOMY, false );
						$count++;
					}
				}
			}

			// Clear cache
			wp_cache_delete( 'am_media_folders_tree' );

			// Add query args for notice
			$redirect_to = add_query_arg(
				array(
					'am_bulk_moved'  => $count,
					'am_folder_id'   => isset( $folder_id ) ? $folder_id : 0,
				),
				$redirect_to
			);
		}

		return $redirect_to;
	}

	/**
	 * Display admin notice after bulk action.
	 */
	public function bulk_action_admin_notice() {
		if ( ! empty( $_REQUEST['am_bulk_moved'] ) ) {
			$count = intval( $_REQUEST['am_bulk_moved'] );
			$folder_id = isset( $_REQUEST['am_folder_id'] ) ? intval( $_REQUEST['am_folder_id'] ) : 0;

			if ( $folder_id > 0 ) {
				$folder = get_term( $folder_id, self::TAXONOMY );
				$folder_name = $folder && ! is_wp_error( $folder ) ? $folder->name : __( 'folder', 'admin-manager' );

				printf(
					'<div class="notice notice-success is-dismissible"><p>' .
					/* translators: 1: Number of items, 2: Folder name */
					_n(
						'%1$d item moved to %2$s.',
						'%1$d items moved to %2$s.',
						$count,
						'admin-manager'
					) .
					'</p></div>',
					$count,
					'<strong>' . esc_html( $folder_name ) . '</strong>'
				);
			} else {
				printf(
					'<div class="notice notice-success is-dismissible"><p>' .
					/* translators: %d: Number of items */
					_n(
						'%d item removed from folder.',
						'%d items removed from folder.',
						$count,
						'admin-manager'
					) .
					'</p></div>',
					$count
				);
			}
		}
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		// Maximum folder depth
		register_setting(
			'am_media_folders_settings',
			'am_folders_max_depth',
			array(
				'type'              => 'integer',
				'default'           => 4,
				'sanitize_callback' => array( $this, 'sanitize_max_depth' ),
			)
		);

		// Enable drag and drop
		register_setting(
			'am_media_folders_settings',
			'am_folders_enable_drag_drop',
			array(
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);

		// Show file count
		register_setting(
			'am_media_folders_settings',
			'am_folders_show_count',
			array(
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);

		// Default collapsed state
		register_setting(
			'am_media_folders_settings',
			'am_folders_default_collapsed',
			array(
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);

		// Remember folder state
		register_setting(
			'am_media_folders_settings',
			'am_folders_remember_state',
			array(
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);

		// Enable bulk move
		register_setting(
			'am_media_folders_settings',
			'am_folders_enable_bulk_move',
			array(
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);
	}

	/**
	 * Sanitize max depth setting.
	 *
	 * @param int $value The value to sanitize.
	 * @return int Sanitized value.
	 */
	public function sanitize_max_depth( $value ) {
		$value = intval( $value );
		return max( 1, min( 10, $value ) );
	}

	/**
	 * Write debug log entry.
	 *
	 * @param string $message Log message.
	 * @param string $type Log type (info, success, error, warning).
	 * @param array  $data Additional data.
	 */
	public static function debug_log( $message, $type = 'info', $data = array() ) {
		$logs = get_option( self::DEBUG_LOG, array() );

		// Keep only last 100 entries
		if ( count( $logs ) >= 100 ) {
			$logs = array_slice( $logs, -99 );
		}

		$logs[] = array(
			'time'    => current_time( 'mysql' ),
			'type'    => $type,
			'message' => $message,
			'data'    => $data,
		);

		update_option( self::DEBUG_LOG, $logs, false );
	}

	/**
	 * Get debug logs.
	 *
	 * @return array Debug logs.
	 */
	public static function get_debug_logs() {
		return get_option( self::DEBUG_LOG, array() );
	}

	/**
	 * Clear debug logs.
	 */
	public static function clear_debug_logs() {
		delete_option( self::DEBUG_LOG );
	}

	/**
	 * AJAX: Log debug message from JavaScript.
	 */
	public function ajax_log_debug() {
		check_ajax_referer( 'am_media_folders', 'nonce' );

		$message = isset( $_POST['message'] ) ? sanitize_text_field( $_POST['message'] ) : '';
		$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'info';
		$data = isset( $_POST['data'] ) ? $_POST['data'] : array();

		self::debug_log( '[JS] ' . $message, $type, $data );

		wp_send_json_success();
	}

	/**
	 * AJAX: Clear debug logs.
	 */
	public function ajax_clear_debug() {
		check_ajax_referer( 'am_media_folders', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		self::clear_debug_logs();

		wp_send_json_success();
	}
}
