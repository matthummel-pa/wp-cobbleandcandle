import Alpine from 'alpinejs'

const root = document.documentElement
const DIRECTIONS = ['lampwright', 'ember-arch', 'ashlar-iron']
const FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]),select,textarea,[tabindex]:not([tabindex="-1"])'

/* Demo style switcher state (HANDOFF §2.3). The server prints the active direction on <html>. */
Alpine.store('site', {
  theme: root.dataset.theme,
  setTheme(theme) {
    if (!DIRECTIONS.includes(theme)) return
    this.theme = theme
    root.dataset.theme = theme
    try { localStorage.setItem('rm-theme', theme) } catch {}
  },
})

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
