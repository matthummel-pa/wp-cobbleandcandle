<?php
/**
 * Opening hours: formatting, grouping, and the server-rendered open-now status (HANDOFF §9).
 *
 * Weekly hours are stored Monday-first as [{open: "17:30", close: "23:00", closed: false}, …].
 * A close time earlier than the open time runs past midnight. Holiday rows override a date.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Minutes since midnight for "HH:MM", or null.
 *
 * @param string $time Time string.
 * @return int|null
 */
function cc_minutes( $time ) {
	if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', (string) $time, $m ) ) {
		return null;
	}
	return (int) $m[1] * 60 + (int) $m[2];
}

/**
 * Human time: "5:30pm", "11pm", "noon", "midnight".
 *
 * @param string $time "HH:MM".
 * @return string
 */
function cc_time_label( $time ) {
	$minutes = cc_minutes( $time );
	if ( null === $minutes ) {
		return '';
	}
	$minutes %= 1440;
	if ( 0 === $minutes ) {
		return __( 'midnight', 'cobbleandcandle-core' );
	}
	if ( 720 === $minutes ) {
		return __( 'noon', 'cobbleandcandle-core' );
	}
	$h      = intdiv( $minutes, 60 );
	$m      = $minutes % 60;
	$suffix = $h < 12 ? 'am' : 'pm';
	$h12    = $h % 12 ? $h % 12 : 12;
	return 0 === $m ? $h12 . $suffix : sprintf( '%d:%02d%s', $h12, $m, $suffix );
}

/**
 * Opening window for one day row as [open, close] minutes (close may exceed 1440), or null when closed.
 *
 * @param array<string, mixed>|null $row Day row.
 * @return array{0: int, 1: int}|null
 */
function cc_day_window( $row ) {
	if ( ! is_array( $row ) || ! empty( $row['closed'] ) ) {
		return null;
	}
	$open  = cc_minutes( $row['open'] ?? '' );
	$close = cc_minutes( $row['close'] ?? '' );
	if ( null === $open || null === $close ) {
		return null;
	}
	if ( $close <= $open ) {
		$close += 1440; // Past midnight.
	}
	return array( $open, $close );
}

/**
 * The row that applies to a date: a holiday override, else the weekly row.
 *
 * @param int                $location_id Location post ID.
 * @param \DateTimeImmutable $date        Date (site timezone).
 * @return array<string, mixed>|null
 */
function cc_hours_row_for_date( $location_id, \DateTimeImmutable $date ) {
	foreach ( (array) get_post_meta( $location_id, 'cc_holiday_hours', true ) as $holiday ) {
		if ( is_array( $holiday ) && ( $holiday['date'] ?? '' ) === $date->format( 'Y-m-d' ) ) {
			return $holiday;
		}
	}
	$week = (array) get_post_meta( $location_id, 'cc_hours', true );
	return $week[ (int) $date->format( 'N' ) - 1 ] ?? null;
}

/**
 * Open-now status for a location.
 *
 * @param int                     $location_id Location post ID.
 * @param \DateTimeImmutable|null $now         Defaults to now in the site timezone.
 * @return array{state: string, text: string}
 */
