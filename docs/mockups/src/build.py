# -*- coding: utf-8 -*-
"""Static mockup generator. Each function == one future Blade component/section.
Run:  python3 src/build.py   (writes *.html + assets/js/content.js)"""
import json, re, copy, os, html as H
from content import THEMES, DAYS, PRESS
import art
from art import icon, crest, ornament

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
TH = list(THEMES)               # lampwright (default), ember-arch, ashlar-iron
DEF = TH[0]
DEMO_DAY, DEMO_MIN = 3, 21 * 60 + 10   # Thu 9:10pm -- frozen "now" so screenshots are deterministic
SWATCH = {"lampwright": ("#15110D", "#C79A55"), "ember-arch": ("#1F1410", "#E2692A"), "ashlar-iron": ("#EFE6D6", "#7A2A2E")}

# ------------------------------------------------------------ time helpers (mirrored in app.js)
def mins(t): h, m = t.split(":"); return int(h) * 60 + int(m)
def fmt(t, short=True):
    m = mins(t) % (24 * 60); h, mm = divmod(m, 60)
    if m == 0: return "midnight"
    if m == 12 * 60: return "noon"
    suf = "am" if h < 12 else "pm"; h12 = h % 12 or 12
    return f"{h12}{suf}" if (mm == 0 and short) else f"{h12}:{mm:02d}{suf}"
def rng(p): return "Closed" if not p else f"{fmt(p[0])} – {fmt(p[1])}"
def status(hours, day=DEMO_DAY, now=DEMO_MIN):
    y = hours[(day - 1) % 7]
    if y and mins(y[1]) > 1440 and now < mins(y[1]) - 1440:
        return ("open", f"Open now · closes {fmt(y[1])}")
    t = hours[day]
    if t and mins(t[0]) <= now < mins(t[1]):
        left = mins(t[1]) - now
        return ("warn", f"Closing soon · closes {fmt(t[1])}") if left <= 60 else ("open", f"Open now · closes {fmt(t[1])}")
    if t and now < mins(t[0]): return ("off", f"Closed · opens {fmt(t[0])}")
    for k in range(1, 8):
        d = (day + k) % 7
        if hours[d]: return ("off", f"Closed · opens {'tomorrow' if k == 1 else DAYS[d]} {fmt(hours[d][0])}")
    return ("off", "Closed")
def grouped(hours):
    out = []; i = 0
    while i < 7:
        j = i
        while j + 1 < 7 and hours[j + 1] == hours[i]: j += 1
        out.append((DAYS[i] if i == j else f"{DAYS[i]} – {DAYS[j]}", rng(hours[i]))); i = j + 1
    return out

# ------------------------------------------------------------ derived content
BADGE = {"V": "Vegetarian", "VG": "Vegan", "GF": "Gluten-free", "S": "Spicy"}
def badges_html(bs):
    out = []
    for b in bs:
        if b == "S": out.append(f'<abbr class="badge badge--s" title="Spicy">{icon("chili")}<span class="sr">Spicy</span></abbr>')
        else: out.append(f'<abbr class="badge badge--{b.lower()}" title="{BADGE[b]}">{b}</abbr>')
    return "".join(out)
def price_html(it):
    if it.get("variants"):
        return '<span class="vars">' + "".join(f'<span class="var"><small>{l}</small>{p}</span>' for l, p in it["variants"]) + '</span>'
    return it["price"]
def derive(t):
    for d in t["sig"]["dishes"]:
        d["badges_html"] = badges_html(d["badges"]); d["diet"] = " ".join(b.lower() for b in d["badges"])
    for tab in t["menu"]:
        for c in tab["cats"]:
            for it in c["items"]:
                it["price_html"] = price_html(it); it["badges_html"] = badges_html(it["badges"])
                it["diet"] = " ".join(b.lower() for b in it["badges"])
    for i, L in enumerate(t["locs"]):
        L["addr"] = f'{L["street"]}, {L["area"]}'
        L["tel"] = "tel:+1" + re.sub(r"\D", "", L["phone"])
        L["mailto"] = "mailto:" + L["email"]
        L["directions"] = "https://maps.google.com/?q=" + re.sub(r"\s+", "+", L["street"] + " " + L["area"])
        L["hours_html"] = "".join(f'<div class="hrow"><dt>{a}</dt><dd>{b}</dd></div>' for a, b in grouped(L["hours"]))
        L["week_html"] = "".join(f'<tr{" class=is-today" if k == DEMO_DAY else ""}><th scope="row">{DAYS[k]}{"<span class=today-tag>Today</span>" if k == DEMO_DAY else ""}</th><td>{rng(L["hours"][k])}</td></tr>' for k in range(7))
        L["holiday_html"] = "".join(f'<li><span class="hol-d">{a}</span><span class="hol-n">{n}</span><span class="hol-h">{h}</span></li>' for a, n, h in L["holiday"])
        L["today"] = rng(L["hours"][DEMO_DAY])
        L["state"], L["status"] = status(L["hours"])
        L["book_label"] = {"native": "Book online here", "opentable": f"Book via {L['provider']}", "resy": f"Book via {L['provider']}", "call": "Call to book"}[L["booking"]]
    for e in t["events"]:
        e["loc_name"] = t["locs"][e["loc"]]["name"]; e["when"] = f'{e["wd"]} {e["day"]} {e["mon"]} · {e["time"]}'
    return t
for k in TH: derive(THEMES[k])

# ------------------------------------------------------------ translation helpers
USED = set()
def get(theme, path):
    v = THEMES[theme]
    for p in path.split("."):
        v = v[int(p)] if isinstance(v, list) else v[p]
    return v
def same(path): return all(get(k, path) == get(DEF, path) for k in TH)
def T(path, tag="span", cls="", attrs=""):
    v = get(DEF, path); c = f' class="{cls}"' if cls else ""
    if same(path): return f"<{tag}{c}{attrs}>{v}</{tag}>"
    USED.add(path); return f'<{tag}{c} data-t="{path}"{attrs}>{v}</{tag}>'
def V(path):
    """value only (for places where the parent already carries data-t or value is shared)"""
    return get(DEF, path)
def A(**kw):
    """attributes that swap per theme: A(href='locs.0.tel') -> href="..." data-ta="href:locs.0.tel" """
    parts, ta = [], []
    for a, p in kw.items():
        a = a.replace("_", "-"); parts.append(f'{a}="{H.escape(str(get(DEF, p)), quote=True)}"')
        if not same(p): USED.add(p); ta.append(f"{a}:{p}")
    return " " + " ".join(parts) + (f' data-ta="{"|".join(ta)}"' if ta else "")

# location-bound (current location) helpers -> filled by app.js from content.locs[current]
def L(field, tag="span", cls=""):
    c = f' class="{cls}"' if cls else ""
    return f'<{tag}{c} data-l="{field}">{THEMES[DEF]["locs"][0][field]}</{tag}>'
def LH(field): return f' href="{THEMES[DEF]["locs"][0][field]}" data-lh="{field}"'
def STATUS(idx=None, cls=""):
    """open-now chip; idx=None -> current location"""
    loc = THEMES[DEF]["locs"][idx or 0]
    hook = 'data-l-status' if idx is None else f'data-status-of="{idx}"'
    return f'<span class="status {cls}" {hook} data-state="{loc["state"]}"><span class="dot" aria-hidden="true"></span><span class="status-t">{loc["status"]}</span></span>'

UID = [0]
def uid(): UID[0] += 1; return f"u{UID[0]}"

