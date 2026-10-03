<?php
/**
 * Plugin Name:       Cobble & Candle Core
 * Plugin URI:        https://github.com/matthummel-pa/wp-cobbleandcandle
 * Description:       Locations, menus and events for the Cobble & Candle restaurant theme. Your content stays when you switch themes.
 * Version:           1.0.0
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

define( 'COBBLE_CORE_VERSION', '1.0.0' );
define( 'COBBLE_CORE_FILE', __FILE__ );
define( 'COBBLE_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'COBBLE_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Translations bundled in languages/ (wp-content/languages/plugins/ wins when present).
 */
function cobble_load_textdomain() {
	load_plugin_textdomain( 'cobbleandcandle-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'cobble_load_textdomain', 0 );

require_once COBBLE_CORE_DIR . 'includes/migrate.php';
require_once COBBLE_CORE_DIR . 'includes/post-types.php';
require_once COBBLE_CORE_DIR . 'includes/fields.php';
require_once COBBLE_CORE_DIR . 'includes/hours.php';
require_once COBBLE_CORE_DIR . 'includes/locations.php';
require_once COBBLE_CORE_DIR . 'includes/menus.php';
require_once COBBLE_CORE_DIR . 'includes/menu-import.php';
require_once COBBLE_CORE_DIR . 'includes/settings.php';
require_once COBBLE_CORE_DIR . 'includes/admin-ui.php';
require_once COBBLE_CORE_DIR . 'includes/status.php';
require_once COBBLE_CORE_DIR . 'includes/forms.php';
require_once COBBLE_CORE_DIR . 'includes/messages.php';
require_once COBBLE_CORE_DIR . 'includes/inquiry.php';
require_once COBBLE_CORE_DIR . 'includes/reservations.php';
require_once COBBLE_CORE_DIR . 'includes/contact.php';
require_once COBBLE_CORE_DIR . 'includes/events.php';
require_once COBBLE_CORE_DIR . 'includes/rooms.php';
require_once COBBLE_CORE_DIR . 'includes/ical.php';
require_once COBBLE_CORE_DIR . 'includes/seo.php';

require_once COBBLE_CORE_DIR . 'includes/seed.php';
require_once COBBLE_CORE_DIR . 'includes/setup.php';

register_activation_hook(
	__FILE__,
	static function () {
		cobble_register_content_types();
		cobble_register_room_types();
		flush_rewrite_rules();
	}
);
register_deactivation_hook(
	__FILE__,
	static function () {
		// Unregister first so the flush drops this plugin's rewrite rules.
		foreach ( array( 'cobble_location', 'cobble_menu_item', 'cobble_event', 'cobble_room', 'cobble_booking', 'cobble_message' ) as $post_type ) {
			unregister_post_type( $post_type );
		}
		wp_clear_scheduled_hook( 'cobble_ical_sync' );
		wp_unschedule_hook( 'cobble_ical_sync_room' );
		wp_clear_scheduled_hook( 'cobble_prune_messages' );
		flush_rewrite_rules();
	}
);
