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
    if (! function_exists('cobble_get_locations') || ! function_exists('cobble_location')) {
        return [];
    }

    return array_values(array_filter(array_map('cobble_location', cobble_get_locations())));
}

/**
 * The server-rendered location (?loc=slug, else the first). The visitor's saved choice is applied in the browser.
 *
 * @return array<string, mixed>
 */
function current_location(): array
{
    return function_exists('cobble_current_location') ? cobble_current_location() : [];
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
        'windows' => function_exists('cobble_status_windows') ? cobble_status_windows($l['id']) : null,
    ], locations()), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

/**
 * Open-now settings for the browser: site timezone and the status phrases (Core plugin).
 */
function status_json(): string
{
    if (! function_exists('cobble_status_labels')) {
        return '{}';
    }

    return (string) wp_json_encode(['tz' => wp_timezone_string(), 'labels' => cobble_status_labels()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

/**
 * One row per weekday (Monday first) for the hours table: [day, hours text, is today].
 *
 * @return list<array{0: string, 1: string, 2: bool}>
 */
function week_rows(int $location_id): array
{
    if (! function_exists('cobble_day_window')) {
        return [];
    }
    $week = (array) get_post_meta($location_id, 'cobble_hours', true);
    $today = (int) wp_date('N') - 1;
    // Build dates in the site timezone: strtotime() is UTC, so wp_date() would shift US sites a day back.
    $monday = new \DateTimeImmutable('monday this week', wp_timezone());
    $rows = [];
    for ($i = 0; $i < 7; $i++) {
        $window = cobble_day_window($week[$i] ?? null);
        $rows[] = [
            wp_date('l', $monday->modify("+{$i} days")->getTimestamp()),
            $window ? cobble_time_label(cobble_minutes_to_time($window[0])).' – '.cobble_time_label(cobble_minutes_to_time($window[1])) : __('Closed', 'cobbleandcandle'),
            $i === $today,
        ];
    }

    return $rows;
}

/**
 * Upcoming holiday hours: [label, date text, hours text].
 *
 * @return list<array{0: string, 1: string, 2: string}>
 */
function holiday_rows(int $location_id): array
{
    if (! function_exists('cobble_day_window')) {
        return [];
    }
    $rows = [];
    foreach ((array) get_post_meta($location_id, 'cobble_holiday_hours', true) as $holiday) {
        if (! is_array($holiday) || ($holiday['date'] ?? '') < wp_date('Y-m-d')) {
            continue;
        }
        $window = cobble_day_window($holiday);
        $rows[] = [
            (string) ($holiday['label'] ?? ''),
            wp_date('D j M', (date_create_immutable($holiday['date'], wp_timezone()) ?: new \DateTimeImmutable('now', wp_timezone()))->getTimestamp()),
            $window ? cobble_time_label(cobble_minutes_to_time($window[0])).' – '.cobble_time_label(cobble_minutes_to_time($window[1])) : __('Closed', 'cobbleandcandle'),
        ];
    }

    return $rows;
}
