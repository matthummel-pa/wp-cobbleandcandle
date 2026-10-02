<?php
/**
 * Rooms & Stays: rooms, availability, pricing and booking requests.
 *
 * Guests pick free nights and send a request; it is held as "pending" (the nights are blocked) until
 * the owner confirms or cancels it from Bookings in the dashboard. No payments are taken.
 * Calendar sync with Airbnb, Booking.com and Vrbo lives in includes/ical.php.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Room amenities: key => label.
 *
 * @return array<string, string>
 */
function cc_room_amenities() {
	/**
	 * Amenities offered on the room edit screen.
	 *
	 * @param array<string, string> $amenities Key => label.
	 */
	return (array) apply_filters(
		'cc_room_amenities',
		array(
			'ensuite'    => __( 'Ensuite bathroom', 'cobbleandcandle-core' ),
			'bath'       => __( 'Bathtub', 'cobbleandcandle-core' ),
			'breakfast'  => __( 'Breakfast included', 'cobbleandcandle-core' ),
			'wifi'       => __( 'Wi-Fi', 'cobbleandcandle-core' ),
			'fireplace'  => __( 'Fireplace', 'cobbleandcandle-core' ),
			'view'       => __( 'Street or garden view', 'cobbleandcandle-core' ),
			'desk'       => __( 'Writing desk', 'cobbleandcandle-core' ),
			'tv'         => __( 'TV', 'cobbleandcandle-core' ),
			'ac'         => __( 'Air conditioning', 'cobbleandcandle-core' ),
			'tea'        => __( 'Tea & coffee', 'cobbleandcandle-core' ),
			'parking'    => __( 'Parking', 'cobbleandcandle-core' ),
			'pets'       => __( 'Dogs welcome', 'cobbleandcandle-core' ),
			'accessible' => __( 'Step-free access', 'cobbleandcandle-core' ),
		)
	);
}

/**
 * Register rooms (public) and bookings (dashboard only).
 */
