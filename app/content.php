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
    return function_exists('cobble_get_menus') ? cobble_get_menus() : [];
}

/**
 * @return list<array<string, mixed>>
 */
function chef_picks(int $limit = 3): array
{
    return function_exists('cobble_chef_picks') ? cobble_chef_picks($limit) : [];
}

/**
 * @return list<array<string, mixed>>
 */
function upcoming_events(int $limit = 3): array
{
    return function_exists('cobble_upcoming_events') ? cobble_upcoming_events($limit) : [];
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

/**
 * Split "a | b | c" lines (block textarea settings) into rows of exactly $columns trimmed strings.
 *
 * @return list<list<string>>
 */
function pipe_lines(string $text, int $columns): array
{
    $rows = [];
    foreach (preg_split('/\R/', $text) ?: [] as $line) {
        if (trim($line) === '') {
            continue;
        }
        $rows[] = array_pad(array_slice(array_map('trim', explode('|', $line, $columns)), 0, $columns), $columns, '');
    }

    return $rows;
}

/**
 * Rooms with a "from" price label for cards.
 *
 * @return list<array<string, mixed>>
 */
function rooms(int $limit = 50): array
{
    if (! function_exists('cobble_get_rooms')) {
        return [];
    }

    return array_map(fn (array $room): array => $room + [
        'from' => $room['price_night'] > 0 ? cobble_money(min(array_filter([$room['price_night'], $room['price_weekend']]))) : '',
    ], array_slice(cobble_get_rooms(), 0, $limit));
}
