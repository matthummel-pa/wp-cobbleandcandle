<?php
/**
 * Setup wizard (Settings → Restaurant setup): five short steps from a fresh install to a working
 * site, with no WP-CLI. Each step saves on its own, so owners can stop and come back. It never
 * deletes or overwrites content: pages, menus and posts that already exist are kept.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wizard URL (optionally at a step).
 *
 * @param string $step Step key.
 * @return string
 */
function cobble_setup_url( $step = '' ) {
	$url = admin_url( 'options-general.php?page=cobble-setup' );
	return '' === $step ? $url : add_query_arg( 'step', $step, $url );
}

/**
 * Steps: key => label.
 *
 * @return array<string, string>
 */
function cobble_setup_steps() {
	return array(
		'place'   => __( 'Your place', 'cobbleandcandle-core' ),
		'look'    => __( 'Look', 'cobbleandcandle-core' ),
		'content' => __( 'Content', 'cobbleandcandle-core' ),
		'pages'   => __( 'Pages & menus', 'cobbleandcandle-core' ),
		'done'    => __( 'Done', 'cobbleandcandle-core' ),
	);
}

/**
 * Wizard state: business kinds and finished steps.
 *
 * @return array{kinds: array<int, string>, done: array<int, string>, complete: bool}
 */
function cobble_setup_state() {
	$state = get_option( 'cobble_setup', array() );
	$state = is_array( $state ) ? $state : array();
	return array(
		'kinds'    => array_values( array_intersect( (array) ( $state['kinds'] ?? array( 'restaurant' ) ), array( 'restaurant', 'bar', 'rooms' ) ) ),
		'done'     => array_values( array_intersect( (array) ( $state['done'] ?? array() ), array_keys( cobble_setup_steps() ) ) ),
		'complete' => ! empty( $state['complete'] ),
	);
}

/**
 * Save part of the wizard state.
 *
 * @param array<string, mixed> $changes Changes.
 */
function cobble_setup_update( array $changes ) {
	update_option( 'cobble_setup', array_merge( cobble_setup_state(), $changes ), false );
}

/**
 * Register the wizard screen.
 */
function cobble_setup_menu() {
	add_options_page( __( 'Restaurant setup', 'cobbleandcandle-core' ), __( 'Restaurant setup', 'cobbleandcandle-core' ), 'manage_options', 'cobble-setup', 'cobble_render_setup_page' );
}
add_action( 'admin_menu', 'cobble_setup_menu' );

/**
 * Nudge administrators until setup is finished or skipped.
 */
function cobble_setup_notice() {
	// Sites that were set up before the wizard existed (they already have locations) count as done.
	if ( false === get_option( 'cobble_setup', false ) && get_posts( array( 'post_type' => 'cobble_location', 'posts_per_page' => 1, 'fields' => 'ids', 'post_status' => 'any' ) ) ) {
		cobble_setup_update( array( 'complete' => true ) );
		return;
	}
	$screen = get_current_screen();
	if ( ! current_user_can( 'manage_options' ) || cobble_setup_state()['complete'] || ( $screen && ( 'settings_page_cobble-setup' === $screen->id || $screen->is_block_editor() ) ) ) {
		return;
	}
	$skip = wp_nonce_url( admin_url( 'admin-post.php?action=cobble_setup_skip' ), 'cobble_setup_skip' );
	printf(
		'<div class="notice notice-info"><p><strong>%1$s</strong> %2$s</p><p><a class="button button-primary" href="%3$s">%4$s</a> <a class="button-link" href="%5$s">%6$s</a></p></div>',
		esc_html__( 'Welcome to Cobble & Candle.', 'cobbleandcandle-core' ),
		esc_html__( 'Five short steps set up your brand, your first location, your pages and menus. You can stop at any point.', 'cobbleandcandle-core' ),
		esc_url( cobble_setup_url() ),
		esc_html__( 'Start setup', 'cobbleandcandle-core' ),
		esc_url( $skip ),
		esc_html__( 'I’ll set up by hand', 'cobbleandcandle-core' )
	);
}
add_action( 'admin_notices', 'cobble_setup_notice' );

/**
 * Hide the notice for good.
 */
function cobble_setup_skip() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'cobbleandcandle-core' ), 403 );
	}
	check_admin_referer( 'cobble_setup_skip' );
	cobble_setup_update( array( 'complete' => true ) );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
	exit;
}
add_action( 'admin_post_cobble_setup_skip', 'cobble_setup_skip' );

