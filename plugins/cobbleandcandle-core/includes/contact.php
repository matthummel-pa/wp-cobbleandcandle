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
function cc_contact_topics() {
	/**
	 * Topics offered on the contact form.
	 *
	 * @param array<string, string> $topics Key => label.
	 */
	return (array) apply_filters(
		'cc_contact_topics',
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
function cc_handle_contact() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$back = remove_query_arg( 'contact', $back );
	$done = static function ( $status ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'contact', $status, $back ) . '#contact' );
		exit;
	};

	if ( ! isset( $_POST['cc_contact_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cc_contact_nonce'] ) ), 'cc_contact' ) ) {
		$done( 'expired' );
	}
	// Honeypot: real visitors never see or fill this field.
	if ( ! empty( $_POST['cc_website'] ) ) {
		$done( 'sent' );
	}
	if ( cc_form_rate_limited( 'contact' ) ) {
		$done( 'busy' );
	}

	$topics   = cc_contact_topics();
	$topic    = isset( $_POST['cc_topic'] ) ? sanitize_key( wp_unslash( $_POST['cc_topic'] ) ) : '';
	$name     = isset( $_POST['cc_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_name'] ) ) : '';
	$email    = isset( $_POST['cc_email'] ) ? sanitize_email( wp_unslash( $_POST['cc_email'] ) ) : '';
	$phone    = isset( $_POST['cc_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_phone'] ) ) : '';
	$message  = isset( $_POST['cc_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cc_message'] ) ) : '';
	$location = isset( $_POST['cc_location'] ) ? absint( $_POST['cc_location'] ) : 0;
	$consent  = ! empty( $_POST['cc_consent'] );

	$valid = $consent && '' !== $name && is_email( $email ) && '' !== $message
		&& isset( $topics[ $topic ] )
		&& ( 0 === $location || cc_is_public_location( $location ) );
	if ( ! $valid ) {
		$done( 'invalid' );
	}

	$to = $location ? sanitize_email( (string) get_post_meta( $location, 'cc_email', true ) ) : '';
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
			__( 'Location', 'cobbleandcandle-core' ) . ': ' . ( $location ? cc_plain_title( $location ) : '—' ),
			'',
			$message,
		)
	);
	$sent    = wp_mail( $to, $subject, $body, array( 'Reply-To: ' . cc_mail_name( $name ) . ' <' . $email . '>' ) );
	$stored  = cc_store_message( 'contact', $subject, $body, $email, $location, $sent );

	$done( $sent || $stored ? 'sent' : 'error' );
}
add_action( 'admin_post_cc_contact', 'cc_handle_contact' );
add_action( 'admin_post_nopriv_cc_contact', 'cc_handle_contact' );
