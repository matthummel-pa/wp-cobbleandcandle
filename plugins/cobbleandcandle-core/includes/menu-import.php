<?php
/**
 * Menu CSV import and export (Food & drink → Import / export).
 *
 * Upload → preview (what will be created or updated, and any row problems) → confirm. Rows match
 * an existing dish by name within the same menu and section; nothing is ever deleted. Export writes
 * the same format, so owners can edit a whole menu in a spreadsheet and bring it back.
 *
 * Columns (header row, any order, case-insensitive): menu, section, name, description, price,
 * sizes ("Glass: $12 | Bottle: $48"), diet ("v, gf" or "vegetarian, gluten-free"), flag,
 * chef_pick (yes/no), menu_intro.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * CSV columns in export order.
 *
 * @return array<int, string>
 */
function cc_menu_csv_columns() {
	return array( 'menu', 'section', 'name', 'description', 'price', 'sizes', 'diet', 'flag', 'chef_pick', 'menu_intro' );
}

/**
 * Diet words accepted in a CSV: word => stored key.
 *
 * @return array<string, string>
 */
function cc_menu_csv_diets() {
	return array(
		'v'            => 'v',
		'veg'          => 'v',
		'vegetarian'   => 'v',
		'vg'           => 'vg',
		'vegan'        => 'vg',
		'gf'           => 'gf',
		'gluten-free'  => 'gf',
		'gluten free'  => 'gf',
		'spicy'        => 'spicy',
		'hot'          => 'spicy',
	);
}

/**
 * Add the Import / export screen under Food & drink.
 */
function cc_menu_import_page() {
	add_submenu_page(
		'edit.php?post_type=cc_menu_item',
		__( 'Import / export menus', 'cobbleandcandle-core' ),
		__( 'Import / export', 'cobbleandcandle-core' ),
		'edit_others_posts',
		'cc-menu-import',
		'cc_render_menu_import_page'
	);
}
add_action( 'admin_menu', 'cc_menu_import_page' );

/**
 * Transient key for the current user's pending import.
 *
 * @return string
 */
function cc_menu_import_key() {
	return 'cc_menu_import_' . get_current_user_id();
}

/**
 * Parse an uploaded CSV file into clean rows.
 *
 * @param string $path File path.
 * @return array{rows: array<int, array<string, mixed>>, errors: array<int, string>}
 */
