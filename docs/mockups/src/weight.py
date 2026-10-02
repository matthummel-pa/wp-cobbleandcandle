"""Page weight per theme (home): fetched resources, gzip size for text assets (what a real server sends)."""
import gzip, json, os
from playwright.sync_api import sync_playwright
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
res = {}
with sync_playwright() as p:
    b = p.chromium.launch(executable_path="/usr/bin/google-chrome")
    for vp in [(1440, 900), (390, 844)]:
        for t in ["lampwright", "ember-arch", "ashlar-iron"]:
            ctx = b.new_context(viewport={"width": vp[0], "height": vp[1]}); pg = ctx.new_page(); urls = []
            pg.on("requestfinished", lambda r: urls.append(r.url))
            pg.goto(f"http://127.0.0.1:8765/index.html?theme={t}"); pg.wait_for_load_state("networkidle"); pg.evaluate("document.fonts.ready")
            rows = []; tot_raw = tot_gz = 0
            for u in urls:
                if not u.startswith("http://127.0.0.1"): continue
                path = u.split("8765/")[1].split("?")[0] or "index.html"
                data = open(os.path.join(ROOT, path), "rb").read()
                gz = len(gzip.compress(data, 6)) if path.endswith((".html", ".css", ".js", ".svg")) else len(data)
                rows.append((path, len(data), gz)); tot_raw += len(data); tot_gz += gz
            res[f"{t}@{vp[0]}"] = {"requests": len(rows), "raw_kb": round(tot_raw / 1024), "transfer_kb_gzip": round(tot_gz / 1024), "files": rows}
            print(t, vp[0], len(rows), "req", round(tot_raw/1024), "KB raw", round(tot_gz/1024), "KB gzip"); ctx.close()
    b.close()
json.dump(res, open(f"{ROOT}/weight.json", "w"), indent=1)
for r in res["lampwright@1440"]["files"]: print("  ", r)
