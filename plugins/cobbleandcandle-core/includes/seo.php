<?php
/**
 * Search and sharing: one connected schema.org graph (Organization → WebSite → Restaurant per
 * location → Menu, Event, BreadcrumbList), plus meta description, Open Graph, Twitter and archive
 * canonicals when no SEO plugin is active. With Yoast SEO or Rank Math, the restaurant pieces join
 * their graph instead and their own meta is left alone.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a full SEO plugin handles titles, meta and the base graph.
 *
 * @return bool
 */
function cc_seo_plugin_active() {
	$active = defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' )
		|| defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) || defined( 'SLIM_SEO_VER' );
	/**
	 * Treat an SEO plugin as active (skips the fallback meta tags).
	 *
	 * @param bool $active Detected.
	 */
	return (bool) apply_filters( 'cc_seo_plugin_active', $active );
}

/**
 * Stable node IDs (the Organization/WebSite IDs match Yoast and Rank Math).
 *
 * @param string $type   organization | website | restaurant | menu.
 * @param int    $post_id Post for restaurant/menu IDs.
 * @return string
 */
function cc_schema_id( $type, $post_id = 0 ) {
	if ( 'organization' === $type || 'website' === $type ) {
		return home_url( '/#' . $type );
	}
	return get_permalink( $post_id ) . '#' . $type;
}

/**
 * The brand logo URL: Settings → Restaurant, the Site Logo, then the Site Icon.
 *
 * @return string
 */
function cc_schema_logo_url() {
	$logo = (int) cc_setting( 'logo_id' ) ? (int) cc_setting( 'logo_id' ) : (int) get_theme_mod( 'custom_logo' );
	$url  = $logo ? wp_get_attachment_image_url( $logo, 'full' ) : '';
	return $url ? $url : (string) get_site_icon_url( 512 );
}

/**
 * Organization node.
 *
 * @return array<string, mixed>
 */
function cc_schema_organization() {
	return array_filter(
		array(
			'@type'  => 'Organization',
			'@id'    => cc_schema_id( 'organization' ),
			'name'   => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'url'    => home_url( '/' ),
			'logo'   => cc_schema_logo_url(),
			'sameAs' => array_values( cc_social_profiles() ),
		)
	);
}

/**
 * WebSite node.
 *
 * @return array<string, mixed>
 */
function cc_schema_website() {
	return array(
		'@type'     => 'WebSite',
		'@id'       => cc_schema_id( 'website' ),
		'url'       => home_url( '/' ),
		'name'      => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		'publisher' => array( '@id' => cc_schema_id( 'organization' ) ),
	);
}

/**
 * "HH:MM" for schema times (a past-midnight close wraps, e.g. 25:00 → 01:00).
 *
 * @param int $minutes Minutes since midnight.
 * @return string
 */
function cc_schema_time( $minutes ) {
	return cc_minutes_to_time( $minutes );
}

/**
 * OpeningHoursSpecification list: identical weekly windows grouped, closed days omitted,
 * holidays as dated overrides.
 *
 * @param int $location_id Location post ID.
 * @return array<int, array<string, mixed>>
 */
function cc_schema_opening_hours( $location_id ) {
	$days    = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
	$windows = cc_status_windows( $location_id );
	$groups  = array();
	foreach ( $windows['week'] as $i => $window ) {
		if ( ! $window ) {
			continue;
		}
		$key = $window[0] . '-' . $window[1];
		if ( ! isset( $groups[ $key ] ) ) {
			$groups[ $key ] = array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => array(),
				'opens'     => cc_schema_time( $window[0] ),
				'closes'    => cc_schema_time( $window[1] ),
			);
		}
		$groups[ $key ]['dayOfWeek'][] = $days[ $i ];
	}
	$specs = array_values( $groups );
	foreach ( (array) $windows['holidays'] as $date => $window ) {
		if ( $date < wp_date( 'Y-m-d' ) ) {
			continue;
		}
		$specs[] = array(
			'@type'        => 'OpeningHoursSpecification',
			'validFrom'    => $date,
			'validThrough' => $date,
			'opens'        => $window ? cc_schema_time( $window[0] ) : '00:00',
			'closes'       => $window ? cc_schema_time( $window[1] ) : '00:00',
		);
	}
	return $specs;
}

/**
 * Restaurant node for one location.
 *
 * @param int $location_id Location post ID.
 * @return array<string, mixed>
 */
