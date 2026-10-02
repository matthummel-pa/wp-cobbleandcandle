#!/usr/bin/env bash
# Pack a built Sage theme (vendor + public/build, no node_modules or dev tooling) for WordPress install.
#   bash .github/scripts/pack-theme.sh <theme-slug> [out.zip]
# The zip's top folder is <theme-slug>, which must match the Vite `base` in vite.config.js.
set -euo pipefail

slug="${1:?usage: pack-theme.sh <theme-slug> [out.zip]}"
root="$(pwd)"
out="${2:-$root/$slug.zip}"
[[ "$out" == /* ]] || out="$root/$out"
stage="$(mktemp -d)"
trap 'rm -rf "$stage"' EXIT

mkdir -p "$stage/$slug"
# Dev and AI tooling would otherwise be publicly readable under wp-content/themes/<slug>/ on the live site.
tar -C "$root" \
  --exclude='.git' --exclude='.github' --exclude='.cursor' --exclude='.claude' \
  --exclude='node_modules' --exclude='docs' --exclude='tests' \
  --exclude='.env' --exclude='.env.*' --exclude='*.log' --exclude='*fatal*.txt' \
  --exclude='*.zip' --exclude='.mcp.json' --exclude='.wp-env.json' --exclude='.pa11yci.json' \
  --exclude='.wp-review-allow' --exclude='.gitignore' --exclude='.editorconfig' \
  --exclude='CLAUDE.md' --exclude='AGENTS.md' --exclude='phpcs.xml.dist' --exclude='phpstan*.neon*' \
  --exclude='lighthouse-report*' --exclude='plugins' \
  -cf - . | tar -C "$stage/$slug" -xf -

[[ -f "$stage/$slug/style.css" ]] || { echo "style.css missing from pack" >&2; exit 1; }
[[ -f "$stage/$slug/public/build/manifest.json" ]] || { echo "Vite manifest missing: run npm run build first" >&2; exit 1; }
[[ -f "$stage/$slug/vendor/autoload.php" ]] || { echo "Composer vendor missing: run composer install --no-dev first" >&2; exit 1; }
grep -q "/wp-content/themes/$slug/public/build/" "$root/vite.config.js" \
  || { echo "vite.config.js base does not point at /wp-content/themes/$slug/public/build/" >&2; exit 1; }

rm -f "$stage/$slug/public/hot"
(cd "$stage" && zip -rq "$out" "$slug")
echo "Wrote $out ($(du -h "$out" | awk '{print $1}'))"
