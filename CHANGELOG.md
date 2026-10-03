# Changelog

All notable changes to the Cobble & Candle theme and the Cobble & Candle Core plugin. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [Semantic Versioning](https://semver.org/).

## [Unreleased] — toward 1.0.0

### Added
- **Three demos in one theme:** Restaurant, Tavern and B&B home pages (patterns `home-tavern`, `home-bnb`) with a “Demo” switcher in the header next to the style switcher. A page can declare its kind (`cobble_kind`): it gets a paired look (Lampwright / Ashlar & Iron / Daylight), “Book a stay” on the B&B, and Stay first in the mobile bar. The demo import creates the pages and turns the switchers on.
- **Demo bar** above the header (theme demo only): business type, the four styles, a “Get this theme” link and a close button that hides it for the session. The utility bar is back to location, status and phone.
- **Rooms & stays (B&B)**: rooms with nightly and weekend prices, minimum stay, units and amenities; a live availability calendar; booking requests confirmed from the dashboard with guest emails; two-way iCal sync with Airbnb, Booking.com and Vrbo; HotelRoom structured data.
- **Stay & dine**: guests can add a dinner table on their first night when booking a room.
- **Setup wizard** (Settings → Restaurant setup) with one-click demo import, pages and menus; no WP-CLI needed.
- **Menu CSV import and export** with a preview step.
- **Messages**: every table request, inquiry and contact message is saved in the dashboard as well as emailed.
- **Status & logs** (Tools → Cobble & Candle status): health checks, test email, event log, system report, `cobble_log` hook for error trackers.
- **Daylight** style (café & brunch), the fourth style direction.
- **Right-to-left** language support.
- Branded admin screens; mobile bar **Stay** button; “Staying the night?” card on Reservations.
- Owner setup: logo upload, brand line, year established, currency, social profiles, 12/24-hour clock.
- SEO layer: Restaurant, Menu, Event, Organization, WebSite and BreadcrumbList schema; meta and share tags; Yoast and Rank Math integration.
- Translation: all copy translatable, `.pot` files, `wpml-config.xml`.
- Packaging: screenshot, readme, licences, bundled plugin with one-click install, uninstall clean-up (opt-in).
- Privacy: personal-data export/erase for bookings and messages; automatic deletion of old messages (default 12 months).

### Changed
- **Unique prefix:** every function, hook, option, post type, taxonomy, meta key and handle now uses `cobble_` (was the generic `cc_`). Existing sites are migrated automatically and once on update; URLs don’t change, and calendar-feed / `.ics` links and the saved-location cookie from before keep working. Theme and plugin must be updated together (both 1.0.0); the theme shows an “Update Cobble & Candle Core” notice while an older plugin is active. WPML translation links are migrated too. Purge the page cache after updating so forms use the new field names. Custom code that used `cc_*` hooks or functions needs the new names (see [Developers](docs/guide/developers.md)).

### Fixed
- Dishes now open in the block editor with their Price & details panel (previously the classic screen showed raw custom fields).
- Active menu-section chip label was invisible (accent on accent).
- Menus load with one query instead of one per section; placeholder art is drawn once per page.
- Responsive and accessibility fixes from the product audit: timezone-correct weekdays, escaped titles and links, landmarks, footer headings, “Continued” link text.

### Security
- Hardened booking engine (capabilities, request limits, feed validation), forms (stable choice keys, client-IP filter) and setup wizard (never overwrites owner settings). See the pull requests for details.

[Unreleased]: https://github.com/matthummel-pa/wp-cobbleandcandle/commits/main
