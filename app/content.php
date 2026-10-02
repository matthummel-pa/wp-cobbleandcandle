<?php

/**
 * Menus, chef's picks, and events from the Cobble & Candle Core plugin. Empty without the plugin.
 */

namespace App;

/**
 * @return list<array<string, mixed>>
 */
function menus(): array
{
    return function_exists('cc_get_menus') ? cc_get_menus() : [];
}

/**
 * @return list<array<string, mixed>>
 */
function chef_picks(int $limit = 3): array
{
    return function_exists('cc_chef_picks') ? cc_chef_picks($limit) : [];
}

/**
 * @return list<array<string, mixed>>
 */
function upcoming_events(int $limit = 3): array
{
    return function_exists('cc_upcoming_events') ? cc_upcoming_events($limit) : [];
}

/**
 * First N items of a menu, across its sections in order.
 *
 * @param  array<string, mixed>  $menu  One entry from menus().
 * @return list<array<string, mixed>>
 */
function menu_preview(array $menu, int $limit = 5): array
{
    $items = [];
    foreach ($menu['sections'] ?? [] as $section) {
        foreach ($section['items'] as $item) {
            $items[] = $item;
        }
    }

    return array_slice($items, 0, $limit);
}

/**
 * Placeholder art for a dish without a photo, varied by position.
 */
function dish_art(int $index): string
{
    return ['dish-plate', 'dish-duck', 'dish-dessert', 'dish-pie', 'dish-board'][$index % 5];
}
