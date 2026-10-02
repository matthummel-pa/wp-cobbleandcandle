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
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

define( 'CC_CORE_VERSION', '0.1.0' );
define( 'CC_CORE_FILE', __FILE__ );
define( 'CC_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'CC_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once CC_CORE_DIR . 'includes/post-types.php';
require_once CC_CORE_DIR . 'includes/fields.php';
require_once CC_CORE_DIR . 'includes/hours.php';
require_once CC_CORE_DIR . 'includes/locations.php';
require_once CC_CORE_DIR . 'includes/menus.php';
require_once CC_CORE_DIR . 'includes/inquiry.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once CC_CORE_DIR . 'includes/seed.php';
}

register_activation_hook(
	__FILE__,
	static function () {
		cc_register_content_types();
		flush_rewrite_rules();
	}
);
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
