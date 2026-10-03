<?php
/**
 * Status & logs (Tools → Cobble & Candle status).
 *
 * A small event log (last 200 entries, never guest details) records what failed and where:
 * emails WordPress couldn't send, calendar feeds that couldn't be read, demo/menu imports. Every
 * entry also fires `cc_log` so error trackers (Sentry, Slack, Query Monitor) can subscribe, and is
 * written to debug.log when WP_DEBUG_LOG is on. The screen adds health checks, a test email and a
 * copyable system report for support requests.
 *
 * @package CobbleAndCandleCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Record an event.
 *
 * @param string               $level   error | warning | info.
 * @param string               $source  Short area name: mail, ical, import, booking, setup…
 * @param string               $message What happened (no personal data).
 * @param array<string, mixed> $context Small, non-personal details (IDs, HTTP codes).
 */
function cc_log( $level, $source, $message, array $context = array() ) {
	$level = in_array( $level, array( 'error', 'warning', 'info' ), true ) ? $level : 'info';
	$entry = array(
		'time'    => time(),
		'level'   => $level,
		'source'  => sanitize_key( $source ),
		// Never keep personal data: email addresses in error text (PHPMailer often includes them) are redacted.
		'message' => (string) preg_replace( '/[^\s<>"\'(),;:]+@[^\s<>"\'(),;:]+/', '[email]', wp_strip_all_tags( (string) $message ) ),
		'context' => array_map(
			static fn( $value ) => is_scalar( $value ) ? (string) $value : wp_json_encode( $value ),
			array_slice( $context, 0, 10, true )
		),
	);
	$log = get_option( 'cc_log', array() );
	$log = is_array( $log ) ? $log : array();
	// The same problem again within a day (an hourly feed failure, say) bumps a count on the existing
	// entry instead of filling the log, so other problems are not pushed out.
	$repeat = null;
	foreach ( $log as $i => $old ) {
		if ( ! is_array( $old ) || (int) ( $old['time'] ?? 0 ) < time() - DAY_IN_SECONDS ) {
			break;
		}
		if ( ( $old['source'] ?? '' ) === $entry['source'] && ( $old['message'] ?? '' ) === $entry['message'] && ( $old['context'] ?? array() ) === $entry['context'] ) {
			$repeat = $i;
			break;
		}
	}
	if ( null !== $repeat ) {
		$entry['count'] = (int) ( $log[ $repeat ]['count'] ?? 1 ) + 1;
		unset( $log[ $repeat ] );
	}
	array_unshift( $log, $entry );
	update_option( 'cc_log', array_slice( array_values( $log ), 0, 200 ), false );
	if ( 'mail' === $entry['source'] && 'error' === $level ) {
		// Kept apart from the log so the weekly email check can't be pushed out by other events.
		$failures   = array_filter( (array) get_option( 'cc_mail_failures', array() ), static fn( $t ) => (int) $t > time() - WEEK_IN_SECONDS );
		$failures[] = time();
		update_option( 'cc_mail_failures', array_slice( array_values( $failures ), -100 ), false );
	}

	if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG && 'info' !== $level ) {
		error_log( sprintf( '[Cobble & Candle] %s %s: %s %s', strtoupper( $level ), $entry['source'], $entry['message'], $entry['context'] ? wp_json_encode( $entry['context'] ) : '' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- only when the site owner turned on WP_DEBUG_LOG.
	}

	/**
	 * Fires for every logged event. Hook an error tracker here, e.g.
	 * add_action( 'cc_log', fn( $e ) => 'error' === $e['level'] && \Sentry\captureMessage( $e['message'] ) );
	 *
	 * @param array<string, mixed> $entry time, level, source, message, context.
	 */
	do_action( 'cc_log', $entry );
}

/**
 * Log every email WordPress fails to send (any plugin or form on the site).
 *
 * @param WP_Error $error Mail error.
 */
function cc_log_mail_failure( $error ) {
	// The subject and recipients are left out on purpose: they can hold guest names and addresses.
	cc_log( 'error', 'mail', is_wp_error( $error ) ? $error->get_error_message() : __( 'An email could not be sent.', 'cobbleandcandle-core' ) );
}
add_action( 'wp_mail_failed', 'cc_log_mail_failure' );

/**
 * Register Tools → Cobble & Candle status.
 */
function cc_status_menu() {
	add_management_page( __( 'Cobble & Candle status', 'cobbleandcandle-core' ), __( 'Cobble & Candle status', 'cobbleandcandle-core' ), 'manage_options', 'cc-status', 'cc_render_status_page' );
}
add_action( 'admin_menu', 'cc_status_menu' );

/**
 * Health checks: [label, state (ok|warn|fail|info), detail].
 *
 * @return array<int, array{0: string, 1: string, 2: string}>
 */
function cc_status_checks() {
	global $wp_version;
	$checks   = array();
	$checks[] = array( __( 'PHP version', 'cobbleandcandle-core' ), version_compare( PHP_VERSION, '8.3', '>=' ) ? 'ok' : 'fail', PHP_VERSION . ' ' . __( '(8.3 or newer required)', 'cobbleandcandle-core' ) );
	$checks[] = array( __( 'WordPress version', 'cobbleandcandle-core' ), version_compare( $wp_version, '6.6', '>=' ) ? 'ok' : 'fail', $wp_version . ' ' . __( '(6.6 or newer required)', 'cobbleandcandle-core' ) );

	$theme    = wp_get_theme( get_template() );
	$active   = 'Cobble & Candle' === wp_specialchars_decode( $theme->get( 'Name' ), ENT_QUOTES ) || 'cobbleandcandle' === $theme->get( 'TextDomain' ); // Works in a renamed folder too.
	$checks[] = array( __( 'Theme', 'cobbleandcandle-core' ), $active ? 'ok' : 'warn', $active ? wp_specialchars_decode( $theme->get( 'Name' ), ENT_QUOTES ) . ' ' . $theme->get( 'Version' ) : __( 'Cobble & Candle is not the active theme.', 'cobbleandcandle-core' ) );
	$checks[] = array( __( 'Cobble & Candle Core', 'cobbleandcandle-core' ), 'ok', CC_CORE_VERSION );

	$checks[] = array( __( 'Permalinks', 'cobbleandcandle-core' ), get_option( 'permalink_structure' ) ? 'ok' : 'warn', get_option( 'permalink_structure' ) ? (string) get_option( 'permalink_structure' ) : __( 'Plain links: choose “Post name” under Settings → Permalinks so /menu/, /rooms/ and /events/ work.', 'cobbleandcandle-core' ) );
	$checks[] = array( __( 'Timezone', 'cobbleandcandle-core' ), get_option( 'timezone_string' ) ? 'ok' : 'warn', get_option( 'timezone_string' ) ? (string) get_option( 'timezone_string' ) : __( 'Set a city under Settings → General so “Open now” and booking times follow daylight saving.', 'cobbleandcandle-core' ) );
	$https    = 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ); // The public address, not this admin request.
	$checks[] = array( __( 'HTTPS', 'cobbleandcandle-core' ), $https ? 'ok' : 'warn', $https ? home_url() : home_url() . ' ' . __( '(use https:// under Settings → General once your host has a certificate)', 'cobbleandcandle-core' ) );

	$cron_off = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
	$next     = wp_next_scheduled( 'cc_ical_sync' );
	$checks[] = array(
		__( 'Scheduled jobs', 'cobbleandcandle-core' ),
		$next ? ( $cron_off ? 'info' : 'ok' ) : 'warn',
		$next
			/* translators: %s: time until the next run */
			? sprintf( __( 'Calendar sync and clean-up run hourly (next in %s).', 'cobbleandcandle-core' ), human_time_diff( time(), $next ) ) . ( $cron_off ? ' ' . __( 'WP-Cron is off: make sure your host runs wp-cron.php on a schedule.', 'cobbleandcandle-core' ) : '' )
			: __( 'Calendar sync is not scheduled. Deactivate and reactivate Cobble & Candle Core.', 'cobbleandcandle-core' ),
	);

	// Acorn compiles templates into WP_CONTENT_DIR/cache/acorn: test the nearest folder that exists.
	$cache    = WP_CONTENT_DIR . '/cache/acorn';
	$probe    = is_dir( $cache ) ? $cache : ( is_dir( dirname( $cache ) ) ? dirname( $cache ) : WP_CONTENT_DIR );
	$writable = wp_is_writable( $probe );
	$shown    = str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $cache ) );
	$checks[] = array(
		__( 'Template cache', 'cobbleandcandle-core' ),
		$writable ? 'ok' : 'fail',
		$writable
			/* translators: %s: folder path */
			? sprintf( __( '%s is writable.', 'cobbleandcandle-core' ), $shown )
			/* translators: %s: folder path */
			: sprintf( __( '%s is not writable: pages cannot be compiled. Ask your host to fix folder permissions.', 'cobbleandcandle-core' ), $shown ),
	);

	$display  = defined( 'WP_DEBUG' ) && WP_DEBUG && ( ! defined( 'WP_DEBUG_DISPLAY' ) || WP_DEBUG_DISPLAY );
	$checks[] = array( __( 'Error display', 'cobbleandcandle-core' ), $display ? 'warn' : 'ok', $display ? __( 'WP_DEBUG shows errors to visitors. On a live site set WP_DEBUG_DISPLAY to false and use WP_DEBUG_LOG.', 'cobbleandcandle-core' ) : __( 'Errors are not shown to visitors.', 'cobbleandcandle-core' ) );

	$mail_errors = count( array_filter( (array) get_option( 'cc_mail_failures', array() ), static fn( $t ) => (int) $t > time() - WEEK_IN_SECONDS ) );
	$checks[]    = array(
		__( 'Email', 'cobbleandcandle-core' ),
		$mail_errors ? 'fail' : 'ok',
		$mail_errors
			/* translators: %d: number of failed emails */
			? sprintf( _n( '%d email failed this week. Install an SMTP plugin (e.g. WP Mail SMTP) and send a test below.', '%d emails failed this week. Install an SMTP plugin (e.g. WP Mail SMTP) and send a test below.', $mail_errors, 'cobbleandcandle-core' ), $mail_errors )
			: __( 'No failed emails this week. Send a test below to be sure guests’ requests reach you.', 'cobbleandcandle-core' ),
	);

	// Same detection the SEO layer uses (includes/seo.php), so the row describes what really happens.
	$graph = defined( 'WPSEO_VERSION' ) ? 'Yoast SEO' : ( defined( 'RANK_MATH_VERSION' ) ? 'Rank Math' : '' );
	$other = defined( 'AIOSEO_VERSION' ) ? 'AIOSEO' : ( defined( 'SEOPRESS_VERSION' ) ? 'SEOPress' : ( defined( 'THE_SEO_FRAMEWORK_VERSION' ) ? 'The SEO Framework' : ( defined( 'SLIM_SEO_VER' ) ? 'Slim SEO' : '' ) ) );
	if ( '' !== $graph ) {
		/* translators: %s: SEO plugin name */
		$seo_text = sprintf( __( '%s handles titles and meta; your restaurant, menu, event and room data joins its schema graph.', 'cobbleandcandle-core' ), $graph );
	} elseif ( function_exists( 'cc_seo_plugin_active' ) && cc_seo_plugin_active() ) {
		/* translators: %s: SEO plugin name */
		$seo_text = sprintf( __( '%s handles titles and meta; Cobble & Candle adds its own restaurant, menu, event and room schema.', 'cobbleandcandle-core' ), '' !== $other ? $other : __( 'Your SEO plugin', 'cobbleandcandle-core' ) );
	} else {
		$seo_text = __( 'No SEO plugin: Cobble & Candle prints its own meta, share tags and schema.', 'cobbleandcandle-core' );
	}
	$checks[] = array( __( 'SEO', 'cobbleandcandle-core' ), 'info', $seo_text );
	$checks[] = array( __( 'Object cache', 'cobbleandcandle-core' ), 'info', wp_using_ext_object_cache() ? __( 'Persistent object cache in use.', 'cobbleandcandle-core' ) : __( 'None (fine for most sites).', 'cobbleandcandle-core' ) );
	return $checks;
}

