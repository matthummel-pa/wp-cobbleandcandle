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
function cobble_reservation_choices() {
	return array(
		'party'     => array( '1', '2', '3', '4', '5', '6', '7', '8' ),
		// Stable keys are posted; labels are only shown (a language switch can't break a submission).
		'seating'   => array(
			'any'      => __( 'No preference', 'cobbleandcandle-core' ),
			'dining'   => __( 'Dining room', 'cobbleandcandle-core' ),
			'bar'      => __( 'Bar / counter', 'cobbleandcandle-core' ),
			'outdoors' => __( 'Outdoors', 'cobbleandcandle-core' ),
		),
		'occasions' => array(
			'none'        => __( 'None', 'cobbleandcandle-core' ),
			'birthday'    => __( 'Birthday', 'cobbleandcandle-core' ),
			'anniversary' => __( 'Anniversary', 'cobbleandcandle-core' ),
			'business'    => __( 'Business', 'cobbleandcandle-core' ),
			'other'       => __( 'Other', 'cobbleandcandle-core' ),
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
function cobble_booking_windows( $location_id ) {
	$to_slots = static function ( $row ) {
		$window = cobble_day_window( $row );
		if ( ! $window ) {
			return null;
		}
		/**
		 * Minutes before closing that the last table can be seated.
		 *
		 * @param int $minutes Default 90.
		 */
		$last = $window[1] - (int) apply_filters( 'cobble_last_seating_offset', 90 );
		return $last >= $window[0] ? array( $window[0], $last ) : null;
	};

	$week = array();
	$rows = (array) get_post_meta( $location_id, 'cobble_hours', true );
	for ( $i = 0; $i < 7; $i++ ) {
		$week[] = $to_slots( $rows[ $i ] ?? null );
	}
	$holidays = array();
	foreach ( (array) get_post_meta( $location_id, 'cobble_holiday_hours', true ) as $holiday ) {
		if ( is_array( $holiday ) && ! empty( $holiday['date'] ) ) {
			$holidays[ $holiday['date'] ] = $to_slots( $holiday );
		}
	}

	return array(
		'week'     => $week,
		'holidays' => $holidays,
		/** Minutes between bookable slots. */
		'step'     => (int) apply_filters( 'cobble_booking_slot_step', 30 ),
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
function cobble_is_bookable( $location_id, $date, $time ) {
	$day = date_create_immutable( $date, wp_timezone() );
	$min = cobble_minutes( $time );
	if ( ! $day || null === $min ) {
		return false;
	}
	$windows = cobble_booking_windows( $location_id );
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
 * A slot today must still be ahead of us. Slots before 6am are tonight's past-midnight seatings.
 *
 * @param string $date Y-m-d.
 * @param string $time HH:MM.
 * @return bool
 */
function cobble_slot_is_future( $date, $time ) {
	$now      = new \DateTimeImmutable( 'now', wp_timezone() );
	$slot_min = (int) cobble_minutes( $time );
	return $date > $now->format( 'Y-m-d' ) || $slot_min < 360 || $slot_min > (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' );
}

/**
 * Handle a table request (logged in or not).
 */
function cobble_handle_reservation() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$back = remove_query_arg( 'reservation', $back );
	$done = static function ( $status ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'reservation', $status, $back ) . '#book' );
		exit;
	};

	if ( ! isset( $_POST['cobble_reservation_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cobble_reservation_nonce'] ) ), 'cobble_reservation' ) ) {
		$done( 'expired' );
	}
	// Honeypot: real visitors never see or fill this field.
	if ( ! empty( $_POST['cobble_website'] ) ) {
		$done( 'sent' );
	}
	if ( cobble_form_rate_limited( 'reservation' ) ) {
		$done( 'busy' );
	}

	$choices  = cobble_reservation_choices();
	$location = isset( $_POST['cobble_location'] ) ? absint( $_POST['cobble_location'] ) : 0;
	$date     = isset( $_POST['cobble_date'] ) ? sanitize_text_field( wp_unslash( $_POST['cobble_date'] ) ) : '';
	$time     = isset( $_POST['cobble_time'] ) ? sanitize_text_field( wp_unslash( $_POST['cobble_time'] ) ) : '';
	$party    = isset( $_POST['cobble_party'] ) ? sanitize_text_field( wp_unslash( $_POST['cobble_party'] ) ) : '';
	$seating  = isset( $_POST['cobble_seating'] ) ? sanitize_key( wp_unslash( $_POST['cobble_seating'] ) ) : 'any';
	$occasion = isset( $_POST['cobble_occasion'] ) ? sanitize_key( wp_unslash( $_POST['cobble_occasion'] ) ) : 'none';
	$name     = isset( $_POST['cobble_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cobble_name'] ) ) : '';
	$phone    = isset( $_POST['cobble_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['cobble_phone'] ) ) : '';
	$email    = isset( $_POST['cobble_email'] ) ? sanitize_email( wp_unslash( $_POST['cobble_email'] ) ) : '';
	$requests = isset( $_POST['cobble_requests'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cobble_requests'] ) ) : '';
	$news     = ! empty( $_POST['cobble_newsletter'] );

	$valid = cobble_slot_is_future( $date, $time ) && cobble_is_public_location( $location )
		&& cobble_is_valid_date( $date ) && $date >= wp_date( 'Y-m-d' ) && $date <= wp_date( 'Y-m-d', strtotime( '+1 year' ) )
		&& preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $time )
		&& cobble_is_bookable( $location, $date, $time )
		&& in_array( $party, $choices['party'], true )
		&& isset( $choices['seating'][ $seating ], $choices['occasions'][ $occasion ] )
		&& '' !== $name && '' !== $phone && is_email( $email );
	if ( ! $valid ) {
		$done( 'invalid' );
	}

	$to = sanitize_email( (string) get_post_meta( $location, 'cobble_email', true ) );
	$to = $to ? $to : get_option( 'admin_email' );
	/* translators: 1: guest name, 2: party size, 3: date, 4: time */
	$subject = sprintf( __( 'Table request: %1$s, %2$s guests, %3$s %4$s', 'cobbleandcandle-core' ), $name, $party, $date, $time );
	$body    = implode(
		"\n",
		array(
			__( 'Location', 'cobbleandcandle-core' ) . ': ' . cobble_plain_title( $location ),
			__( 'Date', 'cobbleandcandle-core' ) . ': ' . $date,
			__( 'Time', 'cobbleandcandle-core' ) . ': ' . cobble_time_label( $time ),
			__( 'Party size', 'cobbleandcandle-core' ) . ': ' . $party,
			__( 'Seating', 'cobbleandcandle-core' ) . ': ' . $choices['seating'][ $seating ],
			__( 'Occasion', 'cobbleandcandle-core' ) . ': ' . $choices['occasions'][ $occasion ],
			__( 'Name', 'cobbleandcandle-core' ) . ': ' . $name,
			__( 'Phone', 'cobbleandcandle-core' ) . ': ' . $phone,
			__( 'Email', 'cobbleandcandle-core' ) . ': ' . $email,
			__( 'Newsletter', 'cobbleandcandle-core' ) . ': ' . ( $news ? __( 'Yes', 'cobbleandcandle-core' ) : __( 'No', 'cobbleandcandle-core' ) ),
			'',
			$requests,
		)
	);
	$sent    = wp_mail( $to, $subject, $body, array( 'Reply-To: ' . cobble_mail_name( $name ) . ' <' . $email . '>' ) );
	$stored  = cobble_store_message( 'reservation', $subject, $body, $email, $location, $sent );

	/**
	 * Fires after a table request is emailed, for CRMs, newsletters or booking systems.
	 *
	 * @param array<string, mixed> $request Sanitized request. `seating`/`occasion` are stable keys; `*_label` the shown text.
	 * @param bool                 $sent    Whether wp_mail() succeeded.
	 */
	$seating_label  = $choices['seating'][ $seating ];
	$occasion_label = $choices['occasions'][ $occasion ];
	do_action( 'cobble_reservation_requested', compact( 'location', 'date', 'time', 'party', 'seating', 'occasion', 'seating_label', 'occasion_label', 'name', 'phone', 'email', 'requests', 'news' ), $sent );

	$done( $sent || $stored ? 'sent' : 'error' );
}
add_action( 'admin_post_cobble_reservation', 'cobble_handle_reservation' );
add_action( 'admin_post_nopriv_cobble_reservation', 'cobble_handle_reservation' );
