#!/usr/bin/env bash
# Build translation templates for the theme and the Core plugin.
#   Theme:  resources/lang/cobbleandcandle.pot   (PHP, patterns, theme.json, block defaults, Blade)
#   Plugin: plugins/cobbleandcandle-core/languages/cobbleandcandle-core.pot
# Blade's {{ __() }} is not PHP until compiled, so the compiled views are scanned too.
#
# Usage: WP="studio wp --path ~/Studio/cobbleandcandle" scripts/make-pot.sh   (default: wp)
#        VIEWS=/path/to/wp-content/cache/acorn/framework/views
set -euo pipefail
cd "$(dirname "$0")/.."
WP=${WP:-wp}
SITE_VIEWS=${VIEWS:-}

node scripts/block-strings.mjs
$WP acorn view:cache >/dev/null
if [[ -z "$SITE_VIEWS" ]]; then
  SITE_VIEWS=$($WP eval 'echo WP_CONTENT_DIR;' 2>/dev/null | tail -1)/cache/acorn/framework/views
fi

tmp=resources/lang/.views
rm -rf "$tmp" && mkdir -p "$tmp"
# Keep the theme's compiled views; skip ones compiled from vendor packages (e.g. pagination).
for f in "$SITE_VIEWS"/*.php; do
  src=$(grep -o 'PATH [^ ]* ENDPATH' "$f" | head -1 || true)
  [[ "$src" == */vendor/* ]] || cp "$f" "$tmp/"
done
echo "Compiled views scanned: $(ls "$tmp" | wc -l | tr -d ' ')"
trap 'rm -rf "$tmp"' EXIT

theme_dir=$($WP eval 'echo get_template_directory();' 2>/dev/null | tail -1)
$WP i18n make-pot "$theme_dir" "$theme_dir/resources/lang/cobbleandcandle.pot" \
  --domain=cobbleandcandle --slug=cobbleandcandle \
  --include="app,patterns,resources/lang,theme.json,styles,functions.php,style.css" \
  --headers='{"Report-Msgid-Bugs-To":"https://matthummel.com/"}'
# Point references at the Blade source instead of the temporary compiled copy.
python3 - "$theme_dir/resources/lang/cobbleandcandle.pot" "$tmp" <<'PY'
import os, re, sys
pot, views = sys.argv[1], sys.argv[2]
src = {}
for name in os.listdir(views):
    m = re.search(r'PATH (\S+) ENDPATH', open(os.path.join(views, name), encoding='utf-8').read())
    if m and '/resources/views/' in m.group(1):
        src[name] = 'resources/views/' + m.group(1).split('/resources/views/', 1)[1]
text = open(pot, encoding='utf-8').read()
text = re.sub(r'resources/lang/\.views/([0-9a-f]+\.php):\d+', lambda m: src.get(m.group(1), 'resources/views (compiled Blade)'), text)
open(pot, 'w', encoding='utf-8').write(text)
PY
$WP i18n make-pot "$theme_dir/plugins/cobbleandcandle-core" "$theme_dir/plugins/cobbleandcandle-core/languages/cobbleandcandle-core.pot" \
  --domain=cobbleandcandle-core --headers='{"Report-Msgid-Bugs-To":"https://matthummel.com/"}'
