# Reservations

Each location chooses a booking mode (Locations → the house → **Booking**):

| Mode | What guests see |
| --- | --- |
| **Booking form on this site** | Date, party size, real time slots from your hours, seating and occasion, then a request. |
| **OpenTable** / **Resy** | Your provider’s widget, loaded only when the guest clicks *Check availability* (keeps the page fast and private). Paste your booking URL. |
| **Bookings by phone** | A large call button with your number and calling hours. |

## What happens to a request

1. The guest sees “Request sent. The house will confirm your table by email or phone.”
2. You get an email at the location’s **bookings email** (or the site admin email).
3. The request is also saved under **Messages** in the dashboard, even if the email fails.
4. You confirm with the guest by reply or phone.

Requests are just that: requests. Nothing is charged and your tables aren’t blocked automatically, so you stay in control of the floor.

## Messages

**Messages** lists every table request, private-dining inquiry and contact message with its house and whether the email went out. Open one to read it and **Reply by email**. Messages older than 12 months are deleted automatically; change that under Settings → Restaurant → *Keep guest messages for (months)*.

## Spam protection

Every form has a hidden honeypot field and a per-connection limit (5 messages per 10 minutes; 3 for room bookings). Behind Cloudflare, see [Developers](developers.md#client-ip-behind-a-proxy).
