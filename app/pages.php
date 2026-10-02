<?php

/**
 * Inner-page helpers: the post a block is rendering for, breadcrumbs, and page-hero defaults.
 */

namespace App;

/**
 * The post being viewed. On the front end that's the queried object; in the editor's server
 * preview (REST block renderer with ?post_id=) it's the global post.
 */
function context_post(): ?\WP_Post
{
    $object = get_queried_object();
    if ($object instanceof \WP_Post) {
        return $object;
    }

    return is_editor_preview() ? get_post() : null;
}

/**
 * A post title as plain text for Blade's {{ }} (get_the_title() returns texturized HTML entities).
 */
function plain_title(int|\WP_Post $post): string
{
    return html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Breadcrumb trail: Home → (archive or parent pages) → current. The last item has no URL.
 *
 * @return list<array{0: string, 1: string}> [label, url]
 */
function crumbs(): array
{
    $trail = [[__('Home', 'cobbleandcandle'), home_url('/')]];
    $post = context_post();

    if ($post) {
        $type = get_post_type_object($post->post_type);
        if ($type && $type->has_archive) {
            $trail[] = [$type->labels->name, (string) get_post_type_archive_link($post->post_type)];
        }
        foreach (array_reverse(get_post_ancestors($post)) as $ancestor) {
            $trail[] = [plain_title($ancestor), (string) get_permalink($ancestor)];
        }
        $trail[] = [plain_title($post), ''];
    } elseif (is_post_type_archive()) {
        $trail[] = [post_type_archive_title('', false), ''];
    } elseif (is_search()) {
        $trail[] = [__('Search', 'cobbleandcandle'), ''];
    } elseif (is_404()) {
        $trail[] = [__('Page not found', 'cobbleandcandle'), ''];
    } elseif (is_archive()) {
        $trail[] = [wp_strip_all_tags(get_the_archive_title()), ''];
    }

    return $trail;
}

/**
 * Page-hero text and image: block attributes win, then the post (title, excerpt, featured image),
 * then the archive (title, description).
 *
 * @param  array<string, mixed>  $attributes  Block attributes.
 * @return array{title: string, lede: string, image_id: int}
 */
function page_hero(array $attributes): array
{
    $post = context_post();
    $title = (string) ($attributes['title'] ?? '');
    $lede = (string) ($attributes['lede'] ?? '');
    $image = (int) ($attributes['imageId'] ?? 0);

    if ($post) {
        $title = $title !== '' ? $title : plain_title($post);
        $lede = $lede !== '' ? $lede : (has_excerpt($post) ? get_the_excerpt($post) : '');
        $image = $image ?: (int) get_post_thumbnail_id($post);
    } elseif (is_archive()) {
        $title = $title !== '' ? $title : wp_strip_all_tags(is_post_type_archive() ? post_type_archive_title('', false) : get_the_archive_title());
        $lede = $lede !== '' ? $lede : wp_strip_all_tags(get_the_archive_description());
    }

    return ['title' => $title !== '' ? $title : __('Page title', 'cobbleandcandle'), 'lede' => $lede, 'image_id' => $image];
}
