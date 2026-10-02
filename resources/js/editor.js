import { registerBlockType } from '@wordpress/blocks'
import { createElement as el, Fragment } from '@wordpress/element'
import { InspectorControls, InnerBlocks, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor'
import { Button, PanelBody, TextControl, ToggleControl } from '@wordpress/components'
import ServerSideRender from '@wordpress/server-side-render'
import { useSelect } from '@wordpress/data'
import { __ } from '@wordpress/i18n'

/* Every resources/blocks/<name>/block.json is registered here and rendered by Blade (app/blocks.php).
   - Data blocks: live server preview + sidebar controls generated from their attributes.
   - Content blocks (CONTENT below): their copy is real inner blocks you type into on the canvas. */
const manifests = import.meta.glob('../blocks/*/block.json', { eager: true, import: 'default' })

const LABELS = {
  reserveLabel: __('Reserve button label', 'cobbleandcandle'),
  reserveUrl: __('Reserve link', 'cobbleandcandle'),
  orderLabel: __('Order button label', 'cobbleandcandle'),
  orderUrl: __('Order online link (empty = use the location’s)', 'cobbleandcandle'),
  showUtilityBar: __('Show utility bar', 'cobbleandcandle'),
  showStyleSwitcher: __('Show demo style switcher', 'cobbleandcandle'),
  about: __('About line (defaults to the site tagline)', 'cobbleandcandle'),
  instagram: __('Instagram URL', 'cobbleandcandle'),
  facebook: __('Facebook URL', 'cobbleandcandle'),
  email: __('Email address', 'cobbleandcandle'),
  phone: __('Phone (empty = the current location’s)', 'cobbleandcandle'),
  directionsUrl: __('Directions link (empty = the current location’s)', 'cobbleandcandle'),
  eyebrow: __('Eyebrow', 'cobbleandcandle'),
  title: __('Heading', 'cobbleandcandle'),
  intro: __('Intro', 'cobbleandcandle'),
  count: __('How many', 'cobbleandcandle'),
  rows: __('Dishes per tab', 'cobbleandcandle'),
  tonightNote: __('Tonight note', 'cobbleandcandle'),
  locationsUrl: __('All locations link', 'cobbleandcandle'),
  menuUrl: __('Full menu link', 'cobbleandcandle'),
  pdfUrl: __('Printable PDF link', 'cobbleandcandle'),
  boardEyebrow: __('Board eyebrow', 'cobbleandcandle'),
  boardTitle: __('Board title', 'cobbleandcandle'),
  boardMenu: __('Board menu (slug, e.g. cellar)', 'cobbleandcandle'),
  boardFoot: __('Board footer', 'cobbleandcandle'),
  linkUrl: __('Link', 'cobbleandcandle'),
  linkLabel: __('Link label', 'cobbleandcandle'),
  caption: __('Photo caption', 'cobbleandcandle'),
  press: __('Press names (comma separated)', 'cobbleandcandle'),
  seatings: __('Seatings nightly', 'cobbleandcandle'),
  imageId: __('Photo', 'cobbleandcandle'),
  imageIds: __('Photos (5)', 'cobbleandcandle'),
  lede: __('Intro (empty = the page excerpt)', 'cobbleandcandle'),
  art: __('Placeholder art when there is no photo', 'cobbleandcandle'),
  showCrumbs: __('Show breadcrumbs', 'cobbleandcandle'),
  picks: __('Chef’s picks to show (0 = none)', 'cobbleandcandle'),
  allergenNote: __('Allergen note', 'cobbleandcandle'),
  orderTitle: __('Order card title (empty = hide)', 'cobbleandcandle'),
  orderText: __('Order card text', 'cobbleandcandle'),
}

/* Starting copy for content blocks: real core blocks, styled with the mockup classes. */
const p = (className, content) => ['core/paragraph', { className, content }]
const buttons = (items) => ['core/buttons', {}, items.map(([text, url, style]) => ['core/button', { text, url, className: style }])]
const CONTENT = {
  'cobbleandcandle/hero': [
    p('eyebrow eyebrow--hero', 'Supper by candlelight · Since 1888'),
    ['core/heading', { level: 1, className: 'h1 hero-h', content: 'Supper by <em>candlelight</em> on the old cobbles.' }],
    p('lede', 'A seasonal tasting menu served in three restored merchant houses, each lit as it was a century ago. Two seatings nightly.'),
    buttons([['Reserve a table', '/reservations/', 'is-style-fill'], ['View the menu', '/menu/', 'is-style-outline']]),
  ],
  'cobbleandcandle/story': [
    p('eyebrow', 'Our story'),
    ['core/heading', { level: 2, className: 'h2', content: 'A lamplighter’s house, still lit by hand' }],
    ['core/paragraph', { content: 'In 1888 the Wharf’s lamplighter turned his front parlour into a supper room for sailors coming off the evening tide. The brass lamps he polished every dusk still hang above table four.' }],
    ['core/paragraph', { content: 'Today Chef Margot Ellery cooks from the same coast and the same walled gardens, with a kitchen that runs on wood, patience and a very old copper stockpot.' }],
    ['core/quote', { className: 'pull' }, [['core/paragraph', { content: 'We cook the way the house is lit: slowly, warmly, and with nothing to hide.' }]]],
    buttons([['Read our story', '/story/', 'is-style-outline']]),
  ],
  'cobbleandcandle/reviews': [
    ['core/quote', { citation: '<strong>The Old Town Courier</strong> ★★★★★ · Restaurant of the Year 2025' }, [['core/paragraph', { content: 'The kind of room that makes you lower your voice and order another bottle. Every plate glowed.' }]]],
    ['core/quote', { citation: '<strong>Harbour &amp; Hearth Magazine</strong> Critic’s choice' }, [['core/paragraph', { content: 'Ellery’s duck is reason enough to cross the harbour. The candlelight is just the bonus.' }]]],
    ['core/quote', { citation: '<strong>Eleanor W., guest</strong> ★★★★★ · Google review' }, [['core/paragraph', { content: 'We celebrated our 30th anniversary in the cellar. Faultless, unhurried, unforgettable.' }]]],
  ],
  'cobbleandcandle/private-dining': [
    p('eyebrow', 'Private dining & events'),
    ['core/heading', { level: 2, className: 'h2', content: 'Private dining in the Lamp Room' }],
    ['core/paragraph', { content: 'Up to 28 guests by candlelight, with a dedicated sommelier and a menu written for the occasion.' }],
    ['core/list', { className: 'rooms' }, [['core/list-item', { content: '<strong>The Lamp Room</strong> Seats 28' }], ['core/list-item', { content: '<strong>The Cellar Table</strong> Seats 22' }], ['core/list-item', { content: '<strong>The Snug</strong> Seats 10' }]]],
  ],
}
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
        el(Button, { variant: 'secondary', onClick: open }, ids.length ? __('Replace', 'cobbleandcandle') : __('Choose', 'cobbleandcandle')),
        ids.length > 0 && el(Button, { variant: 'link', isDestructive: true, onClick: () => onChange(multiple ? [] : 0), style: { marginLeft: 8 } }, __('Remove', 'cobbleandcandle')),
        ids.length > 0 && el('p', { style: { margin: '6px 0 0', color: '#757575' } }, multiple ? `${ids.length} ${__('selected', 'cobbleandcandle')}` : __('Photo selected', 'cobbleandcandle')),
      ),
    }),
  ))
}

