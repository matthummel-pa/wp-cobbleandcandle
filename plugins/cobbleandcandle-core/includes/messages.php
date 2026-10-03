<?php
/**
 * Messages: every table request, private-dining inquiry and contact message is kept in the dashboard
 * (Messages), so nothing is lost when email fails. Editors and Administrators only.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Message types: key => label.
 *
 * @return array<string, string>
 */
function cc_message_types() {
	return array(
		'reservation' => __( 'Table request', 'cobbleandcandle-core' ),
		'inquiry'     => __( 'Private dining', 'cobbleandcandle-core' ),
		'contact'     => __( 'Contact', 'cobbleandcandle-core' ),
	);
}

/**
 * Register the private Messages type.
 */
function cc_register_message_type() {
	register_post_type(
		'cc_message',
		array(
			'labels'          => array(
				'name'          => __( 'Messages', 'cobbleandcandle-core' ),
				'singular_name' => __( 'Message', 'cobbleandcandle-core' ),
				'edit_item'     => __( 'Message', 'cobbleandcandle-core' ),
				'all_items'     => __( 'Messages', 'cobbleandcandle-core' ),
				'not_found'     => __( 'No messages yet.', 'cobbleandcandle-core' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-email-alt',
			'menu_position'   => 26,
			'supports'        => false, // Read-only: the message is shown in a meta box, nothing to edit.
			'capability_type' => 'post',
			// Guest details: Editors and Administrators only. Messages only come from the site's forms.
			'capabilities'    => array_merge(
				array_fill_keys(
					array( 'edit_posts', 'edit_others_posts', 'edit_private_posts', 'edit_published_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_others_posts', 'delete_private_posts', 'delete_published_posts' ),
					'edit_others_posts'
				),
				array( 'create_posts' => 'do_not_allow' )
			),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'cc_register_message_type' );

/**
 * Keep a submission.
 *
 * @param string $type        Message type key.
 * @param string $subject     Email subject (used as the title).
 * @param string $body        Plain-text email body.
 * @param string $email       Sender email.
 * @param int    $location_id House, or 0.
 * @param bool   $sent        Whether the email went out.
 * @return int Message ID, or 0.
 */
function cc_store_message( $type, $subject, $body, $email, $location_id, $sent ) {
	// wp_insert_post() unslashes its input; slash so a guest's backslashes survive.
	$id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'cc_message',
				'post_status'  => 'private', // Never readable outside the dashboard.
				'post_title'   => wp_strip_all_tags( $subject ),
				'post_content' => $body,
				'meta_input'   => array(
					'cc_type'     => sanitize_key( $type ),
					'cc_email'    => sanitize_email( $email ),
					'cc_location' => (int) $location_id,
					'cc_sent'     => $sent ? 1 : 0,
				),
			)
		),
		true
	);
	delete_transient( 'cc_unsent_messages' );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * Messages list: columns.
 *
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function cc_message_columns( $columns ) {
	return array(
		'cb'          => $columns['cb'] ?? '',
		'title'       => __( 'Message', 'cobbleandcandle-core' ),
		'cc_type'     => __( 'Type', 'cobbleandcandle-core' ),
		'cc_location' => __( 'House', 'cobbleandcandle-core' ),
		'cc_sent'     => __( 'Emailed', 'cobbleandcandle-core' ),
		'date'        => __( 'Received', 'cobbleandcandle-core' ),
	);
}
add_filter( 'manage_cc_message_posts_columns', 'cc_message_columns' );

/**
 * Messages list: column values.
 *
 * @param string $column  Column.
 * @param int    $post_id Message ID.
 */
function cc_message_column( $column, $post_id ) {
	if ( 'cc_type' === $column ) {
		$types = cc_message_types();
		$type  = (string) get_post_meta( $post_id, 'cc_type', true );
		echo esc_html( $types[ $type ] ?? $type );
	} elseif ( 'cc_location' === $column ) {
		$location = (int) get_post_meta( $post_id, 'cc_location', true );
		echo esc_html( $location ? cc_plain_title( $location ) : '—' );
	} elseif ( 'cc_sent' === $column ) {
		echo get_post_meta( $post_id, 'cc_sent', true )
			? esc_html__( 'Yes', 'cobbleandcandle-core' )
			: '<strong>' . esc_html__( 'No: reply from here', 'cobbleandcandle-core' ) . '</strong>';
	}
}
add_action( 'manage_cc_message_posts_custom_column', 'cc_message_column', 10, 2 );

/**
 * Message screen: the message, read-only, with a reply link.
 */
function cc_message_meta_box() {
	add_meta_box(
		'cc-message',
		__( 'Message', 'cobbleandcandle-core' ),
		static function ( $post ) {
			$email = sanitize_email( (string) get_post_meta( $post->ID, 'cc_email', true ) );
			echo '<div style="white-space:pre-wrap;font-size:14px;line-height:1.6">' . esc_html( $post->post_content ) . '</div>';
			if ( $email ) {
				echo '<p><a class="button button-primary" href="' . esc_url( 'mailto:' . $email . '?subject=' . rawurlencode( 'Re: ' . $post->post_title ) ) . '">' . esc_html__( 'Reply by email', 'cobbleandcandle-core' ) . '</a></p>';
			}
		},
		'cc_message',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_cc_message', 'cc_message_meta_box' );

/**
 * Warn when messages couldn't be emailed (usually a mail setup problem on the host).
 */
function cc_unsent_messages_notice() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	$unsent = get_transient( 'cc_unsent_messages' );
	if ( false === $unsent ) {
		$unsent = cc_unsent_message_count();
		set_transient( 'cc_unsent_messages', $unsent, HOUR_IN_SECONDS );
	}
	if ( ! $unsent ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
		esc_html__( 'Some guest messages could not be emailed. They are saved under Messages. Check your site’s email setup (an SMTP plugin usually fixes this).', 'cobbleandcandle-core' ),
		esc_url( admin_url( 'edit.php?post_type=cc_message' ) ),
		esc_html__( 'View messages', 'cobbleandcandle-core' )
	);
}
add_action( 'admin_notices', 'cc_unsent_messages_notice' );

/**
 * Unsent messages in the last 14 days (0 or 1 is enough for the notice).
 *
 * @return int
 */
function cc_unsent_message_count() {
	$unsent = get_posts(
		array(
			'post_type'      => 'cc_message',
			'post_status'    => array( 'publish', 'private' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'date_query'     => array( array( 'after' => '14 days ago' ) ),
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one small admin lookup.
				array(
					'key'   => 'cc_sent',
					'value' => '0',
				),
			),
		)
	);
	return count( $unsent );
}

/**
 * Message IDs for a sender email.
 *
 * @param string $email Email address.
 * @param int    $page  1-based page of 100.
 * @return array<int, int>
 */
function cc_messages_for_email( $email, $page = 1 ) {
	return get_posts(
		array(
			'post_type'      => 'cc_message',
			'post_status'    => array_keys( get_post_stati() ), // Trashed messages too.
			'posts_per_page' => 100,
			'paged'          => max( 1, (int) $page ),
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
		$exporters['cobbleandcandle-messages'] = array(
			'exporter_friendly_name' => __( 'Guest messages', 'cobbleandcandle-core' ),
			'callback'               => static function ( $email, $page = 1 ) {
				$data = array();
				$ids  = cc_messages_for_email( $email, $page );
				foreach ( $ids as $id ) {
					$data[] = array(
						'group_id'    => 'cc-messages',
						'group_label' => __( 'Guest messages', 'cobbleandcandle-core' ),
						'item_id'     => 'cc-message-' . $id,
						'data'        => array(
							array(
								'name'  => __( 'Subject', 'cobbleandcandle-core' ),
								'value' => get_the_title( $id ),
							),
							array(
								'name'  => __( 'Message', 'cobbleandcandle-core' ),
								'value' => get_post_field( 'post_content', $id ),
							),
							array(
								'name'  => __( 'Received', 'cobbleandcandle-core' ),
								'value' => get_the_date( 'Y-m-d H:i', $id ),
							),
						),
					);
				}
				return array(
					'data' => $data,
					'done' => count( $ids ) < 100,
				);
			},
		);
		return $exporters;
	}
);

add_filter(
	'wp_privacy_personal_data_erasers',
	static function ( $erasers ) {
		$erasers['cobbleandcandle-messages'] = array(
			'eraser_friendly_name' => __( 'Guest messages', 'cobbleandcandle-core' ),
			'callback'             => static function ( $email ) {
				$ids = cc_messages_for_email( $email ); // Always page 1: erased rows drop out of the next query.
				foreach ( $ids as $id ) {
					wp_delete_post( $id, true ); // A message is all personal data: erase it whole.
				}
				return array(
					'items_removed'  => (bool) $ids,
					'items_retained' => false,
					'messages'       => array(),
					'done'           => count( $ids ) < 100,
				);
			},
		);
		return $erasers;
	}
);

/**
 * Daily: delete messages older than the retention setting (Settings → Restaurant, default 12 months;
 * 0 keeps them forever). Only Messages: bookings and content are never touched.
 */
function cc_prune_messages() {
	$months = (int) cc_setting( 'message_months', '12' );
	if ( $months < 1 ) {
		return;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'cc_message',
			'post_status'    => array_keys( get_post_stati() ),
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'date_query'     => array( array( 'before' => $months . ' months ago' ) ),
		)
	);
	foreach ( $ids as $id ) {
		if ( 'cc_message' === get_post_type( $id ) ) {
			wp_delete_post( $id, true );
		}
	}
}
add_action( 'cc_prune_messages', 'cc_prune_messages' );

/**
 * Keep the daily clean-up scheduled.
 */
function cc_schedule_message_prune() {
	if ( ! wp_next_scheduled( 'cc_prune_messages' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'cc_prune_messages' );
	}
}
add_action( 'init', 'cc_schedule_message_prune' );

/**
 * Suggested privacy-policy text (Settings → Privacy → Policy Guide).
 */
function cc_privacy_policy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}
	$months = (int) cc_setting( 'message_months', '12' );
	$keep   = $months > 0
		/* translators: %d: number of months */
		? sprintf( _n( 'Messages are deleted automatically after %d month.', 'Messages are deleted automatically after %d months.', $months, 'cobbleandcandle-core' ), $months )
		: __( 'Messages are kept until we delete them.', 'cobbleandcandle-core' );
	wp_add_privacy_policy_content(
		__( 'Cobble & Candle Core', 'cobbleandcandle-core' ),
		wp_kses_post(
			'<p>' . __( 'When you request a table, a room or a private dining event, or send us a message, we keep the details you enter (name, email, phone, dates and your message) so we can answer and manage your booking.', 'cobbleandcandle-core' ) . '</p><p>'
			. $keep . ' ' . __( 'You can ask us for a copy of your data or to erase it.', 'cobbleandcandle-core' ) . '</p>'
		)
	);
}
add_action( 'admin_init', 'cc_privacy_policy_content' );