def media(kind, ratio="r-4x5", cls="", label=None, **kw):
    fn = {"street": art.street, "room": art.room, "cellar": art.cellar, "bar": art.bar, "hearth": art.hearth,
          "facade": art.facade, "chef": art.chef, "table": art.table}
    u = uid()
    if kind.startswith("dish:"): svg = art.dish(kind[5:], u, label)
    elif kind == "closed": svg = art.facade(u, closed=True, label=label or "Illustration: a closed door under a lantern")
    else: svg = fn[kind](u, label) if label else fn[kind](u, **kw)
    return f'<div class="media {ratio} {cls}">{svg}</div>'

def themed_media(path_kind, ratio, cls=""):
    """slot whose illustration differs per theme -> render each distinct variant once, CSS shows the right one"""
    kinds = {}
    for k in TH: kinds.setdefault(get(k, path_kind), []).append(k)
    out = []
    for kind, ths in kinds.items():
        show = " ".join(f"show-{t}" for t in ths)
        out.append(f'<div class="themed-art {show}">{art.dish(kind, uid())}</div>')
    return f'<div class="media {ratio} {cls}">{"".join(out)}</div>'

# ------------------------------------------------------------ components
def btn(label, href="#", kind="primary", ic=None, extra="", size=""):
    i = icon(ic) if ic else ""
    return f'<a class="btn btn--{kind} {size}" href="{href}"{extra}>{i}<span>{label}</span></a>'

def eyebrow(text, cls=""): return f'<p class="eyebrow {cls}">{text}</p>'

def sh(eb, title_html, intro_html="", center=False, h="h2", link="", hid=""):
    o = ornament("orn sh-orn", 160) if center else ""
    intro = f'<p class="sh-intro">{intro_html}</p>' if intro_html else ""
    lk = f'<div class="sh-link">{link}</div>' if link else ""
    return f'<header class="sh{" sh--center" if center else ""}">{o}<div class="sh-text"><p class="eyebrow">{eb}</p><{h} class="h2"{f' id="{hid}"' if hid else ""}>{title_html}</{h}>{intro}</div>{lk}</header>'

def theme_switcher(compact=False):
    b = []
    for k in TH:
        bg, ac = SWATCH[k]; lab = THEMES[k]["label"].replace("The ", "")
        b.append(f'<button type="button" class="tsw" data-set-theme="{k}" aria-pressed="{str(k == DEF).lower()}" title="{THEMES[k]["style"]}"><span class="tsw-sw" style="--sw-a:{bg};--sw-b:{ac}" aria-hidden="true"></span><span class="tsw-l">{lab}</span></button>')
    return f'<div class="themesw{" themesw--compact" if compact else ""}" role="group" aria-label="Demo style direction"><span class="themesw-label">{icon("palette")}<span>Style</span></span>{"".join(b)}</div>'

def loc_switcher(idsuf="u"):
    opts = []
    for i in range(3):
        loc = THEMES[DEF]["locs"][i]
        opts.append(f'<li role="option" id="lo-{idsuf}-{i}" data-set-loc="{i}" aria-selected="{str(i == 0).lower()}" tabindex="-1">'
                    f'{T(f"locs.{i}.name", "span", "lo-n")}{STATUS(i, "status--sm")}{icon("check", "i lo-check")}</li>')
    return (f'<div class="locsw" data-locsw><button type="button" class="locsw-btn" aria-haspopup="listbox" aria-expanded="false" aria-controls="ll-{idsuf}">'
            f'{icon("pin")}<span class="locsw-l">Location</span>{L("name", "b", "locsw-n")}{icon("chev-down", "i locsw-c")}</button>'
            f'<ul class="locsw-list" id="ll-{idsuf}" role="listbox" aria-label="Choose a location" hidden>{"".join(opts)}</ul></div>')

NAV_L = [("Menu", "menu.html"), ("Events", "events.html"), ("Gallery", "gallery.html"), ("Story", "about.html")]
NAV_R = [("Locations", "locations.html")]
def navlinks(items, cur):
    return "".join(f'<li><a href="{h}"{" aria-current=page" if h == cur else ""}>{n}</a></li>' for n, h in items)

def brand(u="b"):
    return (f'<a class="brand" href="index.html" aria-label="Home">{crest(u, 52)}'
            f'<span class="brand-t">{T("brand", "span", "brand-n")}{T("sub", "small", "brand-s")}</span></a>')

def header(cur):
    return f'''<a class="skip" href="#main">Skip to content</a>
<div class="util" role="region" aria-label="Location, hours and style">
 <div class="container util-in">
  <div class="util-l">{loc_switcher("u")}<span class="util-status">{STATUS()}</span><a class="util-phone"{LH("tel")}>{icon("phone")}{L("phone")}</a></div>
  <div class="util-r">{theme_switcher()}</div>
 </div>
</div>
<header class="hdr" data-hdr>
 <div class="container hdr-in">
  <nav class="nav nav--l" aria-label="Primary"><ul>{navlinks(NAV_L, cur)}</ul></nav>
  {brand("hb")}
  <div class="hdr-r"><nav class="nav nav--r" aria-label="Secondary"><ul>{navlinks(NAV_R, cur)}</ul></nav>
   {btn(T("order"), "menu.html#order", "secondary", None, "", "btn--sm hdr-order")}{btn(T("reserve"), "reservations.html", "primary", None, "", "btn--sm hdr-reserve")}
   <button type="button" class="hdr-burger" aria-expanded="false" aria-controls="drawer" data-drawer-open>{icon("menu")}<span class="sr">Open menu</span></button>
  </div>
 </div>
</header>
<div class="drawer" id="drawer" hidden data-drawer>
 <div class="drawer-in" role="dialog" aria-modal="true" aria-label="Site menu">
  <div class="drawer-top">{brand("db")}<button type="button" class="icon-btn" data-drawer-close>{icon("close")}<span class="sr">Close menu</span></button></div>
  <nav aria-label="Mobile"><ul class="drawer-nav">{navlinks([("Home","index.html")]+NAV_L+[("Reservations","reservations.html")]+NAV_R, cur)}</ul></nav>
  <div class="drawer-cta">{btn(T("reserve"), "reservations.html", "primary", "calendar")}{btn(T("order"), "menu.html#order", "secondary", "bag")}</div>
  <div class="drawer-loc"><p class="eyebrow">Our houses</p><ul>{"".join(f'<li><a href="locations.html">{T(f"locs.{i}.name")}</a>{STATUS(i, "status--sm")}</li>' for i in range(3))}</ul></div>
  <div class="drawer-theme"><p class="eyebrow">Demo style</p>{theme_switcher()}</div>
 </div>
</div>'''

def mbar():
    return f'''<nav class="mbar" aria-label="Quick actions">
 <a class="mbar-a mbar-a--primary" href="reservations.html">{icon("calendar")}<span>{T("reserve_short")}</span></a>
 <a class="mbar-a" href="menu.html#order">{icon("bag")}<span>Order</span></a>
 <a class="mbar-a"{LH("tel")}>{icon("phone")}<span>Call</span></a>
 <a class="mbar-a"{LH("directions")}>{icon("nav")}<span>Directions</span></a>
</nav>'''

