<?php
/**
 * Native table requests: bookable time slots from each location's hours, and a nonce-checked,
 * honeypot-protected handler that emails the house and redirects back with
 * ?reservation=sent|invalid|expired|error. Requests are confirmed by the house, not instantly.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Party size, seating and occasion choices.
 *
 * @return array{party: array<int, string>, seating: array<int, string>, occasions: array<int, string>}
 */
function cc_reservation_choices() {
	return array(
		'party'     => array( '1', '2', '3', '4', '5', '6', '7', '8' ),
		'seating'   => array(
			__( 'No preference', 'cobbleandcandle-core' ),
			__( 'Dining room', 'cobbleandcandle-core' ),
			__( 'Bar / counter', 'cobbleandcandle-core' ),
			__( 'Outdoors', 'cobbleandcandle-core' ),
		),
		'occasions' => array(
			__( 'None', 'cobbleandcandle-core' ),
			__( 'Birthday', 'cobbleandcandle-core' ),
			__( 'Anniversary', 'cobbleandcandle-core' ),
			__( 'Business', 'cobbleandcandle-core' ),
			__( 'Other', 'cobbleandcandle-core' ),
		),
	);
}

/**
 * Bookable windows for the front end: weekly (Monday first) and holiday overrides, as
 * [first seating, last seating] minutes. The last seating is 90 minutes before closing.
 *
 * @param int $location_id Location post ID.
 * @return array{week: array<int, array{0: int, 1: int}|null>, holidays: array<string, array{0: int, 1: int}|null>, step: int}
 */
function cc_booking_windows( $location_id ) {
	$to_slots = static function ( $row ) {
		$window = cc_day_window( $row );
		if ( ! $window ) {
			return null;
		}
		/**
		 * Minutes before closing that the last table can be seated.
		 *
		 * @param int $minutes Default 90.
		 */
		$last = $window[1] - (int) apply_filters( 'cc_last_seating_offset', 90 );
		return $last >= $window[0] ? array( $window[0], $last ) : null;
	};

	$week = array();
	$rows = (array) get_post_meta( $location_id, 'cc_hours', true );
	for ( $i = 0; $i < 7; $i++ ) {
		$week[] = $to_slots( $rows[ $i ] ?? null );
	}
	$holidays = array();
	foreach ( (array) get_post_meta( $location_id, 'cc_holiday_hours', true ) as $holiday ) {
		if ( is_array( $holiday ) && ! empty( $holiday['date'] ) ) {
			$holidays[ $holiday['date'] ] = $to_slots( $holiday );
		}
	}

	return array(
		'week'     => $week,
		'holidays' => $holidays,
		/** Minutes between bookable slots. */
		'step'     => (int) apply_filters( 'cc_booking_slot_step', 30 ),
	);
}

/**
 * Is "HH:MM" on a date a bookable slot at this location?
 *
 * @param int    $location_id Location post ID.
 * @param string $date        Y-m-d.
 * @param string $time        HH:MM.
 * @return bool
 */
function cc_is_bookable( $location_id, $date, $time ) {
	$day = date_create_immutable( $date, wp_timezone() );
	$min = cc_minutes( $time );
	if ( ! $day || null === $min ) {
		return false;
	}
	$windows = cc_booking_windows( $location_id );
	$window  = array_key_exists( $date, $windows['holidays'] )
		? $windows['holidays'][ $date ]
		: $windows['week'][ (int) $day->format( 'N' ) - 1 ];
	if ( ! $window ) {
		return false;
	}
	// Slots past midnight are sent as early-morning times.
	if ( $min < $window[0] ) {
		$min += 1440;
	}
	return $min >= $window[0] && $min <= $window[1] && 0 === ( $min - $window[0] ) % max( 1, $windows['step'] );
}

/**
 * Handle a table request (logged in or not).
 */
