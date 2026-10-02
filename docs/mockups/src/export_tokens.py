"""tokens.css  ->  tokens.json (W3C DTCG format)  +  app-theme.css (paste-ready Tailwind v4 @theme for Sage 11)."""
import re, json, os
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
css = open(f"{ROOT}/assets/css/tokens.css").read()
css_nc = re.sub(r"/\*.*?\*/", "", css, flags=re.S)
blocks = re.findall(r'([^{}]+)\{([^}]*)\}', css_nc)
def decls(body): return [(k.strip(), v.strip()) for k, v in re.findall(r'(--[\w-]+)\s*:\s*([^;]+);', body)]
base, th = {}, {"lampwright": {}, "ember-arch": {}, "ashlar-iron": {}}
for sel, body in blocks:
    sel = sel.strip()
    for t in th:
        if f'data-theme="{t}"' in sel: th[t].update(decls(body))
    if sel == ":root": base.update(decls(body))
THEME_KEYS = list(dict.fromkeys(k for t in th for k in th[t]))
for t in th:  # every theme defines every key (fallback: lampwright, then base)
    for k in THEME_KEYS: th[t].setdefault(k, th["lampwright"].get(k, base.get(k)))
for k in THEME_KEYS: base.pop(k, None)

def dtcg_type(k, v):
    if v.startswith("#"): return "color"
    if k.startswith("--font-") and "," in v: return "fontFamily"
    if k.startswith("--shadow"): return "shadow"
    if k.startswith("--dur"): return "duration"
    if k.startswith("--ease"): return "cubicBezier"
    if re.fullmatch(r"-?[\d.]+(px|rem|em)", v): return "dimension"
    if re.fullmatch(r"[\d.]+", v): return "number"
    return None
def node(k, v):
    n = {"$value": v}; ty = dtcg_type(k, v)
    if ty: n["$type"] = ty
    return n
def group(d):
    out = {}
    for k, v in d.items():
        name = k[2:]; top = name.split("-")[0]
        out.setdefault(top, {})[name] = node(k, v)
    return out
doc = {"$description": "Restaurant theme system — one component set, three themes. Base tokens + per-theme semantic overrides. Source: assets/css/tokens.css",
       "base": group(base), "themes": {t: group(v) for t, v in th.items()}}
json.dump(doc, open(f"{ROOT}/tokens.json", "w"), indent=1, ensure_ascii=False)

# ---- Tailwind v4 @theme (Sage 11: resources/css/app.css)
TW_NS = ("--color-", "--font-", "--text-", "--radius-", "--shadow-", "--ease", "--breakpoint-")
def is_tw(k): return k.startswith(TW_NS) and k not in ("--font-display-weight",)
lines = ["/* =====================================================================",
 "   app-theme.css — paste into resources/css/app.css (Sage 11, Tailwind v4)",
 "   after  @import \"tailwindcss\";",
 "   - @theme static  => default (Lampwright) values; `static` keeps every variable",
 "     in the output so [data-theme] overrides + custom CSS can rely on them.",
 "   - [data-theme] blocks only override variables; utilities (bg-bg, text-accent,",
 "     font-display, rounded-card, shadow-card...) re-skin automatically.",
 "   ===================================================================== */",
 "@theme static {",
 "  --breakpoint-sm: 40rem; --breakpoint-md: 48rem; --breakpoint-lg: 64rem; --breakpoint-nav: 73.75rem; --breakpoint-xl: 80rem;",
 "  --color-*: initial; /* drop Tailwind's default palette: only brand tokens */"]
for k, v in base.items():
    if is_tw(k): lines.append(f"  {k}: {v};")
for k in THEME_KEYS:
    if is_tw(k): lines.append(f"  {k}: {th['lampwright'][k]};")
lines.append("}\n")
lines.append("/* non-utility tokens (spacing rhythm, illustration palette, component knobs) */")
lines.append(":root, [data-theme=\"lampwright\"] {")
for k, v in base.items():
    if not is_tw(k): lines.append(f"  {k}: {v};")
for k in THEME_KEYS:
    if not is_tw(k): lines.append(f"  {k}: {th['lampwright'][k]};")
lines.append("}")
for t in ("ember-arch", "ashlar-iron"):
    lines.append(f"\n[data-theme=\"{t}\"] {{")
    for k in THEME_KEYS:
        if th[t][k] != th["lampwright"][k]: lines.append(f"  {k}: {th[t][k]};")
    lines.append("}")
open(f"{ROOT}/app-theme.css", "w").write("\n".join(lines) + "\n")
print("tokens.json + app-theme.css written;", len(THEME_KEYS), "theme keys,", len(base), "base keys")