/**
 * Style directions offered by the active theme: slug => [label, mood, swatches].
 *
 * @return array<string, array{label: string, title: string, sw: array{0: string, 1: string}}>
 */
function cobble_setup_directions() {
	if ( function_exists( 'App\\directions' ) ) {
		return (array) call_user_func( 'App\\directions' );
	}
	return array();
}

/**
 * The active style direction ('' when the theme has none).
 *
 * @return string
 */
function cobble_setup_current_direction() {
	return function_exists( 'App\\direction' ) ? (string) call_user_func( 'App\\direction' ) : '';
}

/**
 * Apply a style direction the way the Site Editor does: write the variation into the user's
 * global styles. Lampwright (the default) clears them.
 *
 * @param string $slug Direction slug.
 * @return bool
 */
function cobble_setup_apply_direction( $slug ) {
	if ( ! array_key_exists( $slug, cobble_setup_directions() ) || ! class_exists( 'WP_Theme_JSON_Resolver' ) ) {
		return false;
	}
	$data = array(
		'version'                     => 3,
		'isGlobalStylesUserThemeJSON' => true,
	);
	$file = get_theme_file_path( 'styles/' . $slug . '.json' );
	if ( is_readable( $file ) ) {
		$json = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- theme file.
		if ( is_array( $json ) ) {
			$data['settings'] = $json['settings'] ?? array();
			$data['styles']   = $json['styles'] ?? array();
		}
	}
	$post_id = WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
	if ( ! $post_id ) {
		return false;
	}
	$saved = wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => wp_slash( (string) wp_json_encode( $data ) ),
		),
		true
	);
	if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
		wp_clean_theme_json_cache();
	}
	return ! is_wp_error( $saved );
}

/**
 * A theme pattern's block markup ('' when the theme doesn't provide it).
 *
 * @param string $slug Pattern slug.
 * @return string
 */
function cobble_setup_pattern( $slug ) {
	$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( $slug );
	return $pattern ? (string) $pattern['content'] : '';
}

/**
 * Pages the wizard can create: key => [title, slug, pattern, excerpt].
 *
 * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
 */
function cobble_setup_pages() {
	return array(
		'home'         => array( __( 'Home', 'cobbleandcandle-core' ), 'home', '', '' ),
		'menu'         => array( __( 'Menu', 'cobbleandcandle-core' ), 'menu', 'cobbleandcandle/page-menu', __( 'Written each morning with the growers on the phone. Filter by diet, or jump to a section.', 'cobbleandcandle-core' ) ),
		'reservations' => array( __( 'Reservations', 'cobbleandcandle-core' ), 'reservations', 'cobbleandcandle/page-reservations', __( 'Choose a house, then a time. For larger groups, ask about private dining.', 'cobbleandcandle-core' ) ),
		'gallery'      => array( __( 'Gallery', 'cobbleandcandle-core' ), 'gallery', 'cobbleandcandle/page-gallery', __( 'Rooms, plates and people. Tap any image to enlarge.', 'cobbleandcandle-core' ) ),
		'story'        => array( __( 'Our story', 'cobbleandcandle-core' ), 'story', 'cobbleandcandle/page-about', __( 'Who we are and how we cook.', 'cobbleandcandle-core' ) ),
	);
}

/**
 * Find a page by slug, or create it from the theme pattern.
 *
 * @param string                                   $key  Page key.
 * @param array{0: string, 1: string, 2: string, 3: string} $page Page definition.
 * @return array{id: int, created: bool}
 */
function cobble_setup_page( $key, array $page ) {
	list( $title, $slug, $pattern, $excerpt ) = $page;
	$found = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $found && 'trash' !== $found->post_status ) {
		return array(
			'id'      => (int) $found->ID,
			'created' => false,
		);
	}
	$id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_excerpt' => $excerpt,
				'post_content' => '' !== $pattern ? cobble_setup_pattern( $pattern ) : '',
			)
		),
		true
	);
	return array(
		'id'      => is_wp_error( $id ) ? 0 : (int) $id,
		'created' => ! is_wp_error( $id ),
	);
}

