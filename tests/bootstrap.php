<?php

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

$GLOBALS['wpus_test'] = array();

function wpus_test_call( $name, $arguments ) {
	$GLOBALS['wpus_test']['calls'][ $name ][] = $arguments;

	if ( isset( $GLOBALS['wpus_test']['callbacks'][ $name ] ) ) {
		return call_user_func_array( $GLOBALS['wpus_test']['callbacks'][ $name ], $arguments );
	}

	return isset( $GLOBALS['wpus_test']['returns'][ $name ] )
		? $GLOBALS['wpus_test']['returns'][ $name ]
		: null;
}

function wp_parse_args( $args, $defaults = array() ) {
	if ( is_string( $args ) ) {
		parse_str( $args, $parsed );
		$args = $parsed;
	}

	return array_merge( $defaults, (array) $args );
}

function apply_filters( $hook, $value, ...$arguments ) {
	$filter_arguments = array_merge( array( $value ), $arguments );
	$result           = wpus_test_call( 'apply_filters:' . $hook, $filter_arguments );

	return null === $result ? $value : $result;
}

function do_action( $hook, ...$arguments ) {
	wpus_test_call( 'do_action:' . $hook, $arguments );
}

function absint( $value ) {
	return abs( (int) $value );
}

function sanitize_user( $value, $strict = false ) {
	return strtolower( preg_replace( '/[^a-z0-9_-]/i', '', $value ) );
}

function sanitize_email( $value ) {
	return filter_var( trim( $value ), FILTER_SANITIZE_EMAIL );
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function maybe_serialize( $value ) {
	return is_array( $value ) || is_object( $value ) ? serialize( $value ) : $value;
}

function esc_html__( $text, $domain = 'default' ) {
	return $text;
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

function is_multisite() {
	return ! empty( $GLOBALS['wpus_test']['multisite'] );
}

function network_admin_url( $path ) {
	return 'https://network.example.test/wp-admin/network/' . ltrim( $path, '/' );
}

function admin_url( $path ) {
	return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
}

function add_query_arg( $args, $url ) {
	return $url . '?' . http_build_query( $args );
}

function wp_cache_set_last_changed( $group ) {
	return wpus_test_call( __FUNCTION__, array( $group ) );
}

function wp_cache_set( $key, $value, $group = '', $expire = 0 ) {
	return wpus_test_call( __FUNCTION__, array( $key, $value, $group, $expire ) );
}

function add_metadata( ...$arguments ) {
	return wpus_test_call( __FUNCTION__, $arguments );
}

function delete_metadata( ...$arguments ) {
	return wpus_test_call( __FUNCTION__, $arguments );
}

function get_metadata( ...$arguments ) {
	return wpus_test_call( __FUNCTION__, $arguments );
}

function update_metadata( ...$arguments ) {
	return wpus_test_call( __FUNCTION__, $arguments );
}

function update_meta_cache( ...$arguments ) {
	return wpus_test_call( __FUNCTION__, $arguments );
}

class WP_Error {
	public function __construct() {}
}

require_once dirname( __DIR__ ) . '/wp-user-signups/includes/classes/class-wp-signup.php';
require_once dirname( __DIR__ ) . '/wp-user-signups/includes/functions/cache.php';
require_once dirname( __DIR__ ) . '/wp-user-signups/includes/functions/common.php';
require_once dirname( __DIR__ ) . '/wp-user-signups/includes/functions/metadata.php';
