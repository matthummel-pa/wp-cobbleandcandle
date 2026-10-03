<?php
/**
 * Restaurant settings (Settings → Restaurant): logo, brand line, cuisine, price range, currency and
 * social profiles. Stored in the `cobbleandcandle_brand` option so they survive a theme switch.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings fields: key => [label, type, help].
 *
 * @return array<string, array{0: string, 1: string, 2: string}>
 */
function cobble_settings_fields() {
	return array(
		'logo_id'     => array( __( 'Logo', 'cobbleandcandle-core' ), 'image', __( 'Shown in the header and footer instead of the monogram crest. SVG or PNG, about 120 px tall.', 'cobbleandcandle-core' ) ),
		'tagline'     => array( __( 'Brand line', 'cobbleandcandle-core' ), 'text', __( 'Short line under the name, e.g. “Dining rooms” or “Tavern & kitchen”.', 'cobbleandcandle-core' ) ),
		'est'         => array( __( 'Established (year)', 'cobbleandcandle-core' ), 'text', __( 'Shown as “Est. 1888” and around the crest. Leave empty to hide.', 'cobbleandcandle-core' ) ),
		'cuisine'     => array( __( 'Cuisine', 'cobbleandcandle-core' ), 'text', __( 'For search engines, e.g. “Modern British, Seafood”.', 'cobbleandcandle-core' ) ),
		'price_range' => array( __( 'Price range', 'cobbleandcandle-core' ), 'text', __( 'For search engines, e.g. “$$$”.', 'cobbleandcandle-core' ) ),
		'currency'    => array( __( 'Currency (ISO code)', 'cobbleandcandle-core' ), 'text', __( 'Used for menu and ticket prices in search results, e.g. USD, GBP, EUR.', 'cobbleandcandle-core' ) ),
		'instagram'   => array( 'Instagram', 'url', '' ),
		'facebook'    => array( 'Facebook', 'url', '' ),
		'tiktok'      => array( 'TikTok', 'url', '' ),
		'x'           => array( 'X (Twitter)', 'url', '' ),
		'youtube'     => array( 'YouTube', 'url', '' ),
		'tripadvisor' => array( 'Tripadvisor', 'url', '' ),
		'yelp'        => array( 'Yelp', 'url', '' ),
		'google'      => array( __( 'Google Business Profile', 'cobbleandcandle-core' ), 'url', '' ),
		'message_months' => array( __( 'Keep guest messages for (months)', 'cobbleandcandle-core' ), 'months', __( 'Messages older than this are deleted automatically, once a day. 0 keeps them forever. Bookings are never deleted.', 'cobbleandcandle-core' ) ),
		'remove_data' => array( __( 'Remove data on uninstall', 'cobbleandcandle-core' ), 'checkbox', __( 'Delete all locations, menus, menu items, events, rooms, bookings, messages and gallery categories when this plugin is deleted. Leave unticked to keep your content.', 'cobbleandcandle-core' ) ),
	);
}

/**
 * One setting value (raw; escape on output).
 *
 * @param string $key     Setting key.
 * @param string $fallback Value when unset.
 * @return string
 */
function cobble_setting( $key, $fallback = '' ) {
	$saved = get_option( 'cobbleandcandle_brand', array() );
	$value = is_array( $saved ) && isset( $saved[ $key ] ) ? (string) $saved[ $key ] : '';
	return '' !== $value ? $value : $fallback;
}

/**
 * Social profile URLs that are set: service => url.
 *
 * @return array<string, string>
 */
function cobble_social_profiles() {
	$out = array();
	foreach ( array( 'instagram', 'facebook', 'tiktok', 'x', 'youtube', 'tripadvisor', 'yelp', 'google' ) as $service ) {
		$url = cobble_setting( $service );
		if ( '' !== $url ) {
			$out[ $service ] = $url;
		}
	}
	return $out;
}

/**
 * Sanitize the whole settings array against the field list.
 *
 * @param mixed $input Submitted values.
 * @return array<string, string|int>
 */
function cobble_sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : array();
	$out   = array();
	foreach ( cobble_settings_fields() as $key => list( , $type ) ) {
		$value = isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? (string) $input[ $key ] : ''; // options.php has already unslashed.
		if ( 'image' === $type ) {
			$id          = absint( $value );
			$out[ $key ] = $id && wp_attachment_is_image( $id ) ? $id : 0;
		} elseif ( 'checkbox' === $type ) {
			$out[ $key ] = '1' === $value ? '1' : '';
		} elseif ( 'months' === $type ) {
			$out[ $key ] = '' === $value ? '12' : (string) min( 120, absint( $value ) );
		} elseif ( 'url' === $type ) {
			$out[ $key ] = esc_url_raw( (string) $value );
		} elseif ( 'currency' === $key ) {
			$code        = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) $value ) );
			$out[ $key ] = 3 === strlen( $code ) ? $code : '';
		} elseif ( 'est' === $key ) {
			$out[ $key ] = substr( preg_replace( '/[^0-9]/', '', (string) $value ), 0, 4 );
		} else {
			$out[ $key ] = sanitize_text_field( (string) $value );
		}
	}
	return $out;
}

