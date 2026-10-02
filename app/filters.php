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
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'cobbleandcandle'));
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
