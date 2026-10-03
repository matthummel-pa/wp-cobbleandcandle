<?php
/**
 * One-time data migration for the 1.0 prefix change (`cc_` → `cobble_`).
 *
 * Renames, in place and only for Cobble & Candle's own content: post types, taxonomies, their post
 * and term meta, the plugin's options, saved Site Editor templates (archive-cc_room…), and menu items
 * that point at the renamed types. Old scheduled jobs are cleared (new ones schedule themselves).
 * Runs once per site; a lock stops two requests running it together. URLs do not change.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Old → new post types.
 *
 * @return array<string, string>
 */
function cobble_migrate_post_types() {
	return array(
		'cc_location'  => 'cobble_location',
		'cc_menu_item' => 'cobble_menu_item',
		'cc_event'     => 'cobble_event',
		'cc_room'      => 'cobble_room',
		'cc_booking'   => 'cobble_booking',
		'cc_message'   => 'cobble_message',
	);
}

/**
 * Old → new taxonomies.
 *
 * @return array<string, string>
 */
function cobble_migrate_taxonomies() {
	return array(
		'cc_menu'         => 'cobble_menu',
		'cc_menu_section' => 'cobble_menu_section',
		'cc_gallery'      => 'cobble_gallery',
	);
}

/**
 * Run the migration when needed (early on init, before the post types register).
 */
function cobble_maybe_migrate() {
	if ( (int) get_option( 'cobble_db_version', 0 ) >= 2 ) {
		return;
	}
	// Lock: add_option() fails when the row exists, so only one request migrates.
	if ( ! add_option( 'cobble_migrating', time(), '', false ) ) {
		if ( (int) get_option( 'cobble_migrating' ) < time() - 10 * MINUTE_IN_SECONDS ) {
			delete_option( 'cobble_migrating' ); // A stale lock from a request that died.
		}
		return;
	}
	cobble_migrate_to_v2();
	update_option( 'cobble_db_version', 2 );
	update_option( 'cobble_flush_rewrites', 1 );
	delete_option( 'cobble_migrating' );
}
add_action( 'init', 'cobble_maybe_migrate', 0 );

/**
 * The 1.0 prefix migration. Safe on a fresh install (nothing to rename).
 */
function cobble_migrate_to_v2() {
	global $wpdb;
	$types = cobble_migrate_post_types();
	$taxes = cobble_migrate_taxonomies();
	$moved = 0;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery -- one-time schema migration; no API renames post types, taxonomies or meta keys in bulk.
	foreach ( $types as $old => $new ) {
		$moved += (int) $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s", $new, $old ) );
	}
	$placeholders = implode( ', ', array_fill( 0, count( $types ), '%s' ) );
	$new_types    = array_values( $types );

	// Post meta on our posts: cc_x → cobble_x and _cc_x → _cobble_x.
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id SET pm.meta_key = CONCAT('cobble_', SUBSTRING(pm.meta_key, 4)) WHERE p.post_type IN ($placeholders) AND pm.meta_key LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders built above.
			array_merge( $new_types, array( $wpdb->esc_like( 'cc_' ) . '%' ) )
		)
	);
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id SET pm.meta_key = CONCAT('_cobble_', SUBSTRING(pm.meta_key, 5)) WHERE p.post_type IN ($placeholders) AND pm.meta_key LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders built above.
			array_merge( $new_types, array( $wpdb->esc_like( '_cc_' ) . '%' ) )
		)
	);

	// Taxonomies and their term meta (order, intro).
	foreach ( $taxes as $old => $new ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->term_taxonomy} SET taxonomy = %s WHERE taxonomy = %s", $new, $old ) );
	}
	$tax_placeholders = implode( ', ', array_fill( 0, count( $taxes ), '%s' ) );
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->termmeta} tm INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id SET tm.meta_key = CONCAT('cobble_', SUBSTRING(tm.meta_key, 4)) WHERE tt.taxonomy IN ($tax_placeholders) AND tm.meta_key LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders built above.
			array_merge( array_values( $taxes ), array( $wpdb->esc_like( 'cc_' ) . '%' ) )
		)
	);

	// Menu items that link to the renamed post types or taxonomies.
	foreach ( array_merge( $types, $taxes ) as $old => $new ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = '_menu_item_object' AND meta_value = %s", $new, $old ) );
	}

	// Saved Site Editor templates for our types (archive-cc_room → archive-cobble_room).
	foreach ( $types as $old => $new ) {
		foreach ( array( 'archive-', 'single-' ) as $kind ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_name = %s WHERE post_type = 'wp_template' AND post_name = %s", $kind . $new, $kind . $old ) );
		}
	}
	// phpcs:enable

	// Options (only when the new name is still free).
	foreach ( array( 'cc_setup' => 'cobble_setup', 'cc_log' => 'cobble_log', 'cc_mail_failures' => 'cobble_mail_failures' ) as $old => $new ) {
		$value = get_option( $old, null );
		if ( null !== $value ) {
			if ( null === get_option( $new, null ) ) {
				update_option( $new, $value, false );
			}
			delete_option( $old );
		}
	}
	delete_transient( 'cc_unsent_messages' );
	foreach ( array_keys( $taxes ) as $old ) {
		delete_option( $old . '_children' ); // WordPress's term-hierarchy cache; rebuilt under the new name.
	}

	// Old scheduled jobs; the new ones schedule themselves on init.
	wp_clear_scheduled_hook( 'cc_ical_sync' );
	wp_clear_scheduled_hook( 'cc_prune_messages' );
	wp_unschedule_hook( 'cc_ical_sync_room' );

	wp_cache_flush();
	if ( function_exists( 'cobble_log' ) && $moved > 0 ) {
		/* translators: %d: number of items */
		cobble_log( 'info', 'update', sprintf( 'Updated %d items to the Cobble & Candle 1.0 data format.', $moved ) );
	}
}

/**
 * Refresh permalinks once, after the renamed post types have registered.
 */
function cobble_flush_after_migration() {
	if ( get_option( 'cobble_flush_rewrites' ) ) {
		delete_option( 'cobble_flush_rewrites' );
		flush_rewrite_rules( false );
	}
}
add_action( 'init', 'cobble_flush_after_migration', 99 );