/**
 * Register the option and the settings page.
 */
function cobble_register_settings() {
	register_setting(
		'cobbleandcandle',
		'cobbleandcandle_brand',
		array(
			'type'              => 'object',
			'sanitize_callback' => 'cobble_sanitize_settings',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'cobble_register_settings' );

/**
 * Add Settings → Restaurant.
 */
function cobble_add_settings_page() {
	$hook = add_options_page( __( 'Restaurant settings', 'cobbleandcandle-core' ), __( 'Restaurant', 'cobbleandcandle-core' ), 'manage_options', 'cobbleandcandle', 'cobble_render_settings_page' );
	add_action(
		'admin_enqueue_scripts',
		static function ( $current ) use ( $hook ) {
			if ( $current === $hook ) {
				wp_enqueue_media();
				wp_enqueue_script( 'cobble-settings', COBBLE_CORE_URL . 'assets/settings.js', array( 'jquery' ), COBBLE_CORE_VERSION, true );
			}
		}
	);
}
add_action( 'admin_menu', 'cobble_add_settings_page' );

/**
 * Settings page markup.
 */
function cobble_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$fields = cobble_settings_fields();
	$groups = array(
		array( __( 'Brand', 'cobbleandcandle-core' ), __( 'How your name and mark appear across the site.', 'cobbleandcandle-core' ), array( 'logo_id', 'tagline', 'est' ) ),
		array( __( 'Search & currency', 'cobbleandcandle-core' ), __( 'Shown to Google and other search engines.', 'cobbleandcandle-core' ), array( 'cuisine', 'price_range', 'currency' ) ),
		array( __( 'Social profiles', 'cobbleandcandle-core' ), __( 'Linked in the footer and in your search listing.', 'cobbleandcandle-core' ), array( 'instagram', 'facebook', 'tiktok', 'x', 'youtube', 'tripadvisor', 'yelp', 'google' ) ),
		array( __( 'Data & privacy', 'cobbleandcandle-core' ), '', array( 'message_months', 'remove_data' ) ),
	);
	?>
	<div class="wrap cobble-admin">
		<?php
		cobble_admin_header(
			__( 'Restaurant settings', 'cobbleandcandle-core' ),
			__( 'Your brand details and profiles. Locations, hours, menus and rooms are edited under their own menus.', 'cobbleandcandle-core' ),
			admin_url( 'options-general.php?page=cobbleandcandle' )
		);
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'cobbleandcandle' ); ?>
			<?php foreach ( $groups as list( $heading, $intro, $keys ) ) : ?>
				<section class="cobble-card">
					<h2><?php echo esc_html( $heading ); ?></h2>
					<?php if ( '' !== $intro ) : ?>
						<p class="description"><?php echo esc_html( $intro ); ?></p>
					<?php endif; ?>
					<table class="form-table" role="presentation">
						<?php foreach ( array_intersect_key( $fields, array_flip( $keys ) ) as $key => list( $label, $type, $help ) ) : ?>
							<?php cobble_render_setting_row( $key, $label, $type, $help ); ?>
						<?php endforeach; ?>
					</table>
				</section>
			<?php endforeach; ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * One settings row.
 *
 * @param string $key   Setting key.
 * @param string $label Label.
 * @param string $type  Field type.
 * @param string $help  Help text.
 */
function cobble_render_setting_row( $key, $label, $type, $help ) {
	$id    = 'cobble-setting-' . $key;
	$name  = 'cobbleandcandle_brand[' . $key . ']';
	$value = cobble_setting( $key );
	?>
	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
		<td>
			<?php if ( 'image' === $type ) : ?>
				<div class="cobble-image-field">
					<div class="cobble-image-preview"><?php echo $value ? wp_get_attachment_image( (int) $value, 'medium', false, array( 'style' => 'max-height:80px;width:auto' ) ) : ''; ?></div>
					<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
					<button type="button" class="button cobble-image-choose"><?php esc_html_e( 'Choose logo', 'cobbleandcandle-core' ); ?></button>
					<button type="button" class="button-link cobble-image-remove"<?php echo $value ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'cobbleandcandle-core' ); ?></button>
				</div>
			<?php elseif ( 'checkbox' === $type ) : ?>
				<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $value, '1' ); ?>>
			<?php elseif ( 'months' === $type ) : ?>
				<input type="number" min="0" max="120" step="1" class="small-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( cobble_setting( $key, '12' ) ); ?>">
			<?php else : ?>
				<input type="<?php echo 'url' === $type ? 'url' : 'text'; ?>" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
			<?php endif; ?>
			<?php if ( '' !== $help ) : ?>
				<p class="description"><?php echo esc_html( $help ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Currency for structured data: the setting, else USD.
 *
 * @param string $currency Default.
 * @return string
 */
function cobble_currency_from_settings( $currency ) {
	return cobble_setting( 'currency', $currency );
}
add_filter( 'cobble_currency', 'cobble_currency_from_settings', 5 );
