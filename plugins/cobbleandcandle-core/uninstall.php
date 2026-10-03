<?php
/**
 * Uninstall Cobble & Candle Core.
 *
 * Always removes temporary data (rate-limit counters). Content (locations, menu items, events, rooms, bookings, messages,
 * menus, sections, gallery categories) and the Restaurant settings are deleted only if "Remove data
 * on uninstall" was ticked in Settings → Restaurant. Runs for every site on multisite.
 *
 * @package CobbleAndCandleCore
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Clean up one site.
 */
function cobble_uninstall_site() {
	global $wpdb;
	$settings = get_option( 'cobbleandcandle_brand', array() );
	if ( is_array( $settings ) && ! empty( $settings['remove_data'] ) ) {
		// The plugin is not loaded during uninstall: register its types so terms can be found and deleted.
		require_once __DIR__ . '/includes/post-types.php';
		require_once __DIR__ . '/includes/rooms.php';
		require_once __DIR__ . '/includes/messages.php';
		cobble_register_content_types();
		cobble_register_room_types();
		cobble_register_message_type();
		foreach ( array( 'cobble_location', 'cobble_menu_item', 'cobble_event', 'cobble_room', 'cobble_booking', 'cobble_message' ) as $post_type ) {
			$ids = get_posts(
				array(
					'post_type'   => $post_type,
					'post_status' => array_keys( get_post_stati() ),
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			);
			foreach ( $ids as $id ) {
				wp_delete_post( $id, true );
			}
		}
		foreach ( array( 'cobble_menu', 'cobble_menu_section', 'cobble_gallery' ) as $taxonomy ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);
			foreach ( is_array( $terms ) ? $terms : array() as $term ) {
				wp_delete_term( $term, $taxonomy );
			}
		}
		delete_option( 'cobbleandcandle_brand' );
	}
	wp_clear_scheduled_hook( 'cobble_ical_sync' );
	wp_unschedule_hook( 'cobble_ical_sync_room' );
	delete_option( 'cobble_log' ); // Event log: temporary diagnostics.
	wp_clear_scheduled_hook( 'cobble_prune_messages' );
	delete_option( 'cobble_mail_failures' );
	delete_option( 'cobble_db_version' );
	delete_option( 'cobble_migrating' );
	delete_option( 'cobble_flush_rewrites' );
	// Rate-limit counters (transients named cobble_rl_*). They expire on their own; removed here for tidiness.
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_cobble_rl_' ) . '%', $wpdb->esc_like( '_transient_timeout_cobble_rl_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-off cleanup on uninstall; no API deletes transients by prefix.
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $cobble_site ) {
		switch_to_blog( $cobble_site );
		cobble_uninstall_site();
		restore_current_blog();
	}
} else {
	cobble_uninstall_site();
}
