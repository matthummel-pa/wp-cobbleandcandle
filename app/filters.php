<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    $post = get_post();

    return sprintf(
        ' &hellip; <a href="%s">%s<span class="sr"> %s</span></a>',
        esc_url(get_permalink()),
        esc_html__('Continued', 'cobbleandcandle'),
        esc_html($post ? plain_title($post) : '')
    );
});

/**
 * Acorn adds the page/post slug as a bare body class (e.g. "story"). A slug that matches a
 * component class would restyle <body> itself, so prefix it: "slug-story".
 */
add_filter('body_class', function (array $classes): array {
    if (! (is_single() || (is_page() && ! is_front_page()))) {
        return $classes;
    }
    // Acorn appends the slug last, and only when WordPress hasn't already added that class.
    $classes = array_values($classes);
    $last = array_key_last($classes);
    if ($last !== null && $classes[$last] === basename((string) get_permalink())) {
        $classes[$last] = 'slug-'.$classes[$last];
    }

    return $classes;
}, 11);

/**
 * Images with no alt text fall back to their Media Library caption (never the file name), so
 * photos the owner captioned but forgot to describe still have a text alternative.
 */
add_filter('wp_get_attachment_image_attributes', function (array $attr, \WP_Post $attachment): array {
    if (($attr['alt'] ?? '') === '') {
        $caption = trim(wp_strip_all_tags((string) wp_get_attachment_caption($attachment->ID)));
        if ($caption !== '') {
            $attr['alt'] = $caption;
        }
    }

    return $attr;
}, 10, 2);
