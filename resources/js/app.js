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
