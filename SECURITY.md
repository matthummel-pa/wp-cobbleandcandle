# Security policy

## Supported versions

| Version | Supported |
| --- | --- |
| Latest release (`theme-latest`) | ✅ |
| Older releases | Update to the latest; fixes are not backported |

The theme and the **Cobble & Candle Core** plugin are released together. Keep both current.

## Reporting a vulnerability

Please report security issues **privately**, not in a public issue:

- Use GitHub’s private reporting: **[Report a vulnerability](https://github.com/matthummel-pa/wp-cobbleandcandle/security/advisories/new)** (Security tab → Report a vulnerability).

Include the affected version, the steps to reproduce, and the impact (for example, which user role is needed). Please don’t include real guests’ personal data.

What to expect:

- An acknowledgement within **3 working days**.
- An assessment and a fix plan within **10 working days** for confirmed issues.
- Credit in the [changelog](CHANGELOG.md) if you’d like it, once a fix ships.

## How the code is kept safe

- Every form and admin action checks a nonce **and** the user’s capability; every REST route has a permission check.
- Input is sanitized on the way in and output escaped on the way out; database access goes through WordPress APIs or `$wpdb->prepare()`.
- Public forms have a honeypot and a per-IP rate limit; booking requests lapse after 48 hours.
- Guest data (bookings, messages) is visible only to Editors and Administrators, is included in WordPress’s personal-data export and erase tools, and is never written to the event log.
- Each change is reviewed with [wp-review](https://github.com/matthummel-pa/wp-dev-kit) and a security review before release.