function cc_location_status( $location_id, ?\DateTimeImmutable $now = null ) {
	$now     = $now ? $now : new \DateTimeImmutable( 'now', wp_timezone() );
	$minutes = (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' );

	// Still open from a late night yesterday?
	$yesterday = cc_day_window( cc_hours_row_for_date( $location_id, $now->modify( '-1 day' ) ) );
	if ( $yesterday && $yesterday[1] > 1440 && $minutes < $yesterday[1] - 1440 ) {
		return cc_status_open( $yesterday[1] - 1440 - $minutes, $yesterday[1] );
	}

	$today = cc_day_window( cc_hours_row_for_date( $location_id, $now ) );
	if ( $today && $minutes >= $today[0] && $minutes < $today[1] ) {
		return cc_status_open( $today[1] - $minutes, $today[1] );
	}
	if ( $today && $minutes < $today[0] ) {
		return array(
			'state' => 'off',
			/* translators: %s: opening time, e.g. 5:30pm */
			'text'  => sprintf( __( 'Closed · opens %s', 'cobbleandcandle-core' ), cc_time_label( cc_minutes_to_time( $today[0] ) ) ),
		);
	}

	for ( $k = 1; $k <= 7; $k++ ) {
		$day    = $now->modify( "+{$k} day" );
		$window = cc_day_window( cc_hours_row_for_date( $location_id, $day ) );
		if ( $window ) {
			$when = 1 === $k ? __( 'tomorrow', 'cobbleandcandle-core' ) : wp_date( 'D', $day->getTimestamp() );
			return array(
				'state' => 'off',
				/* translators: 1: "tomorrow" or a weekday, 2: opening time */
				'text'  => sprintf( __( 'Closed · opens %1$s %2$s', 'cobbleandcandle-core' ), $when, cc_time_label( cc_minutes_to_time( $window[0] ) ) ),
			);
		}
	}

	return array(
		'state' => 'off',
		'text'  => __( 'Closed', 'cobbleandcandle-core' ),
	);
}

/**
 * Open status, flagged "closing soon" within the last hour.
 *
 * @param int $left  Minutes until closing.
 * @param int $close Closing minute.
 * @return array{state: string, text: string}
 */
function cc_status_open( $left, $close ) {
	$label = cc_time_label( cc_minutes_to_time( $close ) );
	if ( $left <= 60 ) {
		return array(
			'state' => 'warn',
			/* translators: %s: closing time */
			'text'  => sprintf( __( 'Closing soon · closes %s', 'cobbleandcandle-core' ), $label ),
		);
	}
	return array(
		'state' => 'open',
		/* translators: %s: closing time */
		'text'  => sprintf( __( 'Open now · closes %s', 'cobbleandcandle-core' ), $label ),
	);
}

/**
 * "HH:MM" for a minute count (wraps past midnight).
 *
 * @param int $minutes Minutes.
 * @return string
 */
function cc_minutes_to_time( $minutes ) {
	$minutes %= 1440;
	return sprintf( '%02d:%02d', intdiv( $minutes, 60 ), $minutes % 60 );
}

/**
 * Weekly hours grouped into runs of identical days: [["Wed – Sat", "5:30pm – 11pm"], …].
 *
 * @param int $location_id Location post ID.
 * @return array<int, array{0: string, 1: string}>
 */
function cc_hours_grouped( $location_id ) {
	$week  = (array) get_post_meta( $location_id, 'cc_hours', true );
	$days  = array( __( 'Mon', 'cobbleandcandle-core' ), __( 'Tue', 'cobbleandcandle-core' ), __( 'Wed', 'cobbleandcandle-core' ), __( 'Thu', 'cobbleandcandle-core' ), __( 'Fri', 'cobbleandcandle-core' ), __( 'Sat', 'cobbleandcandle-core' ), __( 'Sun', 'cobbleandcandle-core' ) );
	$label = static function ( $row ) {
		$window = cc_day_window( $row );
		return $window
			? cc_time_label( cc_minutes_to_time( $window[0] ) ) . ' – ' . cc_time_label( cc_minutes_to_time( $window[1] ) )
			: __( 'Closed', 'cobbleandcandle-core' );
	};

	$out = array();
	for ( $i = 0; $i < 7; ) {
		$j = $i;
		while ( $j + 1 < 7 && $label( $week[ $j + 1 ] ?? null ) === $label( $week[ $i ] ?? null ) ) {
			++$j;
		}
		$out[] = array( $i === $j ? $days[ $i ] : $days[ $i ] . ' – ' . $days[ $j ], $label( $week[ $i ] ?? null ) );
		$i     = $j + 1;
	}
	return $out;
}
