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
function cobble_inquiry_form_url() {
	return admin_url( 'admin-post.php' );
}

/**
 * Guest-count and occasion choices.
 *
 * @return array{guests: array<int, string>, occasions: array<string, string>}
 */
function cobble_inquiry_choices() {
	return array(
		'guests'    => array( '10 – 20', '21 – 40', '41 – 80', '80+' ),
		'occasions' => array( // Stable keys are posted; labels are only shown.
			'celebration' => __( 'Celebration', 'cobbleandcandle-core' ),
			'business'    => __( 'Business dinner', 'cobbleandcandle-core' ),
			'wedding'     => __( 'Wedding / rehearsal', 'cobbleandcandle-core' ),
			'memorial'    => __( 'Wake or memorial', 'cobbleandcandle-core' ),
			'other'       => __( 'Other', 'cobbleandcandle-core' ),
		),
	);
}

/**
 * Handle a submission (logged in or not).
 */
function cobble_handle_inquiry() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$back = remove_query_arg( 'inquiry', $back );

	if ( ! isset( $_POST['cobble_inquiry_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cobble_inquiry_nonce'] ) ), 'cobble_inquiry' ) ) {
		wp_safe_redirect( add_query_arg( 'inquiry', 'expired', $back ) . '#private-dining' );
		exit;
	}
	// Honeypot: real visitors never see or fill this field.
	if ( ! empty( $_POST['cobble_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'inquiry', 'sent', $back ) . '#private-dining' );
		exit;
	}
	if ( cobble_form_rate_limited( 'inquiry' ) ) {
		wp_safe_redirect( add_query_arg( 'inquiry', 'busy', $back ) . '#private-dining' );
		exit;
	}

	$choices  = cobble_inquiry_choices();
	$name     = isset( $_POST['cobble_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cobble_name'] ) ) : '';
	$email    = isset( $_POST['cobble_email'] ) ? sanitize_email( wp_unslash( $_POST['cobble_email'] ) ) : '';
	$date     = isset( $_POST['cobble_date'] ) ? sanitize_text_field( wp_unslash( $_POST['cobble_date'] ) ) : '';
	$guests   = isset( $_POST['cobble_guests'] ) ? sanitize_text_field( wp_unslash( $_POST['cobble_guests'] ) ) : '';
	$occasion = isset( $_POST['cobble_occasion'] ) ? sanitize_key( wp_unslash( $_POST['cobble_occasion'] ) ) : '';
	$message  = isset( $_POST['cobble_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cobble_message'] ) ) : '';
	$location = isset( $_POST['cobble_location'] ) ? absint( $_POST['cobble_location'] ) : 0;

	$valid = '' !== $name && is_email( $email )
		&& cobble_is_valid_date( $date )
		&& in_array( $guests, $choices['guests'], true )
		&& isset( $choices['occasions'][ $occasion ] )
		&& ( 0 === $location || cobble_is_public_location( $location ) );
	if ( ! $valid ) {
		wp_safe_redirect( add_query_arg( 'inquiry', 'invalid', $back ) . '#private-dining' );
		exit;
	}

	$to = $location ? sanitize_email( (string) get_post_meta( $location, 'cobble_email', true ) ) : '';
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
			__( 'Occasion', 'cobbleandcandle-core' ) . ': ' . $choices['occasions'][ $occasion ],
			__( 'Location', 'cobbleandcandle-core' ) . ': ' . ( $location ? cobble_plain_title( $location ) : '—' ),
			'',
			$message,
		)
	);
	$sent    = wp_mail( $to, $subject, $body, array( 'Reply-To: ' . cobble_mail_name( $name ) . ' <' . $email . '>' ) );
	$stored  = cobble_store_message( 'inquiry', $subject, $body, $email, $location, $sent );

	wp_safe_redirect( add_query_arg( 'inquiry', $sent || $stored ? 'sent' : 'error', $back ) . '#private-dining' );
	exit;
}
add_action( 'admin_post_cobble_inquiry', 'cobble_handle_inquiry' );
add_action( 'admin_post_nopriv_cobble_inquiry', 'cobble_handle_inquiry' );