def footer():
    cols = []
    for i in range(3):
        p = f"locs.{i}"
        cols.append(f'''<div class="f-loc"><h3 class="h4">{T(p+".name")}</h3>{STATUS(i, "status--sm")}
<p><a href="{V(p+'.directions')}"{A(href=p+".directions")}>{T(p+".street")}<br>{T(p+".area")}</a></p>
<p><a{A(href=p+".tel")}>{T(p+".phone")}</a></p><dl class="hours hours--sm" data-t="{p}.hours_html">{V(p+".hours_html")}</dl></div>''')
        USED.add(p + ".hours_html")
    return f'''<footer class="ftr">
 <div class="container">
  <section class="news" aria-labelledby="news-h">
   <div>{ornament("orn", 120)}<h2 class="h3" id="news-h">{T("news.title","span")}</h2><p>{T("news.text")}</p></div>
   <form class="news-f" data-demo-form><label for="news-email">Email address</label><div class="news-row"><input id="news-email" type="email" autocomplete="email" placeholder="you@example.com" required><button class="btn btn--primary" type="submit">Subscribe</button></div><p class="hint">Monthly. No spam. Unsubscribe any time.</p><p class="form-ok" role="status" hidden>{icon("check")} Thank you, you’re on the list.</p></form>
  </section>
  <div class="f-grid">
   <div class="f-brand">{brand("fb")}<p class="muted">{T("story.p1","span")}</p><div class="social"><a class="icon-btn" href="#">{icon("insta")}<span class="sr">Instagram</span></a><a class="icon-btn" href="#">{icon("fb")}<span class="sr">Facebook</span></a><a class="icon-btn" href="#">{icon("mail")}<span class="sr">Email</span></a></div></div>
   {"".join(cols)}
  </div>
  <div class="f-bottom"><p>© 2026 {T("brand")}. Demo brand for design mockups.</p><ul><li><a href="menu.html#allergens">Allergens</a></li><li><a href="#">Accessibility</a></li><li><a href="#">Privacy</a></li><li><a href="#">Careers</a></li><li><a href="404.html">Gift cards</a></li></ul></div>
 </div>
</footer>'''

def page(slug, title, body, cur="", extra_head="", body_cls=""):
    head_script = """<script>/* no-flash theme + location bootstrap (inline, before CSS paints) */
(function(d){try{var q=new URLSearchParams(location.search),k='rm-theme',ok=/^(lampwright|ember-arch|ashlar-iron)$/,t=q.get('theme')||localStorage.getItem(k)||d.documentElement.getAttribute('data-theme');if(!ok.test(t))t='lampwright';localStorage.setItem(k,t);d.documentElement.setAttribute('data-theme',t);var l=q.get('loc')||localStorage.getItem('rm-loc');if(/^[0-2]$/.test(l))d.documentElement.setAttribute('data-loc',l);}catch(e){}})(document);</script>"""
    return f'''<!doctype html>
<html lang="en" data-theme="lampwright" data-loc="0">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{title} · Lampwright (demo)</title>
<meta name="description" content="Restaurant theme mockup: one component system, three style directions.">
{head_script}
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Ccircle cx='16' cy='16' r='15' fill='%2315110D' stroke='%23C79A55' stroke-width='2'/%3E%3Cpath d='M16 7c-3 5-4 8-1 12-3-1-4-3-4-5-2 4 0 9 5 9s7-5 5-9c0 2-1 3-2 4 1-4 0-7-3-11z' fill='%23F4B860'/%3E%3C/svg%3E">
<link rel="stylesheet" href="assets/css/fonts.css">
<link rel="stylesheet" href="assets/css/tokens.css">
<link rel="stylesheet" href="assets/css/main.css">
{extra_head}
</head>
<body class="{body_cls}">
{art.sprite()}
{header(cur)}
<main id="main" tabindex="-1">
{body}
</main>
{footer()}
{mbar()}
<script src="assets/js/content.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>'''

def diet_attr(p):
    return f' data-diet="{V(p)}"' if same(p) else A(data_diet=p)

def menu_row(p, heading=None):
    tag = heading or "span"
    return (f'<li class="mrow"{diet_attr(p+".diet")}>'
            f'<div class="mrow-top">{T(p+".name", tag, "mrow-name")}{T(p+".flag", "span", "flag")}<span class="leader" aria-hidden="true"></span>{T(p+".price_html", "span", "price")}</div>'
            f'<div class="mrow-bot">{T(p+".desc", "p", "mrow-desc")}{T(p+".badges_html", "span", "badges")}</div></li>')

def board():
    lines = "".join(f'<li>{T(f"board.lines.{i}.0")}<span class="leader" aria-hidden="true"></span>{T(f"board.lines.{i}.1", "span", "price")}</li>' for i in range(3))
    return f'''<aside class="board" aria-labelledby="board-h"><div class="board-in">{ornament("orn", 110)}
<p class="eyebrow">{T("board.sub")}</p><h3 class="board-h" id="board-h">{T("board.title","span")}</h3><ul class="board-l">{lines}</ul><p class="board-f">{T("board.foot")}</p></div></aside>'''

def dish_card(p, i, add=False, h="h3"):
    addb = f'<button type="button" class="icon-btn icon-btn--solid dish-add">{icon("plus")}<span class="sr">Add {V(p+".name")} to order</span></button>' if add else ""
    return f'''<article class="dish"{diet_attr(p+".diet")}>{themed_media(p+".art", "r-4x5", "dish-m")}{T(p+".flag","span","flag flag--over")}
<div class="dish-b">{T(p+".name", h, "dish-n")}{T(p+".desc", "p", "dish-d")}<div class="dish-f">{T(p+".badges_html","span","badges")}{T(p+".price", "span", "price")}{addb}</div></div></article>'''

def datebox(p):
    return f'<div class="datebox" aria-hidden="true">{T(p+".wd","span","db-w")}{T(p+".day","span","db-d")}{T(p+".mon","span","db-m")}</div>'

def event_card(i, h="h3"):
    p = f"events.{i}"
    return f'''<article class="ev">{datebox(p)}<div class="ev-b"><p class="ev-type">{T(p+".type")}</p>{T(p+".title", h, "ev-t")}
<p class="ev-meta">{icon("clock")}{T(p+".when")}<br>{icon("pin")}{T(p+".loc_name")}</p>{T(p+".blurb","p","ev-d")}
<div class="ev-f">{T(p+".price","span","ev-price")}{T(p+".status","span","chip chip--quiet")}<a class="link-arrow" href="event.html">Details{icon("arrow")}<span class="sr"> about {V(p+".title")}</span></a></div></div></article>'''

def quote_card(i):
    p = f"reviews.{i}"
    stars = "".join(icon("star", "i i--fill") for _ in range(5))
    return f'<figure class="quote"><div class="stars" role="img" aria-label="Rated 5 out of 5">{stars}</div><blockquote>{T(p+".q","p")}</blockquote><figcaption>{T(p+".who","b")}{T(p+".src","span")}</figcaption></figure>'

def form_field(id_, label, typ="text", attrs="", opts=None, hint="", full=False, req=True):
    r = " required" if req else ""
    star = '<span class="req" aria-hidden="true">*</span>' if req else '<span class="opt">(optional)</span>'
    if opts is not None:
        o = "".join(f"<option{' selected' if j == 0 else ''}>{x}</option>" for j, x in enumerate(opts))
        ctl = f'<div class="select"><select id="{id_}" name="{id_}"{r}{attrs}>{o}</select>{icon("chev-down")}</div>'
    elif typ == "textarea": ctl = f'<textarea id="{id_}" name="{id_}" rows="4"{r}{attrs}></textarea>'
    else: ctl = f'<input id="{id_}" name="{id_}" type="{typ}"{r}{attrs}>'
    hint_html = f'<p class="hint" id="{id_}-h">{hint}</p>' if hint else ""
    return f'<div class="field{" field--full" if full else ""}"><label for="{id_}">{label} {star}</label>{ctl}{hint_html}</div>'

def loc_select(id_, label="Location"):
    o = "".join(f'<option value="{i}" data-t="locs.{i}.name">{V(f"locs.{i}.name")}</option>' for i in range(3))
    for i in range(3): USED.add(f"locs.{i}.name")
    return f'<div class="field"><label for="{id_}">{label} <span class="req" aria-hidden="true">*</span></label><div class="select"><select id="{id_}" required data-loc-select>{o}</select>{icon("chev-down")}</div></div>'

