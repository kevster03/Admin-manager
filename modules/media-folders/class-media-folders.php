<?php
/**
 * Media Folders Module - REST API + Post Meta Approach
 *
 * Modern implementation using:
 * - Custom post type for folders (am_media_folder)
 * - Post meta for file-to-folder assignments (_am_folder_id)
 * - WordPress REST API for all operations
 * - Meta query for reliable filtering
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
	 * Folder post type name.
	 */
	const POST_TYPE = 'am_media_folder';

	/**
	 * Meta key for folder assignment.
	 */
	const META_KEY = '_am_folder_id';

	/**
	 * Debug log option name.
	 */
	const DEBUG_LOG = 'am_media_folders_debug_log';

	/**
	 * Maximum folder depth.
	 */
	const MAX_DEPTH = 4;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register custom post type for folders
		add_action( 'init', array( $this, 'register_folder_post_type' ) );

		// Register REST API routes
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Enqueue admin assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Filter media library using meta_query
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_media_ajax' ), 10, 1 );
		add_filter( 'posts_clauses', array( $this, 'filter_media_list_view' ), 10, 2 );

		// Add folder column to media library
		add_filter( 'manage_media_columns', array( $this, 'add_folder_column' ) );
		add_action( 'manage_media_custom_column', array( $this, 'display_folder_column' ), 10, 2 );

		// Debug AJAX handlers
		add_action( 'wp_ajax_am_log_debug', array( $this, 'ajax_log_debug' ) );
		add_action( 'wp_ajax_am_clear_debug', array( $this, 'ajax_clear_debug' ) );
	}

	/**
	 * Register folder custom post type.
	 */
	public function register_folder_post_type() {
		$args = array(
			'label'               => __( 'Media Folders', 'admin-manager' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => true,
			'rest_base'           => 'media-folders',
			'hierarchical'        => true,
			'supports'            => array( 'title', 'page-attributes' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => true,
			'delete_with_user'    => false,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		// Get all folders
		register_rest_route(
			'am/v1',
			'/folders',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_folders' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		// Create folder
		register_rest_route(
			'am/v1',
			'/folders',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_create_folder' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
				'args'                => array(
					'name'      => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'parent_id' => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Update folder
		register_rest_route(
			'am/v1',
			'/folders/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'rest_update_folder' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
				'args'                => array(
					'name' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Delete folder
		register_rest_route(
			'am/v1',
			'/folders/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'rest_delete_folder' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		// Move media to folder
		register_rest_route(
			'am/v1',
			'/move-media',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_move_media' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
				'args'                => array(
					'attachment_ids' => array(
						'required' => true,
						'type'     => 'array',
					),
					'folder_id'      => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);
	}

	/**
	 * REST API permission check.
	 *
	 * @return bool
	 */
	public function rest_permission_check() {
		return current_user_can( 'upload_files' );
	}

	/**
	 * REST: Get all folders.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function rest_get_folders( $request ) {
		$folders = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$tree = $this->build_folder_tree( $folders );

		self::debug_log( '[REST] Get folders called', 'info', array(
			'total_folders' => count( $folders ),
			'tree_count'    => count( $tree ),
		) );

		return new WP_REST_Response( $tree, 200 );
	}

	/**
	 * REST: Create folder.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function rest_create_folder( $request ) {
		$name      = $request->get_param( 'name' );
		$parent_id = $request->get_param( 'parent_id' );

		// Check depth limit
		if ( $parent_id > 0 ) {
			$depth = $this->get_folder_depth( $parent_id );
			if ( $depth >= self::MAX_DEPTH ) {
				self::debug_log( '[REST] Create folder failed: max depth reached', 'error', array(
					'parent_id'     => $parent_id,
					'current_depth' => $depth,
				) );
				return new WP_REST_Response(
					array( 'message' => 'Maximum folder depth reached (4 levels)' ),
					400
				);
			}
		}

		$folder_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_title'  => $name,
				'post_status' => 'publish',
				'post_parent' => $parent_id,
			)
		);

		if ( is_wp_error( $folder_id ) ) {
			self::debug_log( '[REST] Create folder failed', 'error', array(
				'name'      => $name,
				'parent_id' => $parent_id,
				'error'     => $folder_id->get_error_message(),
			) );
			return new WP_REST_Response(
				array( 'message' => $folder_id->get_error_message() ),
				500
			);
		}

		self::debug_log( '[REST] Folder created', 'success', array(
			'folder_id' => $folder_id,
			'name'      => $name,
			'parent_id' => $parent_id,
		) );

		return new WP_REST_Response(
			array(
				'id'   => $folder_id,
				'name' => $name,
			),
			201
		);
	}

	/**
	 * REST: Update folder.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function rest_update_folder( $request ) {
		$folder_id = $request->get_param( 'id' );
		$name      = $request->get_param( 'name' );

		$result = wp_update_post(
			array(
				'ID'         => $folder_id,
				'post_title' => $name,
			)
		);

		if ( is_wp_error( $result ) ) {
			self::debug_log( '[REST] Update folder failed', 'error', array(
				'folder_id' => $folder_id,
				'error'     => $result->get_error_message(),
			) );
			return new WP_REST_Response(
				array( 'message' => $result->get_error_message() ),
				500
			);
		}

		self::debug_log( '[REST] Folder updated', 'success', array(
			'folder_id' => $folder_id,
			'name'      => $name,
		) );

		return new WP_REST_Response(
			array( 'message' => 'Folder updated' ),
			200
		);
	}

	/**
	 * REST: Delete folder.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function rest_delete_folder( $request ) {
		$folder_id = $request->get_param( 'id' );

		// Remove folder assignment from all media
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'   => self::META_KEY,
						'value' => $folder_id,
					),
				),
			)
		);

		foreach ( $attachments as $attachment ) {
			delete_post_meta( $attachment->ID, self::META_KEY );
		}

		$result = wp_delete_post( $folder_id, true );

		if ( ! $result ) {
			self::debug_log( '[REST] Delete folder failed', 'error', array(
				'folder_id' => $folder_id,
			) );
			return new WP_REST_Response(
				array( 'message' => 'Failed to delete folder' ),
				500
			);
		}

		self::debug_log( '[REST] Folder deleted', 'success', array(
			'folder_id'         => $folder_id,
			'unassigned_files'  => count( $attachments ),
		) );

		return new WP_REST_Response(
			array( 'message' => 'Folder deleted' ),
			200
		);
	}

	/**
	 * REST: Move media to folder.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function rest_move_media( $request ) {
		$attachment_ids = $request->get_param( 'attachment_ids' );
		$folder_id      = $request->get_param( 'folder_id' );

		$moved_count = 0;

		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_id = absint( $attachment_id );

			// Verify attachment exists
			if ( 'attachment' !== get_post_type( $attachment_id ) ) {
				continue;
			}

			if ( $folder_id > 0 ) {
				// Move to folder
				update_post_meta( $attachment_id, self::META_KEY, $folder_id );
			} else {
				// Remove from folder
				delete_post_meta( $attachment_id, self::META_KEY );
			}

			$moved_count++;
		}

		self::debug_log( '[REST] Media moved', 'success', array(
			'folder_id'      => $folder_id,
			'moved_count'    => $moved_count,
			'requested_count' => count( $attachment_ids ),
		) );

		return new WP_REST_Response(
			array(
				'moved_count' => $moved_count,
				'message'     => sprintf( '%d file(s) moved', $moved_count ),
			),
			200
		);
	}

	/**
	 * Build hierarchical folder tree.
	 *
	 * @param array $folders Flat list of folder posts.
	 * @param int   $parent_id Parent folder ID.
	 * @param int   $level Current level.
	 * @return array Tree structure.
	 */
	private function build_folder_tree( $folders, $parent_id = 0, $level = 1 ) {
		$branch = array();

		foreach ( $folders as $folder ) {
			if ( $folder->post_parent == $parent_id ) {
				$count    = $this->get_folder_file_count( $folder->ID );
				$children = $this->build_folder_tree( $folders, $folder->ID, $level + 1 );

				$branch[] = array(
					'id'       => $folder->ID,
					'name'     => $folder->post_title,
					'parent'   => $folder->post_parent,
					'count'    => $count,
					'level'    => $level,
					'children' => $children,
				);
			}
		}

		return $branch;
	}

	/**
	 * Get file count for a folder.
	 *
	 * @param int $folder_id Folder ID.
	 * @return int File count.
	 */
	private function get_folder_file_count( $folder_id ) {
		$count = get_posts(
			array(
				'post_type'      => 'attachment',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => self::META_KEY,
						'value' => $folder_id,
					),
				),
			)
		);

		return count( $count );
	}

	/**
	 * Get folder depth level.
	 *
	 * @param int $folder_id Folder ID.
	 * @return int Depth level.
	 */
	private function get_folder_depth( $folder_id ) {
		$depth      = 1;
		$current_id = $folder_id;

		while ( $current_id > 0 && $depth < self::MAX_DEPTH + 1 ) {
			$folder = get_post( $current_id );
			if ( ! $folder || self::POST_TYPE !== $folder->post_type ) {
				break;
			}
			$current_id = $folder->post_parent;
			if ( $current_id > 0 ) {
				$depth++;
			}
		}

		return $depth;
	}

	/**
	 * Filter media library for AJAX/Grid view.
	 *
	 * @param array $query Query args.
	 * @return array Modified query args.
	 */
	public function filter_media_ajax( $query ) {
		$folder_id = null;

		// Check for folder parameter
		if ( isset( $_REQUEST['query']['am_folder'] ) ) {
			$folder_id = intval( $_REQUEST['query']['am_folder'] );
		} elseif ( isset( $_GET['am_folder'] ) ) {
			$folder_id = intval( $_GET['am_folder'] );
		}

		if ( null === $folder_id ) {
			return $query;
		}

		self::debug_log( '[AJAX] Filter called', 'info', array(
			'folder_id' => $folder_id,
		) );

		if ( $folder_id > 0 ) {
			// Specific folder - use meta_query
			$query['meta_query'] = array(
				array(
					'key'   => self::META_KEY,
					'value' => $folder_id,
					'type'  => 'NUMERIC',
				),
			);
			self::debug_log( '[AJAX] Filtering by folder', 'success', array(
				'folder_id'  => $folder_id,
				'meta_query' => $query['meta_query'],
			) );
		} elseif ( -1 === $folder_id ) {
			// Uncategorized - files without folder meta
			$query['meta_query'] = array(
				array(
					'key'     => self::META_KEY,
					'compare' => 'NOT EXISTS',
				),
			);
			self::debug_log( '[AJAX] Filtering uncategorized', 'success' );
		}

		return $query;
	}

	/**
	 * Filter media library for list view.
	 *
	 * @param array    $clauses SQL clauses.
	 * @param WP_Query $query WP_Query instance.
	 * @return array Modified clauses.
	 */
	public function filter_media_list_view( $clauses, $query ) {
		global $wpdb, $pagenow;

		// Only on upload.php for attachments
		if ( ! is_admin() || 'upload.php' !== $pagenow ) {
			return $clauses;
		}

		if ( 'attachment' !== $query->get( 'post_type' ) ) {
			return $clauses;
		}

		if ( ! isset( $_GET['am_folder'] ) ) {
			return $clauses;
		}

		$folder_id = intval( $_GET['am_folder'] );

		self::debug_log( '[SQL] List view filter called', 'info', array(
			'folder_id' => $folder_id,
		) );

		if ( $folder_id > 0 ) {
			// Specific folder
			$clauses['join']  .= " INNER JOIN {$wpdb->postmeta} AS pm ON {$wpdb->posts}.ID = pm.post_id";
			$clauses['where'] .= $wpdb->prepare(
				" AND pm.meta_key = %s AND pm.meta_value = %d",
				self::META_KEY,
				$folder_id
			);
			self::debug_log( '[SQL] Applied JOIN filter', 'success' );
		} elseif ( -1 === $folder_id ) {
			// Uncategorized
			$clauses['join']  .= " LEFT JOIN {$wpdb->postmeta} AS pm ON ({$wpdb->posts}.ID = pm.post_id AND pm.meta_key = '" . self::META_KEY . "')";
			$clauses['where'] .= " AND pm.post_id IS NULL";
			self::debug_log( '[SQL] Applied LEFT JOIN filter for uncategorized', 'success' );
		}

		return $clauses;
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
			array( 'jquery', 'wp-api' ),
			AM_VERSION,
			true
		);

		wp_localize_script(
			'am-media-folders',
			'amMediaFolders',
			array(
				'restUrl'       => rest_url( 'am/v1' ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'currentFolder' => isset( $_GET['am_folder'] ) ? intval( $_GET['am_folder'] ) : 0,
				'strings'       => array(
					'createFolder'  => __( 'Create Folder', 'admin-manager' ),
					'folderName'    => __( 'Folder Name', 'admin-manager' ),
					'rename'        => __( 'Rename', 'admin-manager' ),
					'delete'        => __( 'Delete', 'admin-manager' ),
					'addFiles'      => __( 'Add Files', 'admin-manager' ),
					'confirmDelete' => __( 'Delete this folder? Files will not be deleted.', 'admin-manager' ),
					'allMedia'      => __( 'All Media', 'admin-manager' ),
					'uncategorized' => __( 'Uncategorized', 'admin-manager' ),
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
	 * Add folder column to media library.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_folder_column( $columns ) {
		$columns['am_folder'] = __( 'Folder', 'admin-manager' );
		return $columns;
	}

	/**
	 * Display folder column content.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id Post ID.
	 */
	public function display_folder_column( $column_name, $post_id ) {
		if ( 'am_folder' !== $column_name ) {
			return;
		}

		$folder_id = get_post_meta( $post_id, self::META_KEY, true );

		if ( $folder_id ) {
			$folder = get_post( $folder_id );
			if ( $folder ) {
				echo esc_html( $folder->post_title );
				return;
			}
		}

		echo '—';
	}

	/**
	 * Debug log.
	 *
	 * @param string $message Message.
	 * @param string $type Type (info, success, error, warning).
	 * @param array  $data Additional data.
	 */
	public static function debug_log( $message, $type = 'info', $data = array() ) {
		$logs = get_option( self::DEBUG_LOG, array() );

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
	 * AJAX: Log debug message.
	 */
	public function ajax_log_debug() {
		check_ajax_referer( 'wp_rest', 'nonce' );

		$message = isset( $_POST['message'] ) ? sanitize_text_field( $_POST['message'] ) : '';
		$type    = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'info';
		$data    = isset( $_POST['data'] ) ? $_POST['data'] : array();

		self::debug_log( '[JS] ' . $message, $type, $data );

		wp_send_json_success();
	}

	/**
	 * AJAX: Clear debug logs.
	 */
	public function ajax_clear_debug() {
		check_ajax_referer( 'wp_rest', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		self::clear_debug_logs();

		wp_send_json_success();
	}
}
