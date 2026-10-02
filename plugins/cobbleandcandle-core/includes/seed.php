<?php
/**
 * WP-CLI: `wp cobbleandcandle seed` loads the demo locations, menus and events from data/demo.json.
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
function cc_cli_seed( $args, $assoc_args ) {
	$update = ! empty( $assoc_args['update'] );
	$data   = json_decode( (string) file_get_contents( CC_CORE_DIR . 'data/demo.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin file.
	if ( ! is_array( $data ) ) {
		WP_CLI::error( 'data/demo.json is missing or invalid.' );
	}

	$counts       = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
	);
	$location_ids = array();

	foreach ( $data['locations'] as $order => $loc ) {
		$location_ids[] = cc_seed_post(
			'cc_location',
			$loc['name'],
			array( 'menu_order' => $order ),
			array(
				'cc_street'        => $loc['street'],
				'cc_locality'      => $loc['locality'],
				'cc_phone'         => $loc['phone'],
				'cc_email'         => $loc['email'],
				'cc_hours'         => $loc['hours'],
				'cc_holiday_hours' => $loc['holiday_hours'],
				'cc_parking'       => $loc['parking'],
				'cc_transit'       => $loc['transit'],
				'cc_accessibility' => $loc['accessibility'],
				'cc_booking_mode'  => $loc['booking_mode'],
				'cc_price_range'   => $loc['price_range'],
			),
			$update,
			$counts
		);
	}

	foreach ( $data['menus'] as $menu_order => $menu ) {
		$menu_term = cc_seed_term( 'cc_menu', $menu['name'], sanitize_title( $menu['name'] ), $menu_order, $menu['intro'] );
		foreach ( $menu['sections'] as $section_order => $section ) {
			$section_term = cc_seed_term( 'cc_menu_section', $section['name'], sanitize_title( $menu['name'] . '-' . $section['name'] ), $section_order );
			foreach ( $section['items'] as $item_order => $item ) {
				$id = cc_seed_post(
					'cc_menu_item',
					$item['name'],
					array(
						'post_excerpt' => $item['desc'],
						'menu_order'   => $item_order,
					),
					array(
						'cc_price'     => $item['price'],
						'cc_variants'  => $item['variants'],
						'cc_diet'      => $item['diet'],
						'cc_flag'      => $item['flag'],
						'cc_chef_pick' => $item['chef_pick'],
					),
					$update,
					$counts,
					// Same dish can appear on several menus: match on title + menu + section.
					array( $menu_term, $section_term )
				);
				if ( $id ) {
					wp_set_object_terms( $id, $menu_term, 'cc_menu', true );
					wp_set_object_terms( $id, $section_term, 'cc_menu_section', true );
				}
			}
		}
	}

	foreach ( $data['events'] as $event ) {
		$content = '';
		foreach ( (array) ( $event['content'] ?? array() ) as $paragraph ) {
			$content .= "<!-- wp:paragraph -->\n<p>" . esc_html( $paragraph ) . "</p>\n<!-- /wp:paragraph -->\n\n";
		}
		cc_seed_post(
			'cc_event',
			$event['title'],
			array(
				'post_excerpt' => $event['excerpt'],
				'post_content' => $content,
			),
			array(
				'cc_start'        => $event['start'],
				'cc_location'     => $location_ids[ $event['location'] ] ?? 0,
				'cc_type'         => $event['type'],
				'cc_price'        => $event['price'],
				'cc_availability' => $event['availability'],
				'cc_courses'      => $event['courses'] ?? array(),
			),
			$update,
			$counts
		);
	}

	WP_CLI::success( sprintf( 'Demo content: %d created, %d updated, %d skipped.', $counts['created'], $counts['updated'], $counts['skipped'] ) );
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
function cc_seed_post( $post_type, $title, array $postarr, array $meta, $update, array &$counts, array $term_ids = array() ) {
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
		'taxonomy' => 'cc_menu',
		'terms'    => $term_ids[0],
		),
			array(
		'taxonomy' => 'cc_menu_section',
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
		array_merge(
			array(
				'ID'          => $existing,
				'post_type'   => $post_type,
				'post_title'  => $title,
				'post_status' => 'publish',
			),
			$postarr
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $title . ': ' . $id->get_error_message() );
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
function cc_seed_term( $taxonomy, $name, $slug, $order, $intro = '' ) {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	$id   = $term ? (int) $term->term_id : (int) ( wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) )['term_id'] ?? 0 );
	if ( $id ) {
		update_term_meta( $id, 'cc_order', (int) $order );
		if ( '' !== $intro ) {
			update_term_meta( $id, 'cc_intro', $intro );
		}
	}
	return $id;
}

WP_CLI::add_command( 'cobbleandcandle seed', 'cc_cli_seed' );