def phero(eb, h1_html, lede_html="", kind="room", crumbs=None, ratio="r-hero-s"):
    cr = ""
    if crumbs:
        cr = '<nav class="crumbs" aria-label="Breadcrumb"><ol>' + "".join(f'<li><a href="{h}">{n}</a></li>' for n, h in crumbs[:-1]) + f'<li aria-current="page">{crumbs[-1][0]}</li></ol></nav>'
    return f'''<section class="phero">{media(kind, ratio, "phero-m")}<div class="container phero-c">{cr}<p class="eyebrow eyebrow--hero">{eb}</p><h1 class="h1">{h1_html}</h1>{f'<p class="lede">{lede_html}</p>' if lede_html else ""}</div></section>'''

# ============================================================ PAGES
def p_home():
    sig = "".join(dish_card(f"sig.dishes.{i}", i) for i in range(3))
    tabs = "".join(f'<button type="button" role="tab" id="mt-{i}" aria-controls="mp-{i}" aria-selected="{str(i==0).lower()}" tabindex="{0 if i==0 else -1}" class="tab">{T(f"menu.{i}.name")}</button>' for i in range(4))
    panels = ""
    for i in range(4):
        rows = []
        for c, cat in enumerate(THEMES[DEF]["menu"][i]["cats"]):
            for j in range(len(cat["items"])):
                rows.append(f"menu.{i}.cats.{c}.items.{j}")
        rows = rows[:5]
        panels += f'<div role="tabpanel" id="mp-{i}" aria-labelledby="mt-{i}" class="tabpanel"{"" if i==0 else " hidden"}>{T(f"menu.{i}.intro","p","tab-intro")}<ul class="mlist">{"".join(menu_row(r) for r in rows)}</ul></div>'
    gal = [("room", "g-a"), ("dish:plate", "g-b"), ("cellar", "g-c"), ("hearth", "g-d"), ("facade", "g-e")]
    gtiles = "".join(f'<a class="gtile {c}" href="gallery.html">{media(k, "r-fill")}<span class="sr">Open gallery</span></a>' for k, c in gal)
    rooms = "".join(f'<li>{T(f"private.rooms.{i}.0","b")}{T(f"private.rooms.{i}.1","span")}</li>' for i in range(3))
    press = "".join(f'<li class="press-{i}">{n}</li>' for i, n in enumerate(PRESS))
    body = f'''
<section class="hero" aria-labelledby="hero-h">
 {media("street", "r-hero", "hero-m")}
 <div class="container hero-c">
  <p class="eyebrow eyebrow--hero">{ornament("orn orn--kick", 64)}{T("kicker")}</p>
  <h1 class="h1 hero-h" id="hero-h">{T("h1","span")}</h1>
  {T("lede","p","lede")}
  <div class="cta-row">{btn(T("reserve"), "reservations.html", "primary", "calendar")}{btn(T("menu_btn"), "menu.html", "secondary", None, "", "btn--on-dark")}</div>
 </div>
 <p class="hero-cap">Pictured: {T("caption")}</p>
</section>

<section class="strip" aria-label="Hours and location">
 <div class="container strip-in">
  <div class="strip-c"><p class="eyebrow">Tonight</p>{STATUS()}<p class="muted">{T("strip_tonight")}</p></div>
  <div class="strip-c"><p class="eyebrow">Find us</p><p>{L("addr")}</p><a class="link-arrow"{LH("directions")}>Directions{icon("arrow")}</a></div>
  <div class="strip-c"><p class="eyebrow">Call to book</p><p><a class="strip-phone"{LH("tel")}>{L("phone")}</a></p><p class="muted">Today {L("today")}</p></div>
  <div class="strip-c"><p class="eyebrow">Three houses</p><p>{T("locs.0.name")}, {T("locs.1.name")} &amp; {T("locs.2.name")}</p><a class="link-arrow" href="locations.html">All locations{icon("arrow")}</a></div>
 </div>
</section>

<section class="section" aria-labelledby="sig-h">
 <div class="container">
  {sh("Signature dishes", T("sig.title"), T("sig.intro"), True, hid="sig-h")}
  <div class="grid-3 dish-grid">{sig}</div>
 </div>
</section>

<section class="section section--alt texture" aria-labelledby="menu-h">
 <div class="container menu-teaser">
  <div class="mt-main">
   <header class="sh"><div class="sh-text"><p class="eyebrow">The menu</p><h2 class="h2" id="menu-h">{T("menu_title")}</h2><p class="sh-intro">{T("menu_intro")}</p></div></header>
   <div class="tabs" role="tablist" aria-label="Menu sections">{tabs}</div>
   {panels}
   <div class="cta-row">{btn("See the full menu", "menu.html", "primary", None)}<a class="btn btn--text" href="#">{icon("download")}<span>Printable PDF</span></a></div>
  </div>
  {board()}
 </div>
</section>

<section class="section" aria-labelledby="story-h">
 <div class="container story">
  <figure class="story-m">{media("chef", "r-4x5")}<figcaption>{T("story.chef","b")} · {T("story.role")}</figcaption></figure>
  <div class="story-c">
   <p class="eyebrow">{T("story.kicker")}</p><h2 class="h2" id="story-h">{T("story.title")}</h2>
   {T("story.p1","p")}{T("story.p2","p")}
   <blockquote class="pull">{T("story.quote","p")}<cite>{T("story.chef")}</cite></blockquote>
   <dl class="stats"><div><dt>Established</dt><dd>{T("est")}</dd></div><div><dt>Houses</dt><dd>3</dd></div><div><dt>Seatings nightly</dt><dd>2</dd></div></dl>
   {btn("Read our story", "about.html", "secondary")}
  </div>
 </div>
</section>

<section class="section section--alt" aria-labelledby="ev-h">
 <div class="container">
  {sh("Events &amp; specials", "What’s on", "Suppers, music and seasonal nights across all three houses.", False, "h2", '<a class="link-arrow" href="events.html">All events'+icon("arrow")+'</a>', hid="ev-h")}
  <div class="grid-3">{event_card(0)}{event_card(1)}{event_card(2)}</div>
 </div>
</section>

<section class="section" aria-labelledby="rev-h">
 <div class="container">
  {sh("Reviews &amp; press", "Kind words", "", True, hid="rev-h")}
  <ul class="press" aria-label="As featured in">{press}</ul>
  <div class="grid-3">{quote_card(0)}{quote_card(1)}{quote_card(2)}</div>
 </div>
</section>

<section class="section section--tight" aria-labelledby="gal-h">
 <div class="container">
  {sh("Gallery", T("gal_title"), "", False, "h2", '<a class="link-arrow" href="gallery.html">View the gallery'+icon("arrow")+'</a>', hid="gal-h")}
  <div class="gmosaic">{gtiles}</div>
 </div>
</section>

<section class="section section--alt texture" aria-labelledby="pd-h">
 <div class="container pd">
  <div class="pd-c">
   <p class="eyebrow">Private dining &amp; events</p><h2 class="h2" id="pd-h">{T("private.title")}</h2>{T("private.text","p")}
   <ul class="rooms">{rooms}</ul>
   {media("table", "r-16x9", "pd-m")}
  </div>
  <form class="card form pd-f" data-demo-form aria-labelledby="pd-fh">
   <h3 class="h3" id="pd-fh">Send an inquiry</h3>
   <div class="form-grid">
    {form_field("pd-name","Full name",attrs=' autocomplete="name"')}
    {form_field("pd-email","Email","email",' autocomplete="email"')}
    {form_field("pd-date","Preferred date","date",' value="2026-11-14"')}
    {form_field("pd-guests","Guests",opts=["10 – 20","21 – 40","41 – 80","80+"])}
    {loc_select("pd-loc","House")}
    {form_field("pd-occ","Occasion",opts=["Celebration","Business dinner","Wedding / rehearsal","Wake or memorial","Other"])}
    {form_field("pd-msg","Tell us about your event","textarea",full=True,req=False)}
   </div>
   <button class="btn btn--primary btn--block" type="submit">Send inquiry</button>
   <p class="hint">Our events team replies within one working day.</p>
   <p class="form-ok" role="status" hidden>{icon("check")} Thank you. We’ll be in touch within one working day.</p>
  </form>
 </div>
</section>'''
    return body

