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
function cc_form_rate_limited( $form ) {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key = 'cc_rl_' . md5( $form . '|' . $ip );
	$hit = (int) get_transient( $key );
	/**
	 * Submissions allowed per IP per form every 10 minutes.
	 *
	 * @param int    $limit Default 5.
	 * @param string $form  Form name.
	 */
	if ( $hit >= (int) apply_filters( 'cc_form_rate_limit', 5, $form ) ) {
		return true;
	}
	set_transient( $key, $hit + 1, 10 * MINUTE_IN_SECONDS );
	return false;
}

/**
 * Let LiteSpeed Cache serve the form nonces fresh (ESI), so cached pages don't post expired ones.
 * Does nothing without LiteSpeed.
 */
function cc_register_cache_nonces() {
	foreach ( array( 'cc_inquiry', 'cc_reservation', 'cc_contact', 'cc_newsletter' ) as $action ) {
		do_action( 'litespeed_nonce', $action );
	}
}
add_action( 'init', 'cc_register_cache_nonces' );

/**
 * A real calendar date in Y-m-d (rejects 2026-02-31).
 *
 * @param string $date Date string.
 * @return bool
 */
function cc_is_valid_date( $date ) {
	$parsed = \DateTimeImmutable::createFromFormat( '!Y-m-d', (string) $date, wp_timezone() );
	return $parsed && $parsed->format( 'Y-m-d' ) === $date;
}

/**
 * A published location ID.
 *
 * @param int $location_id Post ID.
 * @return bool
 */
function cc_is_public_location( $location_id ) {
	return 'cc_location' === get_post_type( $location_id ) && 'publish' === get_post_status( $location_id );
}
