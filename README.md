# Cobble & Candle

A WordPress theme for a multi-location restaurant group: candlelit old-town dining rooms, menus with diet filters, reservations per location, events, a gallery, and private dining. Built on [Sage 11](https://roots.io/sage/) (Blade, Acorn, Tailwind v4, Vite).

**Status:** scaffold. The design spec and static mockups are in [`docs/mockups/`](docs/mockups/HANDOFF.md).

## Requirements

- WordPress 6.6+ and PHP 8.3+
- For development: Composer, Node 22, and [WordPress Studio](https://developer.wordpress.com/studio/) (or any local WordPress)

## Install a built theme

Download `cobbleandcandle.zip` from the [`theme-latest` release](https://github.com/matthummel-pa/wp-cobbleandcandle/releases/tag/theme-latest) and upload it in **Appearance → Themes → Add New → Upload**. The zip already contains Composer dependencies and compiled assets.

## Local development

```bash
git clone https://github.com/matthummel-pa/wp-cobbleandcandle.git
cd wp-cobbleandcandle
composer install
npm install
npm run build        # or: npm run dev for hot reload
```

Link the folder into a local site as `wp-content/themes/cobbleandcandle`. The folder name must match the Vite `base` in `vite.config.js`.

View the mockups: `cd docs/mockups && python3 -m http.server 8765`, then open http://localhost:8765/.

## License

GPL-2.0-or-later. Bundled fonts are SIL Open Font License 1.1. Sage is MIT licensed.
