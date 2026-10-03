<?php
/**
 * Shared protection for the public form handlers (inquiry, reservation, contact).
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Per-IP throttle: true once an address has sent $limit submissions of a form in 10 minutes.
 * Logged-out nonces are shared and long-lived, so the nonce alone cannot stop scripted mail floods.
 *
 * @param string $form Form name.
 * @return bool
 */
function cobble_form_rate_limited( $form ) {
	$key = 'cobble_rl_' . md5( $form . '|' . cobble_client_ip() );
	$hit = (int) get_transient( $key );
	/**
	 * Submissions allowed per IP per form every 10 minutes.
	 *
	 * @param int    $limit Default 5.
	 * @param string $form  Form name.
	 */
	if ( $hit >= (int) apply_filters( 'cobble_form_rate_limit', 5, $form ) ) {
		return true;
	}
	set_transient( $key, $hit + 1, 10 * MINUTE_IN_SECONDS );
	return false;
}

/**
 * The visitor's IP for rate limiting. Behind Cloudflare or another proxy every visitor shares the
 * proxy's address: filter `cobble_client_ip` to read the real one from your proxy's header. Only do that
 * when REMOTE_ADDR is your proxy (the origin firewall accepts nothing else, or you check REMOTE_ADDR
 * against the proxy's published IP ranges); otherwise anyone can send a fake header and dodge the limit.
 *
 * @return string
 */
function cobble_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	/**
	 * Client IP used for form rate limits.
	 *
	 * @param string $ip REMOTE_ADDR.
	 */
	$filtered = (string) apply_filters( 'cobble_client_ip', $ip );
	return false !== filter_var( $filtered, FILTER_VALIDATE_IP ) ? $filtered : $ip;
}

/**
 * Let LiteSpeed Cache serve the form nonces fresh (ESI), so cached pages don't post expired ones.
 * Does nothing without LiteSpeed.
 */
function cobble_register_cache_nonces() {
	foreach ( array( 'cobble_inquiry', 'cobble_reservation', 'cobble_contact', 'cobble_room_booking' ) as $action ) {
		do_action( 'litespeed_nonce', $action );
	}
}
add_action( 'init', 'cobble_register_cache_nonces' );

/**
 * A real calendar date in Y-m-d (rejects 2026-02-31).
 *
 * @param string $date Date string.
 * @return bool
 */
function cobble_is_valid_date( $date ) {
	$parsed = \DateTimeImmutable::createFromFormat( '!Y-m-d', (string) $date, wp_timezone() );
	return $parsed && $parsed->format( 'Y-m-d' ) === $date;
}

/**
 * A published location ID.
 *
 * @param int $location_id Post ID.
 * @return bool
 */
function cobble_is_public_location( $location_id ) {
	return 'cobble_location' === get_post_type( $location_id ) && 'publish' === get_post_status( $location_id );
}