/**
 * Plain-text system report for support requests (no emails, keys or guest data).
 *
 * @return string
 */
function cc_status_report() {
	global $wp_version;
	$lines = array(
		'Cobble & Candle system report — ' . gmdate( 'Y-m-d H:i' ) . ' UTC',
		'Site: ' . home_url(),
		'WordPress: ' . $wp_version . ( is_multisite() ? ' (multisite)' : '' ),
		'PHP: ' . PHP_VERSION . ' · memory_limit ' . ini_get( 'memory_limit' ),
		'Theme: ' . wp_specialchars_decode( wp_get_theme()->get( 'Name' ), ENT_QUOTES ) . ' ' . wp_get_theme()->get( 'Version' ) . ' (template: ' . get_template() . ')',
		'Core plugin: ' . CC_CORE_VERSION,
		'Locale: ' . get_locale() . ' · timezone ' . wp_timezone_string(),
		'',
		'Checks:',
	);
	foreach ( cc_status_checks() as list( $label, $state, $detail ) ) {
		$lines[] = sprintf( '  [%s] %s: %s', strtoupper( $state ), $label, $detail );
	}
	$lines[] = '';
	$lines[] = 'Active plugins:';
	foreach ( (array) get_option( 'active_plugins', array() ) as $plugin ) {
		$lines[] = '  ' . $plugin;
	}
	$lines[] = '';
	$lines[] = 'Recent log:';
	foreach ( array_slice( (array) get_option( 'cc_log', array() ), 0, 20 ) as $entry ) {
		if ( is_array( $entry ) ) {
			$lines[] = sprintf( '  %s %s %s: %s', gmdate( 'Y-m-d H:i', (int) $entry['time'] ), strtoupper( (string) $entry['level'] ), (string) $entry['source'], (string) $entry['message'] );
		}
	}
	return implode( "\n", $lines );
}

