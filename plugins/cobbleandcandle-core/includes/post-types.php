<?php
/**
 * Content types: locations, menu items, events; taxonomies: menus and menu sections.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * A post title as plain text. get_the_title() returns texturized HTML ("&#038;", "&#8217;"),
 * which templates that escape on output would encode a second time.
 *
 * @param int|\WP_Post $post Post or ID.
 * @return string
 */
function cc_plain_title( $post ) {
	return html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}

/**
 * Register post types and taxonomies.
 */
function cc_register_content_types() {
	register_post_type(
		'cc_location',
		array(
			'labels'        => array(
				'name'          => __( 'Locations', 'cobbleandcandle-core' ),
				'singular_name' => __( 'Location', 'cobbleandcandle-core' ),
				'add_new_item'  => __( 'Add location', 'cobbleandcandle-core' ),
				'edit_item'     => __( 'Edit location', 'cobbleandcandle-core' ),
				'all_items'     => __( 'All locations', 'cobbleandcandle-core' ),
			),
			'public'        => true,
			'has_archive'   => 'locations',
			'rewrite'       => array(
				'slug'       => 'locations',
				'with_front' => false,
			),
			'menu_icon'     => 'dashicons-location',
			'menu_position' => 21,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes', 'revisions' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'cc_menu_item',
		array(
			'labels'        => array(
				'name'          => __( 'Menu items', 'cobbleandcandle-core' ),
				'singular_name' => __( 'Menu item', 'cobbleandcandle-core' ),
				'add_new_item'  => __( 'Add menu item', 'cobbleandcandle-core' ),
				'edit_item'     => __( 'Edit menu item', 'cobbleandcandle-core' ),
				'all_items'     => __( 'All menu items', 'cobbleandcandle-core' ),
				'menu_name'     => __( 'Food & drink', 'cobbleandcandle-core' ),
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'menu_icon'     => 'dashicons-carrot',
			'menu_position' => 22,
			'supports'      => array( 'title', 'excerpt', 'thumbnail', 'custom-fields', 'page-attributes', 'revisions' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'cc_event',
		array(
			'labels'        => array(
				'name'          => __( 'Events', 'cobbleandcandle-core' ),
				'singular_name' => __( 'Event', 'cobbleandcandle-core' ),
				'add_new_item'  => __( 'Add event', 'cobbleandcandle-core' ),
				'edit_item'     => __( 'Edit event', 'cobbleandcandle-core' ),
				'all_items'     => __( 'All events', 'cobbleandcandle-core' ),
			),
			'public'        => true,
			'has_archive'   => 'events',
			'rewrite'       => array(
				'slug'       => 'events',
				'with_front' => false,
			),
			'menu_icon'     => 'dashicons-calendar-alt',
			'menu_position' => 23,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
			'show_in_rest'  => true,
		)
	);

	register_taxonomy(
		'cc_menu',
		'cc_menu_item',
		array(
			'labels'            => array(
				'name'          => __( 'Menus', 'cobbleandcandle-core' ),
				'singular_name' => __( 'Menu', 'cobbleandcandle-core' ),
				'add_new_item'  => __( 'Add menu (e.g. Dinner, Bar, Wine)', 'cobbleandcandle-core' ),
			),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		)
	);

	register_taxonomy(
		'cc_menu_section',
		'cc_menu_item',
		array(
			'labels'            => array(
				'name'          => __( 'Menu sections', 'cobbleandcandle-core' ),
				'singular_name' => __( 'Menu section', 'cobbleandcandle-core' ),
				'add_new_item'  => __( 'Add section (e.g. To begin, Mains)', 'cobbleandcandle-core' ),
			),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		)
	);

	// Gallery filter chips (Rooms, Plates, …) come from this taxonomy on media items.
	register_taxonomy(
		'cc_gallery',
		'attachment',
		array(
			'labels'                => array(
				'name'          => __( 'Gallery categories', 'cobbleandcandle-core' ),
				'singular_name' => __( 'Gallery category', 'cobbleandcandle-core' ),
				'add_new_item'  => __( 'Add gallery category (e.g. Rooms, Plates)', 'cobbleandcandle-core' ),
			),
			'hierarchical'          => true,
			'public'                => false,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'show_in_rest'          => true,
			'update_count_callback' => '_update_generic_term_count',
		)
	);

	// Sections and menus are ordered by an integer term meta.
	foreach ( array( 'cc_menu', 'cc_menu_section' ) as $taxonomy ) {
		register_term_meta(
			$taxonomy,
			'cc_order',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			)
		);
	}
	register_term_meta(
		'cc_menu',
		'cc_intro',
		array(
			'type'              => 'string',
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
		)
	);
}
add_action( 'init', 'cc_register_content_types' );
