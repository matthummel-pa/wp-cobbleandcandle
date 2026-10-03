<?php
/**
 * Demo content importer (setup wizard and `wp cobbleandcandle seed`): loads the demo locations, menus and events from data/demo.json.
 *
 * Safe to re-run: existing posts (matched by title) are skipped unless --update is passed.
 * Never deletes anything.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Seed demo content.
 *
 * ## OPTIONS
 *
 * [--update]
 * : Update fields on posts that already exist instead of skipping them.
 *
 * ## EXAMPLES
 *
 *     wp cobbleandcandle seed
 *     wp cobbleandcandle seed --update
 *
 * @param array<int, string>    $args       Positional args.
 * @param array<string, string> $assoc_args Flags.
 */
function cobble_cli_seed( $args, $assoc_args ) {
	$result = cobble_import_demo( ! empty( $assoc_args['update'] ) );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}
	foreach ( cobble_seed_warn() as $warning ) {
		WP_CLI::warning( $warning );
	}
	WP_CLI::success( sprintf( 'Demo content: %d created, %d updated, %d skipped.', $result['created'], $result['updated'], $result['skipped'] ) );
}

/**
 * Collect (or read back) importer warnings.
 *
 * @param string|null $message Warning to add.
 * @return array<int, string>
 */
function cobble_seed_warn( $message = null ) {
	static $warnings = array();
	if ( null !== $message ) {
		$warnings[] = $message;
		cobble_log( 'warning', 'import', $message );
	}
	return $warnings;
}

/**
 * Import the demo locations, menus, events and rooms from data/demo.json. Used by
 * `wp cobbleandcandle seed` and the setup wizard. Never deletes anything.
 *
 * @param bool $update Update posts that already exist instead of skipping them.
 * @return array{created: int, updated: int, skipped: int}|WP_Error
 */
function cobble_import_demo( $update = false ) {
	$data   = json_decode( (string) file_get_contents( COBBLE_CORE_DIR . 'data/demo.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin file.
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'cobble_demo_missing', __( 'The demo data file (data/demo.json) is missing or invalid.', 'cobbleandcandle-core' ) );
	}

	$counts       = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
	);
	$location_ids = array();

	foreach ( $data['locations'] as $order => $loc ) {
		$location_ids[] = cobble_seed_post(
			'cobble_location',
			$loc['name'],
			array( 'menu_order' => $order ),
			array(
				'cobble_street'        => $loc['street'],
				'cobble_locality'      => $loc['locality'],
				'cobble_phone'         => $loc['phone'],
				'cobble_email'         => $loc['email'],
				'cobble_hours'         => $loc['hours'],
				'cobble_holiday_hours' => $loc['holiday_hours'],
				'cobble_parking'       => $loc['parking'],
				'cobble_transit'       => $loc['transit'],
				'cobble_accessibility' => $loc['accessibility'],
				'cobble_booking_mode'  => $loc['booking_mode'],
				'cobble_price_range'   => $loc['price_range'],
			),
			$update,
			$counts
		);
	}

	foreach ( $data['menus'] as $menu_order => $menu ) {
		$menu_term = cobble_seed_term( 'cobble_menu', $menu['name'], sanitize_title( $menu['name'] ), $menu_order, $menu['intro'] );
		foreach ( $menu['sections'] as $section_order => $section ) {
			$section_term = cobble_seed_term( 'cobble_menu_section', $section['name'], sanitize_title( $menu['name'] . '-' . $section['name'] ), $section_order );
			foreach ( $section['items'] as $item_order => $item ) {
				$id = cobble_seed_post(
					'cobble_menu_item',
					$item['name'],
					array(
						'post_excerpt' => $item['desc'],
						'menu_order'   => $item_order,
					),
					array(
						'cobble_price'     => $item['price'],
						'cobble_variants'  => $item['variants'],
						'cobble_diet'      => $item['diet'],
						'cobble_flag'      => $item['flag'],
						'cobble_chef_pick' => $item['chef_pick'],
					),
					$update,
					$counts,
					// Same dish can appear on several menus: match on title + menu + section.
					array( $menu_term, $section_term )
				);
				if ( $id ) {
					wp_set_object_terms( $id, $menu_term, 'cobble_menu', true );
					wp_set_object_terms( $id, $section_term, 'cobble_menu_section', true );
				}
			}
		}
	}

	foreach ( $data['events'] as $event ) {
		$content = '';
		foreach ( (array) ( $event['content'] ?? array() ) as $paragraph ) {
			$content .= "<!-- wp:paragraph -->\n<p>" . esc_html( $paragraph ) . "</p>\n<!-- /wp:paragraph -->\n\n";
		}
		cobble_seed_post(
			'cobble_event',
			$event['title'],
			array(
				'post_excerpt' => $event['excerpt'],
				'post_content' => $content,
			),
			array(
				'cobble_start'        => $event['start'],
				'cobble_location'     => $location_ids[ $event['location'] ] ?? 0,
				'cobble_type'         => $event['type'],
				'cobble_price'        => $event['price'],
				'cobble_availability' => $event['availability'],
				'cobble_courses'      => $event['courses'] ?? array(),
			),
			$update,
			$counts
		);
	}

	foreach ( (array) ( $data['rooms'] ?? array() ) as $order => $room ) {
		$content = '';
		foreach ( (array) ( $room['content'] ?? array() ) as $paragraph ) {
			$content .= "<!-- wp:paragraph -->\n<p>" . esc_html( $paragraph ) . "</p>\n<!-- /wp:paragraph -->\n\n";
		}
		cobble_seed_post(
			'cobble_room',
			$room['title'],
			array(
				'post_excerpt' => $room['excerpt'],
				'post_content' => $content,
				'menu_order'   => $order,
			),
			array(
				'cobble_price_night'   => $room['price_night'],
				'cobble_price_weekend' => $room['price_weekend'],
				'cobble_min_nights'    => $room['min_nights'],
				'cobble_max_guests'    => $room['max_guests'],
				'cobble_units'         => $room['units'],
				'cobble_beds'          => $room['beds'],
				'cobble_size'          => $room['size'],
				'cobble_amenities'     => $room['amenities'],
				'cobble_location'      => $location_ids[ $room['location'] ] ?? 0,
			),
			$update,
			$counts
		);
	}

	// Demo brand details, only on a site that has none yet.
	if ( ! get_option( 'cobbleandcandle_brand' ) ) {
		update_option(
			'cobbleandcandle_brand',
			array(
				'tagline'     => 'Dining rooms',
				'est'         => '1888',
				'cuisine'     => 'Modern European',
				'price_range' => '$$$',
				'currency'    => 'USD',
			)
		);
	}

	return $counts;
}

