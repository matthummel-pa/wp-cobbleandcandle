"""Contact sheets: per theme (all pages, desktop [+ mobile bonus]) and Home in all three themes side by side."""
import os
from PIL import Image, ImageDraw, ImageFont
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
S = f"{ROOT}/screens"
THEMES = [("lampwright", "Lampwright · Candlelit Old Town", "#15110D", "#C79A55"), ("ember-arch", "The Ember & Arch · Brick & Ember", "#1F1410", "#E2692A"), ("ashlar-iron", "Ashlar & Iron · Limestone & Iron", "#EFE6D6", "#7A2A2E")]
PAGES = [("home", "Home"), ("menu", "Menu"), ("reservations", "Reservations"), ("about", "About / Story / Chef"), ("events", "Events"), ("event", "Single event"), ("gallery", "Gallery"), ("locations", "Locations & contact"), ("404", "404")]
F = "/usr/share/fonts/truetype/dejavu/DejaVuSans"
f_t = ImageFont.truetype(F + "-Bold.ttf", 38); f_l = ImageFont.truetype(F + "-Bold.ttf", 22); f_s = ImageFont.truetype(F + ".ttf", 18)
BG = "#E9E6DF"; INK = "#1d1d1b"

def sheet(theme, title, sw, vp, colw, per_row, out):
    ims = [(lab, Image.open(f"{S}/{theme}/{p}-{vp}.png").convert("RGB")) for p, lab in PAGES]
    ims = [(lab, im.resize((colw, int(im.height * colw / im.width)), Image.LANCZOS)) for lab, im in ims]
    gap, pad, top, lab_h = 36, 60, 130, 40
    rows = [ims[i:i + per_row] for i in range(0, len(ims), per_row)]
    H = top + sum(max(i.height for _, i in r) + lab_h + gap for r in rows) + pad
    W = pad * 2 + per_row * colw + (per_row - 1) * gap
    o = Image.new("RGB", (W, H), BG); d = ImageDraw.Draw(o)
    d.text((pad, 34), f"{title} — all pages ({'1440 desktop' if vp == 'desktop' else '390 mobile'})", fill=INK, font=f_t)
    d.text((pad, 84), "One component system · theme = token set on <html data-theme> · static HTML/CSS mockup · Oct 2026", fill="#55534d", font=f_s)
    for k, c in enumerate(sw): d.rounded_rectangle((W - pad - 60 * (len(sw) - k), 36, W - pad - 60 * (len(sw) - k) + 48, 84), 8, fill=c, outline="#00000033")
    y = top
    for r in rows:
        x = pad
        for lab, im in r:
            d.text((x, y), lab, fill=INK, font=f_l); o.paste(im, (x, y + lab_h)); d.rectangle((x - 1, y + lab_h - 1, x + im.width, y + lab_h + im.height), outline="#00000022")
            x += colw + gap
        y += max(i.height for _, i in r) + lab_h + gap
    o.save(out, optimize=True); print(out, o.size)

def compare(vp, colw, out):
    ims = [(t, Image.open(f"{S}/{t[0]}/home-{vp}.png").convert("RGB")) for t in THEMES]
    ims = [(t, im.resize((colw, int(im.height * colw / im.width)), Image.LANCZOS)) for t, im in ims]
    gap, pad, top = 40, 60, 140
    H = top + max(i.height for _, i in ims) + pad; W = pad * 2 + 3 * colw + 2 * gap
    o = Image.new("RGB", (W, H), BG); d = ImageDraw.Draw(o)
    d.text((pad, 30), f"Home · three themes, one component system ({'1440 desktop' if vp == 'desktop' else '390 mobile'})", fill=INK, font=f_t)
    for k, (t, im) in enumerate(ims):
        x = pad + k * (colw + gap); d.text((x, top - 46), f"0{k+1}  {t[1]}", fill=INK, font=f_l if colw > 420 else f_s)
        o.paste(im, (x, top))
    o.save(out, optimize=True); print(out, o.size)

if __name__ == "__main__":
    for t, title, a, b in THEMES:
        sheet(t, title, [a, b], "desktop", 420, 5, f"{S}/contact-sheet-{t}-desktop.png")
        sheet(t, title, [a, b], "mobile", 260, 9, f"{S}/contact-sheet-{t}-mobile.png")
    compare("desktop", 720, f"{S}/home-compare-desktop.png")
    compare("mobile", 420, f"{S}/home-compare-mobile.png")
