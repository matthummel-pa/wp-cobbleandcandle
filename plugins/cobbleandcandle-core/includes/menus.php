<?php
/**
 * Menu and event queries for themes: menus with sections and items, chef's picks, upcoming events.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Terms of a taxonomy ordered by the cc_order term meta, then name.
 *
 * @param string               $taxonomy Taxonomy.
 * @param array<string, mixed> $args     Extra get_terms() args.
 * @return array<int, \WP_Term>
 */
function cc_ordered_terms( $taxonomy, array $args = array() ) {
	$terms = get_terms(
		array_merge(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			),
			$args
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	usort(
		$terms,
		static function ( $a, $b ) {
			return array( (int) get_term_meta( $a->term_id, 'cc_order', true ), $a->name ) <=> array( (int) get_term_meta( $b->term_id, 'cc_order', true ), $b->name );
		}
	);
	return $terms;
}

/**
 * Display data for one menu item (strings raw; escape on output).
 *
 * @param int|\WP_Post $item Menu item post or ID.
 * @return array<string, mixed>
 */
function cc_menu_item( $item ) {
	$post = get_post( $item );
	if ( ! $post ) {
		return array();
	}
	return array(
		'id'        => $post->ID,
		'name'      => cc_plain_title( $post ),
		'desc'      => $post->post_excerpt,
		'price'     => (string) get_post_meta( $post->ID, 'cc_price', true ),
		'variants'  => (array) get_post_meta( $post->ID, 'cc_variants', true ),
		'diet'      => (array) get_post_meta( $post->ID, 'cc_diet', true ),
		'flag'      => (string) get_post_meta( $post->ID, 'cc_flag', true ),
		'image_id'  => (int) get_post_thumbnail_id( $post ),
		'locations' => (array) get_post_meta( $post->ID, 'cc_available_at', true ),
	);
}

/**
 * Items for a menu (and optional section), in menu order.
 *
 * @param int      $menu_id    cc_menu term ID.
 * @param int|null $section_id cc_menu_section term ID.
 * @param int      $limit      Max items (-1 for all).
 * @return array<int, array<string, mixed>>
 */
function cc_menu_items( $menu_id, $section_id = null, $limit = -1 ) {
	$tax = array(
		array(
			'taxonomy' => 'cc_menu',
			'terms'    => (int) $menu_id,
		),
	);
	if ( $section_id ) {
		$tax[] = array(
			'taxonomy' => 'cc_menu_section',
			'terms'    => (int) $section_id,
		);
	}
	$posts = get_posts(
		array(
			'post_type'      => 'cc_menu_item',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
			),
			'tax_query'      => array_merge( array( 'relation' => 'AND' ), $tax ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- small, cached menu lists.
			'no_found_rows'  => true,
		)
	);
	return array_map( 'cc_menu_item', $posts );
}

/**
 * All menus with their sections and items: [{term, intro, sections: [{term, items}]}].
 *
 * @return array<int, array<string, mixed>>
 */
function cc_get_menus() {
	$out = array();
	foreach ( cc_ordered_terms( 'cc_menu' ) as $menu ) {
		$sections = array();
		foreach ( cc_ordered_terms( 'cc_menu_section' ) as $section ) {
			$items = cc_menu_items( $menu->term_id, $section->term_id );
			if ( $items ) {
				$sections[] = array(
					'term'  => $section,
					'items' => $items,
				);
			}
		}
		$out[] = array(
			'term'     => $menu,
			'intro'    => (string) get_term_meta( $menu->term_id, 'cc_intro', true ),
			'sections' => $sections,
		);
	}
	return $out;
}

/**
 * Menu items flagged as Chef's picks.
 *
 * @param int $limit Max items.
 * @return array<int, array<string, mixed>>
 */
function cc_chef_picks( $limit = 3 ) {
	$posts = get_posts(
		array(
			'post_type'      => 'cc_menu_item',
			'post_status'    => 'publish',
			'posts_per_page' => $limit * 3, // Over-fetch: duplicates across menus are dropped below.
			'meta_key'       => 'cc_chef_pick', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- small post type.
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'orderby'        => array(
		'menu_order' => 'ASC',
		'date'       => 'ASC',
		),
			'no_found_rows'  => true,
		)
	);
	$seen  = array();
	$out   = array();
	foreach ( $posts as $post ) {
		$key = strtolower( $post->post_title ); // The same dish can sit on several menus.
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$out[]        = cc_menu_item( $post );
	}
	return array_slice( $out, 0, $limit );
}

/**
 * Display data for one event.
 *
 * @param int|\WP_Post $event Event post or ID.
 * @return array<string, mixed>
 */
function cc_event( $event ) {
	$post = get_post( $event );
	if ( ! $post ) {
		return array();
	}
	$start    = (string) get_post_meta( $post->ID, 'cc_start', true );
	$time     = $start ? date_create_immutable( $start, wp_timezone() ) : false;
	$location = (int) get_post_meta( $post->ID, 'cc_location', true );
	return array(
		'id'           => $post->ID,
		'title'        => cc_plain_title( $post ),
		'url'          => get_permalink( $post ),
		'excerpt'      => $post->post_excerpt,
		'image_id'     => (int) get_post_thumbnail_id( $post ),
		'type'         => (string) get_post_meta( $post->ID, 'cc_type', true ),
		'start'        => $start,
		'iso'          => $time ? $time->format( DATE_ATOM ) : '',
		'weekday'      => $time ? wp_date( 'D', $time->getTimestamp() ) : '',
		'day'          => $time ? wp_date( 'j', $time->getTimestamp() ) : '',
		'month'        => $time ? wp_date( 'M', $time->getTimestamp() ) : '',
		'when'         => $time ? wp_date( 'D j M · g:i a', $time->getTimestamp() ) : '',
		'location'     => $location ? cc_plain_title( $location ) : '',
		'price'        => (string) get_post_meta( $post->ID, 'cc_price', true ),
		'availability' => (string) get_post_meta( $post->ID, 'cc_availability', true ),
		'location_id'  => $location,
		'end'          => (string) get_post_meta( $post->ID, 'cc_end', true ),
		'booking_url'  => (string) get_post_meta( $post->ID, 'cc_booking_url', true ),
		'courses'      => array_values( array_filter( (array) get_post_meta( $post->ID, 'cc_courses', true ), 'is_array' ) ),
	);
}

/**
 * Upcoming events (start today or later), soonest first.
 *
 * @param int $limit Max events.
 * @return array<int, array<string, mixed>>
 */
function cc_upcoming_events( $limit = 3 ) {
	$today = wp_date( 'Y-m-d\T00:00' );
	$posts = get_posts(
		array(
			'post_type'      => 'cc_event',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_key'       => 'cc_start', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- small post type.
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'cc_start',
					'value'   => $today,
					'compare' => '>=',
				),
			),
			'no_found_rows'  => true,
		)
	);
	return array_map( 'cc_event', $posts );
}
