# Segment Audience Cookie

A WordPress plugin that looks up a visitor's [Segment](https://segment.com) audiences server-side and stores them in a `segment_audiences` cookie, so themes and scripts can personalize content without exposing your Segment API token to the browser.

Originally built for a large corporate WordPress site; rebuilt from scratch as a standalone plugin.

## How it works

1. On page load, a small script reads Segment's `ajs_user_id` / `ajs_anonymous_id` cookies.
2. It sends them to WordPress over AJAX.
3. WordPress calls the Segment Profile API with your secret token and returns the visitor's audience keys (a known user ID takes priority over an anonymous ID).
4. The script saves them as a comma-separated session cookie: `segment_audiences=trial_users,enterprise_leads`.

## Setup

Requires WordPress 5.0+, PHP 7.4+, and Segment Unify (Profiles) with a Profile API access token. Add your credentials to `wp-config.php`:

```php
define( 'SAC_SEGMENT_SPACE_ID', 'spa_xxxxxxxx' );
define( 'SAC_SEGMENT_TOKEN', 'your-profile-api-token' );
```

Then activate the plugin. Segment's analytics.js must already be on the site.

## Filters

Both filters go in your theme's `functions.php` or a small plugin.

```php
// Cache lookups for 6 hours instead of 1.
add_filter( 'sac_cache_ttl', fn() => 6 * HOUR_IN_SECONDS );

// Use a different cookie name.
add_filter( 'sac_cookie_name', fn() => 'my_audiences' );
```

## Reading the cookie

The cookie holds a comma-separated list of audience keys. In PHP:

```php
// Use your custom name here if you changed it with `sac_cookie_name`.
$audiences = explode( ',', $_COOKIE['segment_audiences'] ?? '' );
if ( in_array( 'enterprise_leads', $audiences, true ) ) {
	// Show enterprise content.
}
```
Or in JavaScript:

```js
const audiences = ( document.cookie.match( /(?:^|; )segment_audiences=([^;]*)/ )?.[ 1 ] ?? '' );
if ( decodeURIComponent( audiences ).split( ',' ).includes( 'enterprise_leads' ) ) {
	// Show enterprise content.
}
```
Page caching: if your host caches full pages (WP Engine, Cloudflare, etc.), reading the cookie in PHP won't work for cached pages. Read it in JavaScript instead, or exclude personalized pages from the cache.

## License

GPL-2.0-or-later