/**
 * Find or create a nav menu and assign it to a theme location. Returns [id, was_empty].
 *
 * @param string $name     Menu name.
 * @param string $location Theme location.
 * @return array{0: int, 1: bool}
 */
function cobble_setup_nav_menu( $name, $location ) {
	$menu = wp_get_nav_menu_object( $name );
	$id   = $menu ? (int) $menu->term_id : wp_create_nav_menu( $name );
	if ( is_wp_error( $id ) || ! $id ) {
		return array( 0, false );
	}
	$id        = (int) $id;
	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
	$current   = (int) ( $locations[ $location ] ?? 0 );
	// Never replace a menu the owner assigned; fill the location only when empty or pointing at a deleted menu.
	$locations[ $location ] = $current && is_nav_menu( $current ) ? $current : $id;
	set_theme_mod( 'nav_menu_locations', $locations );
	return array( (int) $locations[ $location ], ! wp_get_nav_menu_items( (int) $locations[ $location ] ) );
}

/**
 * Add a link to a nav menu.
 *
 * @param int    $menu_id Menu.
 * @param string $title   Label.
 * @param int    $page_id Page, or 0 for a custom URL.
 * @param string $url     Custom URL.
 */
function cobble_setup_menu_item( $menu_id, $title, $page_id = 0, $url = '' ) {
	if ( ! $page_id && '' === $url ) {
		return;
	}
	wp_update_nav_menu_item(
		$menu_id,
		0,
		$page_id
			? array(
				'menu-item-title'     => $title,
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $page_id,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
			: array(
				'menu-item-title'  => $title,
				'menu-item-url'    => $url,
				'menu-item-type'   => 'custom',
				'menu-item-status' => 'publish',
			)
	);
}

/**
 * Save a step, then move on.
 */
function cobble_setup_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to run setup.', 'cobbleandcandle-core' ), 403 );
	}
	$step = isset( $_POST['cobble_step'] ) ? sanitize_key( wp_unslash( $_POST['cobble_step'] ) ) : '';
	if ( ! array_key_exists( $step, cobble_setup_steps() ) ) {
		wp_die( esc_html__( 'Unknown setup step.', 'cobbleandcandle-core' ), 400 );
	}
	check_admin_referer( 'cobble_setup_' . $step );
	$state  = cobble_setup_state();
	$notice = '';

	if ( 'place' === $step ) {
		$name = isset( $_POST['cobble_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cobble_name'] ) ) : '';
		if ( '' !== $name ) {
			update_option( 'blogname', $name );
		}
		if ( isset( $_POST['cobble_tagline'] ) ) {
			update_option( 'blogdescription', sanitize_text_field( wp_unslash( $_POST['cobble_tagline'] ) ) );
		}
		$kinds = isset( $_POST['cobble_kinds'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['cobble_kinds'] ) ) : array();
		$kinds = array_values( array_intersect( $kinds, array( 'restaurant', 'bar', 'rooms' ) ) );
		cobble_setup_update( array( 'kinds' => $kinds ? $kinds : array( 'restaurant' ) ) );
	} elseif ( 'look' === $step ) {
		$brand = get_option( 'cobbleandcandle_brand', array() );
		$brand = is_array( $brand ) ? $brand : array();
		foreach ( array( 'tagline', 'est', 'currency' ) as $key ) {
			if ( isset( $_POST[ 'cc_' . $key ] ) ) {
				$brand[ $key ] = sanitize_text_field( wp_unslash( $_POST[ 'cc_' . $key ] ) );
			}
		}
		update_option( 'cobbleandcandle_brand', cobble_sanitize_settings( $brand ) );
		$clock = isset( $_POST['cobble_clock'] ) ? sanitize_key( wp_unslash( $_POST['cobble_clock'] ) ) : '';
		$is24  = (bool) preg_match( '/[GH]/', (string) get_option( 'time_format' ) );
		if ( '24' === $clock && ! $is24 ) { // Only when the clock actually changes: a custom format is kept.
			update_option( 'time_format', 'H:i' );
		} elseif ( '12' === $clock && $is24 ) {
			update_option( 'time_format', 'g:i a' );
		}
		$direction = isset( $_POST['cobble_direction'] ) ? sanitize_key( wp_unslash( $_POST['cobble_direction'] ) ) : '';
		if ( '' !== $direction && cobble_setup_current_direction() !== $direction && current_user_can( 'edit_theme_options' ) ) {
			cobble_setup_apply_direction( $direction );
		}
	} elseif ( 'content' === $step ) {
		$mode = isset( $_POST['cobble_content'] ) ? sanitize_key( wp_unslash( $_POST['cobble_content'] ) ) : '';
		if ( 'demo' === $mode ) {
			$result = cobble_import_demo( false );
			$notice = is_wp_error( $result ) ? 'demo-failed' : 'demo';
		} elseif ( 'own' === $mode ) {
			$name = isset( $_POST['cobble_loc_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cobble_loc_name'] ) ) : '';
			if ( '' !== $name ) {
				$fields = array();
				foreach ( array( 'street', 'locality', 'phone' ) as $key ) {
					$fields[ 'cc_' . $key ] = isset( $_POST[ 'cobble_loc_' . $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'cobble_loc_' . $key ] ) ) : '';
				}
				$fields['cobble_email'] = isset( $_POST['cobble_loc_email'] ) ? sanitize_email( wp_unslash( $_POST['cobble_loc_email'] ) ) : '';
				$existing           = get_posts(
					array(
						'post_type'      => 'cobble_location',
						'title'          => $name,
						'post_status'    => 'any',
						'posts_per_page' => 1,
						'fields'         => 'ids',
					)
				);
				if ( ! $existing ) {
					wp_insert_post(
						wp_slash(
							array(
								'post_type'   => 'cobble_location',
								'post_status' => 'publish',
								'post_title'  => $name,
								'meta_input'  => $fields,
							)
						)
					);
				}
				$notice = 'location';
			}
		}
	} elseif ( 'pages' === $step ) {
		$wanted = isset( $_POST['cobble_pages'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['cobble_pages'] ) ) : array();
		$ids    = array();
		foreach ( cobble_setup_pages() as $key => $page ) {
			if ( in_array( $key, $wanted, true ) ) {
				$ids[ $key ] = cobble_setup_page( $key, $page )['id'];
			}
		}
		$has_front = 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' );
		if ( ! empty( $ids['home'] ) && ! empty( $_POST['cobble_front'] ) && ! $has_front && current_user_can( 'edit_theme_options' ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $ids['home'] );
		}
		if ( ! empty( $_POST['cobble_menus'] ) && current_user_can( 'edit_theme_options' ) ) {
			$registered = get_registered_nav_menus();
			$events     = post_type_exists( 'cobble_event' ) ? (string) get_post_type_archive_link( 'cobble_event' ) : '';
			$rooms      = in_array( 'rooms', $state['kinds'], true ) && post_type_exists( 'cobble_room' ) ? (string) get_post_type_archive_link( 'cobble_room' ) : '';
			$locations  = post_type_exists( 'cobble_location' ) ? (string) get_post_type_archive_link( 'cobble_location' ) : '';
			if ( isset( $registered['primary_navigation'] ) ) {
				list( $menu, $empty ) = cobble_setup_nav_menu( __( 'Primary', 'cobbleandcandle-core' ), 'primary_navigation' );
				if ( $menu && $empty ) { // Only fill empty menus: re-running setup never duplicates links.
					cobble_setup_menu_item( $menu, __( 'Menu', 'cobbleandcandle-core' ), $ids['menu'] ?? 0 );
					cobble_setup_menu_item( $menu, __( 'Rooms', 'cobbleandcandle-core' ), 0, $rooms );
					cobble_setup_menu_item( $menu, __( 'Events', 'cobbleandcandle-core' ), 0, $events );
					cobble_setup_menu_item( $menu, __( 'Gallery', 'cobbleandcandle-core' ), $ids['gallery'] ?? 0 );
					cobble_setup_menu_item( $menu, __( 'Story', 'cobbleandcandle-core' ), $ids['story'] ?? 0 );
				}
			}
			if ( isset( $registered['secondary_navigation'] ) ) {
				list( $menu, $empty ) = cobble_setup_nav_menu( __( 'Right', 'cobbleandcandle-core' ), 'secondary_navigation' );
				if ( $menu && $empty ) {
					cobble_setup_menu_item( $menu, __( 'Locations', 'cobbleandcandle-core' ), 0, $locations );
				}
			}
			if ( isset( $registered['footer_navigation'] ) ) {
				list( $menu, $empty ) = cobble_setup_nav_menu( __( 'Footer', 'cobbleandcandle-core' ), 'footer_navigation' );
				if ( $menu && $empty ) {
					cobble_setup_menu_item( $menu, __( 'Menu', 'cobbleandcandle-core' ), $ids['menu'] ?? 0 );
					cobble_setup_menu_item( $menu, __( 'Reservations', 'cobbleandcandle-core' ), $ids['reservations'] ?? 0 );
					cobble_setup_menu_item( $menu, __( 'Rooms', 'cobbleandcandle-core' ), 0, $rooms );
					cobble_setup_menu_item( $menu, __( 'Events', 'cobbleandcandle-core' ), 0, $events );
					cobble_setup_menu_item( $menu, __( 'Locations', 'cobbleandcandle-core' ), 0, $locations );
					cobble_setup_menu_item( $menu, __( 'Gallery', 'cobbleandcandle-core' ), $ids['gallery'] ?? 0 );
					cobble_setup_menu_item( $menu, __( 'Our story', 'cobbleandcandle-core' ), $ids['story'] ?? 0 );
				}
			}
		}
		flush_rewrite_rules( false );
	}

	$keys = array_keys( cobble_setup_steps() );
	$next = $keys[ min( count( $keys ) - 1, (int) array_search( $step, $keys, true ) + 1 ) ];
	cobble_setup_update(
		array(
			'done'     => array_values( array_unique( array_merge( cobble_setup_state()['done'], array( $step ) ) ) ),
			'complete' => 'pages' === $step || cobble_setup_state()['complete'],
		)
	);
	wp_safe_redirect( add_query_arg( array_filter( array( 'notice' => $notice ) ), cobble_setup_url( $next ) ) );
	exit;
}
add_action( 'admin_post_cobble_setup_save', 'cobble_setup_save' );

