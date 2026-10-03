# Developers

Theme: Sage 11 (Acorn, Blade, Tailwind v4, Vite, Alpine.js), namespace `App`, text domain `cobbleandcandle`. Plugin: **Cobble & Candle Core**, WordPress Coding Standards, prefix `cc_`, text domain `cobbleandcandle-core`.

## Content model

| Type | Key | Notes |
| --- | --- | --- |
| Location | `cc_location` | Public, `/locations/{slug}/`; meta `cc_street`, `cc_locality`, `cc_phone`, `cc_email`, `cc_hours`, `cc_holiday_hours`, `cc_booking_mode`, `cc_booking_url`, `cc_order_url`, `cc_lat`, `cc_lng`… |
| Dish | `cc_menu_item` | Taxonomies `cc_menu`, `cc_menu_section`; meta `cc_price`, `cc_variants`, `cc_diet`, `cc_flag`, `cc_chef_pick`, `cc_available_at` |
| Event | `cc_event` | `/events/{slug}/`; meta `cc_start`, `cc_end`, `cc_location`, `cc_type`, `cc_price`, `cc_availability`, `cc_booking_url`, `cc_courses` |
| Room | `cc_room` | `/rooms/{slug}/`; meta `cc_price_night`, `cc_price_weekend`, `cc_min_nights`, `cc_max_guests`, `cc_units`, `cc_beds`, `cc_size`, `cc_location`, `cc_amenities`, `cc_ical_import` (edit context only) |
| Booking | `cc_booking` | Private; Editors+; meta `cc_room`, `cc_check_in`, `cc_check_out`, `cc_guests`, `cc_status` (pending/confirmed/cancelled), `cc_total`, `cc_dinner` |
| Message | `cc_message` | Private; Editors+; table requests, inquiries, contact |

All fields are registered post meta (REST-enabled, schema-sanitized) and drive the block-editor panels.

## Filters

| Filter | Default | Purpose |
| --- | --- | --- |
| `cc_currency` | Settings → Restaurant or `USD` | ISO currency for prices and schema |
| `cc_money` | — | Format a price string |
| `cc_booking_slot_step` | `30` | Minutes between table slots |
| `cc_last_seating_offset` | `90` | Minutes before closing for the last table |
| `cc_form_rate_limit` | `5` (`3` for rooms) | Submissions per IP per 10 minutes, per form |
| `cc_client_ip` | `REMOTE_ADDR` | IP used for rate limits (see below) |
| `cc_pending_hold_hours` | `48` | Hours an unanswered room request holds its nights |
| `cc_room_amenities` | 13 amenities | Amenity keys and labels |
| `cc_contact_topics` | 5 topics | Contact topics (`sanitize_key`-safe keys → labels) |
| `cc_current_location_slug` | — | Force the current location |
| `cc_meta_description` | excerpt / defaults | Fallback meta description |
| `cc_schema_enabled` | `true` | Turn off the plugin’s JSON-LD |
| `cc_schema_nodes` | — | Add or change schema nodes on a page |
| `cc_breadcrumb_trail` | theme crumbs | Breadcrumb items for BreadcrumbList |
| `cobbleandcandle/brand` | Settings → Restaurant | Theme brand values |
| `cobbleandcandle/page_link` | page with the block | Resolve `/reservations/`, `/menu/`, `/rooms/`… links |

## Actions

| Action | Fires |
| --- | --- |
| `cc_reservation_requested( $request, $sent )` | After a table request (keys **and** `*_label` values) |
| `cc_room_booking_requested( $booking_id, $sent )` | After a room booking request |
| `cc_log( $entry )` | Every logged problem; hook error trackers here ([examples](troubleshooting.md#send-errors-to-sentry-or-slack)) |

## Client IP behind a proxy

Only trust a proxy header when the origin accepts traffic from the proxy alone:

```php
add_filter( 'cc_client_ip', function ( $ip ) {
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
