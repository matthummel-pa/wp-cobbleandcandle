<?php

/**
 * Business kinds: restaurant, tavern, bed & breakfast. One theme serves all three; a page can
 * declare which it is (post meta `cobble_kind`, set by the demo import) and the theme follows:
 * a paired style direction, "Book a stay" instead of "Reserve a table", the Stay action first.
 * The demo switcher in the Site Header links the three demo home pages.
 */

namespace App;

/**
 * Kinds: slug => label, paired style direction, demo home page slug ('' = the front page),
 * primary action label and link.
 *
 * @return array<string, array{label: string, direction: string, home: string, cta: string, cta_url: string}>
 */
function kinds(): array
{
    return [
        'restaurant' => ['label' => __('Restaurant', 'cobbleandcandle'), 'direction' => 'lampwright', 'home' => '', 'cta' => __('Reserve a table', 'cobbleandcandle'), 'cta_url' => '/reservations/'],
        'tavern' => ['label' => __('Tavern', 'cobbleandcandle'), 'direction' => 'ashlar-iron', 'home' => 'tavern', 'cta' => __('Book a table', 'cobbleandcandle'), 'cta_url' => '/reservations/'],
        'bnb' => ['label' => __('B&B', 'cobbleandcandle'), 'direction' => 'daylight', 'home' => 'bnb', 'cta' => __('Book a stay', 'cobbleandcandle'), 'cta_url' => '/rooms/'],
    ];
}

/**
 * The kind the current page declares, or '' when it doesn't (ordinary pages follow the site's
 * own style and wording).
 */
function page_kind(): string
{
    if (is_admin() || ! is_singular()) {
        return '';
    }
    $kind = (string) get_post_meta((int) get_queried_object_id(), 'cobble_kind', true);

    return array_key_exists($kind, kinds()) ? $kind : '';
}

/**
 * The demo home page for a kind, when it exists.
 */
function kind_home_url(string $kind): string
{
    $def = kinds()[$kind] ?? null;
    if (! $def) {
        return '';
    }
    if ($def['home'] === '') {
        return home_url('/');
    }
    $page = get_page_by_path($def['home'], OBJECT, 'page');

    return $page && $page->post_status === 'publish' && ! post_password_required($page) ? (string) get_permalink($page) : '';
}

/**
 * Print the page's kind on <html>, next to data-theme (used by the demo switcher's storage key).
 */
add_filter('language_attributes', function (string $output): string {
    $kind = page_kind();

    return $kind === '' ? $output : $output.' data-kind="'.esc_attr($kind).'"';
}, 11);

/**
 * Register the two page meta keys so only theme editors can set them, and only to known values.
 */
add_action('init', function () {
    foreach (['cobble_kind' => array_keys(kinds()), 'cobble_direction' => array_keys(directions())] as $key => $allowed) {
        register_post_meta('page', $key, [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
            'sanitize_callback' => fn ($value) => in_array($value, $allowed, true) ? $value : '',
            'auth_callback' => fn ($allowed_, $meta_key, $post_id) => current_user_can('edit_theme_options') && current_user_can('edit_post', $post_id),
        ]);
    }
});