/**
 * Open a step form.
 *
 * @param string $step Step key.
 */
function cobble_setup_form_open( $step ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="cobble_setup_save"><input type="hidden" name="cobble_step" value="' . esc_attr( $step ) . '">';
	wp_nonce_field( 'cobble_setup_' . $step );
}

/**
 * Close a step form with Back / Continue.
 *
 * @param string $step  Step key.
 * @param string $label Continue label.
 */
function cobble_setup_form_close( $step, $label = '' ) {
	$keys = array_keys( cobble_setup_steps() );
	$i    = (int) array_search( $step, $keys, true );
	echo '<p class="cobble-actions">';
	submit_button( '' !== $label ? $label : __( 'Save and continue', 'cobbleandcandle-core' ), 'primary', 'submit', false );
	if ( $i > 0 ) {
		echo ' <a class="button" href="' . esc_url( cobble_setup_url( $keys[ $i - 1 ] ) ) . '">' . esc_html__( 'Back', 'cobbleandcandle-core' ) . '</a>';
	}
	echo ' <a class="button-link" href="' . esc_url( cobble_setup_url( $keys[ min( $i + 1, count( $keys ) - 1 ) ] ) ) . '">' . esc_html__( 'Skip this step', 'cobbleandcandle-core' ) . '</a>';
	echo '</p></form>';
}

