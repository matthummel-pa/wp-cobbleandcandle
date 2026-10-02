<?php
/**
 * Newsletter signups: a nonce-checked, honeypot-protected handler that emails the site admin
 * and redirects back with ?newsletter=sent|invalid|expired|busy|error.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Form action URL (admin-post.php).
 *
 * @return string
 */
function cc_newsletter_form_url() {
	return admin_url( 'admin-post.php' );
}

/**
 * Handle a signup (logged in or not).
 */
function cc_handle_newsletter() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$back = remove_query_arg( 'newsletter', $back );
	$done = static function ( $status ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'newsletter', $status, $back ) . '#newsletter' );
		exit;
	};

	if ( ! isset( $_POST['cc_newsletter_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cc_newsletter_nonce'] ) ), 'cc_newsletter' ) ) {
		$done( 'expired' );
	}
	// Honeypot: real visitors never see or fill this field.
	if ( ! empty( $_POST['cc_website'] ) ) {
		$done( 'sent' );
	}
	if ( cc_form_rate_limited( 'newsletter' ) ) {
		$done( 'busy' );
	}

	$email = isset( $_POST['cc_email'] ) ? sanitize_email( wp_unslash( $_POST['cc_email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		$done( 'invalid' );
	}

	/**
	 * Fires when a newsletter address is accepted, before the admin email is sent.
	 * Use it to hand the address to a list provider.
	 *
	 * @param string $email Sanitized address.
	 */
	do_action( 'cc_newsletter_signup', $email );

	$sent = wp_mail(
		get_option( 'admin_email' ),
		__( 'Newsletter signup', 'cobbleandcandle-core' ),
		__( 'Email', 'cobbleandcandle-core' ) . ': ' . $email
	);

	$done( $sent ? 'sent' : 'error' );
}
add_action( 'admin_post_cc_newsletter', 'cc_handle_newsletter' );
add_action( 'admin_post_nopriv_cc_newsletter', 'cc_handle_newsletter' );