function cc_parse_menu_csv( $path ) {
	$rows   = array();
	$errors = array();
	$handle = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- reading the uploaded temp file.
	if ( ! $handle ) {
		return array(
			'rows'   => $rows,
			'errors' => array( __( 'The file could not be read.', 'cobbleandcandle-core' ) ),
		);
	}
	$header = fgetcsv( $handle, 0, ',', '"', '' );
	if ( ! $header ) {
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return array(
			'rows'   => $rows,
			'errors' => array( __( 'The file is empty.', 'cobbleandcandle-core' ) ),
		);
	}
	$header    = array_map(
		static fn( $cell ) => str_replace( ' ', '_', strtolower( trim( (string) preg_replace( '/^\xEF\xBB\xBF/', '', (string) $cell ) ) ) ),
		$header
	);
	$missing   = array_diff( array( 'menu', 'section', 'name' ), $header );
	if ( $missing ) {
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return array(
			'rows'   => $rows,
			/* translators: %s: column names */
			'errors' => array( sprintf( __( 'Missing columns: %s. The first row must name the columns.', 'cobbleandcandle-core' ), implode( ', ', $missing ) ) ),
		);
	}
	$diets   = cc_menu_csv_diets();
	$line    = 1;
	$present = array_values( array_intersect( cc_menu_csv_columns(), $header ) );
	$note    = static function ( $message ) use ( &$errors ) {
		if ( count( $errors ) < 100 ) { // A broken file must not build a huge error list.
			$errors[] = $message;
		}
	};
	while ( false !== ( $cells = fgetcsv( $handle, 0, ',', '"', '' ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		++$line;
		if ( count( $rows ) >= 2000 || $line > 5000 ) {
			$errors[] = __( 'Only the first 2,000 rows are imported. Split larger files.', 'cobbleandcandle-core' );
			break;
		}
		// Excel on Windows saves "CSV" as Windows-1252: convert, or accents and £/€ would be dropped.
		$cells = array_map(
			static fn( $c ) => ( null === $c || mb_check_encoding( (string) $c, 'UTF-8' ) ) ? $c : mb_convert_encoding( (string) $c, 'UTF-8', 'Windows-1252' ),
			$cells
		);
		if ( array( null ) === $cells || '' === trim( implode( '', array_map( 'strval', $cells ) ) ) ) {
			continue;
		}
		$cell = static function ( $name ) use ( $header, $cells ) {
			$i = array_search( $name, $header, true );
			$v = false === $i ? '' : trim( (string) ( $cells[ $i ] ?? '' ) );
			return preg_match( "/^'[=+\\-@\t\r]/", $v ) ? substr( $v, 1 ) : $v; // Undo the export's spreadsheet guard.
		};
		$row  = array(
			'line'        => $line,
			'present'     => $present,
			'menu'        => sanitize_text_field( $cell( 'menu' ) ),
			'section'     => sanitize_text_field( $cell( 'section' ) ),
			'name'        => sanitize_text_field( $cell( 'name' ) ),
			'description' => sanitize_textarea_field( $cell( 'description' ) ),
			'price'       => sanitize_text_field( $cell( 'price' ) ),
			'flag'        => sanitize_text_field( $cell( 'flag' ) ),
			'chef_pick'   => in_array( strtolower( $cell( 'chef_pick' ) ), array( '1', 'yes', 'y', 'true', 'x' ), true ),
			'menu_intro'  => sanitize_text_field( $cell( 'menu_intro' ) ),
			'variants'    => array(),
			'diet'        => array(),
		);
		if ( '' === $row['menu'] || '' === $row['section'] || '' === $row['name'] ) {
			/* translators: %d: line number */
			$note( sprintf( __( 'Line %d skipped: menu, section and name are required.', 'cobbleandcandle-core' ), $line ) );
			continue;
		}
		foreach ( array_filter( array_map( 'trim', explode( '|', $cell( 'sizes' ) ) ) ) as $size ) {
			$parts             = array_map( 'trim', explode( ':', $size, 2 ) );
			$row['variants'][] = array(
				'label' => sanitize_text_field( $parts[0] ),
				'price' => sanitize_text_field( $parts[1] ?? '' ),
			);
		}
		foreach ( array_filter( array_map( 'trim', explode( ',', strtolower( $cell( 'diet' ) ) ) ) ) as $word ) {
			if ( isset( $diets[ $word ] ) ) {
				$row['diet'][] = $diets[ $word ];
			} else {
				/* translators: 1: line number, 2: unknown word */
				$note( sprintf( __( 'Line %1$d: unknown diet "%2$s" ignored (use v, vg, gf or spicy).', 'cobbleandcandle-core' ), $line, $word ) );
			}
		}
		$row['diet'] = array_values( array_unique( $row['diet'] ) );
		$rows[]      = $row;
	}
	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	return array(
		'rows'   => $rows,
		'errors' => $errors,
	);
}

/**
 * The dish a row would update, if any: same name in the same menu and section.
 *
 * @param array<string, mixed> $row Row.
 * @return int Post ID or 0.
 */
function cc_menu_csv_match( array $row ) {
	$menu    = cc_menu_csv_find_term( 'cc_menu', $row['menu'], sanitize_title( $row['menu'] ) );
	$section = $menu ? cc_menu_csv_find_term( 'cc_menu_section', $row['section'], sanitize_title( $row['menu'] . '-' . $row['section'] ) ) : null;
	if ( ! $menu || ! $section ) {
		return 0;
	}
	// Titles may be stored with & as &amp; (users without unfiltered_html): try both spellings.
	foreach ( array_unique( array( $row['name'], esc_html( $row['name'] ) ) ) as $title ) {
		$found = cc_menu_csv_find_dish( $title, (int) $menu->term_id, (int) $section->term_id );
		if ( $found ) {
			return $found;
		}
	}
	return 0;
}

/**
 * A menu or section term: by the importer's slug first, then by name (sections created in the
 * WordPress UI have a slug from the name alone).
 *
 * @param string $taxonomy Taxonomy.
 * @param string $name     Name.
 * @param string $slug     Importer slug.
 * @return WP_Term|null
 */
function cc_menu_csv_find_term( $taxonomy, $name, $slug ) {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( ! $term ) {
		$term = get_term_by( 'name', $name, $taxonomy );
	}
	if ( ! $term ) {
		$term = get_term_by( 'name', esc_html( $name ), $taxonomy );
	}
	return $term instanceof WP_Term ? $term : null;
}

/**
 * A dish by exact title in a menu and section.
 *
 * @param string $title      Title as stored.
 * @param int    $menu_id    Menu term.
 * @param int    $section_id Section term.
 * @return int Post ID or 0.
 */
function cc_menu_csv_find_dish( $title, $menu_id, $section_id ) {
	$found = get_posts(
		array(
			'post_type'      => 'cc_menu_item',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'title'          => $title,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- one lookup per imported row.
				'relation' => 'AND',
				array(
					'taxonomy' => 'cc_menu',
					'terms'    => $menu_id,
				),
				array(
					'taxonomy' => 'cc_menu_section',
					'terms'    => $section_id,
				),
			),
		)
	);
	return $found ? (int) $found[0] : 0;
}

/**
 * A menu or section term, created when missing (new terms go after existing ones).
 *
 * @param string $taxonomy Taxonomy.
 * @param string $name     Name.
 * @param string $slug     Slug.
 * @return int Term ID or 0.
 */
function cc_menu_csv_term( $taxonomy, $name, $slug ) {
	$term = cc_menu_csv_find_term( $taxonomy, $name, $slug );
	if ( $term ) {
		return (int) $term->term_id;
	}
	$created = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
	if ( is_wp_error( $created ) ) {
		return 0;
	}
	$count = (int) wp_count_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		)
	);
	update_term_meta( (int) $created['term_id'], 'cc_order', $count );
	return (int) $created['term_id'];
}

