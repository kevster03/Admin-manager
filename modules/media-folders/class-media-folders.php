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
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_media_by_folder' ) );
		add_action( 'wp_ajax_am_create_folder', array( $this, 'ajax_create_folder' ) );
		add_action( 'wp_ajax_am_rename_folder', array( $this, 'ajax_rename_folder' ) );
		add_action( 'wp_ajax_am_delete_folder', array( $this, 'ajax_delete_folder' ) );
		add_action( 'wp_ajax_am_move_media', array( $this, 'ajax_move_media' ) );
		add_action( 'wp_ajax_am_bulk_move_media', array( $this, 'ajax_bulk_move_media' ) );
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_folder_field_to_attachment' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'save_folder_field_for_attachment' ), 10, 2 );
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
	 * Filter media library by folder.
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
			wp_set_object_terms( $attachment_id, $folder_id, self::TAXONOMY );
		} else {
			// Remove from all folders
			wp_delete_object_term_relationships( $attachment_id, self::TAXONOMY );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: Bulk move media items.
	 */
	public function ajax_bulk_move_media() {
		check_ajax_referer( 'am_media_folders', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'admin-manager' ) ) );
		}

		$attachment_ids = isset( $_POST['attachment_ids'] ) ? array_map( 'intval', $_POST['attachment_ids'] ) : array();
		$folder_id = isset( $_POST['folder_id'] ) ? intval( $_POST['folder_id'] ) : 0;

		if ( empty( $attachment_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No media items selected.', 'admin-manager' ) ) );
		}

		foreach ( $attachment_ids as $attachment_id ) {
			if ( $folder_id > 0 ) {
				wp_set_object_terms( $attachment_id, $folder_id, self::TAXONOMY );
			} else {
				wp_delete_object_term_relationships( $attachment_id, self::TAXONOMY );
			}
		}

		wp_send_json_success( array( 'count' => count( $attachment_ids ) ) );
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
				wp_set_object_terms( $post['ID'], $folder_id, self::TAXONOMY );
			} else {
				wp_delete_object_term_relationships( $post['ID'], self::TAXONOMY );
			}
		}

		return $post;
	}
}
