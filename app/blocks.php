<?php

/**
 * Custom blocks: every resources/blocks/<name>/block.json is registered as a dynamic
 * block rendered by resources/views/blocks/<name>.blade.php.
 *
 * Editor UI lives in resources/js/editor.js (ServerSideRender preview + sidebar settings).
 */

namespace App;

/**
 * Block metadata files, keyed by block folder name.
 *
 * @return array<string, string>
 */
function block_manifests(): array
{
    $out = [];
    foreach (glob(get_theme_file_path('resources/blocks/*/block.json')) ?: [] as $file) {
        $out[basename(dirname($file))] = $file;
    }

    return $out;
}

add_action('init', function () {
    foreach (block_manifests() as $name => $file) {
        register_block_type(dirname($file), [
            'render_callback' => function (array $attributes, string $content, \WP_Block $block) use ($name): string {
                return view("blocks.{$name}", [
                    'attributes' => $attributes,
                    'content' => $content,
                    'block' => $block,
                    'wrapper' => get_block_wrapper_attributes(),
                ])->render();
            },
        ]);
    }
});

/**
 * "Cobble & Candle" block category in the inserter.
 */
add_filter('block_categories_all', function (array $categories): array {
    array_unshift($categories, [
        'slug' => 'cobbleandcandle',
        'title' => __('Cobble & Candle', 'cobbleandcandle'),
        'icon' => null,
    ]);

    return $categories;
});

/**
 * "Cobble & Candle" pattern category (patterns/*.php register themselves).
 */
add_action('init', function () {
    register_block_pattern_category('cobbleandcandle', ['label' => __('Cobble & Candle', 'cobbleandcandle')]);
});
