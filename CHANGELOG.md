# Changelog

All notable changes to the Cobble & Candle theme and the Cobble & Candle Core plugin. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [Semantic Versioning](https://semver.org/).

## [Unreleased] — toward 1.0.0

### Added
- **Rooms & stays (B&B)**: rooms with nightly and weekend prices, minimum stay, units and amenities; a live availability calendar; booking requests confirmed from the dashboard with guest emails; two-way iCal sync with Airbnb, Booking.com and Vrbo; HotelRoom structured data.
- **Stay & dine**: guests can add a dinner table on their first night when booking a room.
- **Setup wizard** (Settings → Restaurant setup) with one-click demo import, pages and menus; no WP-CLI needed.
- **Menu CSV import and export** with a preview step.
- **Messages**: every table request, inquiry and contact message is saved in the dashboard as well as emailed.
- **Status & logs** (Tools → Cobble & Candle status): health checks, test email, event log, system report, `cc_log` hook for error trackers.
- **Daylight** style (café & brunch), the fourth style direction.
- **Right-to-left** language support.
- Branded admin screens; mobile bar **Stay** button; “Staying the night?” card on Reservations.
- Owner setup: logo upload, brand line, year established, currency, social profiles, 12/24-hour clock.
- SEO layer: Restaurant, Menu, Event, Organization, WebSite and BreadcrumbList schema; meta and share tags; Yoast and Rank Math integration.
- Translation: all copy translatable, `.pot` files, `wpml-config.xml`.
- Packaging: screenshot, readme, licences, bundled plugin with one-click install, uninstall clean-up (opt-in).
- Privacy: personal-data export/erase for bookings and messages; automatic deletion of old messages (default 12 months).

### Fixed
- Dishes now open in the block editor with their Price & details panel (previously the classic screen showed raw custom fields).
- Active menu-section chip label was invisible (accent on accent).
- Menus load with one query instead of one per section; placeholder art is drawn once per page.
- Responsive and accessibility fixes from the product audit: timezone-correct weekdays, escaped titles and links, landmarks, footer headings, “Continued” link text.

### Security
- Hardened booking engine (capabilities, request limits, feed validation), forms (stable choice keys, client-IP filter) and setup wizard (never overwrites owner settings). See the pull requests for details.

[Unreleased]: https://github.com/matthummel-pa/wp-cobbleandcandle/commits/main
