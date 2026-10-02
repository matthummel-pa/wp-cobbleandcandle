<?php
/**
 * Location data for themes: all locations, one location's display data, the current location.
 *
 * Themes should call these behind function_exists() so they keep working without the plugin.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Published locations in menu order.
 *
 * @return array<int, \WP_Post>
 */
function cc_get_locations() {
	static $locations = null;
	if ( null === $locations ) {
		$locations = get_posts(
			array(
				'post_type'      => 'cc_location',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'no_found_rows'  => true,
			)
		);
	}
	return $locations;
}

/**
 * Display data for one location (strings are raw; escape on output).
 *
 * @param int|\WP_Post $location Location post or ID.
 * @return array<string, mixed>
 */
function cc_location( $location ) {
	$post = get_post( $location );
	if ( ! $post || 'cc_location' !== $post->post_type ) {
		return array();
	}
	$id      = $post->ID;
	$meta    = static function ( $key ) use ( $id ) {
		return get_post_meta( $id, $key, true );
	};
	$phone   = (string) $meta( 'cc_phone' );
	$address = implode( ', ', array_filter( array( $meta( 'cc_street' ), $meta( 'cc_locality' ), $meta( 'cc_region' ), $meta( 'cc_postcode' ) ) ) );
	$map     = (string) $meta( 'cc_map_url' );
	if ( '' === $map && '' !== $address ) {
		$map = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $address );
	}

	return array(
		'id'           => $id,
		'slug'         => $post->post_name,
		'name'         => cc_plain_title( $post ),
		'url'          => get_permalink( $post ),
		'street'       => (string) $meta( 'cc_street' ),
		'locality'     => (string) $meta( 'cc_locality' ),
		'address'      => $address,
		'phone'        => $phone,
		'tel'          => '' !== $phone ? 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) : '',
		'email'        => (string) $meta( 'cc_email' ),
		'map_url'      => $map,
		'order_url'    => (string) $meta( 'cc_order_url' ),
		'booking_mode' => (string) $meta( 'cc_booking_mode' ),
		'booking_url'  => (string) $meta( 'cc_booking_url' ),
		'status'       => cc_location_status( $id ),
		'hours'        => cc_hours_grouped( $id ),
		'today'        => cc_today_hours( $id ),
	);
}

/**
 * The visitor's current location: ?loc=slug, then the cc_loc cookie, then the first location.
 *
 * @return array<string, mixed> Empty when there are no locations.
 */
function cc_current_location() {
	$locations = cc_get_locations();
	if ( ! $locations ) {
		return array();
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display preference.
	$wanted = isset( $_GET['loc'] ) ? sanitize_title( wp_unslash( $_GET['loc'] ) ) : '';
	if ( '' === $wanted && isset( $_COOKIE['cc_loc'] ) ) {
		$wanted = sanitize_title( wp_unslash( $_COOKIE['cc_loc'] ) );
	}
	foreach ( $locations as $post ) {
		if ( $post->post_name === $wanted ) {
			return cc_location( $post );
		}
	}
	return cc_location( $locations[0] );
}
