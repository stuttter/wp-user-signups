<?php

/**
 * Real-WordPress smoke coverage for signup storage, hooks, cache, and metadata.
 */

defined( 'ABSPATH' ) || exit( 1 );

function wpus_smoke_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

global $wpdb;

do_action( 'admin_init' );

wpus_smoke_assert( class_exists( 'WP_Signup' ), 'WP_Signup was not loaded.' );
wpus_smoke_assert( ! empty( $wpdb->signups ), 'The signups table was not registered.' );
wpus_smoke_assert( ! empty( $wpdb->signupmeta ), 'The signupmeta table was not registered.' );
wpus_smoke_assert(
	$wpdb->signups === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->signups ) ),
	'The signups table was not created.'
);
wpus_smoke_assert(
	$wpdb->signupmeta === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->signupmeta ) ),
	'The signupmeta table was not created.'
);

$observed_key = null;
add_action(
	'after_signup_user',
	function ( $user_login, $user_email, $activation_key ) use ( &$observed_key ) {
		$observed_key = $activation_key;
	},
	1,
	3
);

$suffix = strtolower( wp_generate_password( 10, false, false ) );
$signup = WP_Signup::create(
	array(
		'user_login'     => 'smoke-' . $suffix,
		'user_email'     => 'smoke-' . $suffix . '@example.test',
		'activation_key' => 'smoke-activation-key',
		'meta'           => array( 'source' => 'integration' ),
	)
);

wpus_smoke_assert( ! is_wp_error( $signup ), 'Could not create a signup.' );
wpus_smoke_assert( 'smoke-activation-key' === $observed_key, 'The notification hook received the wrong activation key.' );

$query = new WP_Signup_Query();
$found = $query->query(
	array(
		'ID'     => $signup->signup_id,
		'active' => 0,
	)
);

wpus_smoke_assert( 1 === count( $found ), 'The created signup was not queryable.' );
wpus_smoke_assert( (int) $signup->signup_id === (int) $found[0]->signup_id, 'The query returned the wrong signup.' );

$meta_id = add_signup_meta( $signup->signup_id, 'smoke_key', 'smoke_value', true );
wpus_smoke_assert( false !== $meta_id, 'Could not add signup metadata.' );
wpus_smoke_assert( 'smoke_value' === get_signup_meta( $signup->signup_id, 'smoke_key', true ), 'Signup metadata did not round-trip.' );

wp_cache_set( 'last_changed', 'smoke-sentinel', 'signups' );
do_action( 'after_signup_user', 'smoke', 'smoke@example.test', 'key', array() );
wpus_smoke_assert( 'smoke-sentinel' !== wp_cache_get( 'last_changed', 'signups' ), 'The signup query cache was not invalidated.' );

delete_signup_meta( $signup->signup_id, 'smoke_key' );
$wpdb->delete( $wpdb->signups, array( 'signup_id' => $signup->signup_id ), array( '%d' ) );

echo 'WP User Signups integration smoke test passed on ' . ( is_multisite() ? 'multisite' : 'single-site' ) . ".\n";