/**
 * Upload step: parse and keep a preview for this user.
 */
function cc_handle_menu_import_upload() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'You are not allowed to import menus.', 'cobbleandcandle-core' ), 403 );
	}
	check_admin_referer( 'cc_menu_import_upload' );
	$back = admin_url( 'edit.php?post_type=cc_menu_item&page=cc-menu-import' );
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- checked below; only tmp_name, size, error and name are used.
	$file = isset( $_FILES['cc_menu_csv'] ) && is_array( $_FILES['cc_menu_csv'] ) ? $_FILES['cc_menu_csv'] : array();
	$size = (int) ( $file['size'] ?? 0 );
	$name = sanitize_file_name( (string) ( $file['name'] ?? '' ) );
	$tmp  = (string) ( $file['tmp_name'] ?? '' );
	if ( empty( $file ) || UPLOAD_ERR_OK !== (int) ( $file['error'] ?? -1 ) || ! is_uploaded_file( $tmp )
		|| 'csv' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) || $size > 2 * MB_IN_BYTES ) {
		wp_safe_redirect( add_query_arg( 'import', 'badfile', $back ) );
		exit;
	}
	$parsed = cc_parse_menu_csv( $tmp );
	foreach ( $parsed['rows'] as &$row ) {
		$row['match'] = cc_menu_csv_match( $row );
	}
	unset( $row );
	set_transient( cc_menu_import_key(), $parsed + array( 'file' => $name ), 30 * MINUTE_IN_SECONDS );
	wp_safe_redirect( add_query_arg( 'import', 'preview', $back ) );
	exit;
}
add_action( 'admin_post_cc_menu_import_upload', 'cc_handle_menu_import_upload' );