function control(key, schema, value, setAttributes) {
  const label = LABELS[key] || key
  const onChange = (next) => setAttributes({ [key]: next })
  if (key === 'imageIds') return el(ImageControl, { key, label, value, onChange, multiple: true })
  if (key === 'imageId') return el(ImageControl, { key, label, value, onChange, multiple: false })
  if (schema.type === 'boolean') return el(ToggleControl, { key, label, checked: !!value, onChange, __nextHasNoMarginBottom: true })
  if (schema.type === 'number' || schema.type === 'integer') {
    return el(TextControl, { key, label, type: 'number', min: 1, value: value ?? '', onChange: (v) => onChange(Number(v) || 0), __next40pxDefaultSize: true, __nextHasNoMarginBottom: true })
  }
  return el(TextControl, { key, label, value: value || '', onChange, __next40pxDefaultSize: true, __nextHasNoMarginBottom: true })
}

function Settings({ metadata, attributes, setAttributes }) {
  const fields = Object.entries(metadata.attributes || {})
  if (!fields.length) return null
  return el(InspectorControls, null, el(PanelBody, { title: __('Settings', 'cobbleandcandle') },
    fields.map(([key, schema]) => el('div', { key, style: { marginBottom: 12 } }, control(key, schema, attributes[key], setAttributes)))))
}

Object.values(manifests).forEach((metadata) => {
  const template = CONTENT[metadata.name]
  registerBlockType(metadata, template
    ? {
        edit({ attributes, setAttributes }) {
          const blockProps = useBlockProps({ className: 'cc-content-block' })
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
