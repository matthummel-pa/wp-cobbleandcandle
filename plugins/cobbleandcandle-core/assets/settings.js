/**
 * Cobble & Candle Core: logo picker on Settings → Restaurant (WordPress media library).
 */
( function ( $ ) {
	$( '.cobble-image-field' ).each( function () {
		const field = $( this );
		const input = field.find( 'input[type=hidden]' );
		const preview = field.find( '.cobble-image-preview' );
		const remove = field.find( '.cobble-image-remove' );
		let frame;

		field.find( '.cobble-image-choose' ).on( 'click', function () {
			frame = frame || wp.media( { library: { type: 'image' }, multiple: false } );
			frame.off( 'select' ).on( 'select', function () {
				const image = frame.state().get( 'selection' ).first().toJSON();
				const url = ( image.sizes && image.sizes.medium ? image.sizes.medium : image ).url;
				input.val( image.id );
				preview.empty().append( $( '<img>', { src: url, alt: '', css: { maxHeight: '80px', width: 'auto' } } ) );
				remove.prop( 'hidden', false );
			} );
			frame.open();
		} );

		remove.on( 'click', function () {
			input.val( '' );
			preview.empty();
			remove.prop( 'hidden', true );
		} );
	} );
} )( jQuery );