function cc_register_room_types() {
	register_post_type(
		'cc_room',
		array(
			'labels'        => array(
				'name'          => __( 'Rooms', 'cobbleandcandle-core' ),
				'singular_name' => __( 'Room', 'cobbleandcandle-core' ),
				'add_new_item'  => __( 'Add room', 'cobbleandcandle-core' ),
				'edit_item'     => __( 'Edit room', 'cobbleandcandle-core' ),
				'all_items'     => __( 'All rooms', 'cobbleandcandle-core' ),
				'menu_name'     => __( 'Rooms & stays', 'cobbleandcandle-core' ),
			),
			'public'        => true,
			'has_archive'   => 'rooms',
			'rewrite'       => array(
				'slug'       => 'rooms',
				'with_front' => false,
			),
			'menu_icon'     => 'dashicons-admin-home',
			'menu_position' => 24,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes', 'revisions' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'cc_booking',
		array(
			'labels'          => array(
				'name'          => __( 'Bookings', 'cobbleandcandle-core' ),
				'singular_name' => __( 'Booking', 'cobbleandcandle-core' ),
				'edit_item'     => __( 'Booking', 'cobbleandcandle-core' ),
				'all_items'     => __( 'Bookings', 'cobbleandcandle-core' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'edit.php?post_type=cc_room',
			'show_in_rest'    => false,
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			// Guest details: Editors and Administrators only (edit_others_posts), never Authors or Contributors.
			'capabilities'    => array_fill_keys(
				array( 'edit_posts', 'edit_others_posts', 'edit_private_posts', 'edit_published_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_others_posts', 'delete_private_posts', 'delete_published_posts', 'create_posts' ),
				'edit_others_posts'
			),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'cc_register_room_types' );

/**
 * Booking statuses that hold nights.
 *
 * @return array<int, string>
 */
function cc_booking_holding_statuses() {
	return array( 'pending', 'confirmed' );
}

/**
 * Status labels.
 *
 * @return array<string, string>
 */
function cc_booking_status_labels() {
	return array(
		'pending'   => __( 'Pending', 'cobbleandcandle-core' ),
		'confirmed' => __( 'Confirmed', 'cobbleandcandle-core' ),
		'cancelled' => __( 'Cancelled', 'cobbleandcandle-core' ),
	);
}

/**
 * Room data for display and booking (raw strings; escape on output).
 *
 * @param int|\WP_Post $room Room post or ID.
 * @return array<string, mixed>
 */
function cc_room( $room ) {
	$post = get_post( $room );
	if ( ! $post || 'cc_room' !== $post->post_type ) {
		return array();
	}
	$meta = static function ( $key ) use ( $post ) {
		return get_post_meta( $post->ID, $key, true );
	};
	$all  = cc_room_amenities();
	return array(
		'id'            => $post->ID,
		'slug'          => $post->post_name,
		'name'          => cc_plain_title( $post ),
		'url'           => get_permalink( $post ),
		'excerpt'       => $post->post_excerpt,
		'image_id'      => (int) get_post_thumbnail_id( $post ),
		'price_night'   => (float) $meta( 'cc_price_night' ),
		'price_weekend' => (float) $meta( 'cc_price_weekend' ),
		'min_nights'    => max( 1, (int) $meta( 'cc_min_nights' ) ),
		'max_guests'    => max( 1, (int) $meta( 'cc_max_guests' ) ),
		'units'         => max( 1, (int) $meta( 'cc_units' ) ),
		'beds'          => (string) $meta( 'cc_beds' ),
		'size'          => (string) $meta( 'cc_size' ),
		'location_id'   => (int) $meta( 'cc_location' ),
		'amenities'     => array_values( array_intersect_key( $all, array_flip( (array) $meta( 'cc_amenities' ) ) ) ),
	);
}

/**
 * Published rooms in menu order.
 *
 * @return array<int, array<string, mixed>>
 */
function cc_get_rooms() {
	$posts = get_posts(
		array(
			'post_type'      => 'cc_room',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'  => true,
		)
	);
	return array_values( array_filter( array_map( 'cc_room', $posts ) ) );
}

/**
 * Price for one night (Friday and Saturday nights use the weekend price when set).
 *
 * @param array<string, mixed> $room Room data.
 * @param string               $date Night (Y-m-d).
 * @return float
 */
function cc_room_night_price( array $room, $date ) {
	$weekday = (int) gmdate( 'N', strtotime( $date . ' 12:00:00 UTC' ) );
	return ( 5 === $weekday || 6 === $weekday ) && $room['price_weekend'] > 0 ? $room['price_weekend'] : $room['price_night'];
}

/**
 * Nights (Y-m-d) from check-in up to the night before check-out.
 *
 * @param string $check_in  Y-m-d.
 * @param string $check_out Y-m-d.
 * @return array<int, string>
 */
function cc_stay_nights( $check_in, $check_out ) {
	$nights = array();
	$day    = strtotime( $check_in . ' 12:00:00 UTC' );
	$end    = strtotime( $check_out . ' 12:00:00 UTC' );
	for ( $i = 0; $day && $end && $day < $end && $i < 400; $i++ ) {
		$nights[] = gmdate( 'Y-m-d', $day );
		$day     += DAY_IN_SECONDS;
	}
	return $nights;
}

/**
 * How many units of a room are taken each night in a range: site bookings (pending or confirmed)
 * plus nights blocked by imported calendars.
 *
 * @param int    $room_id Room post ID.
 * @param string $from    First night (Y-m-d).
 * @param string $to      Day after the last night (Y-m-d).
 * @param int    $exclude Booking ID to ignore (when re-checking an existing booking).
 * @return array<string, int> Night => units taken.
 */
function cc_room_taken( $room_id, $from, $to, $exclude = 0 ) {
	$taken    = array();
	$bookings = get_posts(
		array(
			'post_type'      => 'cc_booking',
			'post_status'    => 'publish',
			'posts_per_page' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- one room's bookings in a date window; small and bounded.
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'post__not_in'   => $exclude ? array( $exclude ) : array(),
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small, indexed by room.
				array(
					'key'   => 'cc_room',
					'value' => (int) $room_id,
				),
				array(
					'key'     => 'cc_status',
					'value'   => cc_booking_holding_statuses(),
					'compare' => 'IN',
				),
				array(
					'key'     => 'cc_check_in',
					'value'   => $to,
					'compare' => '<',
				),
				array(
					'key'     => 'cc_check_out',
					'value'   => $from,
					'compare' => '>',
				),
			),
		)
	);
	foreach ( $bookings as $booking_id ) {
		// Walk only the overlap with the window: a long stay never truncates or slows the check.
		$start = max( (string) get_post_meta( $booking_id, 'cc_check_in', true ), $from );
		$end   = min( (string) get_post_meta( $booking_id, 'cc_check_out', true ), $to );
		foreach ( cc_stay_nights( $start, $end ) as $night ) {
			$taken[ $night ] = ( $taken[ $night ] ?? 0 ) + 1;
		}
	}
	// Imported calendars block the whole room type on those nights.
	$room = cc_room( $room_id );
	foreach ( (array) get_post_meta( $room_id, '_cc_ical_blocks', true ) as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}
		$start = max( (string) ( $block['start'] ?? '' ), $from );
		$end   = min( (string) ( $block['end'] ?? '' ), $to );
		foreach ( cc_stay_nights( $start, $end ) as $night ) {
			$taken[ $night ] = max( $taken[ $night ] ?? 0, $room ? $room['units'] : 1 );
		}
	}
	return $taken;
}

/**
 * Nights in a range with no unit left.
 *
 * @param int    $room_id Room post ID.
 * @param string $from    First night (Y-m-d).
 * @param string $to      Day after the last night (Y-m-d).
 * @return array<int, string>
 */
function cc_room_full_nights( $room_id, $from, $to ) {
	$room = cc_room( $room_id );
	if ( ! $room ) {
		return array();
	}
	$full = array();
	foreach ( cc_room_taken( $room_id, $from, $to ) as $night => $count ) {
		if ( $count >= $room['units'] ) {
			$full[] = $night;
		}
	}
	sort( $full );
	return $full;
}

/**
 * Is a stay bookable? Returns '' when it is, else an error code.
 *
 * @param array<string, mixed> $room      Room data.
 * @param string               $check_in  Y-m-d.
 * @param string               $check_out Y-m-d.
 * @param int                  $guests    Guests.
 * @return string '' | invalid | unavailable
 */
function cc_stay_problem( array $room, $check_in, $check_out, $guests ) {
	$today  = wp_date( 'Y-m-d' );
	$nights = cc_stay_nights( $check_in, $check_out );
	if ( ! cc_is_valid_date( $check_in ) || ! cc_is_valid_date( $check_out ) || $check_in < $today
		|| $check_in > wp_date( 'Y-m-d', strtotime( '+18 months' ) ) || count( $nights ) < $room['min_nights'] || count( $nights ) > 30
		|| $guests < 1 || $guests > $room['max_guests'] ) {
		return 'invalid';
	}
	return array_intersect( $nights, cc_room_full_nights( $room['id'], $check_in, $check_out ) ) ? 'unavailable' : '';
}

/**
 * Total for a stay.
 *
 * @param array<string, mixed> $room      Room data.
 * @param string               $check_in  Y-m-d.
 * @param string               $check_out Y-m-d.
 * @return float
 */
function cc_stay_total( array $room, $check_in, $check_out ) {
	$total = 0.0;
	foreach ( cc_stay_nights( $check_in, $check_out ) as $night ) {
		$total += cc_room_night_price( $room, $night );
	}
	return $total;
}

/**
 * Money for display, e.g. "$145" or "€145" (the currency from Settings → Restaurant).
 *
 * @param float $amount Amount.
 * @return string
 */
function cc_money( $amount ) {
	$code    = (string) apply_filters( 'cc_currency', 'USD' );
	$symbols = array(
		'USD' => '$',
		'CAD' => '$',
		'AUD' => '$',
		'NZD' => '$',
		'GBP' => '£',
		'EUR' => '€',
		'JPY' => '¥',
		'CHF' => 'CHF ',
		'SEK' => 'kr ',
		'NOK' => 'kr ',
		'DKK' => 'kr ',
	);
	$decimals = floor( $amount ) === (float) $amount ? 0 : 2;
	$symbol   = $symbols[ $code ] ?? $code . ' ';
	/**
	 * Filter a formatted price.
	 *
	 * @param string $formatted Price.
	 * @param float  $amount    Amount.
	 * @param string $code      ISO currency code.
	 */
	return (string) apply_filters( 'cc_money', $symbol . number_format_i18n( $amount, $decimals ), $amount, $code );
}

/**
 * Public, read-only availability for the booking calendar.
 */
function cc_register_room_routes() {
	register_rest_route(
		'cobbleandcandle/v1',
		'/rooms/(?P<id>\d+)/availability',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true', // Public: free/full nights and prices only, no guest data.
			'args'                => array(
				'id'   => array( 'sanitize_callback' => 'absint' ),
				'from' => array(
					'required'          => true,
					'validate_callback' => static fn( $v ) => cc_is_valid_date( (string) $v ),
				),
				'to'   => array(
					'required'          => true,
					'validate_callback' => static fn( $v ) => cc_is_valid_date( (string) $v ),
				),
			),
			'callback'            => 'cc_rest_room_availability',
		)
	);
}
add_action( 'rest_api_init', 'cc_register_room_routes' );

/**
 * REST callback: full nights and nightly prices for a range (max 13 months).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function cc_rest_room_availability( WP_REST_Request $request ) {
	$room = cc_room( (int) $request['id'] );
	if ( ! $room || 'publish' !== get_post_status( $room['id'] ) ) {
		return new WP_Error( 'cc_no_room', __( 'Room not found.', 'cobbleandcandle-core' ), array( 'status' => 404 ) );
	}
	$from = max( (string) $request['from'], wp_date( 'Y-m-d' ) ); // Never reveal past occupancy.
	$to   = (string) $request['to'];
	if ( $to <= $from || count( cc_stay_nights( $from, $to ) ) > 400 ) {
		return new WP_Error( 'cc_bad_range', __( 'Choose a shorter date range.', 'cobbleandcandle-core' ), array( 'status' => 400 ) );
	}
	return rest_ensure_response(
		array(
			'full'         => cc_room_full_nights( $room['id'], $from, $to ),
			'priceNight'   => $room['price_night'],
			'priceWeekend' => $room['price_weekend'],
			'minNights'    => $room['min_nights'],
			'maxGuests'    => $room['max_guests'],
			'today'        => wp_date( 'Y-m-d' ),
		)
	);
}

/**
 * Handle a booking request (logged in or not).
 */
function cc_handle_room_booking() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$back = remove_query_arg( 'booking', $back );
	$done = static function ( $status ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'booking', $status, $back ) . '#book' );
		exit;
	};

	if ( ! isset( $_POST['cc_room_booking_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cc_room_booking_nonce'] ) ), 'cc_room_booking' ) ) {
		$done( 'expired' );
	}
	if ( ! empty( $_POST['cc_website'] ) ) {
		$done( 'sent' ); // Honeypot.
	}
	if ( cc_form_rate_limited( 'room' ) ) {
		$done( 'busy' );
	}

	$room      = cc_room( isset( $_POST['cc_room'] ) ? absint( $_POST['cc_room'] ) : 0 );
	$check_in  = isset( $_POST['cc_check_in'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_check_in'] ) ) : '';
	$check_out = isset( $_POST['cc_check_out'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_check_out'] ) ) : '';
	$guests    = isset( $_POST['cc_guests'] ) ? absint( $_POST['cc_guests'] ) : 0;
	$name      = isset( $_POST['cc_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_name'] ) ) : '';
	$email     = isset( $_POST['cc_email'] ) ? sanitize_email( wp_unslash( $_POST['cc_email'] ) ) : '';
	$phone     = isset( $_POST['cc_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['cc_phone'] ) ) : '';
	$message   = isset( $_POST['cc_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cc_message'] ) ) : '';

	if ( ! $room || 'publish' !== get_post_status( $room['id'] ) || '' === $name || ! is_email( $email ) || '' === $phone ) {
		$done( 'invalid' );
	}
	$problem = cc_stay_problem( $room, $check_in, $check_out, $guests );
	if ( '' !== $problem ) {
		$done( $problem );
	}
	if ( cc_open_requests_for( $email ) >= 2 ) {
		$done( 'busy' ); // Stops one address holding many rooms with requests it never means to keep.
	}

	$total      = cc_stay_total( $room, $check_in, $check_out );
	$nights     = count( cc_stay_nights( $check_in, $check_out ) );
	$booking_id = wp_insert_post(
		array(
			'post_type'   => 'cc_booking',
			'post_status' => 'publish',
			/* translators: 1: guest name, 2: room, 3: check-in date */
			'post_title'  => sprintf( __( '%1$s · %2$s · %3$s', 'cobbleandcandle-core' ), $name, $room['name'], $check_in ),
			'meta_input'  => array(
				'cc_room'      => $room['id'],
				'cc_check_in'  => $check_in,
				'cc_check_out' => $check_out,
				'cc_guests'    => $guests,
				'cc_name'      => $name,
				'cc_email'     => $email,
				'cc_phone'     => $phone,
				'cc_message'   => $message,
				'cc_total'     => $total,
				'cc_status'    => 'pending',
				'cc_source'    => 'site',
			),
		),
		true
	);
	if ( is_wp_error( $booking_id ) ) {
		$done( 'error' );
	}

	$owner = $room['location_id'] ? sanitize_email( (string) get_post_meta( $room['location_id'], 'cc_email', true ) ) : '';
	$owner = $owner ? $owner : get_option( 'admin_email' );
	$lines = array(
		__( 'Room', 'cobbleandcandle-core' ) . ': ' . $room['name'],
		__( 'Check-in', 'cobbleandcandle-core' ) . ': ' . $check_in,
		__( 'Check-out', 'cobbleandcandle-core' ) . ': ' . $check_out,
		/* translators: %d: number of nights */
		sprintf( _n( '%d night', '%d nights', $nights, 'cobbleandcandle-core' ), $nights ),
		__( 'Guests', 'cobbleandcandle-core' ) . ': ' . $guests,
		__( 'Total', 'cobbleandcandle-core' ) . ': ' . cc_money( $total ),
	);
	wp_mail(
		$owner,
		/* translators: 1: guest name, 2: room */
		sprintf( __( 'Booking request: %1$s, %2$s', 'cobbleandcandle-core' ), $name, $room['name'] ),
		implode( "\n", array_merge( $lines, array( __( 'Name', 'cobbleandcandle-core' ) . ': ' . $name, __( 'Email', 'cobbleandcandle-core' ) . ': ' . $email, __( 'Phone', 'cobbleandcandle-core' ) . ': ' . $phone, '', $message, '', __( 'Confirm or cancel:', 'cobbleandcandle-core' ) . ' ' . admin_url( 'edit.php?post_type=cc_booking' ) ) ) ),
		array( 'Reply-To: ' . cc_mail_name( $name ) . ' <' . $email . '>' )
	);
	$sent = wp_mail(
		$email,
		/* translators: %s: site name */
		sprintf( __( 'We have your booking request · %s', 'cobbleandcandle-core' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
		implode( "\n", array_merge( array( __( 'Thank you. We have your request and will confirm by email shortly. Nothing is charged until we confirm.', 'cobbleandcandle-core' ), '' ), $lines ) )
	);

	/**
	 * Fires after a booking request is stored and emailed.
	 *
	 * @param int  $booking_id Booking post ID.
	 * @param bool $sent       Whether the guest email was sent.
	 */
	do_action( 'cc_room_booking_requested', $booking_id, $sent );
	$done( 'sent' );
}
add_action( 'admin_post_cc_room_booking', 'cc_handle_room_booking' );
add_action( 'admin_post_nopriv_cc_room_booking', 'cc_handle_room_booking' );

/**
 * A display name safe for an email header (no quotes, angle brackets or line breaks).
 *
 * @param string $name Name.
 * @return string
 */
function cc_mail_name( $name ) {
	return '"' . str_replace( array( '"', '<', '>', ',', '\\', "\r", "\n" ), '', $name ) . '"';
}

/**
 * Bookings list: columns.
 *
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function cc_booking_columns( $columns ) {
	return array(
		'cb'        => $columns['cb'] ?? '',
		'title'     => __( 'Booking', 'cobbleandcandle-core' ),
		'cc_dates'  => __( 'Dates', 'cobbleandcandle-core' ),
		'cc_guests' => __( 'Guests', 'cobbleandcandle-core' ),
		'cc_total'  => __( 'Total', 'cobbleandcandle-core' ),
		'cc_status' => __( 'Status', 'cobbleandcandle-core' ),
		'date'      => __( 'Requested', 'cobbleandcandle-core' ),
	);
}
add_filter( 'manage_cc_booking_posts_columns', 'cc_booking_columns' );

/**
 * Bookings list: column values.
 *
 * @param string $column  Column.
 * @param int    $post_id Booking ID.
 */
function cc_booking_column( $column, $post_id ) {
	$meta = static fn( $key ) => (string) get_post_meta( $post_id, $key, true );
	if ( 'cc_dates' === $column ) {
		echo esc_html( $meta( 'cc_check_in' ) . ' → ' . $meta( 'cc_check_out' ) );
	} elseif ( 'cc_guests' === $column ) {
		echo esc_html( $meta( 'cc_guests' ) );
	} elseif ( 'cc_total' === $column ) {
		echo esc_html( cc_money( (float) $meta( 'cc_total' ) ) );
	} elseif ( 'cc_status' === $column ) {
		$labels = cc_booking_status_labels();
		echo esc_html( $labels[ $meta( 'cc_status' ) ] ?? $meta( 'cc_status' ) );
	}
}
add_action( 'manage_cc_booking_posts_custom_column', 'cc_booking_column', 10, 2 );

/**
 * Bookings list: Confirm / Cancel row actions.
 *
 * @param array<string, string> $actions Row actions.
 * @param \WP_Post              $post    Booking.
 * @return array<string, string>
 */
function cc_booking_row_actions( $actions, $post ) {
	if ( 'cc_booking' !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
		return $actions;
	}
	$status = (string) get_post_meta( $post->ID, 'cc_status', true );
	$link   = static function ( $to ) use ( $post ) {
		return wp_nonce_url( admin_url( 'admin-post.php?action=cc_booking_status&booking=' . $post->ID . '&to=' . $to ), 'cc_booking_status_' . $post->ID . '_' . $to );
	};
	if ( 'confirmed' !== $status ) {
		$actions['cc_confirm'] = '<a href="' . esc_url( $link( 'confirmed' ) ) . '">' . esc_html__( 'Confirm', 'cobbleandcandle-core' ) . '</a>';
	}
	if ( 'cancelled' !== $status ) {
		$actions['cc_cancel'] = '<a href="' . esc_url( $link( 'cancelled' ) ) . '">' . esc_html__( 'Cancel', 'cobbleandcandle-core' ) . '</a>';
	}
	unset( $actions['inline hide-if-no-js'] );
	return $actions;
}
add_filter( 'post_row_actions', 'cc_booking_row_actions', 10, 2 );

/**
 * Change a booking's status and email the guest.
 */
function cc_handle_booking_status() {
	$booking_id = isset( $_GET['booking'] ) ? absint( $_GET['booking'] ) : 0;
	$to         = isset( $_GET['to'] ) ? sanitize_key( wp_unslash( $_GET['to'] ) ) : '';
	check_admin_referer( 'cc_booking_status_' . $booking_id . '_' . $to );
	if ( ! $booking_id || 'cc_booking' !== get_post_type( $booking_id ) || ! current_user_can( 'edit_post', $booking_id ) || ! in_array( $to, array( 'confirmed', 'cancelled' ), true ) ) {
		wp_die( esc_html__( 'You cannot change this booking.', 'cobbleandcandle-core' ), 403 );
	}
	$meta = static fn( $key ) => (string) get_post_meta( $booking_id, $key, true );
	if ( 'confirmed' === $to ) {
		// Re-check: the nights may have been taken since (another booking or an imported calendar).
		if ( '' !== cc_booking_conflict( (int) $meta( 'cc_room' ), $meta( 'cc_check_in' ), $meta( 'cc_check_out' ), $booking_id ) ) {
			wp_die( esc_html__( 'This booking cannot be confirmed: its room is missing, its dates are wrong, or those nights are no longer free. Edit it or cancel it.', 'cobbleandcandle-core' ), '', array( 'back_link' => true ) );
		}
	}
	update_post_meta( $booking_id, 'cc_status', $to );

	$room_name = cc_plain_title( (int) $meta( 'cc_room' ) );
	$site      = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$dates     = $meta( 'cc_check_in' ) . ' → ' . $meta( 'cc_check_out' );
	if ( is_email( $meta( 'cc_email' ) ) ) {
		wp_mail(
			$meta( 'cc_email' ),
			'confirmed' === $to
				/* translators: %s: site name */
				? sprintf( __( 'Your stay is confirmed · %s', 'cobbleandcandle-core' ), $site )
				/* translators: %s: site name */
				: sprintf( __( 'About your booking request · %s', 'cobbleandcandle-core' ), $site ),
			'confirmed' === $to
				/* translators: 1: room, 2: dates */
				? sprintf( __( "Good news: %1\$s is yours for %2\$s. We look forward to welcoming you.\n\nReply to this email with any questions.", 'cobbleandcandle-core' ), $room_name, $dates )
				/* translators: 1: room, 2: dates */
				: sprintf( __( "We're sorry, we can't offer %1\$s for %2\$s. Reply to this email and we'll help you find other dates.", 'cobbleandcandle-core' ), $room_name, $dates )
		);
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=cc_booking' ) );
	exit;
}
add_action( 'admin_post_cc_booking_status', 'cc_handle_booking_status' );

/**
 * Booking edit screen: guest, room and dates. Owners also use it to add phone or walk-in bookings.
 */
function cc_booking_meta_box() {
	add_meta_box( 'cc-booking', __( 'Booking details', 'cobbleandcandle-core' ), 'cc_render_booking_meta_box', 'cc_booking', 'normal', 'high' );
}
add_action( 'add_meta_boxes_cc_booking', 'cc_booking_meta_box' );

/**
 * Editable booking fields: key => [label, input type].
 *
 * @return array<string, array{0: string, 1: string}>
 */
function cc_booking_fields() {
	return array(
		'cc_room'      => array( __( 'Room', 'cobbleandcandle-core' ), 'room' ),
		'cc_check_in'  => array( __( 'Check-in', 'cobbleandcandle-core' ), 'date' ),
		'cc_check_out' => array( __( 'Check-out', 'cobbleandcandle-core' ), 'date' ),
		'cc_guests'    => array( __( 'Guests', 'cobbleandcandle-core' ), 'number' ),
		'cc_name'      => array( __( 'Name', 'cobbleandcandle-core' ), 'text' ),
		'cc_email'     => array( __( 'Email', 'cobbleandcandle-core' ), 'email' ),
		'cc_phone'     => array( __( 'Phone', 'cobbleandcandle-core' ), 'tel' ),
		'cc_message'   => array( __( 'Notes', 'cobbleandcandle-core' ), 'textarea' ),
		'cc_status'    => array( __( 'Status', 'cobbleandcandle-core' ), 'status' ),
	);
}

/**
 * Booking meta box markup.
 *
 * @param \WP_Post $post Booking.
 */
function cc_render_booking_meta_box( $post ) {
	wp_nonce_field( 'cc_booking_save_' . $post->ID, 'cc_booking_save_nonce' );
	$total = (float) get_post_meta( $post->ID, 'cc_total', true );
	echo '<table class="form-table" role="presentation">';
	foreach ( cc_booking_fields() as $key => list( $label, $type ) ) {
		$value = (string) get_post_meta( $post->ID, $key, true );
		$id    = 'cc-booking-' . $key;
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'room' === $type ) {
			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '">';
			foreach ( cc_get_rooms() as $room ) {
				echo '<option value="' . esc_attr( $room['id'] ) . '"' . selected( (int) $value, $room['id'], false ) . '>' . esc_html( $room['name'] ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'status' === $type ) {
			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '">';
			foreach ( cc_booking_status_labels() as $status => $status_label ) {
				echo '<option value="' . esc_attr( $status ) . '"' . selected( '' === $value ? 'confirmed' : $value, $status, false ) . '>' . esc_html( $status_label ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'textarea' === $type ) {
			echo '<textarea class="large-text" rows="3" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '">' . esc_textarea( $value ) . '</textarea>';
		} else {
			echo '<input type="' . esc_attr( $type ) . '" class="regular-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '"' . ( 'number' === $type ? ' min="1"' : '' ) . '>';
		}
		echo '</td></tr>';
	}
	echo '</table>';
	if ( $total ) {
		/* translators: %s: price */
		echo '<p><strong>' . esc_html( sprintf( __( 'Total quoted: %s', 'cobbleandcandle-core' ), cc_money( $total ) ) ) . '</strong></p>';
	}
	echo '<p class="description">' . esc_html__( 'Changing the status here does not email the guest. Use Confirm or Cancel in the Bookings list to send the email.', 'cobbleandcandle-core' ) . '</p>';
}

/**
 * Save the booking meta box.
 *
 * @param int $post_id Booking ID.
 */
function cc_save_booking_meta_box( $post_id ) {
	if ( ! isset( $_POST['cc_booking_save_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cc_booking_save_nonce'] ) ), 'cc_booking_save_' . $post_id )
		|| wp_is_post_autosave( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$clean = array();
	foreach ( cc_booking_fields() as $key => list( , $type ) ) {
		$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per type below.
		if ( 'room' === $type ) {
			$clean[ $key ] = 'cc_room' === get_post_type( absint( $raw ) ) ? absint( $raw ) : 0;
		} elseif ( 'date' === $type ) {
			$clean[ $key ] = cc_is_valid_date( sanitize_text_field( (string) $raw ) ) ? sanitize_text_field( (string) $raw ) : '';
		} elseif ( 'number' === $type ) {
			$clean[ $key ] = max( 1, absint( $raw ) );
		} elseif ( 'email' === $type ) {
			$clean[ $key ] = sanitize_email( (string) $raw );
		} elseif ( 'textarea' === $type ) {
			$clean[ $key ] = sanitize_textarea_field( (string) $raw );
		} elseif ( 'status' === $type ) {
			$clean[ $key ] = array_key_exists( (string) $raw, cc_booking_status_labels() ) ? (string) $raw : 'confirmed';
		} else {
			$clean[ $key ] = sanitize_text_field( (string) $raw );
		}
	}
	if ( in_array( $clean['cc_status'], cc_booking_holding_statuses(), true )
		&& '' !== cc_booking_conflict( $clean['cc_room'], $clean['cc_check_in'], $clean['cc_check_out'], $post_id ) ) {
		$clean['cc_status'] = 'confirmed' === $clean['cc_status'] ? 'pending' : $clean['cc_status'];
		set_transient( 'cc_booking_conflict_' . get_current_user_id(), $post_id, MINUTE_IN_SECONDS );
	}
	foreach ( $clean as $key => $value ) {
		update_post_meta( $post_id, $key, $value );
	}
	$room = cc_room( $clean['cc_room'] );
	if ( $room && $clean['cc_check_in'] && $clean['cc_check_out'] > $clean['cc_check_in'] ) {
		update_post_meta( $post_id, 'cc_total', cc_stay_total( $room, $clean['cc_check_in'], $clean['cc_check_out'] ) );
	}
	if ( ! get_post_meta( $post_id, 'cc_source', true ) ) {
		update_post_meta( $post_id, 'cc_source', 'manual' );
	}
}
add_action( 'save_post_cc_booking', 'cc_save_booking_meta_box' );

/**
 * Default title for bookings added by hand.
 *
 * @param array<string, mixed> $data    Post data.
 * @param array<string, mixed> $postarr Submitted data.
 * @return array<string, mixed>
 */
function cc_booking_default_title( $data, $postarr ) {
	if ( 'cc_booking' === $data['post_type'] && '' === trim( (string) $data['post_title'] ) && ! empty( $postarr['ID'] ) ) {
		/* translators: %d: booking ID */
		$data['post_title'] = sprintf( __( 'Booking #%d', 'cobbleandcandle-core' ), (int) $postarr['ID'] );
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'cc_booking_default_title', 10, 2 );

/**
 * Why a booking can't hold its nights: '' when it can, else 'invalid' or 'unavailable'.
 *
 * @param int    $room_id    Room post ID.
 * @param string $check_in   Y-m-d.
 * @param string $check_out  Y-m-d.
 * @param int    $booking_id Booking to leave out of the count.
 * @return string
 */
function cc_booking_conflict( $room_id, $check_in, $check_out, $booking_id = 0 ) {
	$room = cc_room( $room_id );
	if ( ! $room || ! cc_is_valid_date( $check_in ) || ! cc_is_valid_date( $check_out ) || $check_out <= $check_in ) {
		return 'invalid';
	}
	foreach ( cc_room_taken( $room['id'], $check_in, $check_out, $booking_id ) as $count ) {
		if ( $count >= $room['units'] ) {
			return 'unavailable';
		}
	}
	return '';
}

/**
 * Warn after a manual booking was saved over taken nights.
 */
function cc_booking_conflict_notice() {
	$key = 'cc_booking_conflict_' . get_current_user_id();
	if ( ! get_transient( $key ) ) {
		return;
	}
	delete_transient( $key );
	echo '<div class="notice notice-warning"><p>' . esc_html__( 'Saved as Pending: the room is missing, the dates are wrong, or those nights are already taken. Fix the dates before confirming.', 'cobbleandcandle-core' ) . '</p></div>';
}
add_action( 'admin_notices', 'cc_booking_conflict_notice' );

/**
 * Open (pending) requests from one email address.
 *
 * @param string $email Guest email.
 * @return int
 */
function cc_open_requests_for( $email ) {
	return count(
		get_posts(
			array(
				'post_type'      => 'cc_booking',
				'post_status'    => 'publish',
				'posts_per_page' => 5,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one lookup per booking request.
					array(
						'key'   => 'cc_email',
						'value' => $email,
					),
					array(
						'key'   => 'cc_status',
						'value' => 'pending',
					),
				),
			)
		)
	);
}

/**
 * Release requests the owner hasn't answered, so unanswered (or fake) requests can't hold nights forever.
 * Runs with the hourly calendar sync.
 */
function cc_expire_pending_bookings() {
	/**
	 * Hours a booking request holds its nights before it lapses.
	 *
	 * @param int $hours Default 48.
	 */
	$hours = max( 1, (int) apply_filters( 'cc_pending_hold_hours', 48 ) );
	$ids   = get_posts(
		array(
			'post_type'      => 'cc_booking',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'date_query'     => array( array( 'before' => $hours . ' hours ago' ) ),
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- hourly cron.
				array(
					'key'   => 'cc_status',
					'value' => 'pending',
				),
				array(
					'key'   => 'cc_source',
					'value' => 'site',
				),
			),
		)
	);
	foreach ( $ids as $id ) {
		update_post_meta( $id, 'cc_status', 'cancelled' );
		update_post_meta( $id, 'cc_expired', 1 );
	}
}
add_action( 'cc_ical_sync', 'cc_expire_pending_bookings' );

/**
 * Room booking requests: three per IP every 10 minutes.
 *
 * @param int    $limit Limit.
 * @param string $form  Form name.
 * @return int
 */
function cc_room_rate_limit( $limit, $form ) {
	return 'room' === $form ? min( $limit, 3 ) : $limit;
}
add_filter( 'cc_form_rate_limit', 'cc_room_rate_limit', 5, 2 );

/**
 * Privacy: export a guest's bookings (Tools → Export Personal Data).
 *
 * @param string $email Email address.
 * @return array{data: array<int, array<string, mixed>>, done: bool}
 */
function cc_privacy_export_bookings( $email ) {
	$data = array();
	foreach ( cc_bookings_for_email( $email ) as $id ) {
		$items = array();
		foreach ( cc_booking_fields() as $key => list( $label ) ) {
			$value   = 'cc_room' === $key ? cc_plain_title( (int) get_post_meta( $id, $key, true ) ) : (string) get_post_meta( $id, $key, true );
			$items[] = array(
				'name'  => $label,
				'value' => $value,
			);
		}
		$data[] = array(
			'group_id'    => 'cc-bookings',
			'group_label' => __( 'Room bookings', 'cobbleandcandle-core' ),
			'item_id'     => 'cc-booking-' . $id,
			'data'        => $items,
		);
	}
	return array(
		'data' => $data,
		'done' => true,
	);
}

/**
 * Privacy: erase a guest's details from their bookings (the dates stay, so the calendar stays right).
 *
 * @param string $email Email address.
 * @return array{items_removed: bool, items_retained: bool, messages: array<int, string>, done: bool}
 */
function cc_privacy_erase_bookings( $email ) {
	$ids = cc_bookings_for_email( $email );
	foreach ( $ids as $id ) {
		foreach ( array( 'cc_name', 'cc_email', 'cc_phone', 'cc_message' ) as $key ) {
			delete_post_meta( $id, $key );
		}
		wp_update_post(
			array(
				'ID'         => $id,
				/* translators: %d: booking ID */
				'post_title' => sprintf( __( 'Booking #%d', 'cobbleandcandle-core' ), $id ),
			)
		);
	}
	return array(
		'items_removed'  => (bool) $ids,
		'items_retained' => false,
		'messages'       => array(),
		'done'           => true,
	);
}

/**
 * Booking IDs for a guest email.
 *
 * @param string $email Email address.
 * @return array<int, int>
 */
function cc_bookings_for_email( $email ) {
	return get_posts(
		array(
			'post_type'      => 'cc_booking',
			'post_status'    => 'any',
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- privacy requests are rare.
				array(
					'key'   => 'cc_email',
					'value' => sanitize_email( $email ),
				),
			),
		)
	);
}

add_filter(
	'wp_privacy_personal_data_exporters',
	static function ( $exporters ) {
		$exporters['cobbleandcandle-bookings'] = array(
			'exporter_friendly_name' => __( 'Room bookings', 'cobbleandcandle-core' ),
			'callback'               => 'cc_privacy_export_bookings',
		);
		return $exporters;
	}
);
add_filter(
	'wp_privacy_personal_data_erasers',
	static function ( $erasers ) {
		$erasers['cobbleandcandle-bookings'] = array(
			'eraser_friendly_name' => __( 'Room bookings', 'cobbleandcandle-core' ),
			'callback'             => 'cc_privacy_erase_bookings',
		);
		return $erasers;
	}
);

