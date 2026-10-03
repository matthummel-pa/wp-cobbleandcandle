const cookie = (name) => document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`))?.[1] || ''
  try { saved = decodeURIComponent(cookie('cobble_loc') || cookie('cc_loc')) } catch {}import Alpine from 'alpinejs'

const root = document.documentElement
/* Style directions come from the switcher's buttons (App\directions()), so a new style needs no JS change. */
const DIRECTIONS = [...new Set([root.dataset.theme, ...[...document.querySelectorAll('[data-set-theme]')].map((b) => b.dataset.setTheme)].filter(Boolean))]
/* Where a picked demo style is remembered: per business kind (restaurant / tavern / B&B), so each demo keeps its own look.
   The Site Header re-applies it before paint; see the inline script there. */
const THEME_KEY = root.dataset.kind ? `rm-theme:${root.dataset.kind}` : 'rm-theme'
const FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]),select,textarea,[tabindex]:not([tabindex="-1"])'

/* Locations and open-now settings from the Core plugin (printed by the Site Header as JSON). */
function readJson(id, fallback) {
  try { return JSON.parse(document.getElementById(id)?.textContent || '') } catch { return fallback }
}
const LOCATIONS = readJson('cobble-locations', [])
const STATUS = readJson('cobble-status', {})

/* The visitor's location: ?loc= (what the server rendered), then their saved choice, then the first.
   The saved cookie is applied here rather than on the server so cached pages stay shareable. */
function initialLocation() {
  let saved = ''
  // cc_loc: the cookie's name before the 1.0 prefix change, so returning guests keep their house. The new name wins.
  const cookie = (name) => document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`))?.[1] || ''
  try { saved = decodeURIComponent(cookie('cobble_loc') || cookie('cc_loc')) } catch {}
  const wanted = new URLSearchParams(location.search).get('loc') || saved || root.dataset.loc || ''
  return Math.max(0, LOCATIONS.findIndex((l) => l.slug === wanted))
}
const initial = initialLocation()

