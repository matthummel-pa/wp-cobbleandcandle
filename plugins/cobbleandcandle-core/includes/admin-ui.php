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
function cc_admin_post_types() {
	return array( 'cc_location', 'cc_menu_item', 'cc_event', 'cc_room', 'cc_booking', 'cc_message' );
}

/**
 * Is the current admin screen one of ours?
 *
 * @return string '' | 'page' (full branding) | 'content' (light branding)
 */
function cc_admin_screen_kind() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen ) {
		return '';
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading which admin page is open.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( in_array( $page, array( 'cobbleandcandle', 'cc-menu-import', 'cc-setup' ), true ) ) {
		return 'page';
	}
	return in_array( (string) $screen->post_type, cc_admin_post_types(), true ) && ! $screen->is_block_editor() ? 'content' : '';
}

/**
 * Load the admin stylesheet on our screens only.
 */
function cc_admin_enqueue() {
	$kind = cc_admin_screen_kind();
	if ( '' === $kind ) {
		return;
	}
	wp_enqueue_style( 'cc-admin', CC_CORE_URL . 'assets/admin.css', array(), CC_CORE_VERSION );
}
add_action( 'admin_enqueue_scripts', 'cc_admin_enqueue' );

/**
 * Body classes for our screens.
 *
 * @param string $classes Classes.
 * @return string
 */
function cc_admin_body_class( $classes ) {
	$kind = cc_admin_screen_kind();
	return '' === $kind ? $classes : $classes . ' cc-admin-' . $kind;
}
add_filter( 'admin_body_class', 'cc_admin_body_class' );

/**
 * Quick links shown in the branded header: label => [url, capability].
 *
 * @return array<string, array{0: string, 1: string}>
 */
function cc_admin_nav() {
	$nav = array(
		__( 'Setup', 'cobbleandcandle-core' )            => array( admin_url( 'options-general.php?page=cc-setup' ), 'manage_options' ),
		__( 'Restaurant settings', 'cobbleandcandle-core' ) => array( admin_url( 'options-general.php?page=cobbleandcandle' ), 'manage_options' ),
		__( 'Menus', 'cobbleandcandle-core' )            => array( admin_url( 'edit.php?post_type=cc_menu_item' ), 'edit_posts' ),
		__( 'Import / export', 'cobbleandcandle-core' )  => array( admin_url( 'edit.php?post_type=cc_menu_item&page=cc-menu-import' ), 'edit_others_posts' ),
		__( 'Bookings', 'cobbleandcandle-core' )         => array( admin_url( 'edit.php?post_type=cc_booking' ), 'edit_others_posts' ),
		__( 'Messages', 'cobbleandcandle-core' )         => array( admin_url( 'edit.php?post_type=cc_message' ), 'edit_others_posts' ),
	);
	if ( ! function_exists( 'cc_render_setup_page' ) ) {
		unset( $nav[ __( 'Setup', 'cobbleandcandle-core' ) ] );
	}
	if ( ! post_type_exists( 'cc_message' ) ) {
		unset( $nav[ __( 'Messages', 'cobbleandcandle-core' ) ] );
	}
	return $nav;
}

/**
 * The brand mark: a monogram in a ring (the theme's crest, simplified).
 *
 * @return string SVG markup.
 */
function cc_admin_crest() {
	$name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$mono = '' !== $name ? mb_strtoupper( mb_substr( $name, 0, 1 ) ) : 'C';
	return '<svg class="cc-crest" viewBox="0 0 80 80" width="52" height="52" aria-hidden="true" focusable="false">'
		. '<circle cx="40" cy="40" r="38" fill="none" stroke="currentColor" stroke-width="1.5"/>'
		. '<circle cx="40" cy="40" r="32" fill="none" stroke="currentColor" stroke-width=".75" stroke-dasharray="2 3"/>'
		. '<text x="40" y="51" text-anchor="middle" font-size="32" fill="currentColor" class="cc-crest-m">' . esc_html( $mono ) . '</text>'
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
function cc_admin_header( $title, $intro = '', $current = '' ) {
	?>
	<header class="cc-hero">
		<div class="cc-hero-brand">
			<?php echo cc_admin_crest(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG; the monogram is escaped inside. ?>
			<div>
				<p class="cc-hero-kicker"><?php esc_html_e( 'Cobble & Candle', 'cobbleandcandle-core' ); ?></p>
				<h1 class="cc-hero-title"><?php echo esc_html( $title ); ?></h1>
				<?php if ( '' !== $intro ) : ?>
					<p class="cc-hero-intro"><?php echo esc_html( $intro ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<nav class="cc-hero-nav" aria-label="<?php esc_attr_e( 'Cobble & Candle', 'cobbleandcandle-core' ); ?>">
			<?php foreach ( cc_admin_nav() as $label => list( $url, $cap ) ) : ?>
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
