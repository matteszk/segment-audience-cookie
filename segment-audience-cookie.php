<?php
/**
 * Plugin Name:       Segment Audience Cookie
 * Plugin URI:        https://github.com/matteszk/segment-audience-cookie
 * Description:       Looks up a visitor's Segment audiences server-side and stores them in a cookie for personalization.
 * Version:           0.1.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Matthew Szklarz
 * Author URI:        https://matthewszklarz.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       segment-audience-cookie
 */

// Block direct access to this file outside WordPress.
defined( 'ABSPATH' ) || exit;

/*
 * Credentials live in wp-config.php, never in the plugin:
 *
 *   define( 'SAC_SEGMENT_SPACE_ID', 'spa_...' );
 *   define( 'SAC_SEGMENT_TOKEN', '...' );
 */

/**
 * Load the front-end script and tell it where to send requests.
 */
function sac_enqueue_script() {
	wp_enqueue_script(
		'segment-audience-cookie',
		plugins_url( 'segment-audience-cookie.js', __FILE__ ),
		array(),
		'0.1.0',
		true
	);

	wp_localize_script(
		'segment-audience-cookie',
		'sacSettings',
		array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ) )
	);
}
add_action( 'wp_enqueue_scripts', 'sac_enqueue_script' );

/**
 * AJAX handler: fetch the visitor's audiences from the Segment Profile API.
 *
 * Responds with a comma-separated list of audience keys.
 */
function sac_get_audiences() {
	if ( ! defined( 'SAC_SEGMENT_SPACE_ID' ) || ! defined( 'SAC_SEGMENT_TOKEN' ) ) {
		wp_send_json_error( 'Segment credentials are not configured.', 500 );
	}

	$user_id      = sanitize_text_field( wp_unslash( $_POST['user_id'] ?? '' ) );
	$anonymous_id = sanitize_text_field( wp_unslash( $_POST['anonymous_id'] ?? '' ) );

	// A known user ID is more specific than an anonymous ID, so check it first.
	$identifier = '';
	if ( '' !== $user_id ) {
		$identifier = 'user_id:' . $user_id;
	} elseif ( '' !== $anonymous_id ) {
		$identifier = 'anonymous_id:' . $anonymous_id;
	}

	if ( '' === $identifier ) {
		wp_send_json_error( 'No Segment identifier provided.', 400 );
	}

	$url = sprintf(
		'https://profiles.segment.com/v1/spaces/%s/collections/users/profiles/%s/traits?class=audience',
		SAC_SEGMENT_SPACE_ID,
		rawurlencode( $identifier )
	);

	// The Profile API uses Basic auth with the token as the username and no password.
	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 3,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( SAC_SEGMENT_TOKEN . ':' ),
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( $response->get_error_message(), 502 );
	}

	$status = wp_remote_retrieve_response_code( $response );

	// 404 means Segment has no profile for this visitor yet: a valid "no audiences".
	if ( 404 === $status ) {
		wp_send_json_success( '' );
	}

	// Anything else that isn't 200 is a real failure (bad token, rate limit, outage).
	if ( 200 !== $status ) {
		wp_send_json_error( 'Segment API returned HTTP ' . $status, 502 );
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( empty( $body['traits'] ) || ! is_array( $body['traits'] ) ) {
		wp_send_json_success( '' );
	}

	// Audience traits are keyed by audience name; the keys are all we need.
	wp_send_json_success( implode( ',', array_keys( $body['traits'] ) ) );
}
add_action( 'wp_ajax_sac_get_audiences', 'sac_get_audiences' );
add_action( 'wp_ajax_nopriv_sac_get_audiences', 'sac_get_audiences' );
