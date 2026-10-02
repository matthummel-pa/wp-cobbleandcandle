# Cobble & Candle

Roots Sage 11 theme (Blade, Acorn, Tailwind v4, Vite) for a multi-location restaurant group. The repo root is the theme folder `wp-content/themes/cobbleandcandle`. Owner: Matt Hummel (https://matthummel.com).

The build spec is `docs/mockups/HANDOFF.md` (static mockups in `docs/mockups/`). Project rules: `.cursor/rules/cobbleandcandle-project.mdc`.

## Local

- WordPress Studio site: `~/Studio/cobbleandcandle` (theme symlinked as `cobbleandcandle`). Use `studio wp … --path ~/Studio/cobbleandcandle`.
- `composer install`, then `npm install` and `npm run build` (required: no Vite manifest = white screen). `npm run dev` for HMR.
- Vite `base` is `/wp-content/themes/cobbleandcandle/public/build/`; keep it in sync with the folder name.
- After Blade edits: `studio wp acorn view:clear --path ~/Studio/cobbleandcandle`.
- Mockups: `cd docs/mockups && python3 -m http.server 8765`.

## Standards

- PHP style: `vendor/bin/pint`. WordPress review: `wp-review` (CI: `python3 .github/scripts/wp-review`).
- Checklist: `.github/review-checklist.md`. Branch + PR; never push to `main`.
- Rules: `.cursor/rules/*.mdc` is the source; run `sync-rules` to mirror into `.claude/`.

## Deploy

Merging to `main` runs `.github/workflows/deploy-theme.yml`: Composer (no dev) + `npm run build`, then the GitHub Release `theme-latest` gets `cobbleandcandle.zip`. Install that zip on the site and purge the cache. `docs/`, `.claude/`, and other dev files are left out of the zip.

## Gotchas

- WordPress caches the theme's `patterns/` file list. After adding or renaming a pattern file locally:
  `studio wp eval 'wp_get_theme()->delete_pattern_cache();' --path ~/Studio/cobbleandcandle`
  (or set `define('WP_DEVELOPMENT_MODE', 'theme');` in the local `wp-config.php`).
- Core-block styling that must beat WordPress global styles lives in `resources/css/core-blocks.css`,
  which is imported **without** a CSS layer. Layered rules always lose to WordPress's unlayered CSS.
- Demo content: `studio wp cobbleandcandle seed --path ~/Studio/cobbleandcandle` (Core plugin active).
- Blocks that read the current page (Page Hero, Location Details, Event Details) use `App\context_post()`:
  the queried object on the front end, the global post in the editor preview (`?post_id=` is passed by
  `resources/js/editor.js`). Alpine directives in block views need an `x-data` ancestor in the block itself.
- Location and Events pages are post type archives (`/locations/`, `/events/`), rendered by
  `templates/archive-cc_location.html` and `archive-cc_event.html`. A Page with the same slug is shadowed.
- Forms post to `admin-post.php` handlers in the Core plugin (`cc_inquiry`, `cc_reservation`, `cc_contact`)
  and redirect back with `?inquiry=` / `?reservation=` / `?contact=` = sent | invalid | expired | error.
  Locally a valid submission shows "error" unless a mail catcher is set up.
- OpenTable / Resy booking modes load the location's "Provider booking page" URL in an iframe only after
  the guest clicks; with no URL the panel falls back to call-to-book.
- Translations: `WP="studio wp --path ~/Studio/cobbleandcandle" npm run translate:pot` builds
  `resources/lang/cobbleandcandle.pot` (PHP, patterns, Blade via compiled views, and block.json
  defaults via the generated `resources/lang/block-strings.php`) and the plugin's `.pot`.
  Block settings left at their default render translated; `/reservations/`-style defaults resolve
  to the real page (`App\page_link`). Patterns use `esc_html__()` and `\App\page_link()`.
- Theme JS/CSS are enqueued (handles `cobbleandcandle`, `cobbleandcandle-editor`) and loaded as ES
  modules (`script_loader_tag`); Vite `base` is `./` so fonts resolve in any install path.
