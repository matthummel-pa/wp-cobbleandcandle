<?php
/**
 * Branded admin UI for the plugin's own screens (Settings → Restaurant, menu Import / export, the
 * setup wizard) and a lighter touch on its content screens (locations, menus, events, rooms,
 * bookings, messages). WordPress controls, notices and keyboard behaviour are left as they are.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * The plugin's post types (content screens get the light branding).
 *
 * @return array<int, string>
 */
function cobble_admin_post_types() {
	return array( 'cobble_location', 'cobble_menu_item', 'cobble_event', 'cobble_room', 'cobble_booking', 'cobble_message' );
}

/**
 * Is the current admin screen one of ours?
 *
 * @return string '' | 'page' (full branding) | 'content' (light branding)
 */
function cobble_admin_screen_kind() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen ) {
		return '';
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading which admin page is open.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( in_array( $page, array( 'cobbleandcandle', 'cobble-menu-import', 'cobble-setup', 'cobble-status' ), true ) ) {
		return 'page';
	}
	return in_array( (string) $screen->post_type, cobble_admin_post_types(), true ) && ! $screen->is_block_editor() ? 'content' : '';
}

/**
 * Load the admin stylesheet on our screens only.
 */
function cobble_admin_enqueue() {
	$kind = cobble_admin_screen_kind();
	if ( '' === $kind ) {
		return;
	}
	wp_enqueue_style( 'cobble-admin', COBBLE_CORE_URL . 'assets/admin.css', array(), COBBLE_CORE_VERSION );
}
add_action( 'admin_enqueue_scripts', 'cobble_admin_enqueue' );

/**
 * Body classes for our screens.
 *
 * @param string $classes Classes.
 * @return string
 */
function cobble_admin_body_class( $classes ) {
	$kind = cobble_admin_screen_kind();
	return '' === $kind ? $classes : $classes . ' cobble-admin-' . $kind;
}
add_filter( 'admin_body_class', 'cobble_admin_body_class' );

/**
 * Quick links shown in the branded header: label => [url, capability].
 *
 * @return array<string, array{0: string, 1: string}>
 */
function cobble_admin_nav() {
	$nav = array(
		__( 'Setup', 'cobbleandcandle-core' )            => array( admin_url( 'options-general.php?page=cobble-setup' ), 'manage_options' ),
		__( 'Restaurant settings', 'cobbleandcandle-core' ) => array( admin_url( 'options-general.php?page=cobbleandcandle' ), 'manage_options' ),
		__( 'Menus', 'cobbleandcandle-core' )            => array( admin_url( 'edit.php?post_type=cobble_menu_item' ), 'edit_posts' ),
		__( 'Import / export', 'cobbleandcandle-core' )  => array( admin_url( 'edit.php?post_type=cobble_menu_item&page=cobble-menu-import' ), 'edit_others_posts' ),
		__( 'Bookings', 'cobbleandcandle-core' )         => array( admin_url( 'edit.php?post_type=cobble_booking' ), 'edit_others_posts' ),
		__( 'Messages', 'cobbleandcandle-core' )         => array( admin_url( 'edit.php?post_type=cobble_message' ), 'edit_others_posts' ),
		__( 'Status', 'cobbleandcandle-core' )           => array( admin_url( 'tools.php?page=cobble-status' ), 'manage_options' ),
	);
	if ( ! function_exists( 'cobble_render_setup_page' ) ) {
		unset( $nav[ __( 'Setup', 'cobbleandcandle-core' ) ] );
	}
	if ( ! post_type_exists( 'cobble_message' ) ) {
		unset( $nav[ __( 'Messages', 'cobbleandcandle-core' ) ] );
	}
	return $nav;
}

/**
 * The brand mark: a monogram in a ring (the theme's crest, simplified).
 *
 * @return string SVG markup.
 */
function cobble_admin_crest() {
	$name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$mono = '' !== $name ? mb_strtoupper( mb_substr( $name, 0, 1 ) ) : 'C';
	return '<svg class="cobble-crest" viewBox="0 0 80 80" width="52" height="52" aria-hidden="true" focusable="false">'
		. '<circle cx="40" cy="40" r="38" fill="none" stroke="currentColor" stroke-width="1.5"/>'
		. '<circle cx="40" cy="40" r="32" fill="none" stroke="currentColor" stroke-width=".75" stroke-dasharray="2 3"/>'
		. '<text x="40" y="51" text-anchor="middle" font-size="32" fill="currentColor" class="cobble-crest-m">' . esc_html( $mono ) . '</text>'
		. '</svg>';
}

/**
 * Branded page header: crest, product line, H1, intro and quick links. Prints <hr class="wp-header-end">
 * so WordPress puts its notices below it.
 *
 * @param string $title   Page title (the screen's H1).
 * @param string $intro   One-line intro.
 * @param string $current URL of the current page, marked in the quick links.
 */
function cobble_admin_header( $title, $intro = '', $current = '' ) {
	?>
	<header class="cobble-hero">
		<div class="cobble-hero-brand">
			<?php echo cobble_admin_crest(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG; the monogram is escaped inside. ?>
			<div>
				<p class="cobble-hero-kicker"><?php esc_html_e( 'Cobble & Candle', 'cobbleandcandle-core' ); ?></p>
				<h1 class="cobble-hero-title"><?php echo esc_html( $title ); ?></h1>
				<?php if ( '' !== $intro ) : ?>
					<p class="cobble-hero-intro"><?php echo esc_html( $intro ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<nav class="cobble-hero-nav" aria-label="<?php esc_attr_e( 'Cobble & Candle', 'cobbleandcandle-core' ); ?>">
			<?php foreach ( cobble_admin_nav() as $label => list( $url, $cap ) ) : ?>
				<?php
				if ( ! current_user_can( $cap ) ) {
					continue;
				}
				$is = '' !== $current && $url === $current;
				?>
				<a href="<?php echo esc_url( $url ); ?>"<?php echo $is ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
	</header>
	<hr class="wp-header-end">
	<?php
}

/**
 * Bookmarks to the screens' pre-1.0 addresses (page=cc-setup…) land on the new ones.
 */
function cobble_redirect_old_admin_pages() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation only.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	$map  = array(
		'cc-setup'       => 'options-general.php?page=cobble-setup',
		'cc-status'      => 'tools.php?page=cobble-status',
		'cc-menu-import' => 'edit.php?post_type=cobble_menu_item&page=cobble-menu-import',
	);
	if ( isset( $map[ $page ] ) ) {
		wp_safe_redirect( admin_url( $map[ $page ] ) );
		exit;
	}
}
add_action( 'admin_menu', 'cobble_redirect_old_admin_pages', 0 ); // Before WordPress's page-access check (which runs before admin_init).
