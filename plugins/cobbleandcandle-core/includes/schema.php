<?php
/**
 * Site structured data (HANDOFF §10): an Organization plus one Restaurant per location,
 * printed on the front page, the locations archive and a single location.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Brand settings shared with the theme (same option and filter as App\brand()).
 *
 * @return array{cuisine: string, price_range: string}
 */
function cc_brand_settings() {
	$saved = get_option( 'cobbleandcandle_brand', array() );
	/** This filter is documented in the theme, app/theme.php. */
	$brand = (array) apply_filters(
		'cobbleandcandle/brand',
		array_merge(
			array(
				'cuisine'     => 'Modern European',
				'price_range' => '$$$',
			),
			is_array( $saved ) ? $saved : array()
		)
	);

	return array(
		'cuisine'     => is_string( $brand['cuisine'] ?? null ) ? $brand['cuisine'] : '',
		'price_range' => is_string( $brand['price_range'] ?? null ) ? $brand['price_range'] : '',
	);
}

/**
 * The page guests book from: a page with the slug "reservations", else the home page.
 *
 * @return string
 */
function cc_reservations_url() {
	$page = get_page_by_path( 'reservations' );
	return $page ? (string) get_permalink( $page ) : home_url( '/' );
}

/**
 * Weekly hours as OpeningHoursSpecification entries, one per open day (closed days omitted).
 *
 * @param int $location_id Location post ID.
 * @return array<int, array<string, string>>
 */
function cc_opening_hours_schema( $location_id ) {
	$days = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
	$week = (array) get_post_meta( $location_id, 'cc_hours', true );
	$out  = array();

	foreach ( $days as $index => $day ) {
		$window = cc_day_window( $week[ $index ] ?? null );
		if ( ! $window ) {
			continue;
		}
		$out[] = array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => 'https://schema.org/' . $day,
			'opens'     => cc_minutes_to_time( $window[0] ),
			'closes'    => cc_minutes_to_time( $window[1] ),
		);
	}

	return $out;
}

/**
 * Schema.org Restaurant for one location.
 *
 * @param int|\WP_Post $location Location post or ID.
 * @return array<string, mixed>
 */
function cc_restaurant_schema( $location ) {
	$data = cc_location( $location );
	if ( ! $data ) {
		return array();
	}

	$brand   = cc_brand_settings();
	$address = array_filter(
		array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => (string) get_post_meta( $data['id'], 'cc_street', true ),
			'addressLocality' => (string) get_post_meta( $data['id'], 'cc_locality', true ),
			'addressRegion'   => (string) get_post_meta( $data['id'], 'cc_region', true ),
			'postalCode'      => (string) get_post_meta( $data['id'], 'cc_postcode', true ),
			'addressCountry'  => (string) get_post_meta( $data['id'], 'cc_country', true ),
		)
	);

	$schema = array(
		'@type'               => 'Restaurant',
		'@id'                 => $data['url'] . '#restaurant',
		'name'                => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . ' — ' . html_entity_decode( $data['name'], ENT_QUOTES, 'UTF-8' ),
		'url'                 => $data['url'],
		'parentOrganization'  => array( '@id' => home_url( '/#org' ) ),
		'acceptsReservations' => cc_reservations_url(),
	);

	if ( '' !== $data['phone'] ) {
		$schema['telephone'] = $data['phone'];
	}
	if ( count( $address ) > 1 ) {
		$schema['address'] = $address;
	}
	if ( '' !== $brand['cuisine'] ) {
		$schema['servesCuisine'] = array( $brand['cuisine'] );
	}
	if ( '' !== $brand['price_range'] ) {
		$schema['priceRange'] = $brand['price_range'];
	}
	$hours = cc_opening_hours_schema( $data['id'] );
	if ( $hours ) {
		$schema['openingHoursSpecification'] = $hours;
	}
	$image = get_the_post_thumbnail_url( $data['id'], 'full' );
	if ( $image ) {
		$schema['image'] = array( $image );
	}

	return $schema;
}

/**
 * The site-wide graph: the Organization, then every published location.
 *
 * @return array<string, mixed>
 */
function cc_site_schema() {
	$organization = array(
		'@type' => 'Organization',
		'@id'   => home_url( '/#org' ),
		'name'  => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		'url'   => home_url( '/' ),
	);

	$tagline = wp_specialchars_decode( get_bloginfo( 'description' ), ENT_QUOTES );
	if ( '' !== $tagline ) {
		$organization['description'] = $tagline;
	}
	if ( has_custom_logo() ) {
		$logo = wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'full' );
		if ( $logo ) {
			$organization['logo'] = $logo;
		}
	}

	$graph = array( $organization );
	foreach ( cc_get_locations() as $location ) {
		$restaurant = cc_restaurant_schema( $location );
		if ( $restaurant ) {
			$graph[] = $restaurant;
		}
	}

	/**
	 * Filter the site-wide structured data graph.
	 *
	 * @param array<int, array<string, mixed>> $graph Schema.org nodes, Organization first.
	 */
	$graph = (array) apply_filters( 'cc_site_schema', $graph );

	return array(
		'@context' => 'https://schema.org',
		'@graph'   => array_values( $graph ),
	);
}

/**
 * Print the site graph as JSON-LD.
 */
function cc_print_site_schema() {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) ) {
		return; // Yoast, Rank Math, and SEOPress print their own Organization graph.
	}
	// Single events are covered by cc_print_event_schema(), so they are not in this list.
	if ( ! is_front_page() && ! is_post_type_archive( 'cc_location' ) && ! is_singular( 'cc_location' ) ) {
		return;
	}

	$schema = cc_site_schema();
	if ( empty( $schema['@graph'] ) ) {
		return;
	}

	wp_print_inline_script_tag( (string) wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ), array( 'type' => 'application/ld+json' ) );
}
add_action( 'wp_head', 'cc_print_site_schema' );
