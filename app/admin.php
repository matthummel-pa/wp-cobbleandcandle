<?php

/**
 * Companion plugin: an admin notice offers to install (from the copy bundled in the theme at
 * plugins/cobbleandcandle-core.zip) or activate Cobble & Candle Core, which holds the locations,
 * menus, events and booking features. The theme still renders without it.
 */

namespace App;

const CORE_PLUGIN = 'cobbleandcandle-core/cobbleandcandle-core.php';

/**
 * Path to the plugin zip bundled with the theme.
 */
function core_plugin_package(): string
{
    return get_template_directory().'/plugins/cobbleandcandle-core.zip'; // Always the parent theme's bundled copy.
}

/**
 * Notice on admin screens while the plugin is missing or inactive.
 */
add_action('admin_notices', function () {
    if (function_exists('cc_get_locations') || ! current_user_can('activate_plugins')) {
        return;
    }
    $screen = get_current_screen();
    if ($screen && $screen->is_block_editor()) {
        return;
    }
    $installed = file_exists(WP_PLUGIN_DIR.'/'.CORE_PLUGIN);
    if ($installed) {
        $url = wp_nonce_url(admin_url('plugins.php?action=activate&plugin='.rawurlencode(CORE_PLUGIN)), 'activate-plugin_'.CORE_PLUGIN);
        $label = __('Activate Cobble & Candle Core', 'cobbleandcandle');
    } elseif (current_user_can('install_plugins') && is_readable(core_plugin_package())) {
        $url = wp_nonce_url(admin_url('admin-post.php?action=cobbleandcandle_install_core'), 'cobbleandcandle_install_core');
        $label = __('Install & activate Cobble & Candle Core', 'cobbleandcandle');
    } else {
        $url = '';
        $label = '';
    }
    ?>
    <div class="notice notice-warning">
        <p><strong><?php esc_html_e('Cobble & Candle needs its companion plugin.', 'cobbleandcandle'); ?></strong>
        <?php esc_html_e('Cobble & Candle Core adds locations, opening hours, menus, events and bookings. Until it is active, those pages stay empty.', 'cobbleandcandle'); ?></p>
        <?php if ($url !== '') { ?>
            <p><a class="button button-primary" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a></p>
        <?php } else { ?>
            <p><?php esc_html_e('Ask a site administrator to install the cobbleandcandle-core.zip that came with the theme.', 'cobbleandcandle'); ?></p>
        <?php } ?>
    </div>
    <?php
});

/**
 * Install the bundled plugin, activate it, and return to the dashboard.
 */
add_action('admin_post_cobbleandcandle_install_core', function () {
    if (! current_user_can('install_plugins') || ! current_user_can('activate_plugins')) {
        wp_die(esc_html__('You are not allowed to install plugins on this site.', 'cobbleandcandle'), 403);
    }
    check_admin_referer('cobbleandcandle_install_core');

    if (! file_exists(WP_PLUGIN_DIR.'/'.CORE_PLUGIN)) {
        require_once ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH.'wp-admin/includes/plugin.php';
        $upgrader = new \Plugin_Upgrader(new \Automatic_Upgrader_Skin);
        $result = $upgrader->install(core_plugin_package());
        if (is_wp_error($result) || ! $result) {
            wp_die(
                esc_html(is_wp_error($result) ? $result->get_error_message() : __('The plugin could not be installed. Upload cobbleandcandle-core.zip from Plugins → Add New → Upload.', 'cobbleandcandle')),
                '',
                ['back_link' => true]
            );
        }
    }

    $activated = activate_plugin(CORE_PLUGIN);
    if (is_wp_error($activated)) {
        wp_die(esc_html($activated->get_error_message()), '', ['back_link' => true]);
    }
    wp_safe_redirect(admin_url('options-general.php?page=cc-setup')); // Straight into the setup wizard.
    exit;
});
