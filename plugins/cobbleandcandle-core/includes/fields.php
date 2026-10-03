<?php
/**
 * Custom fields: one schema per post type. It registers REST-enabled post meta (with
 * sanitizing) and drives the block editor sidebar panels in assets/editor-fields.js.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field schema, grouped into sidebar panels.
 *
 * Field types: text, textarea, url, email, tel, number, boolean, select, datetime,
 * date, location (post ID), locations (post IDs), hours (7 days), list (repeater).
 *
 * @return array<string, array<int, array{title: string, fields: array<string, array<string, mixed>>}>>
 */
function cobble_field_schema() {
	$booking_modes = array(
		'native'    => __( 'Booking form on this site', 'cobbleandcandle-core' ),
		'opentable' => __( 'OpenTable widget', 'cobbleandcandle-core' ),
		'resy'      => __( 'Resy widget', 'cobbleandcandle-core' ),
		'call'      => __( 'Bookings by phone', 'cobbleandcandle-core' ),
	);

	return array(
		'cobble_location'  => array(
			array(
				'title'  => __( 'Address & contact', 'cobbleandcandle-core' ),
				'fields' => array(
					'cobble_street'   => array(
						'type'  => 'text',
						'label' => __( 'Street address', 'cobbleandcandle-core' ),
					),
					'cobble_locality' => array(
						'type'  => 'text',
						'label' => __( 'Neighbourhood or town', 'cobbleandcandle-core' ),
					),
					'cobble_region'   => array(
						'type'  => 'text',
						'label' => __( 'State / region', 'cobbleandcandle-core' ),
					),
					'cobble_postcode' => array(
						'type'  => 'text',
						'label' => __( 'Postcode', 'cobbleandcandle-core' ),
					),
					'cobble_country'  => array(
						'type'    => 'text',
						'label'   => __( 'Country code', 'cobbleandcandle-core' ),
						'default' => 'US',
					),
					'cobble_lat'      => array(
						'type'  => 'number',
						'label' => __( 'Latitude', 'cobbleandcandle-core' ),
					),
					'cobble_lng'      => array(
						'type'  => 'number',
						'label' => __( 'Longitude', 'cobbleandcandle-core' ),
					),
					'cobble_phone'    => array(
						'type'  => 'tel',
						'label' => __( 'Phone', 'cobbleandcandle-core' ),
					),
					'cobble_email'    => array(
						'type'  => 'email',
						'label' => __( 'Email', 'cobbleandcandle-core' ),
					),
					'cobble_map_url'  => array(
						'type'  => 'url',
						'label' => __( 'Directions link (Google/Apple Maps)', 'cobbleandcandle-core' ),
					),
				),
			),
			array(
				'title'  => __( 'Opening hours', 'cobbleandcandle-core' ),
				'fields' => array(
					'cobble_hours'         => array(
						'type'  => 'hours',
						'label' => __( 'Weekly hours', 'cobbleandcandle-core' ),
					),
					'cobble_holiday_hours' => array(
						'type'   => 'list',
						'label'  => __( 'Holiday hours', 'cobbleandcandle-core' ),
						'add'    => __( 'Add holiday', 'cobbleandcandle-core' ),
						'fields' => array(
							'date'   => array(
								'type'  => 'date',
								'label' => __( 'Date', 'cobbleandcandle-core' ),
							),
							'label'  => array(
								'type'  => 'text',
								'label' => __( 'Name', 'cobbleandcandle-core' ),
							),
							'open'   => array(
								'type'  => 'time',
								'label' => __( 'Opens', 'cobbleandcandle-core' ),
							),
							'close'  => array(
								'type'  => 'time',
								'label' => __( 'Closes', 'cobbleandcandle-core' ),
							),
							'closed' => array(
								'type'  => 'boolean',
								'label' => __( 'Closed all day', 'cobbleandcandle-core' ),
							),
						),
					),
				),
			),
			array(
				'title'  => __( 'Reservations & ordering', 'cobbleandcandle-core' ),
				'fields' => array(
					'cobble_booking_mode' => array(
						'type'    => 'select',
						'label'   => __( 'How guests book', 'cobbleandcandle-core' ),
						'options' => $booking_modes,
						'default' => 'native',
					),
					'cobble_booking_id'   => array(
						'type'  => 'text',
						'label' => __( 'OpenTable rid / Resy venue ID', 'cobbleandcandle-core' ),
					),
					'cobble_booking_url'  => array(
						'type'  => 'url',
						'label' => __( 'Provider booking page (fallback link)', 'cobbleandcandle-core' ),
					),
					'cobble_order_url'    => array(
						'type'  => 'url',
						'label' => __( 'Order online link', 'cobbleandcandle-core' ),
					),
					'cobble_menu_pdf'     => array(
						'type'  => 'url',
						'label' => __( 'Menu PDF link', 'cobbleandcandle-core' ),
					),
					'cobble_price_range'  => array(
						'type'  => 'text',
						'label' => __( 'Price range (e.g. $$$)', 'cobbleandcandle-core' ),
					),
				),
			),
			array(
				'title'  => __( 'Getting there', 'cobbleandcandle-core' ),
				'fields' => array(
					'cobble_parking'       => array(
						'type'  => 'textarea',
						'label' => __( 'Parking', 'cobbleandcandle-core' ),
					),
					'cobble_transit'       => array(
						'type'  => 'textarea',
						'label' => __( 'Public transport', 'cobbleandcandle-core' ),
					),
					'cobble_accessibility' => array(
						'type'  => 'textarea',
						'label' => __( 'Accessibility', 'cobbleandcandle-core' ),
					),
				),
			),
		),
		'cobble_menu_item' => array(
			array(
				'title'  => __( 'Price & details', 'cobbleandcandle-core' ),
				'fields' => array(
					'cobble_price'        => array(
						'type'  => 'text',
						'label' => __( 'Price (leave empty when using sizes)', 'cobbleandcandle-core' ),
					),
					'cobble_variants'     => array(
						'type'   => 'list',
						'label'  => __( 'Sizes (e.g. Glass / Bottle, Starter / Main)', 'cobbleandcandle-core' ),
						'add'    => __( 'Add size', 'cobbleandcandle-core' ),
						'fields' => array(
							'label' => array(
								'type'  => 'text',
								'label' => __( 'Size', 'cobbleandcandle-core' ),
							),
							'price' => array(
								'type'  => 'text',
								'label' => __( 'Price', 'cobbleandcandle-core' ),
							),
						),
					),
					'cobble_diet'         => array(
						'type'    => 'checkboxes',
						'label'   => __( 'Dietary', 'cobbleandcandle-core' ),
						'options' => array(
							'v'     => __( 'Vegetarian', 'cobbleandcandle-core' ),
							'vg'    => __( 'Vegan', 'cobbleandcandle-core' ),
							'gf'    => __( 'Gluten-free', 'cobbleandcandle-core' ),
							'spicy' => __( 'Spicy', 'cobbleandcandle-core' ),
						),
					),
					'cobble_flag'         => array(
						'type'  => 'text',
						'label' => __( 'Flag (e.g. Chef’s pick, Popular)', 'cobbleandcandle-core' ),
					),
					'cobble_chef_pick'    => array(
						'type'  => 'boolean',
						'label' => __( 'Show in Chef’s picks', 'cobbleandcandle-core' ),
					),
					'cobble_available_at' => array(
						'type'  => 'locations',
						'label' => __( 'Served at (none = every location)', 'cobbleandcandle-core' ),
					),
				),
			),
		),
		'cobble_event'     => array(
			array(
				'title'  => __( 'When & where', 'cobbleandcandle-core' ),
				'fields' => array(
					'cobble_start'    => array(
						'type'  => 'datetime',
						'label' => __( 'Starts', 'cobbleandcandle-core' ),
					),
					'cobble_end'      => array(
						'type'  => 'datetime',
						'label' => __( 'Ends', 'cobbleandcandle-core' ),
					),
					'cobble_location' => array(
						'type'  => 'location',
						'label' => __( 'Location', 'cobbleandcandle-core' ),
					),
					'cobble_type'     => array(
						'type'  => 'text',
						'label' => __( 'Type (e.g. Tasting, Live music)', 'cobbleandcandle-core' ),
					),
				),
			),
			array(
				'title'  => __( 'Tickets', 'cobbleandcandle-core' ),
				'fields' => array(
					'cobble_price'        => array(
						'type'  => 'text',
						'label' => __( 'Price (e.g. $145 pp, No cover)', 'cobbleandcandle-core' ),
					),
					'cobble_capacity'     => array(
						'type'  => 'number',
						'label' => __( 'Seats', 'cobbleandcandle-core' ),
					),
					'cobble_availability' => array(
						'type'  => 'text',
						'label' => __( 'Availability note (e.g. 12 seats left)', 'cobbleandcandle-core' ),
					),
					'cobble_booking_url'  => array(
						'type'  => 'url',
						'label' => __( 'Booking link', 'cobbleandcandle-core' ),
					),
				),
			),
			array(
				'title'  => __( 'Menu for the evening', 'cobbleandcandle-core' ),
				'fields' => array(
					'cobble_courses' => array(
						'type'   => 'list',
						'label'  => __( 'Courses', 'cobbleandcandle-core' ),
						'add'    => __( 'Add course', 'cobbleandcandle-core' ),
						'fields' => array(
							'name' => array(
								'type'  => 'text',
								'label' => __( 'Course', 'cobbleandcandle-core' ),
							),
							'note' => array(
								'type'  => 'text',
								'label' => __( 'Note', 'cobbleandcandle-core' ),
							),
						),
					),
				),
			),
		),
		'cobble_room'      => array(
			array(
				'title'  => __( 'Rates & capacity', 'cobbleandcandle-core' ),
				'fields' => array(
					'cobble_price_night'   => array(
						'type'  => 'number',
						'label' => __( 'Price per night', 'cobbleandcandle-core' ),
					),
					'cobble_price_weekend' => array(
						'type'  => 'number',
						'label' => __( 'Friday & Saturday night price (optional)', 'cobbleandcandle-core' ),
					),
					'cobble_min_nights'    => array(
						'type'  => 'number',
						'label' => __( 'Minimum nights', 'cobbleandcandle-core' ),
					),
					'cobble_max_guests'    => array(
						'type'  => 'number',
						'label' => __( 'Maximum guests', 'cobbleandcandle-core' ),
					),
					'cobble_units'         => array(
						'type'  => 'number',
						'label' => __( 'How many rooms of this kind', 'cobbleandcandle-core' ),
					),
					'cobble_beds'          => array(
						'type'  => 'text',
						'label' => __( 'Beds (e.g. 1 king)', 'cobbleandcandle-core' ),
					),
					'cobble_size'          => array(
						'type'  => 'text',
						'label' => __( 'Size (e.g. 28 m²)', 'cobbleandcandle-core' ),
					),
					'cobble_location'      => array(
						'type'  => 'location',
						'label' => __( 'House', 'cobbleandcandle-core' ),
					),
				),
			),
			array(
				'title'  => __( 'Amenities', 'cobbleandcandle-core' ),
				'fields' => array(
					'cobble_amenities' => array(
						'type'    => 'checkboxes',
						'label'   => __( 'In the room', 'cobbleandcandle-core' ),
						'options' => cobble_room_amenities(),
					),
				),
			),
			array(
				'title'  => __( 'Calendar sync (Airbnb, Booking.com, Vrbo)', 'cobbleandcandle-core' ),
				'fields' => array(
					'cobble_ical_import' => array(
						'type'    => 'list',
						'private' => true, // Platform links carry secret tokens: editors only, never the public REST API.
						'label'   => __( 'Import calendars (iCal links)', 'cobbleandcandle-core' ),
						'add'     => __( 'Add calendar', 'cobbleandcandle-core' ),
						'fields'  => array(
							'label' => array(
								'type'  => 'text',
								'label' => __( 'Name (e.g. Airbnb)', 'cobbleandcandle-core' ),
							),
							'url'   => array(
								'type'  => 'url',
								'label' => __( 'iCal link', 'cobbleandcandle-core' ),
							),
						),
					),
				),
			),
		),
	);
}

