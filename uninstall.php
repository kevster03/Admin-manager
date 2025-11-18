<?php
/**
 * Uninstall Admin Manager
 *
 * Removes all plugin data from the database when uninstalled.
 *
 * @package AdminManager
 */

// Exit if accessed directly or not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin options.
 */
function am_uninstall_cleanup_options() {
	global $wpdb;

	// Remove main settings.
	delete_option( 'am_settings' );

	// Remove module-specific settings.
	$modules = array(
		'author-box',
		'reading-time',
		'media-folders',
		'custom-login',
	);

	foreach ( $modules as $module ) {
		delete_option( 'am_' . $module . '_settings' );
	}

	// Clean up any orphaned options with our prefix.
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'am_%'" );
}

/**
 * Clean up custom post types.
 */
function am_uninstall_cleanup_post_types() {
	global $wpdb;

	// Delete all forms.
	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'am_form'" );
	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'am_form_entry'" );

	// Clean up orphaned post meta.
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})" );

	// Delete post meta with our prefix.
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_am_%'" );
}

/**
 * Clean up taxonomies.
 */
function am_uninstall_cleanup_taxonomies() {
	global $wpdb;

	// Get media folder term IDs.
	$term_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
			'am_media_folder'
		)
	);

	if ( ! empty( $term_ids ) ) {
		$term_ids_string = implode( ',', array_map( 'intval', $term_ids ) );

		// Delete term relationships.
		$wpdb->query( "DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ({$term_ids_string})" );

		// Delete term taxonomy records.
		$wpdb->query( "DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'am_media_folder'" );

		// Delete terms.
		$wpdb->query( "DELETE FROM {$wpdb->terms} WHERE term_id IN ({$term_ids_string})" );
	}

	// Clean up orphaned term meta.
	$wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE term_id NOT IN (SELECT term_id FROM {$wpdb->terms})" );
}

/**
 * Clean up transients.
 */
function am_uninstall_cleanup_transients() {
	global $wpdb;

	// Delete transients with our prefix.
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_am_%' OR option_name LIKE '_transient_timeout_am_%'" );
}

/**
 * Main uninstall routine.
 */
function am_uninstall() {
	// Clean up options.
	am_uninstall_cleanup_options();

	// Clean up custom post types.
	am_uninstall_cleanup_post_types();

	// Clean up taxonomies.
	am_uninstall_cleanup_taxonomies();

	// Clean up transients.
	am_uninstall_cleanup_transients();

	// Clear rewrite rules.
	flush_rewrite_rules();
}

// Run uninstall.
am_uninstall();
