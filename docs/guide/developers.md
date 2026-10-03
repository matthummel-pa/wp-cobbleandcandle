# Developers

Theme: Sage 11 (Acorn, Blade, Tailwind v4, Vite, Alpine.js), namespace `App`, text domain `cobbleandcandle`. Plugin: **Cobble & Candle Core**, WordPress Coding Standards, prefix `cc_`, text domain `cobbleandcandle-core`.

## Content model

| Type | Key | Notes |
| --- | --- | --- |
| Location | `cobble_location` | Public, `/locations/{slug}/`; meta `cobble_street`, `cobble_locality`, `cobble_phone`, `cobble_email`, `cobble_hours`, `cobble_holiday_hours`, `cobble_booking_mode`, `cobble_booking_url`, `cobble_order_url`, `cobble_lat`, `cobble_lng`… |
| Dish | `cobble_menu_item` | Taxonomies `cobble_menu`, `cobble_menu_section`; meta `cobble_price`, `cobble_variants`, `cobble_diet`, `cobble_flag`, `cobble_chef_pick`, `cobble_available_at` |
| Event | `cobble_event` | `/events/{slug}/`; meta `cobble_start`, `cobble_end`, `cobble_location`, `cobble_type`, `cobble_price`, `cobble_availability`, `cobble_booking_url`, `cobble_courses` |
| Room | `cobble_room` | `/rooms/{slug}/`; meta `cobble_price_night`, `cobble_price_weekend`, `cobble_min_nights`, `cobble_max_guests`, `cobble_units`, `cobble_beds`, `cobble_size`, `cobble_location`, `cobble_amenities`, `cobble_ical_import` (edit context only) |
| Booking | `cobble_booking` | Private; Editors+; meta `cobble_room`, `cobble_check_in`, `cobble_check_out`, `cobble_guests`, `cobble_status` (pending/confirmed/cancelled), `cobble_total`, `cobble_dinner` |
| Message | `cobble_message` | Private; Editors+; table requests, inquiries, contact |

All fields are registered post meta (REST-enabled, schema-sanitized) and drive the block-editor panels.

## Filters

| Filter | Default | Purpose |
| --- | --- | --- |
| `cobble_currency` | Settings → Restaurant or `USD` | ISO currency for prices and schema |
| `cobble_money` | — | Format a price string |
| `cobble_booking_slot_step` | `30` | Minutes between table slots |
| `cobble_last_seating_offset` | `90` | Minutes before closing for the last table |
| `cobble_form_rate_limit` | `5` (`3` for rooms) | Submissions per IP per 10 minutes, per form |
| `cobble_client_ip` | `REMOTE_ADDR` | IP used for rate limits (see below) |
| `cobble_pending_hold_hours` | `48` | Hours an unanswered room request holds its nights |
| `cobble_room_amenities` | 13 amenities | Amenity keys and labels |
| `cobble_contact_topics` | 5 topics | Contact topics (`sanitize_key`-safe keys → labels) |
| `cobble_current_location_slug` | — | Force the current location |
| `cobble_meta_description` | excerpt / defaults | Fallback meta description |
| `cobble_schema_enabled` | `true` | Turn off the plugin’s JSON-LD |
| `cobble_schema_nodes` | — | Add or change schema nodes on a page |
| `cobble_breadcrumb_trail` | theme crumbs | Breadcrumb items for BreadcrumbList |
| `cobbleandcandle/brand` | Settings → Restaurant | Theme brand values |
| `cobbleandcandle/page_link` | page with the block | Resolve `/reservations/`, `/menu/`, `/rooms/`… links |

## Actions

| Action | Fires |
| --- | --- |
| `cobble_reservation_requested( $request, $sent )` | After a table request (keys **and** `*_label` values) |
| `cobble_room_booking_requested( $booking_id, $sent )` | After a room booking request |
| `cobble_log( $entry )` | Every logged problem; hook error trackers here ([examples](troubleshooting.md#send-errors-to-sentry-or-slack)) |

## Client IP behind a proxy

Only trust a proxy header when the origin accepts traffic from the proxy alone:

```php
add_filter( 'cobble_client_ip', function ( $ip ) {
	// Cloudflare: verify REMOTE_ADDR is a Cloudflare range before trusting the header.
	return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $ip; // phpcs:ignore
} );
```

## REST

`GET /wp-json/cobbleandcandle/v1/rooms/{id}/availability?from=YYYY-MM-DD&to=YYYY-MM-DD` (public): booked nights and prices, never guest data.

## WP-CLI

```bash
wp cobbleandcandle seed            # demo locations, menus, events, rooms (skips existing)
wp cobbleandcandle seed --update   # refresh existing demo posts
```

## Build and release

See [CONTRIBUTING.md](../../CONTRIBUTING.md).
