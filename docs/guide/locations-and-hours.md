# Locations & hours

Each **location** (“house”) has its own address, phone, email, hours, booking mode and map. With more than one, guests pick a house in the header and the whole site follows: hours, phone, directions and the booking form.

## Add or edit a location

**Locations → Add location.** In the right-hand panel:

- **Address & contact**: street, town, phone, bookings email (requests for this house go here), map coordinates (optional; used for Google’s map pin).
- **Opening hours**: open/close per day, or *Closed*. Closing after midnight (e.g. 01:00) is fine.
- **Holiday hours**: specific dates that override the week (Christmas Eve 17:00–21:00, New Year’s Day closed).
- **Booking**: *Booking form on this site*, *OpenTable widget*, *Resy widget* or *Bookings by phone*, plus an optional online-ordering link.
- **Getting there**: parking, transit and accessibility notes shown on the location page and Reservations.

## “Open now”

The header shows *Open · closes 11pm*, *Closing soon*, or *Closed · opens 5:30pm*. It’s worked out in the visitor’s browser from your hours and your site timezone, so it stays correct even on cached pages. Times follow **Settings → General → Time format** (12- or 24-hour).

**Wrong day or time?** Set a city (not a UTC offset) under Settings → General → Timezone.

## Table slots

The native booking form offers times from opening until **90 minutes before closing**, every 30 minutes. Developers can change both (see [Developers](developers.md)).
