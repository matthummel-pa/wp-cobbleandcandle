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
function cc_uninstall_site() {
	global $wpdb;
	$settings = get_option( 'cobbleandcandle_brand', array() );
	if ( is_array( $settings ) && ! empty( $settings['remove_data'] ) ) {
		// The plugin is not loaded during uninstall: register its types so terms can be found and deleted.
		require_once __DIR__ . '/includes/post-types.php';
		require_once __DIR__ . '/includes/rooms.php';
		require_once __DIR__ . '/includes/messages.php';
		cc_register_content_types();
		cc_register_room_types();
		cc_register_message_type();
		foreach ( array( 'cc_location', 'cc_menu_item', 'cc_event', 'cc_room', 'cc_booking', 'cc_message' ) as $post_type ) {
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
		foreach ( array( 'cc_menu', 'cc_menu_section', 'cc_gallery' ) as $taxonomy ) {
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
	wp_clear_scheduled_hook( 'cc_ical_sync' );
	wp_unschedule_hook( 'cc_ical_sync_room' );
	delete_option( 'cc_log' ); // Event log: temporary diagnostics.
	wp_clear_scheduled_hook( 'cc_prune_messages' );
	// Rate-limit counters (transients named cc_rl_*). They expire on their own; removed here for tidiness.
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_cc_rl_' ) . '%', $wpdb->esc_like( '_transient_timeout_cc_rl_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-off cleanup on uninstall; no API deletes transients by prefix.
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $cc_site ) {
		switch_to_blog( $cc_site );
		cc_uninstall_site();
		restore_current_blog();
	}
} else {
	cc_uninstall_site();
}