/**
 * Status actions: send a test email, clear the log.
 */
function cc_handle_status_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'cobbleandcandle-core' ), 403 );
	}
	$do = isset( $_POST['cc_do'] ) ? sanitize_key( wp_unslash( $_POST['cc_do'] ) ) : '';
	check_admin_referer( 'cc_status_' . $do );
	$result = '';
	if ( 'mail' === $do ) {
		$to     = wp_get_current_user()->user_email;
		$sent   = wp_mail(
			$to,
			/* translators: %s: site name */
			sprintf( __( 'Test email from %s', 'cobbleandcandle-core' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
			__( 'This is a test from Tools → Cobble & Candle status. If you can read it, booking and contact requests will reach you too.', 'cobbleandcandle-core' )
		);
		$result = $sent ? 'mail-ok' : 'mail-fail';
		if ( $sent ) { // A failure is already logged by the wp_mail_failed hook.
			cc_log( 'info', 'mail', 'Test email sent.' );
		}
	} elseif ( 'clear' === $do ) {
		delete_option( 'cc_log' );
		delete_option( 'cc_mail_failures' );
		$result = 'cleared';
	}
	wp_safe_redirect( add_query_arg( 'done', $result, admin_url( 'tools.php?page=cc-status' ) ) );
	exit;
}
add_action( 'admin_post_cc_status', 'cc_handle_status_action' );

/**
 * A small action form.
 *
 * @param string $do    Action.
 * @param string $label Button label.
 * @param string $class Button class.
 */
function cc_status_button( $do, $label, $class = 'button' ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin:0 8px 8px 0">';
	echo '<input type="hidden" name="action" value="cc_status"><input type="hidden" name="cc_do" value="' . esc_attr( $do ) . '">';
	wp_nonce_field( 'cc_status_' . $do );
	submit_button( $label, $class, 'submit', false );
	echo '</form>';
}

/**
 * The status screen.
 */
function cc_render_status_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- status flag after redirect.
	$done   = isset( $_GET['done'] ) ? sanitize_key( wp_unslash( $_GET['done'] ) ) : '';
	$labels = array(
		'ok'   => __( 'OK', 'cobbleandcandle-core' ),
		'warn' => __( 'Check', 'cobbleandcandle-core' ),
		'fail' => __( 'Problem', 'cobbleandcandle-core' ),
		'info' => __( 'Info', 'cobbleandcandle-core' ),
	);
	$log    = array_filter( (array) get_option( 'cc_log', array() ), 'is_array' );
	?>
	<div class="wrap cc-admin">
		<?php cc_admin_header( __( 'Status & logs', 'cobbleandcandle-core' ), __( 'Health checks, recent problems and a system report to send with support requests.', 'cobbleandcandle-core' ), admin_url( 'tools.php?page=cc-status' ) ); ?>

		<?php if ( 'mail-ok' === $done ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Test email sent. Check your inbox (and spam folder).', 'cobbleandcandle-core' ); ?></p></div>
		<?php elseif ( 'mail-fail' === $done ) : ?>
			<div class="notice notice-error inline"><p><?php esc_html_e( 'The test email could not be sent. Install an SMTP plugin (e.g. WP Mail SMTP) or ask your host to enable email.', 'cobbleandcandle-core' ); ?></p></div>
		<?php elseif ( 'cleared' === $done ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Log cleared.', 'cobbleandcandle-core' ); ?></p></div>
		<?php endif; ?>

		<section class="cc-card">
			<h2><?php esc_html_e( 'Health checks', 'cobbleandcandle-core' ); ?></h2>
			<table class="widefat striped cc-checks">
				<tbody>
					<?php foreach ( cc_status_checks() as list( $label, $state, $detail ) ) : ?>
						<tr>
							<th scope="row" style="width:200px"><?php echo esc_html( $label ); ?></th>
							<td style="width:90px"><span class="cc-state cc-state--<?php echo esc_attr( $state ); ?>"><?php echo esc_html( $labels[ $state ] ); ?></span></td>
							<td><?php echo esc_html( $detail ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p style="margin-top:16px"><?php cc_status_button( 'mail', __( 'Send a test email to me', 'cobbleandcandle-core' ), 'primary' ); ?></p>
		</section>

		<section class="cc-card">
			<h2><?php esc_html_e( 'Recent problems', 'cobbleandcandle-core' ); ?></h2>
			<?php if ( $log ) : ?>
				<table class="widefat striped">
					<thead><tr>
						<th><?php esc_html_e( 'When', 'cobbleandcandle-core' ); ?></th>
						<th><?php esc_html_e( 'Level', 'cobbleandcandle-core' ); ?></th>
						<th><?php esc_html_e( 'Area', 'cobbleandcandle-core' ); ?></th>
						<th><?php esc_html_e( 'What happened', 'cobbleandcandle-core' ); ?></th>
					</tr></thead>
					<tbody>
						<?php foreach ( array_slice( $log, 0, 50 ) as $entry ) : ?>
							<tr>
								<td><?php echo esc_html( wp_date( 'j M H:i', (int) $entry['time'] ) ); ?></td>
								<td><span class="cc-state cc-state--<?php echo esc_attr( 'error' === $entry['level'] ? 'fail' : ( 'warning' === $entry['level'] ? 'warn' : 'info' ) ); ?>"><?php echo esc_html( ucfirst( (string) $entry['level'] ) ); ?></span></td>
								<td><?php echo esc_html( (string) $entry['source'] ); ?></td>
								<td><?php echo esc_html( (string) $entry['message'] ); ?>
									<?php if ( (int) ( $entry['count'] ?? 1 ) > 1 ) : ?>
										<?php /* translators: %d: how many times */ ?>
										<strong><?php echo esc_html( sprintf( __( '(×%d)', 'cobbleandcandle-core' ), (int) $entry['count'] ) ); ?></strong>
									<?php endif; ?>
									<?php if ( ! empty( $entry['context'] ) ) : ?>
										<br><code><?php echo esc_html( (string) wp_json_encode( $entry['context'] ) ); ?></code>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p style="margin-top:16px"><?php cc_status_button( 'clear', __( 'Clear log', 'cobbleandcandle-core' ) ); ?></p>
			<?php else : ?>
				<p><?php esc_html_e( 'Nothing has gone wrong yet. Failed emails, unreadable calendar feeds and import problems will show here.', 'cobbleandcandle-core' ); ?></p>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( 'Keeps the last 200 events. No guest names, emails or phone numbers are stored. Developers can send events to an error tracker with the cc_log hook.', 'cobbleandcandle-core' ); ?></p>
		</section>

		<section class="cc-card">
			<h2><?php esc_html_e( 'System report', 'cobbleandcandle-core' ); ?></h2>
			<p><?php esc_html_e( 'Copy this into a support request. It contains versions and settings, never passwords, emails or guest details.', 'cobbleandcandle-core' ); ?></p>
			<label class="screen-reader-text" for="cc-report"><?php esc_html_e( 'System report', 'cobbleandcandle-core' ); ?></label>
			<textarea id="cc-report" class="large-text code" rows="14" readonly onfocus="this.select()"><?php echo esc_textarea( cc_status_report() ); ?></textarea>
		</section>
	</div>
	<?php
}