/* Open-now status, recomputed in the browser in the restaurant's timezone (mirrors cobble_location_status). */
const pad = (n) => String(n).padStart(2, '0')
function siteNow() {
  const tz = STATUS.tz || ''
  let y, mo, d, h, mi
  if (tz.includes('/')) {
    const parts = Object.fromEntries(new Intl.DateTimeFormat('en-US', { timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).formatToParts(new Date()).map((p) => [p.type, p.value]))
    ;({ year: y, month: mo, day: d, hour: h, minute: mi } = parts)
  } else {
    const m = tz.match(/^([+-])(\d{2}):(\d{2})$/)
    const offset = m ? (m[1] === '-' ? -1 : 1) * (Number(m[2]) * 60 + Number(m[3])) : 0
    const t = new Date(Date.now() + offset * 60000)
    ;[y, mo, d, h, mi] = [t.getUTCFullYear(), pad(t.getUTCMonth() + 1), pad(t.getUTCDate()), t.getUTCHours(), t.getUTCMinutes()]
  }
  return { date: new Date(Date.UTC(Number(y), Number(mo) - 1, Number(d))), minutes: Number(h) * 60 + Number(mi) }
}
const isoDay = (date) => date.toISOString().slice(0, 10)
const addDays = (date, n) => new Date(date.getTime() + n * 86400000)
function windowOn(windows, date) {
  const iso = isoDay(date)
  if (windows.holidays && iso in windows.holidays) return windows.holidays[iso]
  return windows.week[(date.getUTCDay() + 6) % 7]
}
function timeLabel(minutes) {
  const L = STATUS.labels
  const m = ((minutes % 1440) + 1440) % 1440
  if (L.clock24) return `${pad(Math.floor(m / 60))}:${pad(m % 60)}`
  if (m === 0) return L.midnight
  if (m === 720) return L.noon
  const h = Math.floor(m / 60), h12 = h % 12 || 12, suffix = h < 12 ? 'am' : 'pm'
  return m % 60 ? `${h12}:${pad(m % 60)}${suffix}` : `${h12}${suffix}`
}
function computeStatus(windows) {
  const L = STATUS.labels
  const { date, minutes } = siteNow()
  const open = (left, close) => ({ state: left <= 60 ? 'warn' : 'open', text: (left <= 60 ? L.soon : L.open).replace('%s', timeLabel(close)) })
  const yesterday = windowOn(windows, addDays(date, -1))
  if (yesterday && yesterday[1] > 1440 && minutes < yesterday[1] - 1440) return open(yesterday[1] - 1440 - minutes, yesterday[1])
  const today = windowOn(windows, date)
  if (today && minutes >= today[0] && minutes < today[1]) return open(today[1] - minutes, today[1])
  if (today && minutes < today[0]) return { state: 'off', text: L.opensToday.replace('%s', timeLabel(today[0])) }
  for (let k = 1; k <= 7; k++) {
    const day = addDays(date, k)
    const w = windowOn(windows, day)
    if (w) {
      const when = k === 1 ? L.tomorrow : new Intl.DateTimeFormat(root.lang || undefined, { weekday: 'short', timeZone: 'UTC' }).format(day)
      return { state: 'off', text: L.opensLater.replace('%1$s', when).replace('%2$s', timeLabel(w[0])) }
    }
  }
  return { state: 'off', text: L.closed }
}

/* Site-wide state: demo style direction (HANDOFF §2.3) and the current location (§8). */
Alpine.store('site', {
  theme: root.dataset.theme,
  locations: LOCATIONS,
  current: initial,
  get loc() {
    return this.locations[this.current] || { name: '', phone: '', tel: '', map_url: '', order_url: '', status: { state: 'off', text: '' } }
  },
  setLocation(index) {
    if (!this.locations[index]) return
    this.current = index
    root.dataset.loc = this.locations[index].slug
    document.cookie = `cobble_loc=${encodeURIComponent(this.locations[index].slug)};path=/;max-age=31536000;samesite=lax`
    document.cookie = 'cc_loc=;path=/;max-age=0;samesite=lax' // The pre-1.0 cookie, so it can no longer win.
  },
  setTheme(theme) {
    if (!DIRECTIONS.includes(theme)) return
    this.theme = theme
    root.dataset.theme = theme
    try { localStorage.setItem(THEME_KEY, theme) } catch {}
  },
  /* Recompute every location's status; badges bound to the store update reactively, the rest by slug. */
  refreshStatus() {
    if (!STATUS.labels) return
    this.locations.forEach((loc) => {
      if (!loc.windows) return
      loc.status = computeStatus(loc.windows)
      document.querySelectorAll(`[data-status-of="${CSS.escape(loc.slug)}"]`).forEach((el) => {
        el.dataset.state = loc.status.state
        const text = el.querySelector('.status-t')
        if (text) text.textContent = loc.status.text
      })
    })
  },
  init() {
    root.dataset.loc = this.loc.slug || root.dataset.loc
    this.refreshStatus()
    setInterval(() => this.refreshStatus(), 60000)
  },
})

/* Location switcher: button + listbox with roving focus (HANDOFF §8). */
Alpine.data('locationSwitcher', () => ({
  open: false,
  options() {
    return [...this.$refs.list.querySelectorAll('[role="option"]')]
  },
  show() {
    this.open = true
    this.$nextTick(() => this.options()[Alpine.store('site').current]?.focus())
  },
  close(returnFocus = false) {
    if (!this.open) return
    this.open = false
    if (returnFocus) this.$refs.button.focus()
  },
  toggle() {
    this.open ? this.close() : this.show()
  },
  /* "Change location" buttons elsewhere dispatch cobble-open-locations; only a visible switcher answers. */
  openFromPage() {
    if (!this.$el.offsetParent) return
    window.scrollTo({ top: 0, behavior: 'smooth' })
    this.show()
  },
  choose(index) {
    Alpine.store('site').setLocation(index)
    this.close(true)
  },
  keys(event) {
    const items = this.options()
    const at = items.indexOf(document.activeElement)
    const go = (i) => { event.preventDefault(); items[(i + items.length) % items.length]?.focus() }
    if (event.key === 'ArrowDown') go(at + 1)
    else if (event.key === 'ArrowUp') go(at - 1)
    else if (event.key === 'Home') go(0)
    else if (event.key === 'End') go(items.length - 1)
    else if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); if (at > -1) this.choose(at) }
    else if (event.key === 'Tab') this.close()
  },
}))

