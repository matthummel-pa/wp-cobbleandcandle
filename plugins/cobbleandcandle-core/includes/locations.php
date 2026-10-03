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
function cobble_get_locations() {
	static $locations = null;
	if ( null === $locations ) {
		$locations = get_posts(
			array(
				'post_type'      => 'cobble_location',
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
function cobble_location( $location ) {
	$post = get_post( $location );
	if ( ! $post || 'cobble_location' !== $post->post_type ) {
		return array();
	}
	$id      = $post->ID;
	$meta    = static function ( $key ) use ( $id ) {
		return get_post_meta( $id, $key, true );
	};
	$phone   = (string) $meta( 'cobble_phone' );
	$address = implode( ', ', array_filter( array( $meta( 'cobble_street' ), $meta( 'cobble_locality' ), $meta( 'cobble_region' ), $meta( 'cobble_postcode' ) ) ) );
	$map     = (string) $meta( 'cobble_map_url' );
	if ( '' === $map && '' !== $address ) {
		$map = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $address );
	}

	return array(
		'id'           => $id,
		'slug'         => $post->post_name,
		'name'         => cobble_plain_title( $post ),
		'url'          => get_permalink( $post ),
		'street'       => (string) $meta( 'cobble_street' ),
		'locality'     => (string) $meta( 'cobble_locality' ),
		'address'      => $address,
		'phone'        => $phone,
		'tel'          => '' !== $phone ? 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) : '',
		'email'        => (string) $meta( 'cobble_email' ),
		'map_url'      => $map,
		'order_url'    => (string) $meta( 'cobble_order_url' ),
		'booking_mode' => (string) $meta( 'cobble_booking_mode' ),
		'booking_url'  => (string) $meta( 'cobble_booking_url' ),
		'status'       => cobble_location_status( $id ),
		'hours'        => cobble_hours_grouped( $id ),
		'today'        => cobble_today_hours( $id ),
	);
}

/**
 * The current location for server rendering: ?loc=slug, else the first location.
 *
 * The visitor's saved choice (the cobble_loc cookie) is applied in the browser, so a cached page never
 * shows one visitor's location to everyone. Filter `cobble_current_location_slug` to change the default.
 *
 * @return array<string, mixed> Empty when there are no locations.
 */
function cobble_current_location() {
	$locations = cobble_get_locations();
	if ( ! $locations ) {
		return array();
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display preference.
	$wanted = isset( $_GET['loc'] ) ? sanitize_title( wp_unslash( $_GET['loc'] ) ) : '';
	$wanted = (string) apply_filters( 'cobble_current_location_slug', $wanted );
	foreach ( $locations as $post ) {
		if ( $post->post_name === $wanted ) {
			return cobble_location( $post );
		}
	}
	return cobble_location( $locations[0] );
}