def p_menu():
    tabs = "".join(f'<button type="button" role="tab" id="mt-{i}" aria-controls="mp-{i}" aria-selected="{str(i==0).lower()}" tabindex="{0 if i==0 else -1}" class="tab">{T(f"menu.{i}.name")}</button>' for i in range(4))
    panels = ""
    for i, tab in enumerate(THEMES[DEF]["menu"]):
        catlinks = "".join(f'<li><a href="#c-{i}-{c}"{" aria-current=true" if c==0 else ""}>{T(f"menu.{i}.cats.{c}.name")}</a></li>' for c in range(len(tab["cats"])))
        photo = ""
        if i == 0:
            photo = f'''<section class="mcat" aria-labelledby="c-feat"><h2 class="h3 mcat-h" id="c-feat">{icon("star","i i--fill i--accent")} Chef’s picks</h2>
<div class="grid-3 dish-grid dish-grid--menu">{"".join(dish_card(f"sig.dishes.{k}", k, add=True) for k in range(3))}</div></section>'''
        cats = ""
        for c, cat in enumerate(tab["cats"]):
            rows = "".join(menu_row(f"menu.{i}.cats.{c}.items.{j}", "h3") for j in range(len(cat["items"])))
            cats += f'<section class="mcat" id="c-{i}-{c}" aria-labelledby="ch-{i}-{c}"><h2 class="h3 mcat-h" id="ch-{i}-{c}">{T(f"menu.{i}.cats.{c}.name")}</h2><ul class="mlist mlist--2">{rows}</ul><p class="mcat-empty" hidden>No dishes in this section match your filters. <button type="button" class="btn--link" data-clear-filters>Clear filters</button></p></section>'
        panels += f'''<div role="tabpanel" id="mp-{i}" aria-labelledby="mt-{i}" class="tabpanel menu-panel"{"" if i==0 else " hidden"}>
<nav class="catbar" aria-label="Sections in {V(f'menu.{i}.name')}"><ul>{catlinks}</ul></nav>
<div class="menu-body">{T(f"menu.{i}.intro","p","tab-intro tab-intro--lg")}{photo}{cats}</div></div>'''
    filt = "".join(f'<label class="fchip"><input type="checkbox" value="{v}" data-diet-filter><span>{icon(ic)}{n}</span></label>' for v, n, ic in [("v", "Vegetarian", "leaf"), ("vg", "Vegan", "leaf"), ("gf", "Gluten-free", "wheat"), ("s", "Spicy", "chili")])
    return f'''{phero("The menu", "Menus", T("menu_intro"), "bar", [("Home","index.html"),("Menu","menu.html")])}
<section class="section section--menu">
 <div class="container">
  <div class="menu-tools">
   <div class="menu-where"><p class="eyebrow">Showing the menu at</p><p class="h4">{L("name")} {STATUS(None,"status--sm")}</p><button type="button" class="btn--link" data-open-locsw>Change location</button></div>
   <fieldset class="filters"><legend>{icon("filter")} Dietary filter</legend><div class="fchips">{filt}</div><p class="filter-count" role="status" aria-live="polite"></p></fieldset>
   <a class="btn btn--text menu-pdf" href="#">{icon("download")}<span>Printable PDF <small>(240 KB)</small></span></a>
  </div>
  <div class="tabs tabs--lg" role="tablist" aria-label="Menus">{tabs}</div>
  {panels}
  <div class="menu-foot" id="allergens">
   <div class="legend"><h2 class="h4">Key</h2><ul>{badges_html(["V"])} Vegetarian · {badges_html(["VG"])} Vegan · {badges_html(["GF"])} Gluten-free · {badges_html(["S"])} Spicy</ul></div>
   <p class="muted">Please tell your server about any allergies before ordering. Our kitchens handle nuts, gluten and shellfish; we cannot guarantee any dish is allergen-free. A discretionary 12.5% service charge is added to tables of six or more.</p>
  </div>
  <div class="order-card card" id="order">
   <div>{icon("bag","i i--lg i--accent")}</div>
   <div><h2 class="h3">{T("order")}</h2><p>Collect from any of our houses from 5pm. Ordering runs on your provider of choice (Toast, Square, ChowNow); this card links out or embeds it.</p></div>
   {btn("Start an order", "#", "primary", "arrow")}
  </div>
 </div>
</section>'''

def booking_panel(i):
    p = f"locs.{i}"; kind = THEMES[DEF]["locs"][i]["booking"]
    slots = [("5:30", 0), ("6:00", 1), ("6:30", 0), ("7:00", 2), ("7:30", 0), ("8:00", 0), ("8:30", 1), ("9:00", 0)]
    sl = "".join(f'<label class="slot"><input type="radio" name="time-{i}" value="{t}"{" disabled" if s==2 else ""}{" checked" if t=="7:30" else ""}><span>{t} pm{"<small>Full</small>" if s==2 else ("<small>Bar only</small>" if s==1 else "")}</span></label>' for t, s in slots)
    native = f'''<form class="resv-form" data-demo-form aria-label="Book online">
<div class="form-grid form-grid--3">
 {form_field(f"r{i}-date","Date","date",' value="2026-10-15"')}
 {form_field(f"r{i}-party","Party size",opts=["2 guests","1 guest","3 guests","4 guests","5 guests","6 guests","7 – 8 guests","9+ (private dining)"])}
 {form_field(f"r{i}-seat","Seating",opts=["No preference","Dining room","Bar / counter","Outdoors"],req=False)}
</div>
<fieldset class="slots"><legend>Available times on Thu 15 Oct</legend><div class="slot-grid">{sl}</div></fieldset>
<div class="form-grid">
 {form_field(f"r{i}-name","Full name",attrs=' autocomplete="name"')}
 {form_field(f"r{i}-tel","Phone","tel",' autocomplete="tel"',hint="Only used if we need to reach you about this booking.")}
 {form_field(f"r{i}-email","Email","email",' autocomplete="email"')}
 {form_field(f"r{i}-occ","Occasion",opts=["None","Birthday","Anniversary","Business","Other"],req=False)}
 {form_field(f"r{i}-req","Requests or allergies","textarea",full=True,req=False)}
</div>
<label class="check"><input type="checkbox"><span>Send me the monthly newsletter</span></label>
<button class="btn btn--primary btn--block" type="submit">{icon("calendar")}<span>Request table for 2 · 7:30 pm</span></button>
<p class="form-ok" role="status" hidden>{icon("check")} Table requested. Confirmation sent to your email.</p>
</form>'''
    provider = THEMES[DEF]["locs"][i]["provider"] or "OpenTable"
    embed = f'''<div class="embed" aria-busy="true"><div class="embed-top">{T(p+".provider","span","embed-logo")}<span class="chip chip--quiet">Loading availability…</span></div>
<div class="sk sk--w60"></div><div class="sk-row"><div class="sk"></div><div class="sk"></div><div class="sk"></div></div><div class="sk-row sk-row--4"><div class="sk"></div><div class="sk"></div><div class="sk"></div><div class="sk"></div></div><div class="sk sk--btn"></div>
<p class="embed-note">{icon("info")} Third-party widget loads on interaction (facade pattern) to keep the page fast. If it fails: <a href="#">Book on {T(p+".provider")}</a> or call {T(p+".phone")}.</p></div>'''
    call = f'''<div class="callbook">{icon("phone","i i--xl i--accent")}<h3 class="h3">Bookings by phone</h3><p>{T(p+".name")} is a small house; we take every booking ourselves so we can seat you well.</p>
<a class="callbook-n"{A(href=p+".tel")}>{T(p+".phone")}</a><p class="muted">Phones answered daily 11am – 6pm. Walk-ins always welcome at the bar.</p>
<div class="cta-row cta-row--c"><a class="btn btn--primary"{A(href=p+".tel")}>{icon("phone")}<span>Call now</span></a><a class="btn btn--secondary" href="mailto:hello@example.com">{icon("mail")}<span>Email us</span></a></div></div>'''
    return {"native": native, "opentable": embed, "resy": embed, "call": call}[kind], native, embed, call

