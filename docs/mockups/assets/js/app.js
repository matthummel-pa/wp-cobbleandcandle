/* Restaurant mockup — interactions (vanilla, ~6 KB). Maps 1:1 to Alpine/Interactivity API stores in Sage.
   Modules: theme switcher · copy swap · location switcher · open-now · tabs · menu filter · gallery lightbox · drawer · demo forms */
(function () {
  'use strict';
  var d = document, root = d.documentElement, C = window.RM_CONTENT || {}, NOW = window.RM_DEMO_NOW;
  var THEMES = ['lampwright', 'ember-arch', 'ashlar-iron'];
  var $$ = function (s, el) { return Array.prototype.slice.call((el || d).querySelectorAll(s)); };
  var store = { get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } }, set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} } };
  var theme = function () { return root.getAttribute('data-theme') || 'lampwright'; };
  var loc = function () { return +(root.getAttribute('data-loc') || 0); };

  /* ---------- open-now (production: use real Date in the restaurant's timezone; server renders the first paint) */
  var DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  function mins(t) { var p = t.split(':'); return +p[0] * 60 + +p[1]; }
  function fmt(t) { var m = mins(t) % 1440, h = Math.floor(m / 60), mm = m % 60; if (m === 0) return 'midnight'; if (m === 720) return 'noon';
    var s = h < 12 ? 'am' : 'pm', h12 = h % 12 || 12; return mm ? h12 + ':' + (mm < 10 ? '0' : '') + mm + s : h12 + s; }
  function now() { if (NOW) return NOW; var n = new Date(); return { day: (n.getDay() + 6) % 7, min: n.getHours() * 60 + n.getMinutes() }; }
  function status(hours) {
    var n = now(), day = n.day, m = n.min, y = hours[(day + 6) % 7], t = hours[day];
    if (y && mins(y[1]) > 1440 && m < mins(y[1]) - 1440) return ['open', 'Open now · closes ' + fmt(y[1])];
    if (t && mins(t[0]) <= m && m < mins(t[1])) return mins(t[1]) - m <= 60 ? ['warn', 'Closing soon · closes ' + fmt(t[1])] : ['open', 'Open now · closes ' + fmt(t[1])];
    if (t && m < mins(t[0])) return ['off', 'Closed · opens ' + fmt(t[0])];
    for (var k = 1; k < 8; k++) { var dd = (day + k) % 7; if (hours[dd]) return ['off', 'Closed · opens ' + (k === 1 ? 'tomorrow' : DAYS[dd]) + ' ' + fmt(hours[dd][0])]; }
    return ['off', 'Closed'];
  }
  function paintStatus(el, hours) { var s = status(hours); el.setAttribute('data-state', s[0]); var t = el.querySelector('.status-t'); if (t) t.textContent = s[1]; }

  /* ---------- copy swap (demo only: in WP each theme is a style variation; copy is CMS content) */
  function applyCopy() {
    var c = C[theme()]; if (!c) return;
    $$('[data-t]').forEach(function (el) { var v = c.t[el.getAttribute('data-t')]; if (v !== undefined) el.innerHTML = v; });
    $$('[data-ta]').forEach(function (el) { el.getAttribute('data-ta').split('|').forEach(function (pair) { var p = pair.split(':'), v = c.t[p[1]]; if (v !== undefined) el.setAttribute(p[0], v); }); });
    d.title = d.title.replace(/·[^·]*\(demo\)$/, '· ' + c.label + ' (demo)');
    applyLoc();
    root.classList.add('copy-ready');
  }
  function applyLoc() {
    var c = C[theme()]; if (!c) return; var L = c.locs[loc()];
    $$('[data-l]').forEach(function (el) { var v = L[el.getAttribute('data-l')]; if (v !== undefined) el.innerHTML = v; });
    $$('[data-lh]').forEach(function (el) { var v = L[el.getAttribute('data-lh')]; if (v) el.setAttribute('href', v); });
    $$('[data-l-status]').forEach(function (el) { paintStatus(el, L.hours); });
    $$('[data-status-of]').forEach(function (el) { paintStatus(el, c.locs[+el.getAttribute('data-status-of')].hours); });
    $$('[data-set-loc]').forEach(function (el) { var on = +el.getAttribute('data-set-loc') === loc(); el.setAttribute(el.getAttribute('role') === 'option' ? 'aria-selected' : 'aria-pressed', on); });
    $$('[data-loc-panel]').forEach(function (el) { el.hidden = +el.getAttribute('data-loc-panel') !== loc(); });
    $$('[data-loc-select]').forEach(function (el) { el.value = String(loc()); });
  }

  /* ---------- theme switcher */
  function setTheme(t) {
    if (THEMES.indexOf(t) < 0) return;
    root.setAttribute('data-theme', t); store.set('rm-theme', t);
    $$('[data-set-theme]').forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-set-theme') === t); });
    applyCopy();
  }
  d.addEventListener('click', function (e) { var b = e.target.closest('[data-set-theme]'); if (b) setTheme(b.getAttribute('data-set-theme')); });

  /* ---------- location switcher (listbox popover + any [data-set-loc] control) */
  function setLoc(i) { root.setAttribute('data-loc', i); store.set('rm-loc', i); applyLoc(); }
  $$('[data-locsw]').forEach(function (w) {
    var btn = w.querySelector('.locsw-btn'), list = w.querySelector('[role=listbox]'), opts = $$('[role=option]', list);
    function open(o) { list.hidden = !o; btn.setAttribute('aria-expanded', o); if (o) { var s = opts[loc()] || opts[0]; s.focus(); } }
    btn.addEventListener('click', function () { open(list.hidden); });
    list.addEventListener('keydown', function (e) {
      var i = opts.indexOf(d.activeElement);
      if (e.key === 'ArrowDown') { e.preventDefault(); opts[(i + 1) % opts.length].focus(); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); opts[(i + opts.length - 1) % opts.length].focus(); }
      else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setLoc(+opts[i].getAttribute('data-set-loc')); open(false); btn.focus(); }
      else if (e.key === 'Escape' || e.key === 'Tab') { open(false); btn.focus(); }
    });
    opts.forEach(function (o) { o.addEventListener('click', function () { setLoc(+o.getAttribute('data-set-loc')); open(false); btn.focus(); }); });
    d.addEventListener('click', function (e) { if (!w.contains(e.target) && !e.target.closest('[data-open-locsw]')) open(false); });
    w._open = open;
  });
  d.addEventListener('click', function (e) {
    var b = e.target.closest('[data-set-loc]'); if (b && b.getAttribute('role') !== 'option') setLoc(+b.getAttribute('data-set-loc'));
    var o = e.target.closest('[data-open-locsw]'); if (o) { var w = d.querySelector('[data-locsw]'); window.scrollTo({ top: 0 }); w && w._open(true); }
  });
  $$('[data-loc-select]').forEach(function (s) { s.addEventListener('change', function () { setLoc(+s.value); }); });

  /* ---------- tabs (WAI-ARIA tabs, roving tabindex) */
  $$('[role=tablist]').forEach(function (tl) {
    var tabs = $$('[role=tab]', tl);
    function sel(t, focus) { tabs.forEach(function (x) { var on = x === t; x.setAttribute('aria-selected', on); x.tabIndex = on ? 0 : -1; d.getElementById(x.getAttribute('aria-controls')).hidden = !on; }); if (focus) t.focus(); }
    tabs.forEach(function (t, i) {
      t.addEventListener('click', function () { sel(t); });
      t.addEventListener('keydown', function (e) { var k = e.key, n = k === 'ArrowRight' ? i + 1 : k === 'ArrowLeft' ? i - 1 : k === 'Home' ? 0 : k === 'End' ? tabs.length - 1 : null; if (n !== null) { e.preventDefault(); sel(tabs[(n + tabs.length) % tabs.length], true); } });
    });
  });

  /* ---------- category bar: aria-current on click (+ IntersectionObserver scrollspy) */
  $$('.catbar').forEach(function (bar) {
    var links = $$('a', bar);
    function cur(a) { links.forEach(function (l) { l.toggleAttribute('aria-current', l === a); if (l === a) l.setAttribute('aria-current', 'true'); }); }
    links.forEach(function (a) { a.addEventListener('click', function () { cur(a); }); });
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (es) { es.forEach(function (en) { if (en.isIntersecting) { var a = bar.querySelector('a[href="#' + en.target.id + '"]'); if (a) cur(a); } }); }, { rootMargin: '-30% 0px -60% 0px' });
      links.forEach(function (a) { var s = d.querySelector(a.getAttribute('href')); if (s) io.observe(s); });
    }
  });

  /* ---------- dietary filter (AND logic) */
  var filters = $$('[data-diet-filter]');
  function runFilter() {
    var on = filters.filter(function (f) { return f.checked; }).map(function (f) { return f.value; }), hidden = 0;
    $$('.menu-panel .mrow, .menu-panel .dish').forEach(function (r) { var dt = (r.getAttribute('data-diet') || '').split(' '); var ok = on.every(function (v) { return dt.indexOf(v) > -1; }); r.hidden = !ok; if (!ok) hidden++; });
    $$('.menu-panel .mcat').forEach(function (s) { var e = s.querySelector('.mcat-empty'); if (e) e.hidden = !!s.querySelector('.mrow:not([hidden])'); });
    var c = d.querySelector('.filter-count'); if (c) c.textContent = on.length ? hidden + ' dishes hidden by your filters' : '';
  }
  filters.forEach(function (f) { f.addEventListener('change', runFilter); });
  d.addEventListener('click', function (e) { if (e.target.closest('[data-clear-filters]')) { filters.forEach(function (f) { f.checked = false; }); runFilter(); } });

  /* ---------- chip filters (events, gallery) */
  function chipFilter(attr, items, getKey, empty) {
    var chips = $$('[' + attr + ']');
    chips.forEach(function (c) { c.addEventListener('click', function () {
      var v = c.getAttribute(attr), n = 0; chips.forEach(function (x) { x.setAttribute('aria-pressed', x === c); });
      items().forEach(function (it) { var ok = v === 'all' || getKey(it) === v; it.hidden = !ok; if (ok) n++; });
      if (empty) empty.hidden = n > 0;
    }); });
  }
  chipFilter('data-ev-filter', function () { return $$('[data-ev]'); }, function (it) { return it.getAttribute('data-ev'); }, d.querySelector('[data-ev-empty]'));
  chipFilter('data-gal-filter', function () { return $$('.gitem'); }, function (it) { return it.getAttribute('data-cat'); });

  /* ---------- lightbox (<dialog>, focus returns to trigger) */
  var dlg = d.querySelector('[data-lb-dialog]'), lbIdx = 0, lbFrom = null;
  function lbShow(i) {
    var items = $$('.gitem:not([hidden]) .gbtn'); if (!items.length) return; lbIdx = (i + items.length) % items.length;
    var b = items[lbIdx]; dlg.querySelector('.lb-media').innerHTML = b.querySelector('.media').outerHTML;
    dlg.querySelector('.lb-cap').textContent = b.querySelector('.gcap').textContent; dlg.querySelector('.lb-count').textContent = (lbIdx + 1) + ' / ' + items.length;
  }
  if (dlg) {
    d.addEventListener('click', function (e) { var b = e.target.closest('.gbtn'); if (b) { lbFrom = b; var items = $$('.gitem:not([hidden]) .gbtn'); lbShow(items.indexOf(b)); dlg.showModal(); } });
    dlg.querySelector('[data-lb-close]').addEventListener('click', function () { dlg.close(); });
    dlg.querySelector('[data-lb-prev]').addEventListener('click', function () { lbShow(lbIdx - 1); });
    dlg.querySelector('[data-lb-next]').addEventListener('click', function () { lbShow(lbIdx + 1); });
    dlg.addEventListener('keydown', function (e) { if (e.key === 'ArrowLeft') lbShow(lbIdx - 1); if (e.key === 'ArrowRight') lbShow(lbIdx + 1); });
    dlg.addEventListener('close', function () { if (lbFrom) lbFrom.focus(); });
  }

  /* ---------- mobile drawer */
  var drawer = d.querySelector('[data-drawer]'), burger = d.querySelector('[data-drawer-open]');
  function drw(o) { drawer.hidden = !o; burger.setAttribute('aria-expanded', o); d.body.style.overflow = o ? 'hidden' : ''; (o ? drawer.querySelector('[data-drawer-close]') : burger).focus(); }
  if (drawer) {
    burger.addEventListener('click', function () { drw(true); });
    drawer.querySelector('[data-drawer-close]').addEventListener('click', function () { drw(false); });
    drawer.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') drw(false);
      if (e.key === 'Tab') { var f = $$('a,button,input,select', drawer).filter(function (x) { return x.offsetParent; }), a = f[0], z = f[f.length - 1];
        if (e.shiftKey && d.activeElement === a) { e.preventDefault(); z.focus(); } else if (!e.shiftKey && d.activeElement === z) { e.preventDefault(); a.focus(); } }
    });
  }

  /* ---------- demo forms: native validation + success message */
  $$('[data-demo-form]').forEach(function (f) { f.addEventListener('submit', function (e) { e.preventDefault(); var ok = f.querySelector('.form-ok'); if (ok) ok.hidden = false; }); });

  /* ---------- init */
  var t0 = theme(); $$('[data-set-theme]').forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-set-theme') === t0); });
  applyCopy();

  /* screenshot/QA states: ?state=nav | loc | lightbox | filter */
  var st = new URLSearchParams(location.search).get('state');
  if (st === 'nav' && drawer) drw(true);
  if (st === 'loc') { var w = d.querySelector('[data-locsw]'); w && w._open(true); }
  if (st === 'lightbox' && dlg) { lbShow(2); dlg.showModal(); }
  if (st === 'filter') { filters.forEach(function (f) { f.checked = f.value === 'gf'; }); runFilter(); }
})();
