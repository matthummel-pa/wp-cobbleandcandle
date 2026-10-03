<?php

/**
 * Block rendering: the Cobble & Candle Core plugin registers the blocks (plugins/cobbleandcandle-core/
 * blocks/<name>/block.json, editor UI included) and the theme draws them with
 * resources/views/blocks/<name>.blade.php through the `cobble_render_block` filter.
 */

namespace App;

/**
 * The page that holds one of the theme's blocks (e.g. the Reservations block), or 0.
 */
function page_with_block(string $block): int
{
    static $found = [];
    if (! array_key_exists($block, $found)) {
        $ids = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            's' => "wp:cobbleandcandle/{$block}",
            'numberposts' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        $found[$block] = $ids ? (int) $ids[0] : 0;
    }

    return $found[$block];
}

/**
 * Resolve a default link like "/reservations/" to the real page, so links survive renamed pages,
 * subfolder installs and language prefixes. Filter `cobbleandcandle/page_link` to override.
 */
function page_link(string $path): string
{
    $map = [
        '/reservations/' => fn () => page_with_block('reservations'),
        '/menu/' => fn () => page_with_block('full-menu'),
        '/gallery/' => fn () => page_with_block('gallery-grid'),
    ];
    $url = '';
    if (isset($map[$path]) && ($id = $map[$path]())) {
        $url = (string) get_permalink($id);
    } elseif ($path === '/locations/' && post_type_exists('cobble_location')) {
        $url = (string) get_post_type_archive_link('cobble_location');
    } elseif ($path === '/events/' && post_type_exists('cobble_event')) {
        $url = (string) get_post_type_archive_link('cobble_event');
    } elseif ($path === '/rooms/' && post_type_exists('cobble_room')) {
        $url = (string) get_post_type_archive_link('cobble_room');
    }

    return (string) apply_filters('cobbleandcandle/page_link', $url !== '' ? $url : home_url($path), $path);
}

/**
 * Block settings still at their block.json default are shown translated (copy) or resolved (links).
 * The extraction list for the copy is resources/lang/block-strings.php (npm run translate:blocks).
 *
 * @param  array<string, mixed>  $attributes
 * @return array<string, mixed>
 */
function localize_attributes(array $attributes, \WP_Block_Type $type): array
{
    foreach ($type->attributes ?? [] as $key => $schema) {
        $default = $schema['default'] ?? null;
        if (! is_string($default) || $default === '' || ($attributes[$key] ?? null) !== $default || in_array($key, ['boardMenu', 'art', 'seatings'], true)) {
            continue;
        }
        $attributes[$key] = str_starts_with($default, '/')
            ? page_link($default)
            : translate($default, 'cobbleandcandle'); // phpcs:ignore WordPress.WP.I18n -- defaults are extracted via resources/lang/block-strings.php.
    }

    return $attributes;
}

/**
 * Render the Cobble & Candle blocks. The Core plugin registers them (block registration is plugin
 * territory) and asks the theme for their markup: each block has a Blade view in
 * resources/views/blocks/<name>.blade.php.
 */
add_filter('cobble_render_block', function ($html, string $name, array $attributes, string $content, \WP_Block $block) {
    if (! view()->exists("blocks.{$name}")) {
        return $html;
    }

    return view("blocks.{$name}", [
        'attributes' => localize_attributes($attributes, $block->block_type),
        'content' => $content,
        'block' => $block,
        'wrapper' => get_block_wrapper_attributes(),
    ])->render();
}, 10, 5);

/**
 * Without the Core plugin the blocks aren't registered, so the site chrome would vanish. Keep a plain,
 * working header, footer, page heading and 404 message until the plugin is installed.
 */
add_filter('render_block', function ($html, array $parsed): string {
    $html = (string) $html; // Another plugin at a lower priority may hand back a non-string.
    $name = (string) ($parsed['blockName'] ?? '');
    $fallbacks = ['site-header', 'site-footer', 'page-hero', 'not-found'];
    $short = str_starts_with($name, 'cobbleandcandle/') ? substr($name, 16) : '';
    if ($short === '' || ! in_array($short, $fallbacks, true) || \WP_Block_Type_Registry::get_instance()->is_registered($name)) {
        return $html;
    }

    return view("fallback.{$short}")->render();
}, 10, 2);

/**
 * "Cobble & Candle" pattern category (patterns/*.php register themselves).
 */
add_action('init', function () {
    register_block_pattern_category('cobbleandcandle', ['label' => __('Cobble & Candle', 'cobbleandcandle')]);
});