function cc_schema_restaurant( $location_id ) {
	$l = cc_location( $location_id );
	if ( ! $l ) {
		return array();
	}
	$meta    = static function ( $key ) use ( $location_id ) {
		return (string) get_post_meta( $location_id, $key, true );
	};
	$lat     = $meta( 'cc_lat' );
	$lng     = $meta( 'cc_lng' );
	$image   = get_the_post_thumbnail_url( $location_id, 'full' );
	$cuisine = array_filter( array_map( 'trim', explode( ',', cc_setting( 'cuisine' ) ) ) );
	$booking = '' !== $l['booking_url'] ? $l['booking_url'] : '';

	$node      = array(
		'@type'                     => 'Restaurant',
		'@id'                       => cc_schema_id( 'restaurant', $location_id ),
		'name'                      => $l['name'],
		'url'                       => $l['url'],
		'image'                     => $image ? array( $image ) : null,
		'telephone'                 => $l['phone'],
		'email'                     => $l['email'],
		'address'                   => array_filter(
			array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $l['street'],
				'addressLocality' => $l['locality'],
				'addressRegion'   => $meta( 'cc_region' ),
				'postalCode'      => $meta( 'cc_postcode' ),
				'addressCountry'  => $meta( 'cc_country' ),
			)
		),
		'geo'                       => '' !== $lat && '' !== $lng ? array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $lat,
			'longitude' => (float) $lng,
		) : null,
		'servesCuisine'             => $cuisine ? array_values( $cuisine ) : null,
		'priceRange'                => '' !== $meta( 'cc_price_range' ) ? $meta( 'cc_price_range' ) : cc_setting( 'price_range' ),
		'acceptsReservations'       => '' !== $booking ? $booking : true,
		'openingHoursSpecification' => cc_schema_opening_hours( $location_id ),
		'parentOrganization'        => array( '@id' => cc_schema_id( 'organization' ) ),
	);
	$menu_page = cc_menu_page_id();
	if ( $menu_page ) {
		$node['hasMenu'] = add_query_arg( 'loc', $l['slug'], get_permalink( $menu_page ) );
	}
	if ( '' !== $l['order_url'] ) {
		$node['potentialAction'] = array(
			'@type'  => 'OrderAction',
			'target' => $l['order_url'],
		);
	}
	/**
	 * Filter one location's Restaurant node.
	 *
	 * @param array<string, mixed> $node        Restaurant.
	 * @param int                  $location_id Location post ID.
	 */
	return (array) apply_filters( 'cc_schema_restaurant', array_filter( $node, static fn( $v ) => null !== $v && '' !== $v && array() !== $v ), $location_id );
}

/**
 * The page that shows the full menu (the first published page with the Full Menu block).
 *
 * @return int
 */
function cc_menu_page_id() {
	static $id = null;
	if ( null === $id ) {
		$found = get_posts(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				's'           => 'wp:cobbleandcandle/full-menu',
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);
		/**
		 * The menu page ID used for Restaurant hasMenu links.
		 *
		 * @param int $id Detected page ID (0 when none).
		 */
		$id = (int) apply_filters( 'cc_menu_page_id', $found ? (int) $found[0] : 0 );
	}
	return $id;
}

/**
 * Menu nodes (one per menu) for the page that shows them.
 *
 * @param int $page_id Menu page ID.
 * @return array<int, array<string, mixed>>
 */
function cc_schema_menus( $page_id ) {
	$diets    = array(
		'v'  => 'https://schema.org/VegetarianDiet',
		'vg' => 'https://schema.org/VeganDiet',
		'gf' => 'https://schema.org/GlutenFreeDiet',
	);
	$currency = (string) apply_filters( 'cc_currency', 'USD' );
	$price    = static function ( $text, $name = '' ) use ( $currency ) {
		if ( ! preg_match( '/\d+(?:[.,]\d{1,2})?/', (string) $text, $m ) ) {
			return null;
		}
		return array_filter(
			array(
				'@type'         => 'Offer',
				'name'          => $name,
				'price'         => str_replace( ',', '.', $m[0] ),
				'priceCurrency' => $currency,
			)
		);
	};
	$nodes    = array();
	foreach ( cc_get_menus() as $menu ) {
		$sections = array();
		foreach ( $menu['sections'] as $section ) {
			$items = array();
			foreach ( $section['items'] as $item ) {
				$offers = array();
				if ( '' !== $item['price'] ) {
					$offers[] = $price( $item['price'] );
				}
				foreach ( $item['variants'] as $variant ) {
					if ( is_array( $variant ) ) {
						$offers[] = $price( $variant['price'] ?? '', $variant['label'] ?? '' );
					}
				}
				$diet    = array_values( array_filter( array_map( static fn( $d ) => $diets[ $d ] ?? null, $item['diet'] ) ) );
				$items[] = array_filter(
					array(
						'@type'           => 'MenuItem',
						'name'            => $item['name'],
						'description'     => $item['desc'],
						'offers'          => array_values( array_filter( $offers ) ),
						'suitableForDiet' => $diet,
					)
				);
			}
			$sections[] = array(
				'@type'       => 'MenuSection',
				'name'        => html_entity_decode( $section['term']->name, ENT_QUOTES, 'UTF-8' ),
				'hasMenuItem' => $items,
			);
		}
		$nodes[] = array_filter(
			array(
				'@type'          => 'Menu',
				'@id'            => get_permalink( $page_id ) . '#menu-' . $menu['term']->slug,
				'name'           => html_entity_decode( $menu['term']->name, ENT_QUOTES, 'UTF-8' ),
				'description'    => $menu['intro'],
				'url'            => get_permalink( $page_id ),
				'hasMenuSection' => $sections,
			)
		);
	}
	return $nodes;
}

