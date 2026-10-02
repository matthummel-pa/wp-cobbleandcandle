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