/**
 * REST/JSON schema for one field.
 *
 * @param array<string, mixed> $field Field definition.
 * @return array<string, mixed>
 */
function cobble_field_rest_schema( array $field ) {
	switch ( $field['type'] ) {
		case 'number':
			return array( 'type' => 'number' );
		case 'boolean':
			return array( 'type' => 'boolean' );
		case 'location':
			return array( 'type' => 'integer' );
		case 'locations':
			return array(
				'type'  => 'array',
				'items' => array( 'type' => 'integer' ),
			);
		case 'checkboxes':
			return array(
				'type'  => 'array',
				'items' => array(
					'type' => 'string',
					'enum' => array_keys( $field['options'] ),
				),
			);
		case 'hours':
			return array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'open'   => array( 'type' => 'string' ),
						'close'  => array( 'type' => 'string' ),
						'closed' => array( 'type' => 'boolean' ),
					),
				),
			);
		case 'list':
			$properties = array();
			foreach ( $field['fields'] as $key => $sub ) {
				$properties[ $key ] = cobble_field_rest_schema( $sub );
			}
			return array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => $properties,
				),
			);
		default:
			return array( 'type' => 'string' );
	}
}

/**
 * Sanitize a field value by type.
 *
 * @param mixed                $value Raw value.
 * @param array<string, mixed> $field Field definition.
 * @return mixed
 */
