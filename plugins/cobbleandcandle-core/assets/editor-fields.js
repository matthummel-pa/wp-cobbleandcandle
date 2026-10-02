/**
 * Cobble & Candle Core: block editor sidebar panels for location, menu item and event fields.
 * Renders window.ccFields (from includes/fields.php). No build step: uses the wp.* globals.
 */
( function ( wp ) {
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editor;
	const { createElement: el, Fragment } = wp.element;
	const { useSelect } = wp.data;
	const { useEntityProp, store: coreStore } = wp.coreData;
	const { __ } = wp.i18n;
	const {
		TextControl,
		TextareaControl,
		ToggleControl,
		SelectControl,
		CheckboxControl,
		Button,
		BaseControl,
		Flex,
		FlexBlock,
	} = wp.components;

	const config = window.ccFields;
	if ( ! config ) {
		return;
	}

	const common = { __next40pxDefaultSize: true, __nextHasNoMarginBottom: true };

	function useLocations() {
		return useSelect(
			( select ) =>
				select( coreStore ).getEntityRecords( 'postType', 'cc_location', {
					per_page: 100,
					orderby: 'menu_order',
					order: 'asc',
					_fields: 'id,title',
				} ) || [],
			[]
		);
	}

	function HoursField( { value, onChange } ) {
		const days = Array.from( { length: 7 }, ( _, i ) => ( value && value[ i ] ) || { open: '', close: '', closed: false } );
		const update = ( i, patch ) => onChange( days.map( ( d, j ) => ( j === i ? { ...d, ...patch } : d ) ) );

		return el(
			'div',
			{ className: 'cc-hours' },
			days.map( ( day, i ) =>
				el(
					'fieldset',
					{ key: i, style: { border: 0, padding: 0, margin: '0 0 12px' } },
					el( 'legend', { style: { fontWeight: 600, marginBottom: 4 } }, config.days[ i ] ),
					el( ToggleControl, {
						...common,
						label: __( 'Closed', 'cobbleandcandle-core' ),
						checked: !! day.closed,
						onChange: ( closed ) => update( i, { closed } ),
					} ),
					! day.closed &&
						el(
							Flex,
							{ gap: 2 },
							el( FlexBlock, null, el( TextControl, { ...common, type: 'time', label: __( 'Opens', 'cobbleandcandle-core' ), value: day.open, onChange: ( open ) => update( i, { open } ) } ) ),
							el( FlexBlock, null, el( TextControl, { ...common, type: 'time', label: __( 'Closes', 'cobbleandcandle-core' ), value: day.close, onChange: ( close ) => update( i, { close } ) } ) )
						)
				)
			)
		);
	}

	function ListField( { field, value, onChange } ) {
		const rows = Array.isArray( value ) ? value : [];
		const blank = () => Object.fromEntries( Object.entries( field.fields ).map( ( [ k, f ] ) => [ k, f.type === 'boolean' ? false : '' ] ) );
		const update = ( i, key, v ) => onChange( rows.map( ( r, j ) => ( j === i ? { ...r, [ key ]: v } : r ) ) );

		return el(
			BaseControl,
			{ label: field.label, __nextHasNoMarginBottom: true },
			rows.map( ( row, i ) =>
				el(
					'div',
					{ key: i, style: { borderLeft: '2px solid #ddd', paddingLeft: 10, margin: '8px 0 14px' } },
					Object.entries( field.fields ).map( ( [ key, sub ] ) =>
						el( Control, { key, field: sub, value: row[ key ], onChange: ( v ) => update( i, key, v ) } )
					),
					el( Button, { variant: 'link', isDestructive: true, onClick: () => onChange( rows.filter( ( _, j ) => j !== i ) ) }, __( 'Remove', 'cobbleandcandle-core' ) )
				)
			),
			el( Button, { variant: 'secondary', onClick: () => onChange( [ ...rows, blank() ] ) }, field.add || __( 'Add', 'cobbleandcandle-core' ) )
		);
	}

	function LocationsField( { field, value, onChange, multiple } ) {
		const locations = useLocations();
		if ( ! multiple ) {
			return el( SelectControl, {
				...common,
				label: field.label,
				value: String( value || '' ),
				options: [ { label: __( '— Choose —', 'cobbleandcandle-core' ), value: '' } ].concat(
					locations.map( ( l ) => ( { label: l.title.rendered || l.title.raw, value: String( l.id ) } ) )
				),
				onChange: ( v ) => onChange( parseInt( v, 10 ) || 0 ),
			} );
		}
		const ids = Array.isArray( value ) ? value : [];
		return el(
			BaseControl,
			{ label: field.label, __nextHasNoMarginBottom: true },
			locations.map( ( l ) =>
				el( CheckboxControl, {
					key: l.id,
					__nextHasNoMarginBottom: true,
					label: l.title.rendered || l.title.raw,
					checked: ids.includes( l.id ),
					onChange: ( on ) => onChange( on ? [ ...ids, l.id ] : ids.filter( ( id ) => id !== l.id ) ),
				} )
			)
		);
	}

	function Control( { field, value, onChange } ) {
		switch ( field.type ) {
			case 'textarea':
				return el( TextareaControl, { __nextHasNoMarginBottom: true, label: field.label, value: value || '', onChange } );
			case 'boolean':
				return el( ToggleControl, { ...common, label: field.label, checked: !! value, onChange } );
			case 'select':
				return el( SelectControl, {
					...common,
					label: field.label,
					value: value || field.default || '',
					options: Object.entries( field.options ).map( ( [ v, label ] ) => ( { value: v, label } ) ),
					onChange,
				} );
			case 'checkboxes': {
				const list = Array.isArray( value ) ? value : [];
				return el(
					BaseControl,
					{ label: field.label, __nextHasNoMarginBottom: true },
					Object.entries( field.options ).map( ( [ v, label ] ) =>
						el( CheckboxControl, {
							key: v,
							__nextHasNoMarginBottom: true,
							label,
							checked: list.includes( v ),
							onChange: ( on ) => onChange( on ? [ ...list, v ] : list.filter( ( x ) => x !== v ) ),
						} )
					)
				);
			}
			case 'hours':
				return el( HoursField, { value, onChange } );
			case 'list':
				return el( ListField, { field, value, onChange } );
			case 'location':
				return el( LocationsField, { field, value, onChange, multiple: false } );
			case 'locations':
				return el( LocationsField, { field, value, onChange, multiple: true } );
			case 'number':
				return el( TextControl, { ...common, type: 'number', label: field.label, value: value ?? '', onChange: ( v ) => onChange( v === '' ? 0 : Number( v ) ) } );
			case 'date':
			case 'time':
			case 'datetime':
				return el( TextControl, { ...common, type: field.type === 'datetime' ? 'datetime-local' : field.type, label: field.label, value: value || '', onChange } );
			default:
				return el( TextControl, { ...common, type: [ 'url', 'email', 'tel' ].includes( field.type ) ? field.type : 'text', label: field.label, value: value || '', onChange } );
		}
	}

	function Panels() {
		const [ meta, setMeta ] = useEntityProp( 'postType', config.postType, 'meta' );
		if ( ! meta ) {
			return null;
		}
		return el(
			Fragment,
			null,
			config.panels.map( ( panel, i ) =>
				el(
					PluginDocumentSettingPanel,
					{ key: i, name: 'cc-panel-' + i, title: panel.title, className: 'cc-fields-panel' },
					Object.entries( panel.fields ).map( ( [ key, field ] ) =>
						el( 'div', { key, style: { marginBottom: 16 } }, el( Control, { field, value: meta[ key ], onChange: ( v ) => setMeta( { ...meta, [ key ]: v } ) } ) )
					)
				)
			)
		);
	}

	registerPlugin( 'cobbleandcandle-fields', { render: Panels } );
} )( window.wp );
