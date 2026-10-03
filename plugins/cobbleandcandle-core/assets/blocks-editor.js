/* Cobble & Candle Core: block editor UI for the plugin's blocks (blocks/<name>/block.json, passed in
   as window.cobbleBlocks). Settings panels are built from each block's attributes; dynamic blocks
   preview through ServerSideRender (rendered by the theme); content blocks start with core blocks.
   Plain JavaScript on WordPress's globals: no build step. */
( function () {
const { registerBlockType } = wp.blocks
const { createElement: el, Fragment } = wp.element
const { InspectorControls, InnerBlocks, MediaUpload, MediaUploadCheck, useBlockProps } = wp.blockEditor
const { Button, PanelBody, TextareaControl, TextControl, ToggleControl } = wp.components
const ServerSideRender = wp.serverSideRender
const { useSelect } = wp.data
const { __ } = wp.i18n
/* Every blocks/<name>/block.json is registered here and rendered by the theme's Blade views (app/blocks.php).
   - Data blocks: live server preview + sidebar controls generated from their attributes.
   - Content blocks (CONTENT below): their copy is real inner blocks you type into on the canvas. */
const manifests = window.cobbleBlocks || []

const LABELS = {
  reserveLabel: __('Reserve button label', 'cobbleandcandle-core'),
  reserveUrl: __('Reserve link', 'cobbleandcandle-core'),
  orderLabel: __('Order button label', 'cobbleandcandle-core'),
  orderUrl: __('Order online link (empty = use the location’s)', 'cobbleandcandle-core'),
  showUtilityBar: __('Show utility bar', 'cobbleandcandle-core'),
  showStyleSwitcher: __('Show demo switchers (style and business type)', 'cobbleandcandle-core'),
  about: __('About line (defaults to the site tagline)', 'cobbleandcandle-core'),
  instagram: __('Instagram URL', 'cobbleandcandle-core'),
  facebook: __('Facebook URL', 'cobbleandcandle-core'),
  email: __('Email address', 'cobbleandcandle-core'),
  phone: __('Phone (empty = the current location’s)', 'cobbleandcandle-core'),
  directionsUrl: __('Directions link (empty = the current location’s)', 'cobbleandcandle-core'),
  eyebrow: __('Eyebrow', 'cobbleandcandle-core'),
  title: __('Heading', 'cobbleandcandle-core'),
  intro: __('Intro', 'cobbleandcandle-core'),
  count: __('How many', 'cobbleandcandle-core'),
  rows: __('Dishes per tab', 'cobbleandcandle-core'),
  tonightNote: __('Tonight note', 'cobbleandcandle-core'),
  locationsUrl: __('All locations link', 'cobbleandcandle-core'),
  menuUrl: __('Full menu link', 'cobbleandcandle-core'),
  pdfUrl: __('Printable PDF link', 'cobbleandcandle-core'),
  boardEyebrow: __('Board eyebrow', 'cobbleandcandle-core'),
  boardTitle: __('Board title', 'cobbleandcandle-core'),
  boardMenu: __('Board menu (slug, e.g. cellar)', 'cobbleandcandle-core'),
  boardFoot: __('Board footer', 'cobbleandcandle-core'),
  linkUrl: __('Link', 'cobbleandcandle-core'),
  linkLabel: __('Link label', 'cobbleandcandle-core'),
  caption: __('Photo caption', 'cobbleandcandle-core'),
  press: __('Press names (comma separated)', 'cobbleandcandle-core'),
  seatings: __('Seatings nightly', 'cobbleandcandle-core'),
  imageId: __('Photo', 'cobbleandcandle-core'),
  imageIds: __('Photos', 'cobbleandcandle-core'),
  lede: __('Intro (empty = the page excerpt)', 'cobbleandcandle-core'),
  art: __('Placeholder art when there is no photo', 'cobbleandcandle-core'),
  showCrumbs: __('Show breadcrumbs', 'cobbleandcandle-core'),
  picks: __('Chef’s picks to show (0 = none)', 'cobbleandcandle-core'),
  allergenNote: __('Allergen note', 'cobbleandcandle-core'),
  orderTitle: __('Order card title (empty = hide)', 'cobbleandcandle-core'),
  orderText: __('Order card text', 'cobbleandcandle-core'),
  notes: __('Good to know (one per line)', 'cobbleandcandle-core'),
  callHours: __('Phone hours note', 'cobbleandcandle-core'),
  reserveLabel: __('Reserve button label', 'cobbleandcandle-core'),
  contacts: __('Direct contacts (one per line, Label: value)', 'cobbleandcandle-core'),
  showFeatured: __('Feature the next event', 'cobbleandcandle-core'),
  showStay: __('Show a Stay button when you have rooms', 'cobbleandcandle-core'),
  stayUrl: __('Stay link', 'cobbleandcandle-core'),
  listTitle: __('List heading', 'cobbleandcandle-core'),
  emptyText: __('Text when there are no events', 'cobbleandcandle-core'),
  regularsEyebrow: __('Regulars eyebrow', 'cobbleandcandle-core'),
  regularsTitle: __('Regulars heading', 'cobbleandcandle-core'),
  regularsIntro: __('Regulars intro', 'cobbleandcandle-core'),
  regulars: __('Regulars (one per line)', 'cobbleandcandle-core'),
  related: __('More events to show', 'cobbleandcandle-core'),
  items: __('Items (one per line, parts separated by |)', 'cobbleandcandle-core'),
  reverse: __('Photo on the right', 'cobbleandcandle-core'),
  showStats: __('Show stats', 'cobbleandcandle-core'),
}

/* Settings typed as several lines. */
const MULTILINE = ['notes', 'contacts', 'regulars', 'items', 'allergenNote', 'intro', 'regularsIntro']

/* Starting copy for content blocks: real core blocks, styled with the mockup classes. */
const p = (className, content) => ['core/paragraph', { className, content }]
const buttons = (items) => ['core/buttons', {}, items.map(([text, url, style]) => ['core/button', { text, url, className: style }])]
const CONTENT = {
  'cobbleandcandle/hero': [
    p('eyebrow eyebrow--hero', __('Supper by candlelight · Since 1888', 'cobbleandcandle-core')),
    ['core/heading', { level: 1, className: 'h1 hero-h', content: __('Supper by <em>candlelight</em> on the old cobbles.', 'cobbleandcandle-core') }],
    p('lede', __('A seasonal tasting menu served in three restored merchant houses, each lit as it was a century ago. Two seatings nightly.', 'cobbleandcandle-core')),
    buttons([[__('Reserve a table', 'cobbleandcandle-core'), '/reservations/', 'is-style-fill'], [__('View the menu', 'cobbleandcandle-core'), '/menu/', 'is-style-outline']]),
  ],
  'cobbleandcandle/story': [
    p('eyebrow', __('Our story', 'cobbleandcandle-core')),
    ['core/heading', { level: 2, className: 'h2', content: __('A lamplighter’s house, still lit by hand', 'cobbleandcandle-core') }],
    ['core/paragraph', { content: __('In 1888 the Wharf’s lamplighter turned his front parlour into a supper room for sailors coming off the evening tide. The brass lamps he polished every dusk still hang above table four.', 'cobbleandcandle-core') }],
    ['core/paragraph', { content: __('Today Chef Margot Ellery cooks from the same coast and the same walled gardens, with a kitchen that runs on wood, patience and a very old copper stockpot.', 'cobbleandcandle-core') }],
    ['core/quote', { className: 'pull' }, [['core/paragraph', { content: __('We cook the way the house is lit: slowly, warmly, and with nothing to hide.', 'cobbleandcandle-core') }]]],
    buttons([[__('Read our story', 'cobbleandcandle-core'), '/story/', 'is-style-outline']]),
  ],
  'cobbleandcandle/reviews': [
    ['core/quote', { citation: __('<strong>The Old Town Courier</strong> ★★★★★ · Restaurant of the Year 2025', 'cobbleandcandle-core') }, [['core/paragraph', { content: __('The kind of room that makes you lower your voice and order another bottle. Every plate glowed.', 'cobbleandcandle-core') }]]],
    ['core/quote', { citation: __('<strong>Harbour &amp; Hearth Magazine</strong> Critic’s choice', 'cobbleandcandle-core') }, [['core/paragraph', { content: __('Ellery’s duck is reason enough to cross the harbour. The candlelight is just the bonus.', 'cobbleandcandle-core') }]]],
    ['core/quote', { citation: __('<strong>Eleanor W., guest</strong> ★★★★★ · Google review', 'cobbleandcandle-core') }, [['core/paragraph', { content: __('We celebrated our 30th anniversary in the cellar. Faultless, unhurried, unforgettable.', 'cobbleandcandle-core') }]]],
  ],
  'cobbleandcandle/private-dining': [
    p('eyebrow', __('Private dining & events', 'cobbleandcandle-core')),
    ['core/heading', { level: 2, className: 'h2', content: __('Private dining in the Lamp Room', 'cobbleandcandle-core') }],
    ['core/paragraph', { content: __('Up to 28 guests by candlelight, with a dedicated sommelier and a menu written for the occasion.', 'cobbleandcandle-core') }],
    ['core/list', { className: 'rooms' }, [['core/list-item', { content: __('<strong>The Lamp Room</strong> Seats 28', 'cobbleandcandle-core') }], ['core/list-item', { content: __('<strong>The Cellar Table</strong> Seats 22', 'cobbleandcandle-core') }], ['core/list-item', { content: __('<strong>The Snug</strong> Seats 10', 'cobbleandcandle-core') }]]],
  ],
}
CONTENT['cobbleandcandle/cta-band'] = [
  ['core/heading', { level: 2, className: 'h2', content: __('Your table is waiting.', 'cobbleandcandle-core') }],
  buttons([[__('Reserve a table', 'cobbleandcandle-core'), '/reservations/', 'is-style-fill'], [__('Private dining', 'cobbleandcandle-core'), '/#private-dining', 'is-style-outline']]),
]

const ALLOWED = {
  'cobbleandcandle/reviews': ['core/quote'],
}

function ImageControl({ label, value, onChange, multiple }) {
  const ids = multiple ? (Array.isArray(value) ? value : []) : (value ? [value] : [])
  return el(MediaUploadCheck, null, el('div', { style: { marginBottom: 16 } },
    el('p', { style: { margin: '0 0 6px', fontWeight: 500 } }, label),
    el(MediaUpload, {
      allowedTypes: ['image'],
      multiple: multiple ? 'add' : false,
      gallery: !!multiple,
      value: multiple ? ids : value,
      onSelect: (media) => onChange(multiple ? media.map((m) => m.id) : media.id),
      render: ({ open }) => el(Fragment, null,
        el(Button, { variant: 'secondary', onClick: open }, ids.length ? __('Replace', 'cobbleandcandle-core') : __('Choose', 'cobbleandcandle-core')),
        ids.length > 0 && el(Button, { variant: 'link', isDestructive: true, onClick: () => onChange(multiple ? [] : 0), style: { marginLeft: 8 } }, __('Remove', 'cobbleandcandle-core')),
        ids.length > 0 && el('p', { style: { margin: '6px 0 0', color: '#757575' } }, multiple ? `${ids.length} ${__('selected', 'cobbleandcandle-core')}` : __('Photo selected', 'cobbleandcandle-core')),
      ),
    }),
  ))
}

function control(key, schema, value, setAttributes) {
  const label = LABELS[key] || key
  const onChange = (next) => setAttributes({ [key]: next })
  if (key === 'imageIds') return el(ImageControl, { key, label, value, onChange, multiple: true })
  if (key === 'imageId') return el(ImageControl, { key, label, value, onChange, multiple: false })
  if (MULTILINE.includes(key)) return el(TextareaControl, { key, label, value: value || '', onChange, rows: 5, __nextHasNoMarginBottom: true })
  if (schema.type === 'boolean') return el(ToggleControl, { key, label, checked: !!value, onChange, __nextHasNoMarginBottom: true })
  if (schema.type === 'number' || schema.type === 'integer') {
    return el(TextControl, { key, label, type: 'number', min: 1, value: value ?? '', onChange: (v) => onChange(Number(v) || 0), __next40pxDefaultSize: true, __nextHasNoMarginBottom: true })
  }
  return el(TextControl, { key, label, value: value || '', onChange, __next40pxDefaultSize: true, __nextHasNoMarginBottom: true })
}

function Settings({ metadata, attributes, setAttributes }) {
  const fields = Object.entries(metadata.attributes || {})
  if (!fields.length) return null
  return el(InspectorControls, null, el(PanelBody, { title: __('Settings', 'cobbleandcandle-core') },
    fields.map(([key, schema]) => el('div', { key, style: { marginBottom: 12 } }, control(key, schema, attributes[key], setAttributes)))))
}

manifests.forEach((metadata) => {
  const template = CONTENT[metadata.name]
  registerBlockType(metadata, template
    ? {
        edit({ attributes, setAttributes }) {
          const blockProps = useBlockProps({ className: 'cobble-content-block' })
          return el(Fragment, null,
            el(Settings, { metadata, attributes, setAttributes }),
            el('div', blockProps, el(InnerBlocks, { template, allowedBlocks: ALLOWED[metadata.name], templateLock: false })))
        },
        save: () => el(InnerBlocks.Content),
      }
    : {
        edit({ attributes, setAttributes }) {
          const blockProps = useBlockProps()
          // Pass the post being edited so blocks like Page Hero can preview its title and image.
          const postId = useSelect((select) => select('core/editor')?.getCurrentPostId?.(), [])
          return el(Fragment, null,
            el(Settings, { metadata, attributes, setAttributes }),
            el('div', blockProps, el(ServerSideRender, { block: metadata.name, attributes, urlQueryArgs: postId ? { post_id: postId } : {} })))
        },
        save: () => null,
      })
})
} )()