/**
 * Create (or update) one post with meta, matched by title.
 *
 * @param string               $post_type Post type.
 * @param string               $title     Title.
 * @param array<string, mixed> $postarr   Extra post fields.
 * @param array<string, mixed> $meta      Meta to save.
 * @param bool                 $update    Update existing posts.
 * @param array<string, int>   $counts    Running counts.
 * @param array<int, int>      $term_ids  Optional terms the match must also have (menu items).
 * @return int Post ID.
 */
function cobble_seed_post( $post_type, $title, array $postarr, array $meta, $update, array &$counts, array $term_ids = array() ) {
	$existing = 0;
	$query    = array(
		'post_type'      => $post_type,
		'post_status'    => 'any',
		'title'          => $title,
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	);
	if ( $term_ids ) {
		$query['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- one-off CLI seed.
			'relation' => 'AND',
			array(
		'taxonomy' => 'cobble_menu',
		'terms'    => $term_ids[0],
		),
			array(
		'taxonomy' => 'cobble_menu_section',
		'terms'    => $term_ids[1],
		),
		);
	}
	$found = get_posts( $query );
	if ( $found ) {
		$existing = (int) $found[0];
		if ( ! $update ) {
			++$counts['skipped'];
			return $existing;
		}
	}

	$id = wp_insert_post(
		wp_slash(
			array_merge(
			array(
				'ID'          => $existing,
				'post_type'   => $post_type,
				'post_title'  => $title,
				'post_status' => 'publish',
			),
			$postarr
			)
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		cobble_seed_warn( $title . ': ' . $id->get_error_message() );
		return 0;
	}
	foreach ( $meta as $key => $value ) {
		update_post_meta( $id, $key, $value );
	}
	++$counts[ $existing ? 'updated' : 'created' ];
	return (int) $id;
}

/**
 * Get or create a term with an order (and optional intro) meta.
 *
 * @param string $taxonomy Taxonomy.
 * @param string $name     Term name.
 * @param string $slug     Term slug.
 * @param int    $order    Sort order.
 * @param string $intro    Optional intro (menus).
 * @return int Term ID.
 */
function cobble_seed_term( $taxonomy, $name, $slug, $order, $intro = '' ) {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term ) {
		$id = (int) $term->term_id;
	} else {
		$created = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
		if ( is_wp_error( $created ) ) {
			cobble_seed_warn( $name . ': ' . $created->get_error_message() );
			return 0;
		}
		$id = (int) $created['term_id'];
	}
	if ( $id ) {
		update_term_meta( $id, 'cobble_order', (int) $order );
		if ( '' !== $intro ) {
			update_term_meta( $id, 'cobble_intro', $intro );
		}
	}
	return $id;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'cobbleandcandle seed', 'cobble_cli_seed' );
}