/**
 * BreadcrumbList from the theme's trail (filter `cc_breadcrumb_trail`: list of [label, url]).
 *
 * @return array<string, mixed>
 */
function cc_schema_breadcrumbs() {
	$trail = is_front_page() ? array() : (array) apply_filters( 'cc_breadcrumb_trail', array() );
	if ( count( $trail ) < 2 ) {
		return array();
	}
	$items = array();
	foreach ( array_values( $trail ) as $i => $crumb ) {
		$items[] = array_filter(
			array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => (string) ( $crumb[0] ?? '' ),
				'item'     => '' !== ( $crumb[1] ?? '' ) ? $crumb[1] : ( is_singular() ? get_permalink() : '' ),
			)
		);
	}
	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => home_url( add_query_arg( array() ) ) . '#breadcrumb',
		'itemListElement' => $items,
	);
}

/**
 * The restaurant-specific nodes for the current request (no Organization/WebSite/Breadcrumb).
 *
 * @return array<int, array<string, mixed>>
 */
function cc_schema_page_nodes() {
	$nodes = array();
	if ( is_singular( 'cc_location' ) ) {
		$nodes[] = cc_schema_restaurant( get_queried_object_id() );
	} elseif ( is_post_type_archive( 'cc_location' ) || is_front_page() ) {
		foreach ( cc_get_locations() as $location ) {
			$nodes[] = cc_schema_restaurant( $location->ID );
		}
	}
	if ( is_singular( 'cc_event' ) && function_exists( 'cc_event_schema' ) ) {
		$event = cc_event_schema( get_queried_object_id() );
		unset( $event['@context'] );
		$nodes[] = $event;
	}
	if ( is_singular( 'cc_room' ) && function_exists( 'cc_schema_room' ) ) {
		$nodes[] = cc_schema_room( get_queried_object_id() );
	}
	if ( is_page() && has_block( 'cobbleandcandle/full-menu', get_queried_object() ) ) {
		$nodes = array_merge( $nodes, cc_schema_menus( get_queried_object_id() ) );
	}
	/**
	 * Filter the restaurant nodes added to the page's graph.
	 *
	 * @param array<int, array<string, mixed>> $nodes Nodes.
	 */
	return array_values( array_filter( (array) apply_filters( 'cc_schema_nodes', $nodes ) ) );
}

/**
 * Print the graph when no SEO plugin provides one.
 */
function cc_print_schema() {
	if ( is_admin() || is_feed() || ! apply_filters( 'cc_schema_enabled', true ) ) {
		return;
	}
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return; // Added to their graph below.
	}
	$graph = array_merge( array( cc_schema_organization(), cc_schema_website() ), cc_schema_page_nodes() );
	$crumb = cc_schema_breadcrumbs();
	if ( $crumb ) {
		$graph[] = $crumb;
	}
	wp_print_inline_script_tag(
		(string) wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => array_values( array_filter( $graph ) ),
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
		),
		array( 'type' => 'application/ld+json' )
	);
}
add_action( 'wp_head', 'cc_print_schema', 20 );

/**
 * Yoast SEO: add the restaurant nodes to its graph.
 *
 * @param array<int, array<string, mixed>> $graph Yoast pieces.
 * @return array<int, array<string, mixed>>
 */
function cc_yoast_schema_graph( $graph ) {
	return apply_filters( 'cc_schema_enabled', true ) ? array_merge( (array) $graph, cc_schema_page_nodes() ) : $graph;
}
add_filter( 'wpseo_schema_graph', 'cc_yoast_schema_graph' );

/**
 * Rank Math: add the restaurant nodes to its JSON-LD.
 *
 * @param array<string, mixed> $data Rank Math entities.
 * @return array<string, mixed>
 */
