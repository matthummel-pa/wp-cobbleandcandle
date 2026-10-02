<?php
/**
 * Two-way calendar sync for rooms (Airbnb, Booking.com, Vrbo and anything else that speaks iCal).
 *
 * Export: each room has a secret feed URL listing taken nights as "Reserved" (no guest data).
 * Import: iCal links saved on a room are fetched hourly; their nights block the room here.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Secret key for a room's export feed.
 *
 * @param int $room_id Room post ID.
 * @return string
 */
function cc_room_ical_key( $room_id ) {
	return substr( wp_hash( 'cc_room_ical|' . (int) $room_id ), 0, 16 );
}

/**
 * Export feed URL for a room (paste into Airbnb / Booking.com / Vrbo "import calendar").
 *
 * @param int $room_id Room post ID.
 * @return string
 */
function cc_room_ical_url( $room_id ) {
	return add_query_arg(
		array(
			'cc_room_ical' => (int) $room_id,
			'key'          => cc_room_ical_key( $room_id ),
		),
		home_url( '/' )
	);
}

/**
 * Serve the export feed: site bookings (pending and confirmed) as all-day "Reserved" events.
 * Imported nights are left out so platforms don't echo each other's bookings back.
 */
function cc_serve_room_ical() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only feed guarded by a secret key.
	if ( ! isset( $_GET['cc_room_ical'] ) ) {
		return;
	}
	$room_id = absint( $_GET['cc_room_ical'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$key     = isset( $_GET['key'] ) ? sanitize_key( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $room_id || 'cc_room' !== get_post_type( $room_id ) || ! hash_equals( cc_room_ical_key( $room_id ), $key ) ) {
		status_header( 404 );
		exit;
	}
	$host     = wp_parse_url( home_url(), PHP_URL_HOST );
	$bookings = get_posts(
		array(
			'post_type'      => 'cc_booking',
			'post_status'    => 'publish',
			'posts_per_page' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- one room's bookings in a date window; small and bounded.
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- feed polled by calendar services, small result set.
				array(
					'key'   => 'cc_room',
					'value' => $room_id,
				),
				array(
					'key'     => 'cc_status',
					'value'   => cc_booking_holding_statuses(),
					'compare' => 'IN',
				),
				array(
					'key'     => 'cc_check_out',
					'value'   => wp_date( 'Y-m-d', strtotime( '-30 days' ) ),
					'compare' => '>=',
				),
			),
		)
	);
	$lines    = array(
		'BEGIN:VCALENDAR',
		'VERSION:2.0',
		'PRODID:-//Cobble & Candle Core//Rooms//EN',
		'CALSCALE:GREGORIAN',
		'METHOD:PUBLISH',
		'X-WR-CALNAME:' . cc_ics_text( cc_plain_title( $room_id ) ),
	);
	foreach ( $bookings as $booking_id ) {
		$in  = (string) get_post_meta( $booking_id, 'cc_check_in', true );
		$out = (string) get_post_meta( $booking_id, 'cc_check_out', true );
		if ( ! cc_is_valid_date( $in ) || ! cc_is_valid_date( $out ) ) {
			continue;
		}
		array_push(
			$lines,
			'BEGIN:VEVENT',
			'UID:booking-' . $booking_id . '@' . $host,
			'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
			'DTSTART;VALUE=DATE:' . str_replace( '-', '', $in ),
			'DTEND;VALUE=DATE:' . str_replace( '-', '', $out ),
			'SUMMARY:Reserved',
			'END:VEVENT'
		);
	}
	$lines[] = 'END:VCALENDAR';

	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );
	header( 'Content-Type: text/calendar; charset=utf-8' );
	echo implode( "\r\n", $lines ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text/calendar body: dates are validated, the name goes through cc_ics_text().
	exit;
}
add_action( 'template_redirect', 'cc_serve_room_ical', 0 );

/**
 * Parse all-day (or timed) VEVENTs from an iCal body into night ranges.
 *
 * @param string $body iCal text.
 * @return array<int, array{start: string, end: string}>
 */
function cc_parse_ical_ranges( $body ) {
	$body   = preg_replace( "/\r?\n[ \t]/", '', (string) $body ); // Unfold long lines.
	$ranges = array();
	if ( ! preg_match_all( '/BEGIN:VEVENT(.*?)END:VEVENT/s', (string) $body, $events ) ) {
		return $ranges;
	}
	$horizon = gmdate( 'Y-m-d', strtotime( '+2 years' ) );
	$today   = wp_date( 'Y-m-d' );
	foreach ( $events[1] as $event ) {
		if ( ! preg_match( '/^DTSTART[^:\r\n]*:(\d{8})/m', $event, $start ) ) {
			continue;
		}
		$end   = preg_match( '/^DTEND[^:\r\n]*:(\d{8})/m', $event, $match ) ? $match[1] : gmdate( 'Ymd', strtotime( $start[1] . ' +1 day' ) );
		$start = substr( $start[1], 0, 4 ) . '-' . substr( $start[1], 4, 2 ) . '-' . substr( $start[1], 6, 2 );
		$end   = substr( $end, 0, 4 ) . '-' . substr( $end, 4, 2 ) . '-' . substr( $end, 6, 2 );
		if ( ! cc_is_valid_date( $start ) || ! cc_is_valid_date( $end ) || $end <= $today || $start > $horizon ) {
			continue;
		}
		if ( $end <= $start ) {
			$end = gmdate( 'Y-m-d', strtotime( $start . ' 12:00:00 UTC' ) + DAY_IN_SECONDS );
		}
		$ranges[] = array(
			'start' => $start,
			'end'   => $end,
		);
		if ( count( $ranges ) >= 1000 ) {
			break;
		}
	}
	return $ranges;
}

