# Contributing

Thanks for helping. This file covers the local setup, standards and release flow.

## Local setup

```bash
git clone https://github.com/matthummel-pa/wp-cobbleandcandle.git
cd wp-cobbleandcandle
composer install && npm install
npm run dev            # Vite with hot reload (or npm run build)
```

- Link the repo into a local WordPress (we use [WordPress Studio](https://developer.wordpress.com/studio/)) as `wp-content/themes/cobbleandcandle`, and `plugins/cobbleandcandle-core` into `wp-content/plugins/`.
- Load sample content: `wp cobbleandcandle seed`, or run the setup wizard.
- After Blade edits, if a view looks stale: `wp acorn view:clear`.

## Where things live

| Area | Path |
| --- | --- |
| Theme PHP (Acorn) | `app/` |
| Blocks (block.json + Blade view) | `resources/blocks/*`, `resources/views/blocks/*` |
| Design tokens and styles | `resources/css/` (`tokens.css` = the four style directions) |
| Front-end JS (Alpine) | `resources/js/app.js` |
| Templates, parts, patterns | `templates/`, `parts/`, `patterns/` |
| Style variations | `styles/*.json` |
| Companion plugin (data, forms, bookings, SEO) | `plugins/cobbleandcandle-core/` |

## Standards

- **WordPress Coding Standards** in the plugin (`phpcs.xml.dist`), **Pint** in the theme. Escape late, sanitize early, nonce **and** capability on every state change, `$wpdb->prepare()` for any SQL.
- **CSS:** WordPress’s own CSS is unlayered and beats Tailwind layers. Rules that must override core output go in `resources/css/core-blocks.css`. Use logical properties (`margin-inline-start`) so right-to-left works.
- **Accessibility:** WCAG 2.2 AA. 44 px targets, visible focus, labels on every control, no information by colour alone.
- **i18n:** every user-facing string in `__()` (theme domain `cobbleandcandle`, plugin domain `cobbleandcandle-core`). After changing block defaults: `npm run translate:blocks`.
- **Privacy:** never log or email guest details beyond what the owner needs; new personal data must be added to the privacy exporter/eraser.

## Before you open a pull request

```bash
wp-review            # changed-lines WordPress review (escaping, nonces, i18n…)
vendor/bin/pint      # theme PHP style
npm run build        # when CSS, JS or Blade changed
```

- Branch from `main` as `feat/…`, `fix/…` or `chore/…`. Never push to `main`.
- One concern per PR, with what changed, why, and how you tested it (pages, screen sizes, styles).
- CI runs `wp-review` on every PR.

## Releases

Merging to `main` runs `.github/workflows/deploy-theme.yml`: Composer (no dev), `npm run build`, then `.github/scripts/pack-theme.sh` builds `cobbleandcandle.zip` (with the plugin zip inside) and publishes it on the `theme-latest` release. A merged PR is not live on a site until that zip is installed there. Update `CHANGELOG.md` and the version in `style.css` and the plugin header for tagged releases.