/**
 * The wizard screen.
 */
function cobble_render_setup_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$steps = cobble_setup_steps();
	$state = cobble_setup_state();
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- navigation and status flags only.
	$step   = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : 'place';
	$notice = isset( $_GET['notice'] ) ? sanitize_key( wp_unslash( $_GET['notice'] ) ) : '';
	// phpcs:enable
	$step  = array_key_exists( $step, $steps ) ? $step : 'place';
	$brand = get_option( 'cobbleandcandle_brand', array() );
	$brand = is_array( $brand ) ? $brand : array();
	?>
	<div class="wrap cobble-admin">
		<?php cobble_admin_header( __( 'Restaurant setup', 'cobbleandcandle-core' ), __( 'Five short steps from a fresh install to a working site. Nothing you already have is deleted or overwritten.', 'cobbleandcandle-core' ), cobble_setup_url() ); ?>
		<ol class="cobble-steps" aria-label="<?php esc_attr_e( 'Setup steps', 'cobbleandcandle-core' ); ?>">
			<?php foreach ( $steps as $key => $label ) : ?>
				<li class="<?php echo in_array( $key, $state['done'], true ) ? 'is-done' : ''; ?>"<?php echo $key === $step ? ' aria-current="step"' : ''; ?>>
					<a href="<?php echo esc_url( cobble_setup_url( $key ) ); ?>"><?php echo esc_html( $label ); ?></a>
				</li>
			<?php endforeach; ?>
		</ol>

		<?php if ( 'demo' === $notice ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Demo locations, menus, events and rooms are in. Replace them with your own whenever you like.', 'cobbleandcandle-core' ); ?></p></div>
		<?php elseif ( 'demo-failed' === $notice ) : ?>
			<div class="notice notice-error inline"><p><?php esc_html_e( 'The demo content could not be loaded. Reinstall Cobble & Candle Core and try again.', 'cobbleandcandle-core' ); ?></p></div>
		<?php elseif ( 'location' === $notice ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Your location is saved. Add its opening hours under Locations.', 'cobbleandcandle-core' ); ?></p></div>
		<?php endif; ?>

		<section class="cobble-card">
		<?php if ( 'place' === $step ) : ?>
			<h2><?php esc_html_e( 'Tell us about your place', 'cobbleandcandle-core' ); ?></h2>
			<?php cobble_setup_form_open( 'place' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="cobble-name"><?php esc_html_e( 'Name', 'cobbleandcandle-core' ); ?></label></th>
					<td><input type="text" class="regular-text" id="cobble-name" name="cobble_name" value="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" required></td></tr>
				<tr><th scope="row"><label for="cobble-tagline"><?php esc_html_e( 'One-line description', 'cobbleandcandle-core' ); ?></label></th>
					<td><input type="text" class="large-text" id="cobble-tagline" name="cobble_tagline" value="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>">
					<p class="description"><?php esc_html_e( 'Used in search results and when your site is shared.', 'cobbleandcandle-core' ); ?></p></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'What do you run?', 'cobbleandcandle-core' ); ?></th>
					<td><fieldset><legend class="screen-reader-text"><?php esc_html_e( 'What do you run?', 'cobbleandcandle-core' ); ?></legend>
					<?php
					foreach ( array(
						'restaurant' => __( 'Restaurant', 'cobbleandcandle-core' ),
						'bar'        => __( 'Bar or tavern', 'cobbleandcandle-core' ),
						'rooms'      => __( 'Rooms to stay (B&B or inn)', 'cobbleandcandle-core' ),
					) as $kind => $label ) :
						?>
						<label style="display:block;margin:4px 0"><input type="checkbox" name="cobble_kinds[]" value="<?php echo esc_attr( $kind ); ?>" <?php checked( in_array( $kind, $state['kinds'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
					<?php endforeach; ?>
					</fieldset></td></tr>
			</table>
			<?php cobble_setup_form_close( 'place' ); ?>

		<?php elseif ( 'look' === $step ) : ?>
			<h2><?php esc_html_e( 'Choose your look', 'cobbleandcandle-core' ); ?></h2>
			<?php cobble_setup_form_open( 'look' ); ?>
			<table class="form-table" role="presentation">
				<?php $directions = cobble_setup_directions(); ?>
				<?php if ( $directions ) : ?>
					<tr><th scope="row"><?php esc_html_e( 'Style', 'cobbleandcandle-core' ); ?></th>
						<td><fieldset class="cobble-styles"><legend class="screen-reader-text"><?php esc_html_e( 'Style', 'cobbleandcandle-core' ); ?></legend>
						<?php foreach ( $directions as $slug => $dir ) : ?>
							<label class="cobble-style">
								<input type="radio" name="cobble_direction" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $slug, cobble_setup_current_direction() ); ?>>
								<span class="cobble-style-sw" style="background:linear-gradient(135deg,<?php echo esc_attr( (string) sanitize_hex_color( $dir['sw'][0] ) ); ?> 55%,<?php echo esc_attr( (string) sanitize_hex_color( $dir['sw'][1] ) ); ?> 55%)"></span>
								<strong><?php echo esc_html( $dir['label'] ); ?></strong> <span class="description"><?php echo esc_html( $dir['title'] ); ?></span>
							</label>
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'Changing style replaces colour and font changes made in the Site Editor (earlier versions stay in Appearance → Editor → Styles → Revisions). Keep the current style to leave them as they are.', 'cobbleandcandle-core' ); ?></p>
						</fieldset></td></tr>
				<?php endif; ?>
				<tr><th scope="row"><label for="cobble-brandline"><?php esc_html_e( 'Brand line', 'cobbleandcandle-core' ); ?></label></th>
					<td><input type="text" class="regular-text" id="cobble-brandline" name="cobble_tagline" value="<?php echo esc_attr( (string) ( $brand['tagline'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Tavern & kitchen', 'cobbleandcandle-core' ); ?>"></td></tr>
				<tr><th scope="row"><label for="cobble-est"><?php esc_html_e( 'Established (year)', 'cobbleandcandle-core' ); ?></label></th>
					<td><input type="text" inputmode="numeric" class="small-text" id="cobble-est" name="cobble_est" value="<?php echo esc_attr( (string) ( $brand['est'] ?? '' ) ); ?>"></td></tr>
				<tr><th scope="row"><label for="cobble-currency"><?php esc_html_e( 'Currency', 'cobbleandcandle-core' ); ?></label></th>
					<td><input type="text" class="small-text" id="cobble-currency" name="cobble_currency" maxlength="3" value="<?php echo esc_attr( (string) ( $brand['currency'] ?? 'USD' ) ); ?>">
					<p class="description"><?php esc_html_e( 'Three-letter code: USD, GBP, EUR, CAD, AUD…', 'cobbleandcandle-core' ); ?></p></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Clock', 'cobbleandcandle-core' ); ?></th>
					<td><fieldset><legend class="screen-reader-text"><?php esc_html_e( 'Clock', 'cobbleandcandle-core' ); ?></legend>
					<?php $is24 = (bool) preg_match( '/[GH]/', (string) get_option( 'time_format' ) ); ?>
					<label><input type="radio" name="cobble_clock" value="12" <?php checked( ! $is24 ); ?>> <?php esc_html_e( '12-hour (7:30 pm)', 'cobbleandcandle-core' ); ?></label>&nbsp;&nbsp;
					<label><input type="radio" name="cobble_clock" value="24" <?php checked( $is24 ); ?>> <?php esc_html_e( '24-hour (19:30)', 'cobbleandcandle-core' ); ?></label>
					</fieldset></td></tr>
			</table>
			<p class="description"><?php esc_html_e( 'Upload your logo and add social profiles under Settings → Restaurant.', 'cobbleandcandle-core' ); ?></p>
			<?php cobble_setup_form_close( 'look' ); ?>

		<?php elseif ( 'content' === $step ) : ?>
			<?php $has_locations = (bool) get_posts( array( 'post_type' => 'cobble_location', 'posts_per_page' => 1, 'fields' => 'ids' ) ); ?>
			<h2><?php esc_html_e( 'Add your content', 'cobbleandcandle-core' ); ?></h2>
			<?php cobble_setup_form_open( 'content' ); ?>
			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'Content', 'cobbleandcandle-core' ); ?></legend>
				<p><label><input type="radio" name="cobble_content" value="demo" <?php checked( ! $has_locations ); ?>> <strong><?php esc_html_e( 'Import the demo', 'cobbleandcandle-core' ); ?></strong></label><br>
				<span class="description"><?php esc_html_e( 'Three locations with hours, four menus, events and four rooms, as on the live demo. Edit or delete them later. Existing posts with the same names are kept.', 'cobbleandcandle-core' ); ?></span></p>
				<p><label><input type="radio" name="cobble_content" value="own"> <strong><?php esc_html_e( 'Start with my own location', 'cobbleandcandle-core' ); ?></strong></label></p>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label for="cobble-loc-name"><?php esc_html_e( 'Location name', 'cobbleandcandle-core' ); ?></label></th><td><input type="text" class="regular-text" id="cobble-loc-name" name="cobble_loc_name"></td></tr>
					<tr><th scope="row"><label for="cobble-loc-street"><?php esc_html_e( 'Street', 'cobbleandcandle-core' ); ?></label></th><td><input type="text" class="regular-text" id="cobble-loc-street" name="cobble_loc_street" autocomplete="street-address"></td></tr>
					<tr><th scope="row"><label for="cobble-loc-locality"><?php esc_html_e( 'Town or area', 'cobbleandcandle-core' ); ?></label></th><td><input type="text" class="regular-text" id="cobble-loc-locality" name="cobble_loc_locality"></td></tr>
					<tr><th scope="row"><label for="cobble-loc-phone"><?php esc_html_e( 'Phone', 'cobbleandcandle-core' ); ?></label></th><td><input type="tel" class="regular-text" id="cobble-loc-phone" name="cobble_loc_phone"></td></tr>
					<tr><th scope="row"><label for="cobble-loc-email"><?php esc_html_e( 'Bookings email', 'cobbleandcandle-core' ); ?></label></th><td><input type="email" class="regular-text" id="cobble-loc-email" name="cobble_loc_email"></td></tr>
				</table>
				<p><label><input type="radio" name="cobble_content" value="none" <?php checked( $has_locations ); ?>> <?php esc_html_e( 'I already have my content', 'cobbleandcandle-core' ); ?></label></p>
			</fieldset>
			<p class="description"><?php esc_html_e( 'Got a menu in a spreadsheet? Import it next from Food & drink → Import / export.', 'cobbleandcandle-core' ); ?></p>
			<?php cobble_setup_form_close( 'content' ); ?>

		<?php elseif ( 'pages' === $step ) : ?>
			<h2><?php esc_html_e( 'Pages & menus', 'cobbleandcandle-core' ); ?></h2>
			<?php cobble_setup_form_open( 'pages' ); ?>
			<fieldset>
				<legend><?php esc_html_e( 'Create these pages (ones you already have are kept):', 'cobbleandcandle-core' ); ?></legend>
				<?php foreach ( cobble_setup_pages() as $key => list( $title, $slug ) ) : ?>
					<?php $exists = get_page_by_path( $slug, OBJECT, 'page' ); ?>
					<label style="display:block;margin:6px 0"><input type="checkbox" name="cobble_pages[]" value="<?php echo esc_attr( $key ); ?>" checked> <?php echo esc_html( $title ); ?>
					<?php if ( $exists ) : ?><span class="description">— <?php esc_html_e( 'already exists, kept as is', 'cobbleandcandle-core' ); ?></span><?php endif; ?></label>
				<?php endforeach; ?>
			</fieldset>
			<?php if ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) ) : ?>
				<p class="description"><?php esc_html_e( 'Your site already has a front page, so it is kept.', 'cobbleandcandle-core' ); ?></p>
			<?php else : ?>
				<p><label><input type="checkbox" name="cobble_front" value="1" checked> <?php esc_html_e( 'Use Home as the front page', 'cobbleandcandle-core' ); ?></label></p>
			<?php endif; ?>
			<p><label><input type="checkbox" name="cobble_menus" value="1" checked> <?php esc_html_e( 'Set up the header and footer menus (only menus that are still empty)', 'cobbleandcandle-core' ); ?></label></p>
			<?php cobble_setup_form_close( 'pages', __( 'Create pages and finish', 'cobbleandcandle-core' ) ); ?>

		<?php else : ?>
			<h2><?php esc_html_e( 'You’re open', 'cobbleandcandle-core' ); ?></h2>
			<p><?php esc_html_e( 'Your site is set up. A few good next steps:', 'cobbleandcandle-core' ); ?></p>
			<ul style="list-style:disc;margin-left:20px">
				<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=cobble_location' ) ); ?>"><?php esc_html_e( 'Set opening hours and holiday hours for each location', 'cobbleandcandle-core' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=cobble_menu_item&page=cobble-menu-import' ) ); ?>"><?php esc_html_e( 'Import your menu from a spreadsheet', 'cobbleandcandle-core' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'options-general.php?page=cobbleandcandle' ) ); ?>"><?php esc_html_e( 'Upload your logo and add social profiles', 'cobbleandcandle-core' ); ?></a></li>
				<?php if ( in_array( 'rooms', $state['kinds'], true ) ) : ?>
					<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=cobble_room' ) ); ?>"><?php esc_html_e( 'Add your rooms, prices and calendar links (Airbnb, Booking.com)', 'cobbleandcandle-core' ); ?></a></li>
				<?php endif; ?>
				<li><a href="<?php echo esc_url( admin_url( 'site-editor.php' ) ); ?>"><?php esc_html_e( 'Fine-tune pages in the Site Editor', 'cobbleandcandle-core' ); ?></a></li>
			</ul>
			<p><a class="button button-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'View your site', 'cobbleandcandle-core' ); ?></a></p>
		<?php endif; ?>
		</section>
	</div>
	<?php
}