/**
 * Fetch one room's import calendars and store the blocked nights.
 * A feed that fails keeps its last good nights, so an outage never frees booked dates.
 *
 * @param int $room_id Room post ID.
 * @return array{ok: int, failed: int}
 */
function cc_sync_room_ical( $room_id ) {
	$feeds    = (array) get_post_meta( $room_id, 'cc_ical_import', true );
	$previous = (array) get_post_meta( $room_id, '_cc_ical_cache', true );
	$cache    = array();
	$result   = array(
		'ok'     => 0,
		'failed' => 0,
	);
	foreach ( $feeds as $feed ) {
		$url = is_array( $feed ) ? esc_url_raw( (string) ( $feed['url'] ?? '' ), array( 'http', 'https', 'webcal' ) ) : '';
		if ( '' === $url ) {
			continue;
		}
		$url      = preg_replace( '#^webcal://#i', 'https://', $url );
		$hash     = md5( $url );
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 10,
				'limit_response_size' => 2 * MB_IN_BYTES,
				'user-agent'          => 'CobbleAndCandle/' . CC_CORE_VERSION . '; ' . home_url( '/' ),
			)
		);
		$body     = is_wp_error( $response ) ? '' : wp_remote_retrieve_body( $response );
		if ( 200 === wp_remote_retrieve_response_code( $response ) && false !== strpos( $body, 'BEGIN:VCALENDAR' ) ) {
			$cache[ $hash ] = cc_parse_ical_ranges( $body );
			++$result['ok'];
		} else {
			$cache[ $hash ] = isset( $previous[ $hash ] ) && is_array( $previous[ $hash ] ) ? $previous[ $hash ] : array();
			++$result['failed'];
		}
	}
	update_post_meta( $room_id, '_cc_ical_cache', $cache );
	update_post_meta( $room_id, '_cc_ical_blocks', array_merge( array(), ...array_values( $cache ) ) );
	update_post_meta( $room_id, '_cc_ical_synced', time() );
	update_post_meta( $room_id, '_cc_ical_failed', $result['failed'] );
	return $result;
}

/**
 * Hourly: sync every room with import calendars.
 */
function cc_sync_all_room_icals() {
	$rooms = get_posts(
		array(
			'post_type'      => 'cc_room',
			'post_status'    => array( 'publish', 'private', 'draft' ),
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	foreach ( $rooms as $room_id ) {
		if ( get_post_meta( $room_id, 'cc_ical_import', true ) ) {
			cc_sync_room_ical( $room_id );
		}
	}
}
add_action( 'cc_ical_sync', 'cc_sync_all_room_icals' );

/**
 * Keep the hourly sync scheduled.
 */
function cc_schedule_ical_sync() {
	if ( ! wp_next_scheduled( 'cc_ical_sync' ) ) {
		wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'hourly', 'cc_ical_sync' );
	}
}
add_action( 'init', 'cc_schedule_ical_sync' );

/**
 * Sync a room right after its import links are saved.
 *
 * @param int $room_id Room post ID.
 */
function cc_sync_room_on_save( $room_id ) {
	if ( wp_is_post_revision( $room_id ) || wp_is_post_autosave( $room_id ) ) {
		return;
	}
	wp_schedule_single_event( time(), 'cc_ical_sync_room', array( (int) $room_id ) );
}
add_action( 'save_post_cc_room', 'cc_sync_room_on_save' );
add_action( 'cc_ical_sync_room', 'cc_sync_room_ical' );

/**
 * Room edit screen: the export link and the last sync.
 */
function cc_room_ical_meta_box() {
	add_meta_box(
		'cc-room-ical',
		__( 'Calendar export', 'cobbleandcandle-core' ),
		static function ( $post ) {
			$synced = (int) get_post_meta( $post->ID, '_cc_ical_synced', true );
			$failed = (int) get_post_meta( $post->ID, '_cc_ical_failed', true );
			?>
			<p><?php esc_html_e( 'Paste this link into Airbnb, Booking.com or Vrbo (“import calendar”) so nights booked here are blocked there. Keep it private.', 'cobbleandcandle-core' ); ?></p>
			<input type="text" class="widefat" readonly value="<?php echo esc_attr( cc_room_ical_url( $post->ID ) ); ?>" onfocus="this.select()" aria-label="<?php esc_attr_e( 'Export calendar link', 'cobbleandcandle-core' ); ?>">
			<?php if ( $synced ) : ?>
				<p class="description">
					<?php
					/* translators: %s: time since the last sync, e.g. "5 mins" */
					echo esc_html( sprintf( __( 'Imported calendars last synced %s ago.', 'cobbleandcandle-core' ), human_time_diff( $synced ) ) );
					if ( $failed ) {
						echo ' ' . esc_html__( 'Some links could not be read; their last known nights stay blocked.', 'cobbleandcandle-core' );
					}
					?>
				</p>
			<?php endif; ?>
			<?php
		},
		'cc_room',
		'side',
		'low'
	);
}
add_action( 'add_meta_boxes_cc_room', 'cc_room_ical_meta_box' );