function cobble_sanitize_field( $value, array $field ) {
	switch ( $field['type'] ) {
		case 'textarea':
			return sanitize_textarea_field( (string) $value );
		case 'url':
			return esc_url_raw( (string) $value );
		case 'email':
			return sanitize_email( (string) $value );
		case 'number':
			return is_numeric( $value ) ? (float) $value : 0;
		case 'boolean':
			return (bool) $value;
		case 'select':
			return array_key_exists( (string) $value, $field['options'] ) ? (string) $value : (string) ( $field['default'] ?? '' );
		case 'time':
			return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', (string) $value ) ? (string) $value : '';
		case 'date':
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $value ) ? (string) $value : '';
		case 'datetime':
			return preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/', (string) $value ) ? (string) $value : '';
		case 'location':
			return absint( $value );
		case 'locations':
			return array_values( array_filter( array_map( 'absint', (array) $value ) ) );
		case 'checkboxes':
			return array_values( array_intersect( array_map( 'strval', (array) $value ), array_keys( $field['options'] ) ) );
		case 'hours':
			$days = array();
			for ( $i = 0; $i < 7; $i++ ) {
				$day    = (array) ( $value[ $i ] ?? array() );
				$days[] = array(
					'open'   => cobble_sanitize_field( $day['open'] ?? '', array( 'type' => 'time' ) ),
					'close'  => cobble_sanitize_field( $day['close'] ?? '', array( 'type' => 'time' ) ),
					'closed' => ! empty( $day['closed'] ),
				);
			}
			return $days;
		case 'list':
			$rows = array();
			foreach ( (array) $value as $row ) {
				$clean = array();
				foreach ( $field['fields'] as $key => $sub ) {
					$clean[ $key ] = cobble_sanitize_field( ( (array) $row )[ $key ] ?? '', $sub );
				}
				$rows[] = $clean;
			}
			return $rows;
		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Register every field as REST-enabled post meta.
 */
function cobble_register_fields() {
	foreach ( cobble_field_schema() as $post_type => $panels ) {
		foreach ( $panels as $panel ) {
			foreach ( $panel['fields'] as $key => $field ) {
				$schema = cobble_field_rest_schema( $field );
				if ( ! empty( $field['private'] ) ) {
					$schema['context'] = array( 'edit' );
				}
				register_post_meta(
					$post_type,
					$key,
					array(
						'type'              => $schema['type'],
						'single'            => true,
						'default'           => $field['default'] ?? cobble_field_default( $field ),
						'sanitize_callback' => static function ( $value ) use ( $field ) {
							return cobble_sanitize_field( $value, $field );
						},
						'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
							return current_user_can( 'edit_post', $post_id );
						},
						'show_in_rest'      => 'array' === $schema['type'] || 'object' === $schema['type'] || ! empty( $field['private'] )
							? array( 'schema' => $schema )
							: true,
					)
				);
			}
		}
	}
}
add_action( 'init', 'cobble_register_fields', 11 );

