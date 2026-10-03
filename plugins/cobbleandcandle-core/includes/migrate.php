<?php
/**
 * One-time data migration for the 1.0 prefix change (`cc_` → `cobble_`).
 *
 * Renames, in place and only for Cobble & Candle's own content: post types, taxonomies, their post
 * and term meta, the plugin's options, saved Site Editor templates (archive-cc_room…), WPML
 * translation links, and menu items that point at the renamed types. Old scheduled jobs are
 * cleared (new ones schedule themselves). Runs once per site, and only on a site that held
 * pre-1.0 Cobble & Candle data: a fresh install just records the data version, so another
 * plugin's `cc_*` content is never touched. A lock stops two requests running it together, and
 * the version is only recorded when every statement succeeded. URLs do not change.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

const COBBLE_DB_VERSION = 2;

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
 * Did Cobble & Candle run on this site before 1.0? Proof is the old options, or a `cc_location`
 * / `cc_menu_item` post carrying our meta keys. Generic `cc_*` post types alone are not proof.
 *
 * @return bool
 */
function cobble_has_legacy_data() {
	global $wpdb;
	foreach ( array( 'cc_setup', 'cc_log', 'cc_mail_failures' ) as $option ) {
		if ( false !== get_option( $option, false ) ) {
			return true;
		}
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-time detection before the post types exist.
	return (bool) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT 1 FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type IN (%s, %s, %s) AND pm.meta_key IN (%s, %s, %s, %s) LIMIT 1",
			'cc_location',
			'cc_menu_item',
			'cc_room',
			'cc_hours',
			'cc_street',
			'cc_price',
			'cc_price_night'
		)
	);
}

/**
 * Run the migration when needed (early on init, before the post types register).
 */
function cobble_maybe_migrate() {
	if ( (int) get_option( 'cobble_db_version', 0 ) >= COBBLE_DB_VERSION ) {
		return;
	}
	if ( ! cobble_has_legacy_data() ) {
		update_option( 'cobble_db_version', COBBLE_DB_VERSION );
		return;
	}
	// Lock. add_option() is not atomic under a cold object cache, so the token is read back.
	$token = wp_generate_password( 12, false ) . ':' . time();
	if ( ! add_option( 'cobble_migrating', $token, '', false ) ) {
		$held = (string) get_option( 'cobble_migrating' );
		if ( (int) substr( $held, strpos( $held, ':' ) + 1 ) < time() - 10 * MINUTE_IN_SECONDS ) {
			delete_option( 'cobble_migrating' ); // A stale lock from a request that died.
		}
		return;
	}
	wp_cache_delete( 'cobble_migrating', 'options' );
	wp_cache_delete( 'alloptions', 'options' );
	if ( get_option( 'cobble_migrating' ) !== $token ) {
		return; // Another request got there first.
	}

	$ok = cobble_migrate_to_v2();
	if ( $ok ) {
		update_option( 'cobble_db_version', COBBLE_DB_VERSION );
		update_option( 'cobble_flush_rewrites', 1 );
	}
	delete_option( 'cobble_migrating' );
}
add_action( 'init', 'cobble_maybe_migrate', 0 );

/**
 * Move an option to its new name. When both exist (a request wrote the new one while the
 * migration ran) the two are merged so nothing saved is lost.
 *
 * @param string $old Old option name.
 * @param string $new New option name.
 */
function cobble_migrate_option( $old, $new ) {
	$value = get_option( $old, null );
	if ( null === $value ) {
		return;
	}
	$current = get_option( $new, null );
	if ( null === $current ) {
		update_option( $new, $value, false );
	} elseif ( 'cobble_log' === $new && is_array( $value ) && is_array( $current ) ) {
		$merged = array_merge( $value, $current );
		usort( $merged, static fn( $a, $b ) => (int) ( $a['time'] ?? 0 ) <=> (int) ( $b['time'] ?? 0 ) );
		update_option( $new, array_slice( $merged, -200 ), false );
	} elseif ( is_array( $value ) && is_array( $current ) ) {
		update_option( $new, array_merge( $value, $current ), false );
	} elseif ( is_numeric( $value ) && is_numeric( $current ) ) {
		update_option( $new, (int) $value + (int) $current, false );
	}
	delete_option( $old );
}

/**
 * The 1.0 prefix migration.
 *
 * @return bool True when every statement ran; false when one failed (the migration is retried).
 */
