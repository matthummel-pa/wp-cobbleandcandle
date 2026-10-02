"""Screenshots: every page x theme x viewport -> screens/{theme}/{page}-{desktop|mobile}.png  (+ a few interaction states)
usage: /workspace/.venv-pw/bin/python src/shoot.py [theme ...] [--pages a,b] [--states]"""
import sys, os
from playwright.sync_api import sync_playwright
BASE = "http://127.0.0.1:8765/"
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
THEMES = ["lampwright", "ember-arch", "ashlar-iron"]
PAGES = ["index", "menu", "reservations", "about", "events", "event", "gallery", "locations", "404"]
NAMES = {"index": "home"}
args = sys.argv[1:]
themes = [a for a in args if a in THEMES] or THEMES
pages = PAGES
for a in args:
    if a.startswith("--pages="): pages = a.split("=", 1)[1].split(",")
states = "--states" in args
VPS = {"desktop": dict(viewport={"width": 1440, "height": 900}, device_scale_factor=1),
       "mobile": dict(viewport={"width": 390, "height": 844}, device_scale_factor=2, is_mobile=True, has_touch=True)}
with sync_playwright() as p:
    b = p.chromium.launch(executable_path="/usr/bin/google-chrome")
    for vp, opts in VPS.items():
        ctx = b.new_context(**opts); pg = ctx.new_page()
        errs = []; pg.on("pageerror", lambda e: errs.append(str(e))); pg.on("console", lambda m: m.type == "error" and errs.append(m.text))
        for t in themes:
            os.makedirs(f"{ROOT}/screens/{t}", exist_ok=True)
            todo = [(pgname, "") for pgname in pages]
            if states: todo += [("index", "nav"), ("index", "loc"), ("gallery", "lightbox"), ("menu", "filter")]
            for pgname, st in todo:
                url = f"{BASE}{pgname}.html?theme={t}&loc=0" + (f"&state={st}" if st else "")
                name = NAMES.get(pgname, pgname) + (f"-state-{st}" if st else "")
                full = st not in ("nav", "lightbox", "loc")
                pg.goto(url); pg.wait_for_load_state("networkidle"); pg.evaluate("document.fonts.ready")
                if vp == "mobile" and full:
                    # full-page capture: pin the fixed action bar to the page bottom so it doesn't float mid-page
                    pg.add_style_tag(content="body{position:relative}.mbar{position:absolute!important}")
                pg.wait_for_timeout(150)
                pg.screenshot(path=f"{ROOT}/screens/{t}/{name}-{vp}.png", full_page=full)
                print(t, name, vp, pg.evaluate("document.documentElement.scrollHeight"), flush=True)
        if errs: print("JS ERRORS:", set(errs))
        ctx.close()
    b.close()