/**
 * Confirm step: create or update the dishes from the preview.
 */
function cc_handle_menu_import_run() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'You are not allowed to import menus.', 'cobbleandcandle-core' ), 403 );
	}
	check_admin_referer( 'cc_menu_import_run' );
	$back    = admin_url( 'edit.php?post_type=cc_menu_item&page=cc-menu-import' );
	$pending = get_transient( cc_menu_import_key() );
	delete_transient( cc_menu_import_key() );
	if ( ! is_array( $pending ) || empty( $pending['rows'] ) ) {
		wp_safe_redirect( add_query_arg( 'import', 'expired', $back ) );
		exit;
	}
	$skip    = ! empty( $_POST['cc_skip_existing'] );
	$counts  = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
	);
	$order   = array();
	$intros  = array();
	wp_defer_term_counting( true );
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 300 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- large menus: up to 2,000 dishes.
	}
	foreach ( $pending['rows'] as $row ) {
		$menu_id    = cc_menu_csv_term( 'cc_menu', $row['menu'], sanitize_title( $row['menu'] ) );
		$section_id = cc_menu_csv_term( 'cc_menu_section', $row['section'], sanitize_title( $row['menu'] . '-' . $row['section'] ) );
		if ( ! $menu_id || ! $section_id ) {
			++$counts['skipped'];
			continue;
		}
		if ( '' !== $row['menu_intro'] && ! isset( $intros[ $menu_id ] ) ) {
			$intros[ $menu_id ] = true;
			update_term_meta( $menu_id, 'cc_intro', $row['menu_intro'] );
		}
		$key      = $menu_id . '-' . $section_id;
		$existing = cc_menu_csv_match( $row ); // Re-check: the preview may be 30 minutes old.
		if ( $existing && $skip ) {
			++$counts['skipped'];
			continue;
		}
		$meta    = array(
			'price'     => array( 'cc_price', $row['price'] ),
			'sizes'     => array( 'cc_variants', $row['variants'] ),
			'diet'      => array( 'cc_diet', $row['diet'] ),
			'flag'      => array( 'cc_flag', $row['flag'] ),
			'chef_pick' => array( 'cc_chef_pick', $row['chef_pick'] ),
		);
		$present = (array) ( $row['present'] ?? cc_menu_csv_columns() );
		$postarr = array(
			'post_type'  => 'cc_menu_item',
			'post_title' => $row['name'],
			'meta_input' => array(),
		);
		if ( ! $existing ) {
			// New dishes go after the section's existing dishes, in file order; existing dishes keep their place.
			if ( ! isset( $order[ $key ] ) ) {
				$last          = get_posts(
					array(
						'post_type'      => 'cc_menu_item',
						'post_status'    => 'any',
						'posts_per_page' => 1,
						'orderby'        => 'menu_order',
						'order'          => 'DESC',
						'no_found_rows'  => true,
						'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- once per section per import.
							'relation' => 'AND',
							array(
								'taxonomy' => 'cc_menu',
								'terms'    => $menu_id,
							),
							array(
								'taxonomy' => 'cc_menu_section',
								'terms'    => $section_id,
							),
						),
					)
				);
				$order[ $key ] = $last ? (int) $last[0]->menu_order : -1;
			}
			$order[ $key ]          = $order[ $key ] + 1;
			$postarr['menu_order'] = $order[ $key ];
		}
		foreach ( $meta as $column => list( $meta_key, $value ) ) {
			// Updating: a column missing from the file leaves that field alone (nothing is wiped).
			if ( ! $existing || in_array( $column, $present, true ) ) {
				$postarr['meta_input'][ $meta_key ] = $value;
			}
		}
		if ( ! $existing || in_array( 'description', $present, true ) ) {
			$postarr['post_excerpt'] = $row['description'];
		}
		if ( $existing ) {
			$postarr['ID'] = $existing; // Status untouched: an unpublished dish stays unpublished.
		} else {
			$postarr['post_status'] = 'publish';
		}
		// wp_update_post() merges with the stored dish, so fields not in the file stay as they are.
		$id = $existing ? wp_update_post( wp_slash( $postarr ), true ) : wp_insert_post( wp_slash( $postarr ), true );
		if ( is_wp_error( $id ) ) {
			++$counts['skipped'];
			continue;
		}
		wp_set_object_terms( $id, $menu_id, 'cc_menu', true );
		wp_set_object_terms( $id, $section_id, 'cc_menu_section', true );
		++$counts[ $existing ? 'updated' : 'created' ];
	}
	wp_defer_term_counting( false );
	wp_safe_redirect( add_query_arg( array_merge( array( 'import' => 'done' ), $counts ), $back ) );
	exit;
}
add_action( 'admin_post_cc_menu_import_run', 'cc_handle_menu_import_run' );

