<?php
/**
 * Event extras: "Add to calendar" (.ics) downloads and Event structured data (HANDOFF §10).
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Start and end of an event as site-timezone dates. Without an end, events last three hours.
 *
 * @param int $event_id Event post ID.
 * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}|null
 */
function cobble_event_times( $event_id ) {
	$start = date_create_immutable( (string) get_post_meta( $event_id, 'cobble_start', true ), wp_timezone() );
	if ( ! $start || '' === (string) get_post_meta( $event_id, 'cobble_start', true ) ) {
		return null;
	}
	$end_meta = (string) get_post_meta( $event_id, 'cobble_end', true );
	$end      = '' !== $end_meta ? date_create_immutable( $end_meta, wp_timezone() ) : false;
	return array( $start, $end && $end > $start ? $end : $start->modify( '+3 hours' ) );
}

/**
 * Link that downloads an event as an .ics file.
 *
 * @param int $event_id Event post ID.
 * @return string
 */
function cobble_event_ics_url( $event_id ) {
	return add_query_arg( 'cobble_ics', '1', get_permalink( $event_id ) );
}

/**
 * Escape text for an iCalendar property value (RFC 5545 §3.3.11).
 *
 * @param string $text Text.
 * @return string
 */
function cobble_ics_text( $text ) {
	$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' );
	return str_replace( array( '\\', ';', ',', "\r\n", "\r", "\n" ), array( '\\\\', '\;', '\,', '\n', '\n', '\n' ), $text );
}

/**
 * Serve ?cobble_ics=1 on a single event as a calendar file.
 */
function cobble_serve_event_ics() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public, read-only download.
	// cc_ics: calendar links shared before the 1.0 prefix change keep working.
	if ( ( ! isset( $_GET['cobble_ics'] ) && ! isset( $_GET['cc_ics'] ) ) || ! is_singular( 'cobble_event' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public, read-only download.
		return;
	}
	$id    = get_queried_object_id();
	$times = cobble_event_times( $id );
	if ( ! $times ) {
		return;
	}
	$utc      = new \DateTimeZone( 'UTC' );
	$location = (int) get_post_meta( $id, 'cobble_location', true );
	$place    = $location && function_exists( 'cobble_location' ) ? cobble_location( $location ) : array();
	$where    = $place ? trim( $place['name'] . ', ' . $place['address'], ', ' ) : '';
	$lines    = array(
		'BEGIN:VCALENDAR',
		'VERSION:2.0',
		'PRODID:-//Cobble & Candle Core//EN',
		'CALSCALE:GREGORIAN',
		'BEGIN:VEVENT',
		'UID:event-' . $id . '@' . wp_parse_url( home_url(), PHP_URL_HOST ),
		'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
		'DTSTART:' . $times[0]->setTimezone( $utc )->format( 'Ymd\THis\Z' ),
		'DTEND:' . $times[1]->setTimezone( $utc )->format( 'Ymd\THis\Z' ),
		'SUMMARY:' . cobble_ics_text( get_the_title( $id ) ),
		'DESCRIPTION:' . cobble_ics_text( get_the_excerpt( $id ) . "\n\n" . get_permalink( $id ) ),
		'LOCATION:' . cobble_ics_text( $where ),
		'URL:' . get_permalink( $id ),
		'END:VEVENT',
		'END:VCALENDAR',
	);

	nocache_headers();
	header( 'X-Robots-Tag: noindex' );
	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( get_post_field( 'post_name', $id ) ) . '.ics"' );
	echo implode( "\r\n", $lines ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text/calendar body, values escaped by cobble_ics_text().
	exit;
}
add_action( 'template_redirect', 'cobble_serve_event_ics' );

/**
 * Schema.org Event for a single event page.
 *
 * @param int $event_id Event post ID.
 * @return array<string, mixed>
 */
function cobble_event_schema( $event_id ) {
	$times = cobble_event_times( $event_id );
	if ( ! $times ) {
		return array();
	}
	$schema = array(
		'@context'            => 'https://schema.org',
		'@type'               => 'Event',
		'name'                => cobble_plain_title( $event_id ),
		'description'         => wp_strip_all_tags( get_the_excerpt( $event_id ) ),
		'url'                 => get_permalink( $event_id ),
		'startDate'           => $times[0]->format( DATE_ATOM ),
		'endDate'             => $times[1]->format( DATE_ATOM ),
		'eventStatus'         => 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
		'organizer'           => array( '@id' => home_url( '/#organization' ) ),
	);
	$image  = get_the_post_thumbnail_url( $event_id, 'full' );
	if ( $image ) {
		$schema['image'] = array( $image );
	}
	$location = (int) get_post_meta( $event_id, 'cobble_location', true );
	if ( $location ) {
		$schema['location'] = array(
			'@type'   => 'Restaurant',
			'@id'     => get_permalink( $location ) . '#restaurant',
			'name'    => cobble_plain_title( $location ),
			'address' => array_filter(
				array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => (string) get_post_meta( $location, 'cobble_street', true ),
					'addressLocality' => (string) get_post_meta( $location, 'cobble_locality', true ),
					'addressRegion'   => (string) get_post_meta( $location, 'cobble_region', true ),
					'postalCode'      => (string) get_post_meta( $location, 'cobble_postcode', true ),
					'addressCountry'  => (string) get_post_meta( $location, 'cobble_country', true ),
				)
			),
		);
	}
	// Prices are free text ("$145 pp"); structured data needs the number.
	$price_text = (string) get_post_meta( $event_id, 'cobble_price', true );
	$free       = (bool) preg_match( '/\b(free|no cover)\b/i', $price_text );
	if ( $free || preg_match( '/\d+(?:[.,]\d{1,2})?/', $price_text, $price ) ) {
		$booking          = (string) get_post_meta( $event_id, 'cobble_booking_url', true );
		$schema['offers'] = array(
			'@type'         => 'Offer',
			'price'         => $free ? '0' : str_replace( ',', '.', $price[0] ),
			/** ISO 4217 currency for event prices. */
			'priceCurrency' => (string) apply_filters( 'cobble_currency', 'USD' ),
			'url'           => '' !== $booking ? $booking : get_permalink( $event_id ),
			'availability'  => cobble_event_availability( (string) get_post_meta( $event_id, 'cobble_availability', true ) ),
			'validFrom'     => get_the_date( DATE_ATOM, $event_id ),
		);
	}
	/**
	 * Filter an event's structured data.
	 *
	 * @param array<string, mixed> $schema   Schema.org Event.
	 * @param int                  $event_id Event post ID.
	 */
	return (array) apply_filters( 'cobble_event_schema', $schema, $event_id );
}

/**
 * Map the free-text availability ("Sold out", "Waitlist", "12 seats left") to schema.org.
 *
 * @param string $text Availability text.
 * @return string
 */
function cobble_event_availability( $text ) {
	if ( preg_match( '/sold\s*out|full/i', $text ) ) {
		return 'https://schema.org/SoldOut';
	}
	if ( preg_match( '/wait\s*list|few|left|last/i', $text ) ) {
		return 'https://schema.org/LimitedAvailability';
	}
	return 'https://schema.org/InStock';
}