/* ARIA tabs (menu teaser, menu page): arrows / Home / End, roving tabindex (HANDOFF §8). */
Alpine.data('tabs', () => ({
  active: 0,
  tabs() {
    return [...this.$root.querySelectorAll('[role="tab"]')]
  },
  select(index, focus = false) {
    this.active = index
    if (focus) this.$nextTick(() => this.tabs()[index]?.focus())
  },
  keys(event) {
    const count = this.tabs().length
    const go = (i) => { event.preventDefault(); this.select((i + count) % count, true) }
    const step = getComputedStyle(this.$el).direction === 'rtl' ? -1 : 1 // Arrows follow the reading direction.
    if (event.key === 'ArrowRight') go(this.active + step)
    else if (event.key === 'ArrowLeft') go(this.active - step)
    else if (event.key === 'Home') go(0)
    else if (event.key === 'End') go(count - 1)
  },
}))

/* Full menu: dietary filters (AND logic) and per-location availability, with a live count (HANDOFF §3, §8). */
Alpine.data('menuFilter', () => ({
  diets: [],
  summary: '',
  init() {
    Alpine.effect(() => this.apply())
  },
  clear() {
    this.diets = []
  },
  apply() {
    const slug = Alpine.store('site').loc.slug || ''
    const diets = [...this.diets]
    let hidden = 0
    this.$root.querySelectorAll('.mrow, .dish').forEach((el) => {
      const diet = (el.dataset.diet || '').split(' ')
      const locs = (el.dataset.locs || '').split(' ').filter(Boolean)
      const here = !slug || !locs.length || locs.includes(slug)
      const match = diets.every((d) => diet.includes(d))
      el.hidden = !(here && match)
      if (here && !match) hidden++
    })
    // A section emptied by filters says so; one with nothing served at this location disappears.
    this.$root.querySelectorAll('.mcat').forEach((section) => {
      const empty = !section.querySelector('.mrow:not([hidden]), .dish:not([hidden])')
      const note = section.querySelector('.mcat-empty')
      section.hidden = empty && (!diets.length || !note)
      if (note) note.hidden = !empty
    })
    const { hiddenOne, hiddenMany } = this.$root.dataset
    this.summary = !diets.length ? '' : hidden === 1 ? hiddenOne : hiddenMany.replace('%d', hidden)
  },
}))

/* Menu section links: aria-current follows the section in view (IntersectionObserver scrollspy). */
Alpine.data('catbar', () => ({
  current: '',
  init() {
    const links = [...this.$el.querySelectorAll('a[href^="#"]')]
    this.current = links[0]?.hash.slice(1) || ''
    if (!('IntersectionObserver' in window)) return
    const spy = new IntersectionObserver((entries) => entries.forEach((e) => { if (e.isIntersecting) this.current = e.target.id }), { rootMargin: '-30% 0px -60% 0px' })
    links.forEach((a) => { const section = document.getElementById(a.hash.slice(1)); if (section) spy.observe(section) })
  },
}))

/* Native table request: time slots for the chosen date from the location's hours (Core plugin's
   cobble_booking_windows: Monday-first [first, last seating] minutes plus holiday overrides). */
const isoDate = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
const timeFormat = new Intl.DateTimeFormat(root.lang || undefined, { hour: 'numeric', minute: '2-digit' })
const dayFormat = new Intl.DateTimeFormat(root.lang || undefined, { weekday: 'short', day: 'numeric', month: 'short' })

/* Table slots for a day from a house's booking windows (skips the next 30 minutes today). */
const seatingSlots = (windows, iso) => {
  if (!iso || !windows) return []
  const window = iso in windows.holidays ? windows.holidays[iso] : windows.week[(new Date(`${iso}T12:00`).getDay() + 6) % 7]
  if (!window) return []
  const now = new Date()
  const soonest = iso === isoDate(now) ? now.getHours() * 60 + now.getMinutes() + 30 : -1
  const slots = []
  for (let m = window[0]; m <= window[1]; m += windows.step) {
    if (m < soonest) continue
    const at = new Date(2000, 0, 1, Math.floor(m / 60) % 24, m % 60)
    slots.push({ value: `${pad(Math.floor(m / 60) % 24)}:${pad(m % 60)}`, label: timeFormat.format(at) })
  }
  return slots
}