function cc_rank_math_json_ld( $data ) {
	if ( ! apply_filters( 'cc_schema_enabled', true ) ) {
		return $data;
	}
	foreach ( cc_schema_page_nodes() as $i => $node ) {
		$data[ 'cobbleandcandle-' . $i ] = $node;
	}
	return $data;
}
add_filter( 'rank_math/json_ld', 'cc_rank_math_json_ld', 99 );

/**
 * Meta description for the current request (≤ 155 characters), or ''.
 *
 * @return string
 */
function cc_meta_description() {
	$text = '';
	if ( is_front_page() ) {
		$text = get_bloginfo( 'description' );
	} elseif ( is_singular( 'cc_location' ) ) {
		$l     = cc_location( get_queried_object_id() );
		$parts = array_filter( array( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . ' ' . $l['name'], cc_setting( 'cuisine' ), $l['address'] ) );
		$text  = has_excerpt( get_queried_object_id() ) ? get_the_excerpt( get_queried_object_id() ) : implode( ' · ', $parts );
	} elseif ( is_singular( 'cc_event' ) ) {
		$event = cc_event( get_queried_object_id() );
		$text  = trim( $event['when'] . '. ' . $event['excerpt'] );
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		$text = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 30, '' );
	} elseif ( is_post_type_archive( 'cc_location' ) ) {
		/* translators: %s: site name */
		$text = sprintf( __( 'Find %s: addresses, opening hours, holiday hours and directions for every location.', 'cobbleandcandle-core' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	} elseif ( is_post_type_archive( 'cc_event' ) ) {
		/* translators: %s: site name */
		$text = sprintf( __( 'What’s on at %s: upcoming suppers, tastings, live music and holiday nights.', 'cobbleandcandle-core' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	} elseif ( is_archive() ) {
		$text = get_the_archive_description();
	}
	/**
	 * Filter the fallback meta description.
	 *
	 * @param string $text Description (plain text).
	 */
	$text = (string) apply_filters( 'cc_meta_description', $text );
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $text ) ) );
	return '' !== $text ? wp_html_excerpt( $text, 155, '…' ) : '';
}

/**
 * Share image: the featured image, else the logo / Site Icon.
 *
 * @return string
 */
function cc_share_image() {
	if ( is_singular() && has_post_thumbnail( get_queried_object_id() ) ) {
		return (string) get_the_post_thumbnail_url( get_queried_object_id(), 'large' );
	}
	return cc_schema_logo_url();
}

/**
 * Fallback meta, Open Graph, Twitter and archive canonical tags (skipped when an SEO plugin runs).
 */
function cc_print_meta_tags() {
	if ( is_admin() || is_feed() || cc_seo_plugin_active() ) {
		return;
	}
	$description = cc_meta_description();
	$title       = wp_get_document_title();
	$url         = is_singular() ? get_permalink() : ( is_post_type_archive() ? get_post_type_archive_link( (string) get_query_var( 'post_type' ) ) : home_url( add_query_arg( array() ) ) );
	$image       = cc_share_image();

	if ( is_post_type_archive( array( 'cc_location', 'cc_event' ) ) && $url ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
	}
	if ( '' !== $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	}
	$og = array_filter(
		array(
			'og:type'        => is_singular( array( 'post', 'cc_event' ) ) ? 'article' : 'website',
			'og:site_name'   => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'og:title'       => $title,
			'og:description' => $description,
			'og:url'         => $url,
			'og:image'       => $image,
			'og:locale'      => str_replace( '-', '_', get_bloginfo( 'language' ) ),
		)
	);
	foreach ( $og as $property => $content ) {
		printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $property ), 'og:url' === $property || 'og:image' === $property ? esc_url( $content ) : esc_attr( $content ) );
	}
	printf( '<meta name="twitter:card" content="%s">' . "\n", $image ? 'summary_large_image' : 'summary' );
}
add_action( 'wp_head', 'cc_print_meta_tags', 5 );

/**
 * Page excerpts double as meta descriptions and page-header intros.
 */
function cc_page_excerpts() {
	add_post_type_support( 'page', 'excerpt' );
}
add_action( 'init', 'cc_page_excerpts' );

/**
 * Without an SEO plugin, keep author archives out of the core sitemap (thin pages on a restaurant site).
 *
 * @param WP_Sitemaps_Provider|false $provider Provider.
 * @param string                     $name     Provider name.
 * @return WP_Sitemaps_Provider|false
 */
function cc_sitemap_providers( $provider, $name ) {
	return 'users' === $name && ! cc_seo_plugin_active() ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'cc_sitemap_providers', 10, 2 );
