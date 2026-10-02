<?php

/**
 * Cobble & Candle Child: your customisations live here.
 */
defined('ABSPATH') || exit;

/**
 * Load this child theme's style.css after the parent's styles.
 */
function cobbleandcandle_child_styles()
{
    wp_enqueue_style('cobbleandcandle-child', get_stylesheet_uri(), ['cobbleandcandle'], wp_get_theme()->get('Version'));
}
add_action('wp_enqueue_scripts', 'cobbleandcandle_child_styles', 20);
