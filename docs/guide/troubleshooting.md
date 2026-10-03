# Troubleshooting

## Start with the Status page

**Tools → Cobble & Candle status** checks PHP and WordPress versions, theme and plugin, permalinks, timezone, HTTPS, scheduled jobs, the template cache, error display and failed emails, and shows the last 200 problems. Anything marked **Problem** or **Check** comes with what to do.

| Symptom | Likely cause | Fix |
| --- | --- | --- |
| Booking or contact emails don’t arrive | Host mail blocked or going to spam | Install [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/), then **Send a test email** on the Status page. Requests are still saved under **Messages**. |
| /menu/, /rooms/ or /events/ show “Page not found” | Plain permalinks, or rules not refreshed | Settings → Permalinks → choose *Post name* → **Save** (twice is fine). |
| “Open now” is an hour off | UTC offset instead of a city | Settings → General → Timezone → pick your city. |
| Changes don’t show | Page cache | Purge LiteSpeed / WP Rocket / host cache. |
| Airbnb nights don’t block | Wrong link, or the platform’s feed is down | Use the platform’s **export** link; check the Status page log for “calendar feed could not be read”. |
| Blank page or “There has been a critical error” | PHP below 8.3, or a plugin conflict | Check the Status page and the error log below; temporarily deactivate other plugins. |
| A page looks unstyled after an update | Old cached CSS | Purge the cache; hard-refresh the browser. |
| Dish fields missing | Old version | Update Cobble & Candle Core; dishes edit in the block editor with a *Price & details* panel. |

## Error logs

- **Event log (built in):** Tools → Cobble & Candle status → *Recent problems*. Failed emails (from any plugin), unreadable calendar feeds and import warnings. No guest details are stored.
- **PHP errors:** add to `wp-config.php` (above “That’s all, stop editing”):

  ```php
  define( 'WP_DEBUG', true );
  define( 'WP_DEBUG_LOG', true );      // writes wp-content/debug.log
  define( 'WP_DEBUG_DISPLAY', false ); // never show errors to visitors
  ```

  Reproduce the problem, then read `wp-content/debug.log` (via your host’s file manager). Cobble & Candle problems appear as `[Cobble & Candle] ERROR …`. Turn `WP_DEBUG` off again afterwards.
- **Your host’s error log** (cPanel / hPanel → Logs) catches fatal errors that happen before WordPress loads.
- **[Query Monitor](https://wordpress.org/plugins/query-monitor/)** (free plugin) shows PHP notices, slow queries and HTTP calls for the page you’re on.

## Send errors to Sentry or Slack

Every problem the plugin records fires the `cc_log` action. Put one of these in a small must-use plugin (`wp-content/mu-plugins/cc-alerts.php`):

```php
<?php
// Sentry (with the Sentry PHP SDK or the WP Sentry plugin installed).
add_action( 'cc_log', function ( array $entry ) {
	if ( 'error' === $entry['level'] && function_exists( '\Sentry\captureMessage' ) ) {
		\Sentry\captureMessage( "[{$entry['source']}] {$entry['message']}" );
	}
} );

// Slack incoming webhook: alert on errors only.
add_action( 'cc_log', function ( array $entry ) {
	if ( 'error' !== $entry['level'] ) {
		return;
	}
	wp_remote_post( 'https://hooks.slack.com/services/XXX/YYY/ZZZ', array(
		'body'     => wp_json_encode( array( 'text' => "Cobble & Candle: {$entry['source']}: {$entry['message']}" ) ),
		'headers'  => array( 'Content-Type' => 'application/json' ),
		'blocking' => false,
	) );
} );
```

Each `$entry` has `time`, `level` (`error`, `warning`, `info`), `source` (`mail`, `ical`, `import`…), `message` and a small `context` array (IDs, HTTP codes). Never personal data.

## Still stuck?

Copy the **System report** from the Status page and open an issue: see [SUPPORT.md](../../SUPPORT.md).
