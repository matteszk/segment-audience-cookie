# Segment Audience Cookie

A WordPress plugin that looks up a visitor's [Segment](https://segment.com) audiences server-side and stores them in a `segment_audiences` cookie, so themes and scripts can personalize content without exposing your Segment API token to the browser.

Originally built for a large corporate WordPress site; rebuilt from scratch as a standalone plugin.

## How it works

1. On page load, a small script reads Segment's `ajs_user_id` / `ajs_anonymous_id` cookies.
2. It sends them to WordPress over AJAX.
3. WordPress calls the Segment Profile API with your secret token and returns the visitor's audience keys (a known user ID takes priority over an anonymous ID).
4. The script saves them as a comma-separated session cookie: `segment_audiences=trial_users,enterprise_leads`.

## Setup

Add your credentials to `wp-config.php`:

```php
define( 'SAC_SEGMENT_SPACE_ID', 'spa_xxxxxxxx' );
define( 'SAC_SEGMENT_TOKEN', 'your-profile-api-token' );
```

Then activate the plugin. Segment's analytics.js must already be on the site.

## Reading the cookie

```php
$audiences = explode( ',', $_COOKIE['segment_audiences'] ?? '' );
if ( in_array( 'enterprise_leads', $audiences, true ) ) {
	// Show enterprise content.
}
```

## License

GPL-2.0-or-later