/**
 * A spreadsheet-safe CSV cell: values starting with = + - @ are prefixed so Excel and Sheets
 * never run them as formulas.
 *
 * @param string $value Cell.
 * @return string
 */
function cc_csv_cell( $value ) {
	$value = (string) $value;
	return '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ? "'" . $value : $value;
}

/**
 * Export every menu (or a sample when there are none) as CSV.
 */
function cc_handle_menu_export() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'You are not allowed to export menus.', 'cobbleandcandle-core' ), 403 );
	}
	check_admin_referer( 'cc_menu_export' );
	$sample = ! empty( $_GET['sample'] );
	$lines  = array();
	if ( $sample ) {
		$lines[] = array( 'Dinner', 'Starters', 'Oyster, cucumber & dill', 'Buttermilk, caviar', '$9', '', 'gf', '', 'no', 'Served from 5:30pm' );
		$lines[] = array( 'Dinner', 'Mains', 'Dry-aged duck, two ways', 'Cherry, chicory, duck-fat potatoes', '$42', '', '', 'Signature', 'yes', '' );
		$lines[] = array( 'Wine', 'By the glass', 'House red', 'Ask for today’s pour', '', 'Glass: $12 | Bottle: $48', 'vg', '', 'no', '' );
	} else {
		$diet_words = array_flip( array( 'v', 'vg', 'gf', 'spicy' ) );
		foreach ( cc_get_menus() as $menu ) {
			$intro = (string) get_term_meta( $menu['term']->term_id, 'cc_intro', true );
			foreach ( $menu['sections'] as $section ) {
				$section_name = $section['term']->name;
				foreach ( $section['items'] as $item ) {
					$post     = get_post( $item['id'] );
					$variants = (array) get_post_meta( $item['id'], 'cc_variants', true );
					$lines[]  = array(
						html_entity_decode( $menu['term']->name, ENT_QUOTES, 'UTF-8' ),
						html_entity_decode( $section_name, ENT_QUOTES, 'UTF-8' ),
						$post ? html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ) : '', // Raw title (not texturized) so re-import matches.
						$post ? $post->post_excerpt : '',
						(string) get_post_meta( $item['id'], 'cc_price', true ),
						implode(
							' | ',
							array_map(
								static fn( $v ) => trim( ( $v['label'] ?? '' ) . ': ' . ( $v['price'] ?? '' ), ': ' ),
								array_filter( $variants, 'is_array' )
							)
						),
						implode( ', ', array_intersect( (array) get_post_meta( $item['id'], 'cc_diet', true ), array_keys( $diet_words ) ) ),
						(string) get_post_meta( $item['id'], 'cc_flag', true ),
						get_post_meta( $item['id'], 'cc_chef_pick', true ) ? 'yes' : 'no',
						$intro,
					);
					$intro    = ''; // Once per menu is enough.
				}
			}
		}
	}
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . ( $sample ? 'menu-sample' : 'menus-' . wp_date( 'Y-m-d' ) ) . '.csv"' );
	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- streaming the download.
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- UTF-8 BOM so Excel reads accents and £/€.
	fputcsv( $out, cc_menu_csv_columns(), ',', '"', '' );
	foreach ( $lines as $line ) {
		fputcsv( $out, array_map( 'cc_csv_cell', $line ), ',', '"', '' );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_cc_menu_export', 'cc_handle_menu_export' );

/**
 * The Import / export screen.
 */
function cc_render_menu_import_page() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only status flags after redirects.
	$status = isset( $_GET['import'] ) ? sanitize_key( wp_unslash( $_GET['import'] ) ) : '';
	$counts = array_map(
		static fn( $key ) => isset( $_GET[ $key ] ) ? absint( $_GET[ $key ] ) : 0,
		array(
			'created' => 'created',
			'updated' => 'updated',
			'skipped' => 'skipped',
		)
	);
	// phpcs:enable
	$pending = 'preview' === $status ? get_transient( cc_menu_import_key() ) : false;
	$export  = wp_nonce_url( admin_url( 'admin-post.php?action=cc_menu_export' ), 'cc_menu_export' );
	?>
	<div class="wrap cc-admin">
		<?php
		cc_admin_header(
			__( 'Import / export menus', 'cobbleandcandle-core' ),
			__( 'Add or update a whole menu from a spreadsheet. Dishes are matched by name within the same menu and section; nothing is deleted.', 'cobbleandcandle-core' ),
			admin_url( 'edit.php?post_type=cc_menu_item&page=cc-menu-import' )
		);
		?>

		<?php if ( 'done' === $status ) : ?>
			<div class="notice notice-success"><p>
				<?php
				/* translators: 1: created, 2: updated, 3: skipped */
				echo esc_html( sprintf( __( 'Import finished: %1$d dishes added, %2$d updated, %3$d skipped.', 'cobbleandcandle-core' ), $counts['created'], $counts['updated'], $counts['skipped'] ) );
				?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=cc_menu_item' ) ); ?>"><?php esc_html_e( 'View dishes', 'cobbleandcandle-core' ); ?></a>
			</p></div>
		<?php elseif ( 'badfile' === $status ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'Please choose a .csv file under 2 MB.', 'cobbleandcandle-core' ); ?></p></div>
		<?php elseif ( 'expired' === $status ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'That preview expired. Please upload the file again.', 'cobbleandcandle-core' ); ?></p></div>
		<?php endif; ?>

		<?php if ( is_array( $pending ) ) : ?>
			<?php
			$rows    = (array) $pending['rows'];
			$updates = count( array_filter( array_column( $rows, 'match' ) ) );
			?>
			<ol class="cc-steps" aria-label="<?php esc_attr_e( 'Import steps', 'cobbleandcandle-core' ); ?>">
				<li class="is-done"><?php esc_html_e( '1 · Upload', 'cobbleandcandle-core' ); ?></li>
				<li aria-current="step"><?php esc_html_e( '2 · Check', 'cobbleandcandle-core' ); ?></li>
				<li><?php esc_html_e( '3 · Import', 'cobbleandcandle-core' ); ?></li>
			</ol>
			<section class="cc-card">
			<h2><?php esc_html_e( 'Check before importing', 'cobbleandcandle-core' ); ?></h2>
			<p>
				<?php
				/* translators: 1: file name, 2: new dishes, 3: dishes to update */
				echo esc_html( sprintf( __( '%1$s: %2$d new dishes, %3$d that match a dish already on that menu and section.', 'cobbleandcandle-core' ), $pending['file'], count( $rows ) - $updates, $updates ) );
				?>
			</p>
			<?php if ( ! empty( $pending['errors'] ) ) : ?>
				<div class="notice notice-warning inline"><ul style="list-style:disc;margin-left:20px">
					<?php foreach ( array_slice( (array) $pending['errors'], 0, 30 ) as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul></div>
			<?php endif; ?>
			<table class="widefat striped" style="max-width:1100px">
				<thead><tr>
					<th><?php esc_html_e( 'Menu', 'cobbleandcandle-core' ); ?></th>
					<th><?php esc_html_e( 'Section', 'cobbleandcandle-core' ); ?></th>
					<th><?php esc_html_e( 'Dish', 'cobbleandcandle-core' ); ?></th>
					<th><?php esc_html_e( 'Price', 'cobbleandcandle-core' ); ?></th>
					<th><?php esc_html_e( 'Will', 'cobbleandcandle-core' ); ?></th>
				</tr></thead>
				<tbody>
					<?php foreach ( array_slice( $rows, 0, 50 ) as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['menu'] ); ?></td>
							<td><?php echo esc_html( $row['section'] ); ?></td>
							<td><?php echo esc_html( $row['name'] ); ?></td>
							<td><?php echo esc_html( '' !== $row['price'] ? $row['price'] : implode( ' / ', array_column( $row['variants'], 'price' ) ) ); ?></td>
							<td><?php echo $row['match'] ? esc_html__( 'Update', 'cobbleandcandle-core' ) : esc_html__( 'Add', 'cobbleandcandle-core' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( count( $rows ) > 50 ) : ?>
				<p class="description">
					<?php
					/* translators: %d: number of rows not shown */
					echo esc_html( sprintf( __( '…and %d more rows.', 'cobbleandcandle-core' ), count( $rows ) - 50 ) );
					?>
				</p>
			<?php endif; ?>
			<?php if ( $rows ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px">
					<input type="hidden" name="action" value="cc_menu_import_run">
					<?php wp_nonce_field( 'cc_menu_import_run' ); ?>
					<p><label><input type="checkbox" name="cc_skip_existing" value="1"> <?php esc_html_e( 'Leave existing dishes as they are (only add new ones)', 'cobbleandcandle-core' ); ?></label></p>
					<?php submit_button( __( 'Import these dishes', 'cobbleandcandle-core' ), 'primary', 'submit', false ); ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=cc_menu_item&page=cc-menu-import' ) ); ?>"><?php esc_html_e( 'Cancel', 'cobbleandcandle-core' ); ?></a>
				</form>
			<?php endif; ?>
			</section>
		<?php else : ?>
			<div class="cc-grid">
			<section class="cc-card">
			<h2><?php esc_html_e( 'Import', 'cobbleandcandle-core' ); ?></h2>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cc_menu_import_upload">
				<?php wp_nonce_field( 'cc_menu_import_upload' ); ?>
				<p><label for="cc-menu-csv"><?php esc_html_e( 'CSV file', 'cobbleandcandle-core' ); ?></label><br>
				<input type="file" id="cc-menu-csv" name="cc_menu_csv" accept=".csv,text/csv" required></p>
				<?php submit_button( __( 'Upload and preview', 'cobbleandcandle-core' ), 'primary', 'submit', false ); ?>
			</form>
			<p class="description">
				<?php esc_html_e( 'Columns: menu, section, name, description, price, sizes (Glass: $12 | Bottle: $48), diet (v, vg, gf, spicy), flag, chef_pick (yes/no), menu_intro. Only menu, section and name are required.', 'cobbleandcandle-core' ); ?>
				<a href="<?php echo esc_url( add_query_arg( 'sample', '1', $export ) ); ?>"><?php esc_html_e( 'Download a sample file', 'cobbleandcandle-core' ); ?></a>
			</p>
			</section>
			<section class="cc-card">
			<h2><?php esc_html_e( 'Export', 'cobbleandcandle-core' ); ?></h2>
			<p><?php esc_html_e( 'Download every menu in the same format, edit it in Excel, Numbers or Google Sheets, and import it back.', 'cobbleandcandle-core' ); ?></p>
			<p><a class="button button-primary" href="<?php echo esc_url( $export ); ?>"><?php esc_html_e( 'Export menus (CSV)', 'cobbleandcandle-core' ); ?></a></p>
			</section>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