def p_reservations():
    cards = "".join(f'''<button type="button" class="lcard" data-set-loc="{i}" aria-pressed="{str(i==0).lower()}"><span class="lcard-n">{T(f"locs.{i}.name")}</span>{STATUS(i,"status--sm")}<span class="lcard-b">{T(f"locs.{i}.book_label")}</span>{icon("check","i lcard-check")}</button>''' for i in range(3))
    panels = ""
    for i in range(3):
        main, *_ = booking_panel(i)
        panels += f'<div class="resv-panel" data-loc-panel="{i}"{"" if i==0 else " hidden"}><h2 class="h3 resv-h">{icon("calendar")} Book at {T(f"locs.{i}.name")}</h2>{main}</div>'
    _, _, embed, call = booking_panel(1)
    _, _, _, call2 = booking_panel(2)
    return f'''{phero("Reservations", "Reserve a table", "Choose a house, then a time. Tables for up to 8 online; for larger groups see private dining.", "room", [("Home","index.html"),("Reservations","reservations.html")])}
<section class="section">
 <div class="container resv">
  <div class="resv-main">
   <fieldset class="lcards"><legend class="eyebrow">1 · Choose a house</legend><div class="lcards-g">{cards}</div></fieldset>
   <div class="card resv-card"><p class="eyebrow">2 · Find a table</p>{panels}</div>
  </div>
  <aside class="resv-side">
   <div class="card"><h2 class="h4">{icon("clock")} Hours at {L("name")}</h2>{STATUS()}<dl class="hours" data-l="hours_html">{THEMES[DEF]["locs"][0]["hours_html"]}</dl></div>
   <div class="card"><h2 class="h4">{icon("info")} Good to know</h2><ul class="ticks"><li>Tables are held for 15 minutes.</li><li>Cancel free up to 24 hours before.</li><li>Parties of 9+ book <a href="index.html#pd-h">private dining</a>.</li><li>Tell us about allergies when booking.</li><li>Smart-casual; no dress code.</li></ul></div>
   <div class="card"><h2 class="h4">{icon("pin")} Getting there</h2><p>{L("addr")}</p><p class="muted">{L("parking")}</p><a class="link-arrow"{LH("directions")}>Directions{icon("arrow")}</a></div>
  </aside>
 </div>
</section>
<section class="section section--alt devnote-wrap" aria-labelledby="states-h">
 <div class="container">
  <div class="devnote"><p class="devnote-tag">Design states · for the developer</p><h2 class="h3" id="states-h">Provider-agnostic booking states</h2><p class="muted">Each location sets a booking mode in WordPress: <b>native form</b> (above), <b>third-party embed</b> (OpenTable / Resy / Tock) or <b>call to book</b>. The panel above swaps with the selected house.</p>
   <div class="grid-2 states">
    <div><p class="state-l">Embed · loading placeholder</p><div class="card">{embed}</div></div>
    <div><p class="state-l">Call to book</p><div class="card">{call2}</div></div>
   </div>
  </div>
 </div>
</section>'''

def p_about():
    tl = "".join(f'<li class="tl-i"><span class="tl-y">{T(f"story.timeline.{i}.0")}</span><h3 class="h4">{T(f"story.timeline.{i}.1")}</h3>{T(f"story.timeline.{i}.2","p")}</li>' for i in range(4))
    vals = "".join(f'<div class="val">{icon(ic,"i i--lg i--accent")}<h3 class="h4">{T(f"story.values.{i}.0")}</h3>{T(f"story.values.{i}.1","p")}</div>' for i, ic in enumerate(["flame", "leaf", "clock"]))
    houses = "".join(f'<article class="house">{media("facade","r-4x3")}<div class="house-b"><h3 class="h4">{T(f"locs.{i}.name")}</h3><p class="muted">{T(f"locs.{i}.addr")}</p>{STATUS(i,"status--sm")}<a class="link-arrow" href="locations.html">Visit{icon("arrow")}</a></div></article>' for i in range(3))
    return f'''{phero(T("story.kicker"), T("story.title"), T("brand") + " · " + T("sub"), "room", [("Home","index.html"),("Our story","about.html")])}
<section class="section">
 <div class="container split">
  <div class="prose">{ornament("orn",120)}<p class="dropcap">{V("story.p1")}</p>{T("story.p2","p")}<p>We still keep the original ledgers, the brass and the stubborn habit of doing things slowly. What has changed is the cooking: lighter, seasonal and rooted in growers we know by name.</p></div>
  {media("room","r-4x5","split-m")}
 </div>
</section>
<section class="section section--alt texture" aria-labelledby="tl-h">
 <div class="container">{sh("Since " + T("est"), "A short history", "", True, hid="tl-h")}<ol class="timeline">{tl}</ol></div>
</section>
<section class="section" aria-labelledby="chef-h">
 <div class="container story story--rev">
  <figure class="story-m">{media("chef","r-4x5")}<figcaption>{T("story.chef","b")} · {T("story.role")}</figcaption></figure>
  <div class="story-c"><p class="eyebrow">The kitchen</p><h2 class="h2" id="chef-h">Meet {T("story.chef")}</h2>
   <blockquote class="pull pull--lg">{T("story.quote","p")}</blockquote>
   <p>Trained in old harbour kitchens and two seasons abroad, our chef writes the menu each morning with the growers on the phone. The brigade is small, the stockpot is older than any of us, and staff meal is taken seriously.</p>
   <p class="sig">{T("story.chef")}</p></div>
 </div>
</section>
<section class="section section--alt" aria-labelledby="val-h"><div class="container">{sh("What we believe", "House rules", "", True, hid="val-h")}<div class="grid-3 vals">{vals}</div></div></section>
<section class="section" aria-labelledby="houses-h"><div class="container">{sh("Three houses", "Find your table", hid="houses-h")}<div class="grid-3">{houses}</div></div></section>
<section class="cta-band texture"><div class="container cta-band-in"><h2 class="h2">Your table is waiting.</h2><div class="cta-row">{btn(T("reserve"),"reservations.html","primary","calendar")}{btn("Private dining","index.html#pd-h","secondary")}</div></div></section>'''