Alpine.data('bookingForm', (windows) => ({
  date: '',
  party: '2',
  time: '',
  init() {
    const today = new Date()
    for (let k = 0; k < 14 && !this.date; k++) {
      const day = new Date(today.getFullYear(), today.getMonth(), today.getDate() + k)
      if (this.slotsFor(isoDate(day)).length) this.date = isoDate(day)
    }
    this.$watch('date', () => { if (!this.slots.some((s) => s.value === this.time)) this.time = '' })
  },
  slotsFor(iso) {
    return seatingSlots(windows, iso)
  },
  get slots() {
    return this.slotsFor(this.date)
  },
  get dayLabel() {
    return this.date ? `· ${dayFormat.format(new Date(`${this.date}T12:00`))}` : ''
  },
  get submitLabel() {
    const slot = this.slots.find((s) => s.value === this.time)
    const { submit, submitEmpty } = this.$root.dataset
    return slot ? submit.replace('%1$s', this.party).replace('%2$s', slot.label) : submitEmpty
  },
}))

/* Room booking: live availability calendar (two months), check-in/out picking that skips booked
   nights, minimum stay, and a running total. The native date inputs stay the accessible source of truth. */
Alpine.data('stayPicker', (cfg) => ({
  full: new Set(),
  today: isoDate(new Date()),
  ready: false,
  offset: 0,
  checkIn: '',
  checkOut: '',
  dinner: false,
  dinnerTime: '',
  weekdays: [...Array(7)].map((_, i) => new Intl.DateTimeFormat(root.lang || undefined, { weekday: 'narrow' }).format(new Date(2024, 0, 1 + i))),
  async init() {
    const to = new Date()
    to.setDate(to.getDate() + 380)
    try {
      const url = new URL(cfg.rest)
      url.searchParams.set('from', this.today)
      url.searchParams.set('to', isoDate(to))
      const res = await fetch(url, { credentials: 'same-origin' })
      if (!res.ok) return
      const data = await res.json()
      this.full = new Set(data.full || [])
      this.today = data.today || this.today
      this.ready = true
    } catch (e) {
      // Calendar stays hidden; the date inputs still work and the server re-checks every request.
    }
  },
  addDays(iso, n) {
    const d = new Date(`${iso}T12:00`)
    d.setDate(d.getDate() + n)
    return isoDate(d)
  },
  nights(from, to) {
    const out = []
    for (let d = from; d < to && out.length < 400; d = this.addDays(d, 1)) out.push(d)
    return out
  },
  rangeFree(from, to) {
    return this.nights(from, to).every((n) => !this.full.has(n))
  },
  pick(iso) {
    if (this.checkIn && !this.checkOut && iso > this.checkIn && this.rangeFree(this.checkIn, iso)) {
      this.checkOut = iso
      return
    }
    if (!this.full.has(iso)) {
      this.checkIn = iso
      this.checkOut = ''
    }
  },
  shift(n) {
    this.offset = Math.min(11, Math.max(0, this.offset + n))
  },
  get months() {
    const base = new Date(`${this.today}T12:00`)
    const fmt = new Intl.DateTimeFormat(root.lang || undefined, { month: 'long', year: 'numeric' })
    const long = new Intl.DateTimeFormat(root.lang || undefined, { weekday: 'long', day: 'numeric', month: 'long' })
    const { free, full } = this.$root.dataset
    return [0, 1].map((k) => {
      const first = new Date(base.getFullYear(), base.getMonth() + this.offset + k, 1, 12)
      const count = new Date(first.getFullYear(), first.getMonth() + 1, 0).getDate()
      const days = []
      for (let d = 1; d <= count; d++) {
        const date = new Date(first.getFullYear(), first.getMonth(), d, 12)
        const iso = isoDate(date)
        const taken = this.full.has(iso)
        const canOut = this.checkIn && !this.checkOut && iso > this.checkIn && this.rangeFree(this.checkIn, iso)
        const inStay = this.checkIn && this.checkOut && iso >= this.checkIn && iso <= this.checkOut
        days.push({
          iso,
          n: d,
          disabled: iso < this.today || (taken && !canOut),
          selected: iso === this.checkIn || iso === this.checkOut,
          cls: { 'is-full': taken, 'is-past': iso < this.today, 'is-in': inStay, 'is-end': iso === this.checkIn || iso === this.checkOut },
          label: `${long.format(date)}, ${taken ? full : free}`,
        })
      }
      return { key: `${first.getFullYear()}-${first.getMonth()}`, label: fmt.format(first), blank: (first.getDay() + 6) % 7, days }
    })
  },
  get dinnerSlots() {
    return seatingSlots(cfg.windows, this.checkIn)
  },
  get dinnerOk() {
    return !this.dinner || this.dinnerSlots.some((s) => s.value === this.dinnerTime)
  },
  clearOut() {
    if (this.checkOut <= this.checkIn) this.checkOut = ''
  },
  get atEnd() {
    return this.offset >= 11
  },
  get minOut() {
    return this.checkIn ? this.addDays(this.checkIn, cfg.minNights || 1) : ''
  },
  get stay() {
    return this.checkIn && this.checkOut > this.checkIn ? this.nights(this.checkIn, this.checkOut) : []
  },
  get problem() {
    if (!this.stay.length) return ''
    if (this.stay.length < (cfg.minNights || 1)) return this.$root.dataset.min
    if (this.stay.some((n) => this.full.has(n))) return this.$root.dataset.taken
    return ''
  },
  get valid() {
    return this.stay.length > 0 && this.problem === '' && this.dinnerOk
  },
  get nightsLabel() {
    const n = this.stay.length
    return (n === 1 ? this.$root.dataset.nights : this.$root.dataset.nightsPlural).replace('%d', n)
  },
  get total() {
    if (!this.valid || !cfg.priceNight) return ''
    const sum = this.stay.reduce((t, n) => {
      const day = new Date(`${n}T12:00`).getDay()
      return t + ((day === 5 || day === 6) && cfg.priceWeekend ? cfg.priceWeekend : cfg.priceNight)
    }, 0)
    try {
      return new Intl.NumberFormat(root.lang || undefined, { style: 'currency', currency: cfg.currency, maximumFractionDigits: sum % 1 ? 2 : 0 }).format(sum)
    } catch (e) {
      return String(sum)
    }
  },
}))

