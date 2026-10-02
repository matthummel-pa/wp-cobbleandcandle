"""WCAG 2.x contrast for every semantic token pair, per theme. Parses tokens.css. Writes contrast.json + prints a table."""
import re, json, os
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
css = open(f"{ROOT}/assets/css/tokens.css").read()
def block(sel):
    out = {}
    for m in re.finditer(r'([^{}]*)\{([^}]*)\}', css):
        if sel in m.group(1):
            for k, v in re.findall(r'(--[\w-]+)\s*:\s*(#[0-9A-Fa-f]{6})', m.group(2)): out[k] = v
    return out
def lum(h):
    r, g, b = [int(h[i:i+2], 16) / 255 for i in (1, 3, 5)]
    f = lambda c: c / 12.92 if c <= 0.03928 else ((c + 0.055) / 1.055) ** 2.4
    return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b)
def cr(a, b):
    x, y = sorted([lum(a), lum(b)], reverse=True); return (x + .05) / (y + .05)
def mix(a, b, p):  # color-mix(in srgb, a p%, b)
    return "#" + "".join(f"{round(int(a[i:i+2],16)*p + int(b[i:i+2],16)*(1-p)):02X}" for i in (1, 3, 5))
res = {}
for th in ["lampwright", "ember-arch", "ashlar-iron"]:
    t = block(f'data-theme="{th}"')
    c = lambda k: t["--" + k]
    pairs = [
      ("Body text / bg", c("color-text"), c("color-bg"), "text"), ("Body text / surface", c("color-text"), c("color-surface"), "text"),
      ("Body text / surface-2 (popover)", c("color-text"), c("color-surface-2"), "text"),
      ("Muted / bg", c("color-muted"), c("color-bg"), "text"), ("Muted / surface", c("color-muted"), c("color-surface"), "text"),
      ("Button text / accent", c("color-accent-contrast"), c("color-accent"), "text"),
      ("Accent link / bg", c("color-accent"), c("color-bg"), "text"), ("Accent link / surface", c("color-accent"), c("color-surface"), "text"),
      ("Eyebrow / bg", c("color-eyebrow"), c("color-bg"), "text"), ("Eyebrow / surface", c("color-eyebrow"), c("color-surface"), "text"),
      ("Hero text / hero scrim", c("color-hero-text"), c("color-hero"), "text"), ("Hero accent / hero scrim", c("color-hero-accent"), c("color-hero"), "text"),
      ("Hero muted (82% mix) / hero", mix(c("color-hero-text"), c("color-hero"), .82), c("color-hero"), "text"),
      ("Board ink / board", c("color-board-ink"), c("color-board"), "text"), ("Board title / board", c("color-board-title"), c("color-board"), "text"),
      ("Footer text / footer", c("footer-text"), c("footer-bg"), "text"), ("Footer muted / footer", c("footer-muted"), c("footer-bg"), "text"),
      ("Footer accent / footer", c("footer-accent"), c("footer-bg"), "text"), ("Footer button text / footer accent", c("footer-accent-contrast"), c("footer-accent"), "text"),
      ("Focus ring / bg", c("color-focus"), c("color-bg"), "ui"), ("Input border (52% mix) / bg", mix(c("color-text"), c("color-bg"), .52), c("color-bg"), "ui"),
      ("Open-now dot / surface", c("color-status-open"), c("color-surface"), "ui"), ("Closing-soon dot / surface", c("color-status-warn"), c("color-surface"), "ui"),
    ]
    res[th] = [dict(pair=n, fg=f, bg=b, ratio=round(cr(f, b), 2), kind=k, passes=(cr(f, b) >= 4.5 if k == "text" else cr(f, b) >= 3)) for n, f, b, k in pairs]
    print(f"\n{th}")
    for r in res[th]: print(f"  {'OK ' if r['passes'] else 'LOW'} {r['ratio']:5.2f}  {r['pair']}  ({r['fg']} on {r['bg']})")
json.dump(res, open(f"{ROOT}/contrast.json", "w"), indent=1)