/**
 * Empty default for a field type.
 *
 * @param array<string, mixed> $field Field definition.
 * @return mixed
 */
function cobble_field_default( array $field ) {
	switch ( cobble_field_rest_schema( $field )['type'] ) {
		case 'array':
			return array();
		case 'boolean':
			return false;
		case 'number':
		case 'integer':
			return 0;
		default:
			return '';
	}
}

/**
 * Editor sidebar panels (no build step: uses the wp.* globals).
 */
function cobble_enqueue_field_panels() {
	$screen = get_current_screen();
	if ( ! $screen || ! array_key_exists( (string) $screen->post_type, cobble_field_schema() ) ) {
		return;
	}

	wp_enqueue_script(
		'cobble-editor-fields',
		COBBLE_CORE_URL . 'assets/editor-fields.js',
		array( 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-i18n' ),
		COBBLE_CORE_VERSION,
		true
	);
	wp_add_inline_script(
		'cobble-editor-fields',
		'window.ccFields = ' . wp_json_encode(
			array(
				'postType' => $screen->post_type,
				'panels'   => cobble_field_schema()[ $screen->post_type ],
				'days'     => array(
					__( 'Monday', 'cobbleandcandle-core' ),
					__( 'Tuesday', 'cobbleandcandle-core' ),
					__( 'Wednesday', 'cobbleandcandle-core' ),
					__( 'Thursday', 'cobbleandcandle-core' ),
					__( 'Friday', 'cobbleandcandle-core' ),
					__( 'Saturday', 'cobbleandcandle-core' ),
					__( 'Sunday', 'cobbleandcandle-core' ),
				),
			)
		) . ';',
		'before'
	);
	wp_set_script_translations( 'cobble-editor-fields', 'cobbleandcandle-core' );
}
add_action( 'enqueue_block_editor_assets', 'cobble_enqueue_field_panels' );
