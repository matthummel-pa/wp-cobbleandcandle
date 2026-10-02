<?php

/**
 * Location data from the Cobble & Candle Core plugin. Every helper returns empty data when the
 * plugin is inactive, so the theme still renders (the switcher and location columns just hide).
 */

namespace App;

/**
 * All published locations as display arrays.
 *
 * @return list<array<string, mixed>>
 */
function locations(): array
{
    if (! function_exists('cc_get_locations') || ! function_exists('cc_location')) {
        return [];
    }

    return array_values(array_filter(array_map('cc_location', cc_get_locations())));
}

/**
 * The visitor's current location (?loc=slug, then the cc_loc cookie, then the first).
 *
 * @return array<string, mixed>
 */
function current_location(): array
{
    return function_exists('cc_current_location') ? cc_current_location() : [];
}

/**
 * Locations as JSON for the Alpine store (only the fields the header, footer, and mobile bar swap).
 */
function locations_json(): string
{
    return (string) wp_json_encode(array_map(fn (array $l): array => [
        'slug' => $l['slug'],
        'name' => $l['name'],
        'phone' => $l['phone'],
        'tel' => $l['tel'],
        'map_url' => $l['map_url'],
        'order_url' => $l['order_url'],
        'status' => $l['status'],
    ], locations()), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}
