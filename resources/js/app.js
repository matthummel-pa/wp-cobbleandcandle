import Alpine from 'alpinejs'

const root = document.documentElement
const DIRECTIONS = ['lampwright', 'ember-arch', 'ashlar-iron']
const FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]),select,textarea,[tabindex]:not([tabindex="-1"])'

/* Locations from the Core plugin (printed by the Site Header as JSON); the server renders the current one first. */
function readLocations() {
  try { return JSON.parse(document.getElementById('cc-locations')?.textContent || '[]') } catch { return [] }
}
const LOCATIONS = readLocations()
const initial = Math.max(0, LOCATIONS.findIndex((l) => l.slug === root.dataset.loc))

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
    document.cookie = `cc_loc=${encodeURIComponent(this.locations[index].slug)};path=/;max-age=31536000;samesite=lax`
  },
  setTheme(theme) {
    if (!DIRECTIONS.includes(theme)) return
    this.theme = theme
    root.dataset.theme = theme
    try { localStorage.setItem('rm-theme', theme) } catch {}
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
  /* "Change location" buttons elsewhere dispatch cc-open-locations; only a visible switcher answers. */
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
    if (event.key === 'ArrowRight') go(this.active + 1)
    else if (event.key === 'ArrowLeft') go(this.active - 1)
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
   cc_booking_windows: Monday-first [first, last seating] minutes plus holiday overrides). */
const pad = (n) => String(n).padStart(2, '0')
const isoDate = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
const timeFormat = new Intl.DateTimeFormat(root.lang || undefined, { hour: 'numeric', minute: '2-digit' })
const dayFormat = new Intl.DateTimeFormat(root.lang || undefined, { weekday: 'short', day: 'numeric', month: 'short' })

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
    if (!iso) return []
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