def p_events():
    chips = '<button type="button" class="fchip-b" aria-pressed="true" data-ev-filter="all">All</button>' + "".join(f'<button type="button" class="fchip-b" aria-pressed="false" data-ev-filter="{i}">{T(f"events.{i}.type")}</button>' for i in range(4))
    rows = ""
    for i in range(4):
        p = f"events.{i}"
        rows += f'''<li class="evrow" data-ev="{i}">{datebox(p)}<div class="evrow-b"><p class="ev-type">{T(p+".type")} · {T(p+".loc_name")}</p><h3 class="h4">{T(p+".title")}</h3>{T(p+".blurb","p","muted")}</div>
<div class="evrow-m"><span>{icon("clock")}{T(p+".time")}</span><span>{T(p+".price","b")}</span>{T(p+".status","span","chip chip--quiet")}</div><div class="evrow-a">{btn("Details","event.html","secondary",None,"","btn--sm")}</div></li>'''
    f = "events.0"
    return f'''{phero("Events &amp; specials", "What’s on", "Seasonal suppers, live music and holiday nights across all three houses.", "table", [("Home","index.html"),("Events","events.html")])}
<section class="section">
 <div class="container">
  <article class="feature card">
   {media("table","r-16x9","feature-m")}
   <div class="feature-b"><p class="eyebrow">Featured · {T(f+".type")}</p><h2 class="h2">{T(f+".title")}</h2>{T(f+".blurb","p")}
    <ul class="meta-list"><li>{icon("calendar")}{T(f+".when")}</li><li>{icon("pin")}{T(f+".loc_name")}</li><li>{icon("users")}{T(f+".status")}</li></ul>
    <div class="cta-row">{btn("Book tickets · " + T(f+".price"),"event.html","primary","calendar")}{btn("Details","event.html","secondary")}</div></div>
  </article>
  <div class="ev-tools"><h2 class="h3" id="all-ev">All upcoming</h2><div class="fchips" role="group" aria-labelledby="all-ev">{chips}</div></div>
  <ol class="evlist">{rows}</ol>
  <p class="empty" data-ev-empty hidden>{icon("calendar")} Nothing in this category yet. <a href="#">Get the newsletter</a> and we’ll tell you first.</p>
 </div>
</section>
<section class="section section--alt texture"><div class="container split split--board"><div>{sh("Every week", "Regulars &amp; specials", "Chalked up fresh each day at every house.")}<ul class="ticks"><li>Two-course weekday lunch from $34</li><li>Half-price bottles on Sunday evenings</li><li>Seasonal set menu, Tuesday to Thursday</li></ul></div>{board()}</div></section>'''

def p_event():
    f = "events.0"
    courses = "".join(f'<li>{T(f"ev_menu.{i}")}</li>' for i in range(5))
    return f'''<section class="section section--first">
 <div class="container">
  <nav class="crumbs" aria-label="Breadcrumb"><ol><li><a href="index.html">Home</a></li><li><a href="events.html">Events</a></li><li aria-current="page">{T(f+".title")}</li></ol></nav>
  <header class="ev-head"><p class="eyebrow">{T(f+".type")} · {T(f+".loc_name")}</p><h1 class="h1 h1--page">{T(f+".title")}</h1><p class="lede">{T(f+".blurb")}</p></header>
  {media("table","r-16x9","ev-hero")}
  <div class="ev-layout">
   <div class="prose">
    <h2 class="h3">About the evening</h2>{T("ev_long.0","p")}{T("ev_long.1","p")}
    <h2 class="h3">The menu</h2><ol class="courses">{courses}</ol>
    <h2 class="h3">Good to know</h2><ul class="ticks"><li>Dietary needs can be accommodated with 72 hours’ notice.</li><li>Tickets are refundable up to 7 days before.</li><li>Ages 18+.</li></ul>
   </div>
   <aside class="card ticket" aria-labelledby="tk-h">
    <h2 class="h4" id="tk-h">Book this event</h2>
    <ul class="meta-list"><li>{icon("calendar")}{T(f+".when")}</li><li>{icon("pin")}<span>{T(f+".loc_name")}<br><small class="muted">{T("locs.0.addr")}</small></span></li><li>{icon("users")}{T(f+".status")}</li></ul>
    <p class="ticket-price">{T(f+".price")}</p>
    <form data-demo-form>{form_field("tk-q","Tickets",opts=["2 tickets","1 ticket","3 tickets","4 tickets","5 tickets","6 tickets"])}<button class="btn btn--primary btn--block" type="submit">{icon("calendar")}<span>Book tickets</span></button><p class="form-ok" role="status" hidden>{icon("check")} Held for 10 minutes. Complete payment to confirm.</p></form>
    <a class="btn btn--text" href="#">{icon("calendar")}<span>Add to calendar (.ics)</span></a>
   </aside>
  </div>
 </div>
</section>
<section class="section section--alt"><div class="container">{sh("More events", "You might also like", "", False, "h2", '<a class="link-arrow" href="events.html">All events'+icon("arrow")+'</a>')}<div class="grid-3">{event_card(1)}{event_card(2)}{event_card(3)}</div></div></section>'''

GALLERY = [("room", "rooms", "The front room at dusk", "g-w"), ("dish:plate", "plates", "Signature plate", ""), ("cellar", "cellar", "The vaulted cellar", "g-t"),
           ("dish:dessert", "plates", "Pudding course", ""), ("facade", "outside", "Our front door", "g-t"), ("bar", "cellar", "The back bar", "g-w"),
           ("dish:pie", "plates", "From the oven", ""), ("hearth", "rooms", "The hearth, lit nightly", ""), ("table", "rooms", "Long table for a private supper", "g-w"),
           ("dish:board", "plates", "Cheese from the cave", "g-t"), ("chef", "rooms", "The pass", "g-t"), ("dish:duck", "plates", "Dry-aged duck", "g-w")]
def p_gallery():
    chips = "".join(f'<button type="button" class="fchip-b" aria-pressed="{str(v=="all").lower()}" data-gal-filter="{v}">{n}</button>' for v, n in [("all", "All"), ("rooms", "Rooms"), ("plates", "Plates"), ("cellar", "Cellar & bar"), ("outside", "Outside")])
    tiles = "".join(f'<li class="gitem {c}" data-cat="{cat}"><button type="button" class="gbtn" data-lb="{i}" aria-label="Open image: {cap}">{media(k,"r-fill")}<span class="gcap">{cap}</span><span class="gzoom">{icon("expand")}</span></button></li>' for i, (k, cat, cap, c) in enumerate(GALLERY))
    return f'''{phero("Gallery", T("gal_title"), "Rooms, plates and people across our three houses. Tap any image to enlarge.", "cellar", [("Home","index.html"),("Gallery","gallery.html")])}
<section class="section">
 <div class="container">
  <div class="fchips gal-chips" role="group" aria-label="Filter gallery">{chips}</div>
  <ul class="ggrid">{tiles}</ul>
 </div>
</section>
<dialog class="lb" data-lb-dialog aria-label="Image viewer">
 <div class="lb-in"><figure class="lb-fig"><div class="lb-media"></div><figcaption><span class="lb-cap"></span><span class="lb-count"></span></figcaption></figure>
 <button type="button" class="icon-btn lb-close" data-lb-close>{icon("close")}<span class="sr">Close</span></button>
 <button type="button" class="icon-btn lb-prev" data-lb-prev>{icon("chev-left")}<span class="sr">Previous image</span></button>
 <button type="button" class="icon-btn lb-next" data-lb-next>{icon("chev-right")}<span class="sr">Next image</span></button></div>
</dialog>'''

