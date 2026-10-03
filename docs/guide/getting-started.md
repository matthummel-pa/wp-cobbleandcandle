# Getting started

## Requirements

- WordPress **6.6+** and PHP **8.3+** (ask your host; most offer 8.3).
- “Post name” permalinks and a city timezone (Settings → General). The setup wizard reminds you.
- An email setup that works. Install [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/) if your host’s mail is unreliable.

## Install

1. **Appearance → Themes → Add New → Upload Theme** → choose `cobbleandcandle.zip` → **Install** → **Activate**.
2. A notice appears: **Install & activate Cobble & Candle Core**. Click it. The plugin is bundled inside the theme; it holds your locations, menus, events, rooms and bookings so they survive a theme change.
3. You land in the **setup wizard**.

## The setup wizard (Settings → Restaurant setup)

| Step | What you do |
| --- | --- |
| **Your place** | Name, one-line description, and what you run: restaurant, bar or tavern, rooms. |
| **Look** | Pick a style (Lampwright, Ember & Arch, Ashlar & Iron, Daylight), brand line (“Tavern & kitchen”), year established, currency and 12/24-hour clock. |
| **Content** | **Import the demo** (3 houses, 4 menus, events, 4 rooms) to see everything working, or **start with your own location**. |
| **Pages & menus** | Creates Home, Menu, Reservations, Gallery and Our story, sets the front page, and fills the header and footer menus. Anything you already have is kept. |
| **Done** | Links to the next steps below. |

Each step saves on its own; you can stop and come back. Re-running it never duplicates pages or menu links.

## Your first hour

- [ ] **Locations**: set opening hours and holiday hours for each house ([guide](locations-and-hours.md)).
- [ ] **Menus**: import your menu from a spreadsheet: Food & drink → Import / export ([guide](menus.md)).
- [ ] **Settings → Restaurant**: upload your logo, add social profiles, check currency.
- [ ] **Tools → Cobble & Candle status**: send a test email; fix anything marked *Problem*.
- [ ] **Rooms** (if you have them): set prices and paste your Airbnb / Booking.com calendar links ([guide](rooms-and-stays.md)).
- [ ] Replace demo photos with your own (Media → Add New, then set each dish, room or page photo).
- [ ] Delete the demo content you don’t need (Locations, Food & drink, Events, Rooms).

## Updating

Upload the newer `cobbleandcandle.zip` the same way; WordPress offers **Replace current with uploaded**. Your content is untouched. Purge your page cache afterwards.
