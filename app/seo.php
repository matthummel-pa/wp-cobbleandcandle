<?php

/**
 * Share and search metadata: meta description, Open Graph, Twitter card, and theme-color.
 *
 * A dedicated SEO plugin prints its own set, so this stands down when one is active.
 */

namespace App;

/**
 * Whether an SEO plugin already prints these tags.
 */
function seo_plugin_active(): bool
{
    return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('SEOPRESS_VERSION');
}

/**
 * Meta description: the post's excerpt or content, then the site tagline, then a brand sentence.
 */
function meta_description(): string
{
    $description = '';

    // The front page is a block canvas; its tagline is the share sentence, not the block markup.
    if (is_singular() && ! is_front_page()) {
        $post = get_queried_object();
        if ($post instanceof \WP_Post) {
            $text = has_excerpt($post) ? get_the_excerpt($post) : strip_shortcodes($post->post_content);
            $text = trim((string) preg_replace('/\s+/', ' ', wp_strip_all_tags($text)));
            $description = html_entity_decode(wp_html_excerpt($text, 160, '…'), ENT_QUOTES, 'UTF-8');
        }
    }

    if ($description === '') {
        $description = wp_specialchars_decode((string) get_bloginfo('description'), ENT_QUOTES);
    }

    if ($description === '') {
        $description = sprintf(
            /* translators: 1: site name, 2: cuisine or tagline, e.g. "Modern European" */
            __('%1$s — %2$s, served by candlelight across our dining rooms.', 'cobbleandcandle'),
            site_name(),
            brand('cuisine') ?: brand('tagline')
        );
    }

    /**
     * Filter the meta description printed in <head>.
     *
     * @param  string  $description  Plain text, escaped on output.
     */
    return (string) apply_filters('cobbleandcandle/meta_description', $description);
}

/**
 * URL this view should be shared as. Archives keep their own address.
 */
function share_url(): string
{
    if (is_singular()) {
        return (string) get_permalink();
    }

    global $wp;
    $path = $wp instanceof \WP ? trim((string) $wp->request, '/') : '';
    if ($path !== '') {
        return home_url(user_trailingslashit($path));
    }

    // Plain permalinks leave the path empty; the archive link still has the right query.
    if (is_post_type_archive()) {
        $type = get_queried_object();
        if ($type instanceof \WP_Post_Type) {
            $archive = get_post_type_archive_link($type->name);
            if (is_string($archive) && $archive !== '') {
                return $archive;
            }
        }
    }

    return home_url('/');
}

/**
 * Print the share and search tags, ahead of everything else in <head>.
 */
add_action('wp_head', function () {
    if (seo_plugin_active()) {
        return;
    }

    $description = meta_description();
    $image = is_singular() ? (string) get_the_post_thumbnail_url(get_queried_object_id(), 'large') : '';
    $title = wp_get_document_title();

    $tags = [
        ['name', 'description', $description],
        ['name', 'theme-color', directions()[direction()]['sw'][0]],
        ['property', 'og:site_name', site_name()],
        ['property', 'og:locale', get_locale()],
        ['property', 'og:type', (is_singular() && ! is_front_page()) ? 'article' : 'website'],
        ['property', 'og:title', $title],
        ['property', 'og:description', $description],
        ['name', 'twitter:card', $image !== '' ? 'summary_large_image' : 'summary'],
    ];

    foreach ($tags as [$attribute, $name, $value]) {
        if ($value !== '') {
            printf('<meta %s="%s" content="%s">'."\n", esc_attr($attribute), esc_attr($name), esc_attr($value));
        }
    }

    printf(
        '<meta property="og:url" content="%s">'."\n",
        esc_url(share_url())
    );

    if ($image !== '') {
        printf('<meta property="og:image" content="%s">'."\n", esc_url($image));
        printf('<meta name="twitter:image" content="%s">'."\n", esc_url($image));
    }
}, 1);
