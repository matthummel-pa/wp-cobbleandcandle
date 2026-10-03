<?php
/**
 * Plugin Name:       Cobble & Candle Core
 * Plugin URI:        https://github.com/matthummel-pa/wp-cobbleandcandle
 * Description:       Locations, menus and events for the Cobble & Candle restaurant theme. Your content stays when you switch themes.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.3
 * Author:            Matt Hummel
 * Author URI:        https://matthummel.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cobbleandcandle-core
 * Domain Path:       /languages
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

define( 'CC_CORE_VERSION', '0.1.0' );
define( 'CC_CORE_FILE', __FILE__ );
define( 'CC_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'CC_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Translations bundled in languages/ (wp-content/languages/plugins/ wins when present).
 */
function cc_load_textdomain() {
	load_plugin_textdomain( 'cobbleandcandle-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'cc_load_textdomain', 0 );

require_once CC_CORE_DIR . 'includes/post-types.php';
require_once CC_CORE_DIR . 'includes/fields.php';
require_once CC_CORE_DIR . 'includes/hours.php';
require_once CC_CORE_DIR . 'includes/locations.php';
require_once CC_CORE_DIR . 'includes/menus.php';
require_once CC_CORE_DIR . 'includes/settings.php';
require_once CC_CORE_DIR . 'includes/forms.php';
require_once CC_CORE_DIR . 'includes/messages.php';
require_once CC_CORE_DIR . 'includes/inquiry.php';
require_once CC_CORE_DIR . 'includes/reservations.php';
require_once CC_CORE_DIR . 'includes/contact.php';
require_once CC_CORE_DIR . 'includes/events.php';
require_once CC_CORE_DIR . 'includes/rooms.php';
require_once CC_CORE_DIR . 'includes/ical.php';
require_once CC_CORE_DIR . 'includes/seo.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once CC_CORE_DIR . 'includes/seed.php';
}

register_activation_hook(
	__FILE__,
	static function () {
		cc_register_content_types();
		cc_register_room_types();
		flush_rewrite_rules();
	}
);
register_deactivation_hook(
	__FILE__,
	static function () {
		// Unregister first so the flush drops this plugin's rewrite rules.
		foreach ( array( 'cc_location', 'cc_menu_item', 'cc_event', 'cc_room', 'cc_booking', 'cc_message' ) as $post_type ) {
			unregister_post_type( $post_type );
		}
		wp_clear_scheduled_hook( 'cc_ical_sync' );
		wp_unschedule_hook( 'cc_ical_sync_room' );
		flush_rewrite_rules();
	}
);
