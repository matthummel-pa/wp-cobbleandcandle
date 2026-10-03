<?php

/**
 * Placeholder illustrations, printed once per page: each kind goes into a hidden SVG sprite in the
 * footer and every photo slot reuses it with <use>. A menu with ten dish placeholders sends one
 * drawing, not ten. The map stays inline (its pins change per page), and so does the editor preview
 * (no footer to hold the sprite).
 */

namespace App;

/**
 * Art kinds drawn this request, in first-use order.
 *
 * @return array<string, true>
 */
function art_used(?string $add = null): array
{
    static $used = [];
    if ($add !== null) {
        $used[$add] = true;
    }

    return $used;
}

/**
 * One drawing split into its <svg> attributes and inner markup, rendered once per request.
 *
 * @return array{viewBox: string, aspect: string, label: string, inner: string}|null
 */
function art_parts(string $kind): ?array
{
    static $cache = [];
    if (array_key_exists($kind, $cache)) {
        return $cache[$kind];
    }
    $cache[$kind] = null;
    if (! view()->exists('art.'.$kind)) {
        return null;
    }
    $svg = view('art.'.$kind, ['uid' => '-s'.$kind])->render();
    if (! preg_match('/<svg\b([^>]*)>(.*)<\/svg>\s*$/s', $svg, $m)) {
        return null;
    }
    $attr = fn (string $name, string $fallback = ''): string => preg_match('/\b'.$name.'="([^"]*)"/', $m[1], $a) ? $a[1] : $fallback;

    return $cache[$kind] = [
        'viewBox' => $attr('viewBox', '0 0 1000 1000'),
        'aspect' => $attr('preserveAspectRatio', 'xMidYMid slice'),
        'label' => $attr('aria-label'),
        'inner' => $m[2],
    ];
}

/**
 * A reference to a shared drawing, or '' when this kind must be drawn inline.
 */
function art_ref(string $kind, bool $use = true): string
{
    if ($kind === 'map' || is_editor_preview() || did_action('wp_footer') || ! ($parts = art_parts($kind))) {
        return '';
    }
    if ($use) {
        art_used($kind);
    }

    return sprintf(
        '<svg class="ill" viewBox="%1$s" preserveAspectRatio="%2$s" role="img" aria-label="%3$s"><use href="#art-%4$s" width="100%%" height="100%%"/></svg>',
        esc_attr($parts['viewBox']),
        esc_attr($parts['aspect']),
        esc_attr($parts['label']),
        esc_attr($kind)
    );
}

/**
 * The sprite: each drawing used on this page, once.
 */
add_action('wp_footer', function () {
    $used = art_used();
    if (! $used) {
        return;
    }
    echo '<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs>';
    foreach (array_keys($used) as $kind) {
        $parts = art_parts($kind);
        if ($parts) {
            // Drawings are theme-authored Blade templates (resources/views/art), escaped where they print data.
            echo '<symbol id="art-'.esc_attr($kind).'" viewBox="'.esc_attr($parts['viewBox']).'">'.$parts['inner'].'</symbol>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
    echo '</defs></svg>';
}, 5);
