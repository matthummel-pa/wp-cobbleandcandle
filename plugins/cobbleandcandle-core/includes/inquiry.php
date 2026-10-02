<?php
/**
 * Private dining inquiries: a nonce-checked, honeypot-protected form handler that emails the
 * chosen location (or the site admin) and redirects back with ?inquiry=sent|invalid|expired.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Form action URL (admin-post.php).
 *
 * @return string
 */
function cc_inquiry_form_url() {
	return admin_url( 'admin-post.php' );
}

/**
 * Guest-count and occasion choices.
 *
 * @return array{guests: array<int, string>, occasions: array<int, string>}
 */
function cc_inquiry_choices() {
	return array(
		'guests'    => array( '10 – 20', '21 – 40', '41 – 80', '80+' ),
		'occasions' => array(
			__( 'Celebration', 'cobbleandcandle-core' ),
			__( 'Business dinner', 'cobbleandcandle-core' ),
			__( 'Wedding / rehearsal', 'cobbleandcandle-core' ),
			__( 'Wake or memorial', 'cobbleandcandle-core' ),
			__( 'Other', 'cobbleandcandle-core' ),
		),
	);
}

/**
 * Handle a submission (logged in or not).
 */
function cc_handle_inquiry() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$back = remove_query_arg( 'inquiry', $back );

	if ( ! isset( $_POST['cc_inquiry_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cc_inquiry_nonce'] ) ), 'cc_inquiry' ) ) {
		wp_safe_redirect( add_query_arg( 'inquiry', 'expired', $back ) . '#private-dining' );
		exit;
	}
	// Honeypot: real visitors never see or fill this field.
	if ( ! empty( $_POST['cc_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'inquiry', 'sent', $back ) . '#private-dining' );
		exit;
	}

	$choices  = cc_inquiry_choices();
	$name     = isset( $_POST['cc_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_name'] ) ) : '';
	$email    = isset( $_POST['cc_email'] ) ? sanitize_email( wp_unslash( $_POST['cc_email'] ) ) : '';
	$date     = isset( $_POST['cc_date'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_date'] ) ) : '';
	$guests   = isset( $_POST['cc_guests'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_guests'] ) ) : '';
	$occasion = isset( $_POST['cc_occasion'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_occasion'] ) ) : '';
	$message  = isset( $_POST['cc_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cc_message'] ) ) : '';
	$location = isset( $_POST['cc_location'] ) ? absint( $_POST['cc_location'] ) : 0;

	$valid = '' !== $name && is_email( $email )
		&& preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date )
		&& in_array( $guests, $choices['guests'], true )
		&& in_array( $occasion, $choices['occasions'], true )
		&& ( 0 === $location || 'cc_location' === get_post_type( $location ) );
	if ( ! $valid ) {
		wp_safe_redirect( add_query_arg( 'inquiry', 'invalid', $back ) . '#private-dining' );
		exit;
	}

	$to = $location ? sanitize_email( (string) get_post_meta( $location, 'cc_email', true ) ) : '';
	$to = $to ? $to : get_option( 'admin_email' );
	/* translators: 1: guest name, 2: date */
	$subject = sprintf( __( 'Private dining inquiry: %1$s, %2$s', 'cobbleandcandle-core' ), $name, $date );
	$body    = implode(
		"\n",
		array(
			__( 'Name', 'cobbleandcandle-core' ) . ': ' . $name,
			__( 'Email', 'cobbleandcandle-core' ) . ': ' . $email,
			__( 'Date', 'cobbleandcandle-core' ) . ': ' . $date,
			__( 'Guests', 'cobbleandcandle-core' ) . ': ' . $guests,
			__( 'Occasion', 'cobbleandcandle-core' ) . ': ' . $occasion,
			__( 'Location', 'cobbleandcandle-core' ) . ': ' . ( $location ? get_the_title( $location ) : '—' ),
			'',
			$message,
		)
	);
	$sent    = wp_mail( $to, $subject, $body, array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );

	wp_safe_redirect( add_query_arg( 'inquiry', $sent ? 'sent' : 'error', $back ) . '#private-dining' );
	exit;
}
add_action( 'admin_post_cc_inquiry', 'cc_handle_inquiry' );
add_action( 'admin_post_nopriv_cc_inquiry', 'cc_handle_inquiry' );
