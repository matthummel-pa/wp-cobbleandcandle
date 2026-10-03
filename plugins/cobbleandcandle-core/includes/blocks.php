<?php
/**
 * Blocks: every blocks/<name>/block.json in this plugin is registered here (block registration is
 * plugin territory). Each is a dynamic block; its markup comes from the active theme through the
 * `cobble_render_block` filter (Cobble & Candle renders them with Blade views). Without a theme that
 * renders them, a block prints nothing on the front end and a short note in the editor.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block folders: name => absolute path.
 *
 * @return array<string, string>
 */
function cobble_block_dirs() {
	$dirs = array();
	foreach ( glob( COBBLE_CORE_DIR . 'blocks/*/block.json' ) ?: array() as $file ) {
		$dirs[ basename( dirname( $file ) ) ] = dirname( $file );
	}
	return $dirs;
}

/**
 * Register the blocks.
 */
function cobble_register_blocks() {
	foreach ( cobble_block_dirs() as $name => $dir ) {
		register_block_type(
			$dir,
			array(
				'render_callback' => static function ( $attributes, $content, $block ) use ( $name ) {
					return cobble_render_block( $name, (array) $attributes, (string) $content, $block );
				},
			)
		);
	}
}
add_action( 'init', 'cobble_register_blocks' );

/**
 * Markup for one block, from the theme.
 *
 * @param string   $name       Block folder name (e.g. hero).
 * @param array    $attributes Attributes.
 * @param string   $content    Inner blocks' HTML.
 * @param WP_Block $block      Block instance.
 * @return string
 */
function cobble_render_block( $name, array $attributes, $content, $block ) {
	/**
	 * Render a Cobble & Candle block. Return HTML; return null to fall back to nothing.
	 *
	 * @param string|null $html       Markup.
	 * @param string      $name       Block folder name.
	 * @param array       $attributes Block attributes.
	 * @param string      $content    Inner blocks' HTML.
	 * @param WP_Block    $block      Block instance.
	 */
	$html = apply_filters( 'cobble_render_block', null, $name, $attributes, $content, $block );
	if ( null !== $html ) {
		return (string) $html;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return '<p>' . esc_html__( 'This block needs the Cobble & Candle theme to display.', 'cobbleandcandle-core' ) . '</p>';
	}
	return '';
}

/**
 * “Cobble & Candle” block category in the inserter.
 *
 * @param array<int, array<string, mixed>> $categories Categories.
 * @return array<int, array<string, mixed>>
 */
function cobble_block_category( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'cobbleandcandle',
			'title' => __( 'Cobble & Candle', 'cobbleandcandle-core' ),
			'icon'  => null,
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', 'cobble_block_category' );

/**
 * Editor script: settings panels, server-rendered previews and starter content for each block.
 */
function cobble_enqueue_block_editor() {
	wp_enqueue_script(
		'cobble-blocks-editor',
		COBBLE_CORE_URL . 'assets/blocks-editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n', 'wp-data' ),
		COBBLE_CORE_VERSION,
		true
	);
	$meta = array();
	foreach ( cobble_block_dirs() as $dir ) {
		$json = json_decode( (string) file_get_contents( $dir . '/block.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- plugin file.
		if ( is_array( $json ) ) {
			$meta[] = $json;
		}
	}
	wp_add_inline_script( 'cobble-blocks-editor', 'window.cobbleBlocks = ' . wp_json_encode( $meta ) . ';', 'before' );
	wp_set_script_translations( 'cobble-blocks-editor', 'cobbleandcandle-core', COBBLE_CORE_DIR . 'languages' );
}
add_action( 'enqueue_block_editor_assets', 'cobble_enqueue_block_editor' );
