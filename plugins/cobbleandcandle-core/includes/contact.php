<?php
/**
 * General contact form: a nonce-checked, honeypot-protected handler that emails the chosen
 * location (or the site admin) and redirects back with ?contact=sent|invalid|expired|error.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Contact topics: stable key => label (the key is posted, the label shown).
 *
 * @return array<string, string>
 */
function cobble_contact_topics() {
	/**
	 * Topics offered on the contact form.
	 *
	 * @param array<string, string> $topics Key => label. Keys must be lowercase a-z, 0-9, _ or - (sanitize_key-safe).
	 */
	return (array) apply_filters(
		'cobble_contact_topics',
		array(
			'general'     => __( 'General', 'cobbleandcandle-core' ),
			'reservation' => __( 'Reservation', 'cobbleandcandle-core' ),
			'private'     => __( 'Private dining', 'cobbleandcandle-core' ),
			'press'       => __( 'Press', 'cobbleandcandle-core' ),
			'lost'        => __( 'Lost property', 'cobbleandcandle-core' ),
		)
	);
}

/**
 * Handle a contact message (logged in or not).
 */
function cobble_handle_contact() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$back = remove_query_arg( 'contact', $back );
	$done = static function ( $status ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'contact', $status, $back ) . '#contact' );
		exit;
	};

	if ( ! isset( $_POST['cobble_contact_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cobble_contact_nonce'] ) ), 'cobble_contact' ) ) {
		$done( 'expired' );
	}
	// Honeypot: real visitors never see or fill this field.
	if ( ! empty( $_POST['cobble_website'] ) ) {
		$done( 'sent' );
	}
	if ( cobble_form_rate_limited( 'contact' ) ) {
		$done( 'busy' );
	}

	$topics   = cobble_contact_topics();
	$topic    = isset( $_POST['cobble_topic'] ) ? sanitize_key( wp_unslash( $_POST['cobble_topic'] ) ) : '';
	$name     = isset( $_POST['cobble_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cobble_name'] ) ) : '';
	$email    = isset( $_POST['cobble_email'] ) ? sanitize_email( wp_unslash( $_POST['cobble_email'] ) ) : '';
	$phone    = isset( $_POST['cobble_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['cobble_phone'] ) ) : '';
	$message  = isset( $_POST['cobble_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cobble_message'] ) ) : '';
	$location = isset( $_POST['cobble_location'] ) ? absint( $_POST['cobble_location'] ) : 0;
	$consent  = ! empty( $_POST['cobble_consent'] );

	$valid = $consent && '' !== $name && is_email( $email ) && '' !== $message
		&& isset( $topics[ $topic ] )
		&& ( 0 === $location || cobble_is_public_location( $location ) );
	if ( ! $valid ) {
		$done( 'invalid' );
	}

	$to = $location ? sanitize_email( (string) get_post_meta( $location, 'cobble_email', true ) ) : '';
	$to = $to ? $to : get_option( 'admin_email' );
	/* translators: 1: topic, 2: sender name */
	$subject = sprintf( __( 'Website message (%1$s) from %2$s', 'cobbleandcandle-core' ), $topics[ $topic ], $name );
	$body    = implode(
		"\n",
		array(
			__( 'Topic', 'cobbleandcandle-core' ) . ': ' . $topics[ $topic ],
			__( 'Name', 'cobbleandcandle-core' ) . ': ' . $name,
			__( 'Email', 'cobbleandcandle-core' ) . ': ' . $email,
			__( 'Phone', 'cobbleandcandle-core' ) . ': ' . ( '' !== $phone ? $phone : '—' ),
			__( 'Location', 'cobbleandcandle-core' ) . ': ' . ( $location ? cobble_plain_title( $location ) : '—' ),
			'',
			$message,
		)
	);
	$sent    = wp_mail( $to, $subject, $body, array( 'Reply-To: ' . cobble_mail_name( $name ) . ' <' . $email . '>' ) );
	$stored  = cobble_store_message( 'contact', $subject, $body, $email, $location, $sent );

	$done( $sent || $stored ? 'sent' : 'error' );
}
add_action( 'admin_post_cobble_contact', 'cobble_handle_contact' );
add_action( 'admin_post_nopriv_cobble_contact', 'cobble_handle_contact' );
