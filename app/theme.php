<?php

/**
 * Block-theme runtime: style direction, front-end assets, icon sprite, brand settings.
 *
 * The three style directions (HANDOFF §2) are Site Editor style variations
 * (styles/*.json). Each sets `settings.custom.direction`; PHP prints it as
 * `<html data-theme="…">`, which switches the token blocks in tokens.css.
 */

namespace App;

use Illuminate\Support\Facades\Vite;

/**
 * Style directions: slug => label, mood, swatches, display font file to preload.
 *
 * @return array<string, array{label: string, title: string, sw: array{0: string, 1: string}, font: string}>
 */
function directions(): array
{
    return [
        'lampwright' => ['label' => 'Lampwright', 'title' => __('Candlelit Old Town', 'cobbleandcandle'), 'sw' => ['#15110D', '#C79A55'], 'font' => 'BodoniModa-400-normal.woff2'],
        'ember-arch' => ['label' => 'Ember & Arch', 'title' => __('Brick & Ember', 'cobbleandcandle'), 'sw' => ['#1F1410', '#E2692A'], 'font' => 'EBGaramond-500-normal.woff2'],
        'ashlar-iron' => ['label' => 'Ashlar & Iron', 'title' => __('Limestone & Iron', 'cobbleandcandle'), 'sw' => ['#EFE6D6', '#7A2A2E'], 'font' => 'Marcellus-400-normal.woff2'],
    ];
}

/**
 * Active style direction from the Site Editor style variation.
 */
function direction(): string
{
    $direction = (string) (wp_get_global_settings(['custom', 'direction']) ?: 'lampwright');

    return array_key_exists($direction, directions()) ? $direction : 'lampwright';
}

/**
 * Brand settings (est. year, tagline, cuisine, price range). Editable later from the
 * theme settings page; filter `cobbleandcandle/brand` to override.
 */
function brand(string $key, string $default = ''): string
{
    static $brand = null;
    if ($brand === null) {
        $saved = get_option('cobbleandcandle_brand', []);
        $brand = (array) apply_filters('cobbleandcandle/brand', array_merge([
            'est' => '1888',
            'tagline' => __('Dining rooms', 'cobbleandcandle'),
            'cuisine' => __('Modern European', 'cobbleandcandle'),
            'price_range' => '$$$',
        ], is_array($saved) ? $saved : []));
    }

    return (string) ($brand[$key] ?? $default);
}

/**
 * Print the active direction on <html> (front end). Server-rendered, so no flash without JS.
 */
add_filter('language_attributes', function (string $output): string {
    if (is_admin()) {
        return $output;
    }

    $location = current_location();

    return $output.' data-theme="'.esc_attr(direction()).'"'.($location ? ' data-loc="'.esc_attr($location['slug']).'"' : '');
});

/**
 * Front-end assets. Block themes have no Blade layout, so print the Vite tags here.
 */
add_action('wp_head', function () {
    $font = directions()[direction()]['font'];
    printf(
        '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>'."\n",
        esc_url(Vite::asset('resources/fonts/'.$font))
    );
    // Vite builds these tags from its own manifest (asset URLs only).
    echo Vite::withEntryPoints(['resources/css/app.css', 'resources/js/app.js'])->toHtml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 7);

/**
 * Inline SVG icon sprite, once per page (used by <x-icon>).
 */
add_action('wp_body_open', function () {
    echo view('partials.icon-sprite')->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG from the theme.
});

/**
 * Block editor: apply the active direction's tokens to the editor canvas, so previews match
 * the front end. Re-scopes that direction's [data-theme] block from tokens.css to :root.
 */
add_filter('block_editor_settings_all', function (array $settings): array {
    $direction = direction();
    if ($direction === 'lampwright') {
        return $settings; // Lampwright is already the :root default.
    }

    $css = (string) file_get_contents(get_theme_file_path('resources/css/tokens.css')); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
    if (preg_match('/\\[data-theme="'.preg_quote($direction, '/').'"\\]\\s*\\{(.*?)\\n\\}/s', $css, $m)) {
        $settings['styles'][] = ['css' => ':root{'.$m[1].'}'];
    }

    return $settings;
}, 20);

/**
 * Icon SVG paths by name, read once from the sprite partial (used for inline icons in editor previews).
 *
 * @return array<string, string>
 */
function icon_symbols(): array
{
    static $symbols = null;
    if ($symbols === null) {
        $symbols = [];
        $sprite = (string) file_get_contents(get_theme_file_path('resources/views/partials/icon-sprite.blade.php')); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
        preg_match_all('/<symbol id="i-([a-z0-9-]+)" viewBox="0 0 24 24">(.*?)<\/symbol>/s', $sprite, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $symbols[$match[1]] = $match[2];
        }
    }

    return $symbols;
}

/**
 * Whether markup is being rendered for a block editor preview (REST block renderer).
 */
function is_editor_preview(): bool
{
    return defined('REST_REQUEST') && REST_REQUEST;
}

/**
 * Render a nav menu location as a bare <ul> (no container), or nothing when unassigned.
 */
function menu(string $location, string $class = ''): string
{
    if (! has_nav_menu($location)) {
        return '';
    }

    return (string) wp_nav_menu([
        'theme_location' => $location,
        'container' => false,
        'menu_class' => $class,
        'items_wrap' => '<ul class="%2$s">%3$s</ul>',
        'depth' => 1,
        'fallback_cb' => false,
        'echo' => false,
    ]);
}

/**
 * Site name as plain text. WordPress stores blogname HTML-escaped ("&amp;"); Blade escapes on output.
 */
function site_name(): string
{
    return wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
}
