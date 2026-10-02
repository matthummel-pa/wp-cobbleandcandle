import { registerBlockType } from '@wordpress/blocks'
import { createElement as el, Fragment } from '@wordpress/element'
import { InspectorControls, useBlockProps } from '@wordpress/block-editor'
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components'
import ServerSideRender from '@wordpress/server-side-render'
import { __ } from '@wordpress/i18n'

/* Every resources/blocks/<name>/block.json becomes a dynamic block: live server preview +
   sidebar controls generated from its attributes. Rendering happens in Blade (app/blocks.php). */
const manifests = import.meta.glob('../blocks/*/block.json', { eager: true, import: 'default' })

const LABELS = {
  reserveLabel: __('Reserve button label', 'cobbleandcandle'),
  reserveUrl: __('Reserve link', 'cobbleandcandle'),
  orderLabel: __('Order button label', 'cobbleandcandle'),
  orderUrl: __('Order online link (leave empty to hide)', 'cobbleandcandle'),
  showUtilityBar: __('Show utility bar', 'cobbleandcandle'),
  showStyleSwitcher: __('Show demo style switcher', 'cobbleandcandle'),
  about: __('About line (defaults to the site tagline)', 'cobbleandcandle'),
  instagram: __('Instagram URL', 'cobbleandcandle'),
  facebook: __('Facebook URL', 'cobbleandcandle'),
  email: __('Email address', 'cobbleandcandle'),
  phone: __('Phone (defaults to the brand phone)', 'cobbleandcandle'),
  directionsUrl: __('Directions link', 'cobbleandcandle'),
}

function control(key, schema, value, setAttributes) {
  const label = LABELS[key] || key
  const onChange = (next) => setAttributes({ [key]: next })
  if (schema.type === 'boolean') {
    return el(ToggleControl, { key, label, checked: !!value, onChange, __nextHasNoMarginBottom: true })
  }
  return el(TextControl, { key, label, value: value || '', onChange, __next40pxDefaultSize: true, __nextHasNoMarginBottom: true })
}

Object.values(manifests).forEach((metadata) => {
  registerBlockType(metadata, {
    edit({ attributes, setAttributes }) {
      const blockProps = useBlockProps()
      const fields = Object.entries(metadata.attributes || {})
      return el(Fragment, null,
        fields.length > 0 && el(InspectorControls, null,
          el(PanelBody, { title: __('Settings', 'cobbleandcandle') },
            fields.map(([key, schema]) => control(key, schema, attributes[key], setAttributes)))),
        el('div', blockProps, el(ServerSideRender, { block: metadata.name, attributes })),
      )
    },
    save: () => null,
  })
})
