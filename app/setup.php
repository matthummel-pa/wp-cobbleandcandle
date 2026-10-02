<?php

/**
 * Theme setup.
 */

namespace App;

use Illuminate\Support\Facades\Vite;

/**
 * Inject styles into the block editor.
 *
 * @return array
 */
add_filter('block_editor_settings_all', function ($settings) {
    $style = Vite::asset('resources/css/editor.css');

    $settings['styles'][] = [
        'css' => "@import url('{$style}')",
    ];

    return $settings;
});

/**
 * Block editor script (block registration and settings), enqueued with its translations.
 *
 * @link https://developer.wordpress.org/block-editor/how-to-guides/internationalization/
 */
add_action('enqueue_block_editor_assets', function () {
    if (Vite::isRunningHot()) {
        return; // Printed by the dev-server hook below.
    }
    wp_enqueue_script(
        'cobbleandcandle-editor',
        Vite::asset('resources/js/editor.js'),
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n', 'wp-data'],
        wp_get_theme(get_template())->get('Version'),
        true
    );
    wp_set_script_translations('cobbleandcandle-editor', 'cobbleandcandle', get_theme_file_path('resources/lang'));
});

/**
 * Vite dev server: print the editor entry while developing.
 */
add_action('admin_head', function () {
    if (Vite::isRunningHot() && get_current_screen()?->is_block_editor()) {
        echo Vite::withEntryPoints(['resources/js/editor.js'])->toHtml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Vite dev-server tags (local only).
    }
});

/**
 * Use the generated theme.json file.
 *
 * @return string
 */
add_filter('theme_file_path', function ($path, $file) {
    // Only the parent's theme.json: a child theme's own theme.json must still load on top of it.
    return $file === 'theme.json' && str_starts_with(wp_normalize_path($path), trailingslashit(wp_normalize_path(get_template_directory())))
        ? public_path('build/assets/theme.json')
        : $path;
}, 10, 2);

/**
 * Disable on-demand block asset loading.
 *
 * @link https://core.trac.wordpress.org/ticket/61965
 */
add_filter('should_load_separate_core_block_assets', '__return_false');

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Translations: resources/lang/cobbleandcandle-{locale}.mo (or wp-content/languages/themes/).
     *
     * @link https://developer.wordpress.org/reference/functions/load_theme_textdomain/
     */
    load_theme_textdomain('cobbleandcandle', get_theme_file_path('resources/lang'));

    /**
     * Register the navigation menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'cobbleandcandle'),
        'secondary_navigation' => __('Header Right Links', 'cobbleandcandle'),
        'footer_navigation' => __('Footer Navigation', 'cobbleandcandle'),
        'legal_navigation' => __('Legal Links', 'cobbleandcandle'),
    ]);

    /**
     * Disable the default block patterns.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     */
    remove_theme_support('core-block-patterns');

    /**
     * Enable plugins to manage the document title.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable post thumbnail support.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    /**
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');

    /**
     * Site logo (core Site Logo block / custom logo). Settings → Restaurant can also set one.
     *
     * @link https://developer.wordpress.org/themes/functionality/custom-logo/
     */
    add_theme_support('custom-logo', ['height' => 120, 'width' => 360, 'flex-height' => true, 'flex-width' => true]);

    /**
     * Enable HTML5 markup support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

}, 20);

/**
 * Vite builds ES modules: load the theme's scripts as modules so their top-level variables stay
 * private (as classic scripts they would overwrite globals such as underscore's `_`).
 */
add_filter('script_loader_tag', function (string $tag, string $handle): string {
    if (! in_array($handle, ['cobbleandcandle', 'cobbleandcandle-editor'], true) || str_contains($tag, 'type="module"')) {
        return $tag;
    }

    return (string) preg_replace('/<script(?![^>]*\btype=)/', '<script type="module"', $tag, 1);
}, 10, 2);