function cc_handle_reservation() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$back = remove_query_arg( 'reservation', $back );
	$done = static function ( $status ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'reservation', $status, $back ) . '#book' );
		exit;
	};

	if ( ! isset( $_POST['cc_reservation_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cc_reservation_nonce'] ) ), 'cc_reservation' ) ) {
		$done( 'expired' );
	}
	// Honeypot: real visitors never see or fill this field.
	if ( ! empty( $_POST['cc_website'] ) ) {
		$done( 'sent' );
	}
	if ( cc_form_rate_limited( 'reservation' ) ) {
		$done( 'busy' );
	}

	$choices  = cc_reservation_choices();
	$location = isset( $_POST['cc_location'] ) ? absint( $_POST['cc_location'] ) : 0;
	$date     = isset( $_POST['cc_date'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_date'] ) ) : '';
	$time     = isset( $_POST['cc_time'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_time'] ) ) : '';
	$party    = isset( $_POST['cc_party'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_party'] ) ) : '';
	$seating  = isset( $_POST['cc_seating'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_seating'] ) ) : '';
	$occasion = isset( $_POST['cc_occasion'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_occasion'] ) ) : '';
	$name     = isset( $_POST['cc_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_name'] ) ) : '';
	$phone    = isset( $_POST['cc_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_phone'] ) ) : '';
	$email    = isset( $_POST['cc_email'] ) ? sanitize_email( wp_unslash( $_POST['cc_email'] ) ) : '';
	$requests = isset( $_POST['cc_requests'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cc_requests'] ) ) : '';
	$news     = ! empty( $_POST['cc_newsletter'] );

	// A slot today must still be ahead of us. Slots before 6am are tonight's past-midnight seatings.
	$now      = new \DateTimeImmutable( 'now', wp_timezone() );
	$slot_min = (int) cc_minutes( $time );
	$future   = $date > $now->format( 'Y-m-d' ) || $slot_min < 360 || $slot_min > (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' );

	$valid = $future && cc_is_public_location( $location )
		&& cc_is_valid_date( $date ) && $date >= wp_date( 'Y-m-d' ) && $date <= wp_date( 'Y-m-d', strtotime( '+1 year' ) )
		&& preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $time )
		&& cc_is_bookable( $location, $date, $time )
		&& in_array( $party, $choices['party'], true )
		&& in_array( $seating, $choices['seating'], true )
		&& in_array( $occasion, $choices['occasions'], true )
		&& '' !== $name && '' !== $phone && is_email( $email );
	if ( ! $valid ) {
		$done( 'invalid' );
	}

	$to = sanitize_email( (string) get_post_meta( $location, 'cc_email', true ) );
	$to = $to ? $to : get_option( 'admin_email' );
	/* translators: 1: guest name, 2: party size, 3: date, 4: time */
	$subject = sprintf( __( 'Table request: %1$s, %2$s guests, %3$s %4$s', 'cobbleandcandle-core' ), $name, $party, $date, $time );
	$body    = implode(
		"\n",
		array(
			__( 'Location', 'cobbleandcandle-core' ) . ': ' . get_the_title( $location ),
			__( 'Date', 'cobbleandcandle-core' ) . ': ' . $date,
			__( 'Time', 'cobbleandcandle-core' ) . ': ' . cc_time_label( $time ),
			__( 'Party size', 'cobbleandcandle-core' ) . ': ' . $party,
			__( 'Seating', 'cobbleandcandle-core' ) . ': ' . $seating,
			__( 'Occasion', 'cobbleandcandle-core' ) . ': ' . $occasion,
			__( 'Name', 'cobbleandcandle-core' ) . ': ' . $name,
			__( 'Phone', 'cobbleandcandle-core' ) . ': ' . $phone,
			__( 'Email', 'cobbleandcandle-core' ) . ': ' . $email,
			__( 'Newsletter', 'cobbleandcandle-core' ) . ': ' . ( $news ? __( 'Yes', 'cobbleandcandle-core' ) : __( 'No', 'cobbleandcandle-core' ) ),
			'',
			$requests,
		)
	);
	$sent    = wp_mail( $to, $subject, $body, array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );

	/**
	 * Fires after a table request is emailed, for CRMs, newsletters or booking systems.
	 *
	 * @param array<string, mixed> $request Sanitized request.
	 * @param bool                 $sent    Whether wp_mail() succeeded.
	 */
	do_action( 'cc_reservation_requested', compact( 'location', 'date', 'time', 'party', 'seating', 'occasion', 'name', 'phone', 'email', 'requests', 'news' ), $sent );

	$done( $sent ? 'sent' : 'error' );
}
add_action( 'admin_post_cc_reservation', 'cc_handle_reservation' );
add_action( 'admin_post_nopriv_cc_reservation', 'cc_handle_reservation' );
