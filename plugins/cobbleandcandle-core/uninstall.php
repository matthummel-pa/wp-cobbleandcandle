<?php
/**
 * Uninstall Cobble & Candle Core.
 *
 * Always removes the plugin's settings and temporary data. Content (locations, menu items, events,
 * menus, sections and gallery categories) is deleted only if "Remove data on uninstall" was ticked
 * in Settings → Restaurant.
 *
 * @package CobbleAndCandleCore
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$cc_settings = get_option( 'cobbleandcandle_brand', array() );
$cc_remove   = is_array( $cc_settings ) && ! empty( $cc_settings['remove_data'] );

if ( $cc_remove ) {
	foreach ( array( 'cc_location', 'cc_menu_item', 'cc_event' ) as $cc_post_type ) {
		$cc_ids = get_posts(
			array(
				'post_type'   => $cc_post_type,
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
		foreach ( $cc_ids as $cc_id ) {
			wp_delete_post( $cc_id, true );
		}
	}
	foreach ( array( 'cc_menu', 'cc_menu_section', 'cc_gallery' ) as $cc_taxonomy ) {
		$cc_terms = get_terms(
			array(
				'taxonomy'   => $cc_taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		if ( is_array( $cc_terms ) ) {
			foreach ( $cc_terms as $cc_term ) {
				wp_delete_term( $cc_term, $cc_taxonomy );
			}
		}
	}
	delete_option( 'cobbleandcandle_brand' );
}

// Rate-limit counters (transients named cc_rl_*). Expire on their own; removed here for tidiness.
global $wpdb;
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_cc_rl_' ) . '%', $wpdb->esc_like( '_transient_timeout_cc_rl_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-off cleanup on uninstall; no API deletes transients by prefix.
