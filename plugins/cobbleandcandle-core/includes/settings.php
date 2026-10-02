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
function cc_settings_fields() {
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
	);
}

/**
 * One setting value (raw; escape on output).
 *
 * @param string $key     Setting key.
 * @param string $fallback Value when unset.
 * @return string
 */
function cc_setting( $key, $fallback = '' ) {
	$saved = get_option( 'cobbleandcandle_brand', array() );
	$value = is_array( $saved ) && isset( $saved[ $key ] ) ? (string) $saved[ $key ] : '';
	return '' !== $value ? $value : $fallback;
}

/**
 * Social profile URLs that are set: service => url.
 *
 * @return array<string, string>
 */
function cc_social_profiles() {
	$out = array();
	foreach ( array( 'instagram', 'facebook', 'tiktok', 'x', 'youtube', 'tripadvisor', 'yelp', 'google' ) as $service ) {
		$url = cc_setting( $service );
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
function cc_sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : array();
	$out   = array();
	foreach ( cc_settings_fields() as $key => list( , $type ) ) {
		$value = isset( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : '';
		if ( 'image' === $type ) {
			$id          = absint( $value );
			$out[ $key ] = $id && wp_attachment_is_image( $id ) ? $id : 0;
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
function cc_register_settings() {
	register_setting(
		'cobbleandcandle',
		'cobbleandcandle_brand',
		array(
			'type'              => 'object',
			'sanitize_callback' => 'cc_sanitize_settings',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'cc_register_settings' );

/**
 * Add Settings → Restaurant.
 */
function cc_add_settings_page() {
	$hook = add_options_page( __( 'Restaurant settings', 'cobbleandcandle-core' ), __( 'Restaurant', 'cobbleandcandle-core' ), 'manage_options', 'cobbleandcandle', 'cc_render_settings_page' );
	add_action(
		'admin_enqueue_scripts',
		static function ( $current ) use ( $hook ) {
			if ( $current === $hook ) {
				wp_enqueue_media();
				wp_enqueue_script( 'cc-settings', CC_CORE_URL . 'assets/settings.js', array( 'jquery' ), CC_CORE_VERSION, true );
			}
		}
	);
}
add_action( 'admin_menu', 'cc_add_settings_page' );

/**
 * Settings page markup.
 */
function cc_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Restaurant settings', 'cobbleandcandle-core' ); ?></h1>
		<p><?php esc_html_e( 'Your brand details and profiles. Locations, hours and menus are edited under their own menus.', 'cobbleandcandle-core' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'cobbleandcandle' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( cc_settings_fields() as $key => list( $label, $type, $help ) ) : ?>
					<?php
					$id    = 'cc-setting-' . $key;
					$name  = 'cobbleandcandle_brand[' . $key . ']';
					$value = cc_setting( $key );
					?>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<?php if ( 'image' === $type ) : ?>
								<div class="cc-image-field">
									<div class="cc-image-preview"><?php echo $value ? wp_get_attachment_image( (int) $value, 'medium', false, array( 'style' => 'max-height:80px;width:auto' ) ) : ''; ?></div>
									<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
									<button type="button" class="button cc-image-choose"><?php esc_html_e( 'Choose logo', 'cobbleandcandle-core' ); ?></button>
									<button type="button" class="button-link cc-image-remove"<?php echo $value ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'cobbleandcandle-core' ); ?></button>
								</div>
							<?php else : ?>
								<input type="<?php echo 'url' === $type ? 'url' : 'text'; ?>" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
							<?php endif; ?>
							<?php if ( '' !== $help ) : ?>
								<p class="description"><?php echo esc_html( $help ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Currency for structured data: the setting, else USD.
 *
 * @param string $currency Default.
 * @return string
 */
function cc_currency_from_settings( $currency ) {
	return cc_setting( 'currency', $currency );
}
add_filter( 'cc_currency', 'cc_currency_from_settings', 5 );