/* Gallery: category filter and a <dialog> lightbox over the visible tiles (arrows, Esc, focus return). */
Alpine.data('gallery', () => ({
  filter: 'all',
  index: 0,
  caption: '',
  count: '',
  from: null,
  items() {
    return [...this.$root.querySelectorAll('.gitem:not([hidden]) .gbtn')]
  },
  open(button) {
    this.from = button
    this.show(this.items().indexOf(button))
    this.$refs.dialog.showModal()
  },
  show(i) {
    const items = this.items()
    if (!items.length) return
    this.index = (i + items.length) % items.length
    const button = items[this.index]
    const media = button.querySelector('.media').cloneNode(true)
    const img = media.querySelector('img')
    if (img && button.dataset.full) {
      img.removeAttribute('srcset')
      img.removeAttribute('sizes')
      img.loading = 'eager'
      img.src = button.dataset.full
    }
    this.$refs.media.replaceChildren(media)
    this.caption = button.querySelector('.gcap')?.textContent || ''
    this.count = `${this.index + 1} / ${items.length}`
  },
  closed() {
    this.$refs.media.replaceChildren()
    // After the dialog's own focus restore, so the tile that opened it always gets focus back.
    requestAnimationFrame(() => this.from?.focus())
  },
}))

/* Site Header drawer: focus trap, inert page, scroll lock, Esc, focus return (HANDOFF §8). */
Alpine.data('siteHeader', () => ({
  open: false,
  openDrawer() {
    this.open = true
    this.lockPage(true)
    this.$nextTick(() => this.$refs.drawer.querySelector(FOCUSABLE)?.focus())
  },
  closeDrawer() {
    if (!this.open) return
    this.open = false
    this.lockPage(false)
    this.$nextTick(() => this.$refs.burger?.focus())
  },
  lockPage(locked) {
    document.body.style.overflow = locked ? 'hidden' : ''
    document.querySelectorAll('.wp-site-blocks > :not(header)').forEach((el) => { el.inert = locked })
  },
  trap(event) {
    if (event.key !== 'Tab') return
    const items = [...this.$refs.drawer.querySelectorAll(FOCUSABLE)].filter((el) => el.offsetParent)
    const first = items[0]
    const last = items[items.length - 1]
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus() }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus() }
  },
}))

window.Alpine = Alpine
Alpine.start()