def p_locations():
    tabs = "".join(f'<button type="button" class="ltab" data-set-loc="{i}" aria-pressed="{str(i==0).lower()}"><span class="ltab-n">{i+1}</span><span>{T(f"locs.{i}.name","b")}{STATUS(i,"status--sm")}</span></button>' for i in range(3))
    panels = ""
    names = [V(f"locs.{i}.name") for i in range(3)]
    for i in range(3):
        p = f"locs.{i}"
        USED.update([p + ".week_html", p + ".holiday_html"])
        panels += f'''<div class="lpanel" data-loc-panel="{i}"{"" if i==0 else " hidden"}>
<div class="lmap card">{art.map_svg(uid(), names)}<a class="btn btn--primary lmap-btn" href="{V(p+'.directions')}"{A(href=p+".directions")}>{icon("nav")}<span>Get directions</span></a></div>
<div class="ldetail">
 <h2 class="h2">{T(p+".name")}</h2>{STATUS(i)}
 <address class="laddr">{icon("pin")}<span>{T(p+".street")}<br>{T(p+".area")}, Old Town</span></address>
 <p class="lcontact"><a{A(href=p+".tel")}>{icon("phone")}{T(p+".phone")}</a><a{A(href=p+".mailto")}>{icon("mail")}{T(p+".email")}</a></p>
 <div class="lcols">
  <div><h3 class="h4">Opening hours</h3><table class="week"><caption class="sr">Weekly hours</caption><tbody data-t="{p}.week_html">{V(p+".week_html")}</tbody></table></div>
  <div><h3 class="h4">Holiday hours</h3><ul class="holiday" data-t="{p}.holiday_html">{V(p+".holiday_html")}</ul></div>
 </div>
 <ul class="notes"><li>{icon("car")}<div><b>Parking</b>{T(p+".parking","p")}</div></li><li>{icon("train")}<div><b>Transit</b>{T(p+".transit","p")}</div></li><li>{icon("access")}<div><b>Access</b>{T(p+".access","p")}</div></li></ul>
 <div class="cta-row">{btn(T("reserve"),"reservations.html","primary","calendar")}<a class="btn btn--secondary"{A(href=p+".tel")}>{icon("phone")}<span>Call</span></a></div>
</div></div>'''
    topics = "".join(f'<label class="fchip"><input type="radio" name="topic" value="{t}"{" checked" if j==0 else ""}><span>{t}</span></label>' for j, t in enumerate(["General", "Reservation", "Private dining", "Press", "Lost property"]))
    return f'''{phero("Locations &amp; contact", "Find us", "Three houses in the old town, each with its own hours, menu and booking.", "facade", [("Home","index.html"),("Locations","locations.html")])}
<section class="section">
 <div class="container">
  <div class="ltabs" role="group" aria-label="Choose a location">{tabs}</div>
  {panels}
 </div>
</section>
<section class="section section--alt" aria-labelledby="contact-h">
 <div class="container contact">
  <div><p class="eyebrow">Contact</p><h2 class="h2" id="contact-h">Write to us</h2><p>For bookings within 48 hours please call the house directly. Everything else, drop us a line and we’ll reply within one working day.</p>
   <ul class="notes"><li>{icon("mail")}<div><b>Press</b><p>press@example.com</p></div></li><li>{icon("users")}<div><b>Careers</b><p>jobs@example.com</p></div></li><li>{icon("info")}<div><b>Gift cards &amp; lost property</b><p>Ask any house, or use the form.</p></div></li></ul></div>
  <form class="card form" data-demo-form aria-labelledby="cf-h"><h3 class="h3" id="cf-h">Send a message</h3>
   <fieldset class="topics"><legend>Topic</legend><div class="fchips">{topics}</div></fieldset>
   <div class="form-grid">{form_field("cf-name","Full name",attrs=' autocomplete="name"')}{form_field("cf-email","Email","email",' autocomplete="email"')}{form_field("cf-tel","Phone","tel",req=False)}{loc_select("cf-loc")}{form_field("cf-msg","Message","textarea",full=True)}</div>
   <label class="check"><input type="checkbox" required><span>I agree to the <a href="#">privacy policy</a>. <span class="req" aria-hidden="true">*</span></span></label>
   <button class="btn btn--primary" type="submit">Send message</button>
   <p class="form-error" role="alert" hidden>{icon("info")} Please fill in the highlighted fields.</p>
   <p class="form-ok" role="status" hidden>{icon("check")} Thanks, your message is on its way.</p>
  </form>
 </div>
</section>'''

def p_404():
    links = "".join(f'<li><a href="locations.html">{T(f"locs.{i}.name")}</a>{STATUS(i,"status--sm")}</li>' for i in range(3))
    return f'''<section class="section nf">
 <div class="container nf-in">
  {media("closed","r-4x5","nf-m")}
  <div class="nf-c"><p class="eyebrow">Error 404 · Page not found</p><h1 class="h1 h1--page">{T("nf.title")}</h1>{T("nf.text","p","lede")}
   <div class="cta-row">{btn("Back to home","index.html","primary")}{btn("See the menu","menu.html","secondary")}{btn(T("reserve"),"reservations.html","text","calendar")}</div>
   <h2 class="h4">Or visit one of our houses</h2><ul class="nf-locs">{links}</ul></div>
 </div>
</section>'''

def jsonld():
    t = THEMES[DEF]; g = []
    for L_ in t["locs"]:
        spec = []
        for k, h in enumerate(L_["hours"]):
            if h: spec.append({"@type": "OpeningHoursSpecification", "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"][k], "opens": h[0], "closes": ("%02d:%s" % (int(h[1][:2]) % 24, h[1][3:]))})
        g.append({"@type": "Restaurant", "name": f'{t["brand"]} · {L_["name"]}', "servesCuisine": "Modern European", "priceRange": "$$$",
                  "telephone": L_["phone"], "address": {"@type": "PostalAddress", "streetAddress": L_["street"], "addressLocality": L_["area"]},
                  "acceptsReservations": True, "hasMenu": "https://example.com/menu/", "openingHoursSpecification": spec})
    return '<script type="application/ld+json">' + json.dumps({"@context": "https://schema.org", "@graph": g}, ensure_ascii=False) + "</script>"

PAGES = [("index", "Home", p_home, "index.html"), ("menu", "Menu", p_menu, "menu.html"), ("reservations", "Reservations", p_reservations, "reservations.html"),
         ("about", "Our story", p_about, "about.html"), ("events", "Events", p_events, "events.html"), ("event", "Candlelit Cellar Supper", p_event, "events.html"),
         ("gallery", "Gallery", p_gallery, "gallery.html"), ("locations", "Locations & contact", p_locations, "locations.html"), ("404", "Page not found", p_404, "")]

if __name__ == "__main__":
    out = {}
    for slug, title, fn, cur in PAGES:
        body = fn()
        html = page(slug, title, body, cur, jsonld() if slug == "index" else "", f"page-{slug}")
        open(os.path.join(ROOT, f"{slug}.html"), "w").write(html)
        out[slug] = len(html.encode())
        USED.update(re.findall(r'data-t="([^"]+)"', html))
    # content map: only keys whose value differs per theme
    cmap = {}
    for k in TH:
        t = THEMES[k]
        locs = [{f: L_[f] for f in ["name","street","area","addr","phone","tel","email","mailto","directions","hours","hours_html","today","parking","transit","access","booking","provider","book_label"]} for L_ in t["locs"]]
        cmap[k] = {"t": {p: get(k, p) for p in sorted(USED)}, "locs": locs, "label": t["label"]}
    js = ("/* GENERATED by src/build.py -- demo copy per theme. In WordPress this is real CMS content. */\n"
          f"window.RM_CONTENT={json.dumps(cmap, ensure_ascii=False, separators=(',',':'))};\n"
          f"window.RM_DEMO_NOW={{day:{DEMO_DAY},min:{DEMO_MIN}}};\n")
    open(os.path.join(ROOT, "assets/js/content.js"), "w").write(js)
    print({k: f"{v//1024}KB" for k, v in out.items()}, "content.js", len(js)//1024, "KB", "keys", len(USED))
