<div align="center">

[![Cobble & Candle — WordPress theme for restaurants, taverns and inns](docs/marketplace/banner.jpg)](https://cobbleandcandle.matthummel.com/)

# Cobble & Candle

**A WordPress block theme for restaurants, taverns and inns.**
Menus with diet filters, live “open now” hours, table *and* room bookings, events and private dining, in one theme with no page builder.

[![Deploy theme](https://github.com/matthummel-pa/wp-cobbleandcandle/actions/workflows/deploy-theme.yml/badge.svg)](https://github.com/matthummel-pa/wp-cobbleandcandle/actions/workflows/deploy-theme.yml)
[![wp-review](https://github.com/matthummel-pa/wp-cobbleandcandle/actions/workflows/wp-review.yml/badge.svg)](https://github.com/matthummel-pa/wp-cobbleandcandle/actions/workflows/wp-review.yml)
[![Version](https://img.shields.io/badge/version-0.1.0-C79A55?style=flat-square)](CHANGELOG.md)
[![License: GPLv2+](https://img.shields.io/badge/license-GPLv2%2B-5C2A22?style=flat-square)](LICENSE)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777bb4?style=flat-square)](https://www.php.net/)
[![WordPress 6.6+](https://img.shields.io/badge/WordPress-6.6%2B-21759b?style=flat-square)](https://wordpress.org/)
[![WCAG 2.2 AA](https://img.shields.io/badge/accessibility-WCAG%202.2%20AA-3D7A47?style=flat-square)](#accessibility)

</div>

|  |  |
| --- | --- |
| **Live demo** | [cobbleandcandle.matthummel.com](https://cobbleandcandle.matthummel.com/) |
| **Shop** | [matthummel.com/shop](https://matthummel.com/shop/) (Cobble & Candle coming soon) |
| **Support** | [SUPPORT.md](SUPPORT.md) · [GitHub Issues](https://github.com/matthummel-pa/wp-cobbleandcandle/issues) |
| **Owner guide** | [docs/guide](docs/guide/README.md) |
| **Author** | [Matt Hummel](https://matthummel.com/) |

> **Demo content is fiction.** Houses, dishes, events, rooms, `555` phone numbers and `@example` emails are sample data. Demo photos are from [Unsplash](https://unsplash.com/) and are **not** included in the theme zip (see [CREDITS.md](CREDITS.md)).

<div align="center">

[📸 Screenshots](#screenshots) · [✨ Features](#features) · [🎨 Styles](#four-styles) · [🛏️ Rooms](#rooms--stays) · [⚙️ Admin](#owner-tools) · [🚀 Install](#install) · [🧰 Developers](#developers) · [🆘 Support](#support)

</div>

---

## Who it is for

- **Restaurant owners** with one house or several, who want menus, hours and bookings that stay right without a developer.
- **Taverns and bars** that live on events, specials and walk-ins.
- **Inns and B&Bs** with rooms upstairs: a real availability calendar, booking requests, and two-way calendar sync with Airbnb, Booking.com and Vrbo.
- **Agencies and freelancers** who want to ship a polished hospitality site in a day and hand it to a client who can edit it safely.

## Screenshots

### Guest experience

| | |
|:---:|:---:|
| [![Home](docs/marketplace/screenshots/01-home.jpg)](docs/marketplace/screenshots/01-home.jpg) | [![Signature dishes](docs/marketplace/screenshots/02-home-signatures.jpg)](docs/marketplace/screenshots/02-home-signatures.jpg) |
| **Home**: live “Closed · opens 5:30pm”, location switcher | **Signature dishes**: chef’s picks, diet badges, prices |
| [![Menu](docs/marketplace/screenshots/03-menu.jpg)](docs/marketplace/screenshots/03-menu.jpg) | [![Reservations](docs/marketplace/screenshots/04-reservations.jpg)](docs/marketplace/screenshots/04-reservations.jpg) |
| **Menu**: tabs, section links that follow your scroll, diet filters | **Reservations**: real time slots from each house’s hours |
| [![Rooms](docs/marketplace/screenshots/05-rooms.jpg)](docs/marketplace/screenshots/05-rooms.jpg) | [![Room booking](docs/marketplace/screenshots/06-room-booking.jpg)](docs/marketplace/screenshots/06-room-booking.jpg) |
| **Rooms**: beds, guests, size, from-price | **Room booking**: live availability calendar, weekend pricing, running total |
| [![Events](docs/marketplace/screenshots/07-events.jpg)](docs/marketplace/screenshots/07-events.jpg) | [![Locations](docs/marketplace/screenshots/08-locations.jpg)](docs/marketplace/screenshots/08-locations.jpg) |
| **Events**: featured next event, type filters, add to calendar | **Locations**: hours, holiday hours, directions, parking |
| [![Gallery](docs/marketplace/screenshots/09-gallery.jpg)](docs/marketplace/screenshots/09-gallery.jpg) | [![Story](docs/marketplace/screenshots/10-story.jpg)](docs/marketplace/screenshots/10-story.jpg) |
| **Gallery**: category filters, keyboard lightbox | **Our story**: timeline, values, team |

### On a phone

| | | | | |
|:---:|:---:|:---:|:---:|:---:|
| [![Home](docs/marketplace/screenshots/m-home.jpg)](docs/marketplace/screenshots/m-home.jpg) | [![Menu](docs/marketplace/screenshots/m-menu.jpg)](docs/marketplace/screenshots/m-menu.jpg) | [![Table booking](docs/marketplace/screenshots/m-reservations.jpg)](docs/marketplace/screenshots/m-reservations.jpg) | [![Room booking](docs/marketplace/screenshots/m-room-booking.jpg)](docs/marketplace/screenshots/m-room-booking.jpg) | [![Events](docs/marketplace/screenshots/m-events.jpg)](docs/marketplace/screenshots/m-events.jpg) |
| Home | Menu | Book a table | Book a room | Events |

| | | | | |
|:---:|:---:|:---:|:---:|:---:|
| [![Menu drawer](docs/marketplace/screenshots/m-drawer.jpg)](docs/marketplace/screenshots/m-drawer.jpg) | [![Location](docs/marketplace/screenshots/m-location.jpg)](docs/marketplace/screenshots/m-location.jpg) | [![Stay total and dinner](docs/marketplace/screenshots/m-room-dinner.jpg)](docs/marketplace/screenshots/m-room-dinner.jpg) | [![Lightbox](docs/marketplace/screenshots/m-lightbox.jpg)](docs/marketplace/screenshots/m-lightbox.jpg) | [![Daylight](docs/marketplace/screenshots/m-daylight-home.jpg)](docs/marketplace/screenshots/m-daylight-home.jpg) |
| Menu drawer | Location | Total + dinner | Lightbox | Daylight |

The bottom bar (Reserve · Stay · Call · Directions) follows the guest’s chosen location. On a room page, **Book stay** leads.

### More of the guest experience

| | |
|:---:|:---:|
| [![Location page](docs/marketplace/screenshots/11-location.jpg)](docs/marketplace/screenshots/11-location.jpg) | [![Event page](docs/marketplace/screenshots/12-event.jpg)](docs/marketplace/screenshots/12-event.jpg) |
| **Location page**: hours, holiday hours, parking, transit, map | **Event page**: courses, ticket card, add to calendar |
| [![Diet filter](docs/marketplace/screenshots/15-menu-diet-filter.jpg)](docs/marketplace/screenshots/15-menu-diet-filter.jpg) | [![Location switcher](docs/marketplace/screenshots/16-location-switcher.jpg)](docs/marketplace/screenshots/16-location-switcher.jpg) |
| **Diet filter**: vegetarian on, live count | **Location switcher**: each house open/closed at a glance |
| [![Private dining](docs/marketplace/screenshots/13-private-dining.jpg)](docs/marketplace/screenshots/13-private-dining.jpg) | [![Reviews](docs/marketplace/screenshots/14-reviews.jpg)](docs/marketplace/screenshots/14-reviews.jpg) |
| **Private dining**: rooms, capacities and inquiry form | **Reviews**: guest quotes by house |
| [![Stay with us](docs/marketplace/screenshots/18-stay-section.jpg)](docs/marketplace/screenshots/18-stay-section.jpg) | [![What’s on](docs/marketplace/screenshots/19-events-home.jpg)](docs/marketplace/screenshots/19-events-home.jpg) |
| **Stay with us**: rooms on the home page (hidden when you have none) | **What’s on**: next events with date boxes |
| [![Gallery lightbox](docs/marketplace/screenshots/17-lightbox.jpg)](docs/marketplace/screenshots/17-lightbox.jpg) | [![Page not found](docs/marketplace/screenshots/20-404.jpg)](docs/marketplace/screenshots/20-404.jpg) |
| **Gallery lightbox**: arrows, Esc, captions, focus return | **Page not found**: branded, with a way back |
| [![Daylight room page](docs/marketplace/screenshots/21-daylight-room.jpg)](docs/marketplace/screenshots/21-daylight-room.jpg) | [![Daylight menu](docs/marketplace/screenshots/22-daylight-menu.jpg)](docs/marketplace/screenshots/22-daylight-menu.jpg) |
| **Daylight room page**: the café style on a room | **Daylight menu**: the café style on the menu |

## Four styles

One click in the setup wizard or **Appearance → Editor → Styles** changes type, colour, texture and illustrations together.

[![Four styles](docs/marketplace/styles.jpg)](docs/marketplace/styles.jpg)

| Style | Mood | Fonts |
| --- | --- | --- |
| **Lampwright** | Candlelit old town, brass and lamp-black | Bodoni Moda · Mulish |
| **Ember & Arch** | Brick, ember and firelight | EB Garamond · Outfit |
| **Ashlar & Iron** | Limestone, iron and claret | Marcellus · Alegreya Sans |
| **Daylight** | Café and brunch, warm white and terracotta | Outfit · Mulish |

## Features

### For guests
- **Live “open now”** in the header (“Closing soon · closes 11pm”), computed in the browser so cached pages never go stale. Holiday hours, past-midnight closing, 12- or 24-hour clocks.
- **Several houses** with a location switcher. Hours, phone, directions and the booking form follow the guest’s choice.
- **Menus** with tabs, sticky section links, dietary filters (vegetarian, vegan, gluten-free, spicy) with a live count, allergen key, sizes (glass/bottle, starter/main) and chef’s picks.
- **Table bookings** three ways per house: a native request form with real slots from opening hours, OpenTable/Resy behind a fast click-to-load facade, or call-to-book.
- **Rooms & stays**: availability calendar (only free nights are selectable), minimum stay, weekend pricing, running total, and an optional **“add dinner on your first night”** table.
- **Events** with a featured next event, type filters, tickets or booking links, and **Add to calendar (.ics)**.
- **Private dining** inquiries, gallery with lightbox, contact form, reviews, timeline and values blocks.

### Rooms & stays
- Rooms with nightly and Friday/Saturday prices, minimum nights, max guests, identical units, beds, size and amenities.
- Booking **requests** held as pending until you confirm. **Confirm / Cancel** from the dashboard emails the guest. Unanswered requests lapse after 48 hours so fake requests can’t block your calendar.
- **Two-way iCal sync**: a private feed per room for Airbnb, Booking.com and Vrbo; their calendars are imported hourly. If a feed fails, its last known nights stay blocked.
- No card payments in v1, on purpose. You confirm every stay yourself.

### Owner tools
- **Setup wizard**: name, style, brand line, currency, clock, demo import or your first location, pages and menus. Five steps, no WP-CLI. Nothing existing is overwritten.
- **Restaurant settings**: logo, brand line, year established, cuisine, price range, currency, social profiles, message retention.
- **Menu CSV import / export**: edit a whole menu in Excel or Google Sheets; preview before importing; never deletes.
- **Messages**: every table request, inquiry and contact message is saved in the dashboard as well as emailed, so a broken mail setup never loses a guest.
- **Status & logs**: health checks, a test email, recent problems (failed emails, unreadable calendar feeds) and a copyable system report for support.
- **Privacy**: personal-data export and erase for bookings and messages, automatic deletion of old messages, suggested privacy-policy text.

### Every owner screen

| | |
|:---:|:---:|
| [![Setup · your place](docs/marketplace/screenshots/a-setup-place.jpg)](docs/marketplace/screenshots/a-setup-place.jpg) | [![Setup · look](docs/marketplace/screenshots/a-setup.jpg)](docs/marketplace/screenshots/a-setup.jpg) |
| **Setup · your place**: name, tagline, restaurant / bar / rooms | **Setup · look**: style, brand line, currency, clock |
| [![Setup · content](docs/marketplace/screenshots/a-setup-content.jpg)](docs/marketplace/screenshots/a-setup-content.jpg) | [![Setup · pages & menus](docs/marketplace/screenshots/a-setup-pages.jpg)](docs/marketplace/screenshots/a-setup-pages.jpg) |
| **Setup · content**: import the demo or add your first location | **Setup · pages & menus**: creates what is missing, keeps what you have |
| [![Setup · done](docs/marketplace/screenshots/a-setup-done.jpg)](docs/marketplace/screenshots/a-setup-done.jpg) | [![Restaurant settings](docs/marketplace/screenshots/a-settings.jpg)](docs/marketplace/screenshots/a-settings.jpg) |
| **Setup · done**: next steps | **Restaurant settings**: brand, search, social, privacy |
| [![Bookings](docs/marketplace/screenshots/a-bookings.jpg)](docs/marketplace/screenshots/a-bookings.jpg) | [![Booking](docs/marketplace/screenshots/a-booking-edit.jpg)](docs/marketplace/screenshots/a-booking-edit.jpg) |
| **Bookings**: dates, guests, totals, dinner, status; Confirm / Cancel | **Booking**: edit or add a phone booking |
| [![Rooms](docs/marketplace/screenshots/a-rooms.jpg)](docs/marketplace/screenshots/a-rooms.jpg) | [![Room editor](docs/marketplace/screenshots/a-room-edit.jpg)](docs/marketplace/screenshots/a-room-edit.jpg) |
| **Rooms**: your rooms in menu order | **Room editor**: rates, amenities, calendar sync |
| [![Location editor](docs/marketplace/screenshots/a-location-edit.jpg)](docs/marketplace/screenshots/a-location-edit.jpg) | [![Dish editor](docs/marketplace/screenshots/a-dish-edit.jpg)](docs/marketplace/screenshots/a-dish-edit.jpg) |
| **Location editor**: address, hours, holiday hours, booking mode | **Dish editor**: price, sizes, diet, chef’s pick |
| [![Food & drink](docs/marketplace/screenshots/a-menu-items.jpg)](docs/marketplace/screenshots/a-menu-items.jpg) | [![CSV import preview](docs/marketplace/screenshots/a-import-preview.jpg)](docs/marketplace/screenshots/a-import-preview.jpg) |
| **Food & drink**: every dish across menus and sections | **CSV import preview**: check before importing; nothing is deleted |
| [![Messages](docs/marketplace/screenshots/a-messages.jpg)](docs/marketplace/screenshots/a-messages.jpg) | [![Message](docs/marketplace/screenshots/a-message.jpg)](docs/marketplace/screenshots/a-message.jpg) |
| **Messages**: table requests, inquiries, contact — kept even if email fails | **Message**: read and reply by email |
| [![Status & logs](docs/marketplace/screenshots/a-status.jpg)](docs/marketplace/screenshots/a-status.jpg) | [![Site Editor](docs/marketplace/screenshots/a-site-editor.jpg)](docs/marketplace/screenshots/a-site-editor.jpg) |
| **Status & logs**: health checks, test email, error log, system report | **Site Editor**: templates, patterns and the four styles |

### Search (SEO)
- Google-ready **Restaurant** data per location (hours including holidays, map coordinates, cuisine, price range, reservations, ordering), **Menu** → sections → dishes with prices and diet tags, **Event**, **HotelRoom** with nightly offers, **BreadcrumbList**, **Organization** and **WebSite**.
- Joins the Yoast SEO and Rank Math graphs instead of duplicating them; prints its own meta description, Open Graph and Twitter tags when no SEO plugin is active.
- Menus are real, indexable text. Gallery images are crawlable links. Lighthouse SEO 100.

### Accessibility
- Built for **WCAG 2.2 AA**: Lighthouse accessibility 100 and zero axe-core violations across the demo pages in all four styles.
- Keyboard-friendly tabs, location listbox, drawer focus trap and lightbox; 44 px touch targets; reduced-motion support; visible focus; labelled forms with clear errors.
- **Right-to-left** languages supported (Arabic, Hebrew, Farsi). Translation-ready with `.pot` files and `wpml-config.xml`.

> We test against WCAG 2.2 AA; no theme can promise legal compliance on its own. Your content (images, PDFs, colours you change) matters too.

### Under the hood
- Block theme on [Sage 11](https://roots.io/sage/) (Blade, Acorn, Tailwind v4, Vite, Alpine.js). Edit everything in the Site Editor.
- Your content lives in the **Cobble & Candle Core** plugin (bundled in the zip, installed with one click), so it survives a theme switch.
- No page builder, no ACF, no jQuery on the front end. Fonts are bundled, so pages make no Google Fonts requests.

## Why not a typical restaurant theme?

| | Cobble & Candle | Typical marketplace restaurant theme |
| --- | --- | --- |
| Editing | WordPress Site Editor, blocks | Elementor or a page builder |
| “Open now” and holiday hours | Live, per location | Static text |
| Diet filters and allergen key | Built in | Rare |
| Table bookings | Native form, OpenTable/Resy, or call | Usually a third-party widget only |
| Rooms with availability and iCal sync | Built in | Needs a separate booking plugin |
| Restaurant / Menu schema | Built in, works with Yoast and Rank Math | Usually none |
| Accessibility | WCAG 2.2 AA tested, RTL | Rarely tested |

## Install

**Requirements:** WordPress 6.6+, PHP 8.3+, HTTPS recommended. Works with any host that runs WordPress; caching plugins (LiteSpeed, WP Rocket) are supported.

1. Download `cobbleandcandle.zip` (from your purchase, or the [`theme-latest` release](https://github.com/matthummel-pa/wp-cobbleandcandle/releases/tag/theme-latest)).
2. **Appearance → Themes → Add New → Upload Theme**, choose the zip, then **Activate**.
3. Click **Install & activate Cobble & Candle Core** in the notice. It is bundled inside the theme.
4. The **setup wizard** opens. Five steps and you have a working site.
5. Set opening hours under **Locations**, then import your menu from a spreadsheet under **Food & drink → Import / export**.

Full walkthrough: [docs/guide/getting-started.md](docs/guide/getting-started.md).

### Updates

Install the newer `cobbleandcandle.zip` the same way (WordPress offers **Replace current with uploaded**). Your content lives in the plugin and the database, so theme updates never touch it. If you use a page cache, purge it after updating.

## Developers

```bash
git clone https://github.com/matthummel-pa/wp-cobbleandcandle.git
cd wp-cobbleandcandle
composer install
npm install
npm run build        # or: npm run dev for hot reload
```

Link the folder into a local site (for example [WordPress Studio](https://developer.wordpress.com/studio/)) as `wp-content/themes/cobbleandcandle`, and link or copy `plugins/cobbleandcandle-core` into `wp-content/plugins/`. Load demo content with `wp cobbleandcandle seed` or the setup wizard.

- **Hooks and filters:** [docs/guide/developers.md](docs/guide/developers.md)
- **Contributing, coding standards and the release flow:** [CONTRIBUTING.md](CONTRIBUTING.md)
- **Error tracking:** every failure fires `do_action( 'cc_log', $entry )`. See [Troubleshooting](docs/guide/troubleshooting.md#send-errors-to-sentry-or-slack).

## Support

Start with **Tools → Cobble & Candle status** in your dashboard. It checks your setup and gives you a system report to paste into a request. Then see [SUPPORT.md](SUPPORT.md) for the owner guide, the FAQ and how to report a bug. Security issues: [SECURITY.md](SECURITY.md).

## Credits and license

Theme and plugin: **GPL-2.0-or-later** © [Matt Hummel](https://matthummel.com/). Fonts: SIL Open Font License 1.1. Sage and Acorn: MIT. Full list in [CREDITS.md](CREDITS.md). Brand usage in [BRAND.md](BRAND.md). Release notes in [CHANGELOG.md](CHANGELOG.md).