function cobble_migrate_to_v2() {
	global $wpdb;
	$types  = cobble_migrate_post_types();
	$taxes  = cobble_migrate_taxonomies();
	$moved  = 0;
	$failed = false;
	$run    = static function ( $sql ) use ( $wpdb, &$failed ) {
		$result = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared -- prepared by the caller; one-time schema migration.
		if ( false === $result ) {
			$failed = true;
			return 0;
		}
		return (int) $result;
	};

	// Options first, so a request during the migration sees the saved setup and log.
	foreach ( array( 'cc_setup' => 'cobble_setup', 'cc_log' => 'cobble_log', 'cc_mail_failures' => 'cobble_mail_failures' ) as $old => $new ) {
		cobble_migrate_option( $old, $new );
	}

	// Post types. No API renames post types, taxonomies or meta keys in bulk, so this is direct SQL.
	foreach ( $types as $old => $new ) {
		$moved += $run( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s", $new, $old ) );
	}
	$placeholders = implode( ', ', array_fill( 0, count( $types ), '%s' ) );
	$new_types    = array_values( $types );

	// Post meta on our posts: cc_x → cobble_x and _cc_x → _cobble_x.
	$run(
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id SET pm.meta_key = CONCAT('cobble_', SUBSTRING(pm.meta_key, 4)) WHERE p.post_type IN ($placeholders) AND pm.meta_key LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders built above.
			array_merge( $new_types, array( $wpdb->esc_like( 'cc_' ) . '%' ) )
		)
	);
	$run(
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id SET pm.meta_key = CONCAT('_cobble_', SUBSTRING(pm.meta_key, 5)) WHERE p.post_type IN ($placeholders) AND pm.meta_key LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders built above.
			array_merge( $new_types, array( $wpdb->esc_like( '_cc_' ) . '%' ) )
		)
	);

	// Taxonomies and their term meta (order, intro).
	foreach ( $taxes as $old => $new ) {
		$moved += $run( $wpdb->prepare( "UPDATE {$wpdb->term_taxonomy} SET taxonomy = %s WHERE taxonomy = %s", $new, $old ) );
	}
	$tax_placeholders = implode( ', ', array_fill( 0, count( $taxes ), '%s' ) );
	$run(
		$wpdb->prepare(
			"UPDATE {$wpdb->termmeta} tm INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id SET tm.meta_key = CONCAT('cobble_', SUBSTRING(tm.meta_key, 4)) WHERE tt.taxonomy IN ($tax_placeholders) AND tm.meta_key LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders built above.
			array_merge( array_values( $taxes ), array( $wpdb->esc_like( 'cc_' ) . '%' ) )
		)
	);

	// Menu items that link to the renamed post types or taxonomies.
	foreach ( array_merge( $types, $taxes ) as $old => $new ) {
		$run( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = '_menu_item_object' AND meta_value = %s", $new, $old ) );
	}

	// Saved Site Editor templates for our types (archive-cc_room → archive-cobble_room).
	foreach ( $types as $old => $new ) {
		foreach ( array( 'archive-', 'single-' ) as $kind ) {
			$run( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_name = %s WHERE post_type = 'wp_template' AND post_name = %s", $kind . $new, $kind . $old ) );
		}
	}

	// WPML keeps translation links by element type (post_cc_room, tax_cc_menu).
	$icl = $wpdb->prefix . 'icl_translations';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- table check.
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $icl ) ) === $icl ) {
		foreach ( array_merge( $types, $taxes ) as $old => $new ) {
			$prefix = isset( $types[ $old ] ) ? 'post_' : 'tax_';
			$run( $wpdb->prepare( "UPDATE {$icl} SET element_type = %s WHERE element_type = %s", $prefix . $new, $prefix . $old ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name built from $wpdb->prefix.
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

	cobble_migrate_flush_caches();
	if ( function_exists( 'cobble_log' ) ) {
		if ( $failed ) {
			cobble_log( 'error', 'update', 'The Cobble & Candle 1.0 data update did not finish: a database statement failed. It will run again on the next page load. ' . $wpdb->last_error );
		} elseif ( $moved > 0 ) {
			cobble_log( 'info', 'update', sprintf( 'Updated %d items to the Cobble & Candle 1.0 data format.', $moved ) );
		}
	}
	return ! $failed;
}

/**
 * Drop cached posts, terms and options after the SQL updates. On a network, only this site's
 * groups when the cache supports it, instead of emptying every site's cache.
 */
function cobble_migrate_flush_caches() {
	if ( is_multisite() && function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_group' ) ) {
		foreach ( array( 'posts', 'post_meta', 'terms', 'term_meta', 'term_relationships', 'options', 'transient', 'post-queries', 'term-queries' ) as $group ) {
			wp_cache_flush_group( $group );
		}
		return;
	}
	wp_cache_flush();
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
