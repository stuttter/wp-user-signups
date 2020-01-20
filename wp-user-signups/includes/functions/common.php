<?php

/**
 * User Sign-ups Functions
 *
 * @package Plugins/Signups/Functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Wrapper for admin URLs
 *
 * @since 1.0.0
 *
 * @param array $args
 * @return array
 */
function wp_signups_admin_url( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'page' => 'signups'
	) );

	// Location
	$admin_url = is_multisite()
		? network_admin_url( 'admin.php' )
		: admin_url( 'admin.php' );

	// Add query args
	$url = add_query_arg( $r, $admin_url );

	// Add args and return
	return apply_filters( 'wp_signups_admin_url', $url, $admin_url, $r, $args );
}

/**
 * Is this the all signups screen?
 *
 * @since 1.0.0
 *
 * @return bool
 */
function wp_signups_is_list_page() {
	return isset( $_GET['page'] ) && ( 'signups' === $_GET['page'] );
}

/**
 * Is this the network signup edit screen?
 *
 * @since 1.0.0
 *
 * @return bool
 */
function wp_signups_is_network_edit() {
	return isset( $_GET['referrer'] ) && ( 'network' === $_GET['referrer'] );
}

/**
 * Get all available signup statuses
 *
 * @since 1.0.0
 *
 * @return array
 */
function wp_signups_get_statuses() {

	// Pending count
	$query = new WP_Signup_Query();

	// Pending
	$pending = $query->query( array(
		'count'  => true,
		'active' => 0
	) );

	// Activated count
	$activated = $query->query( array(
		'count'  => true,
		'active' => 1
	) );

	// Filter and return
	return apply_filters( 'wp_signups_get_statuses', array(
		(object) array(
			'id'    => 'pending',
			'value' => 0,
			'name'  => _x( 'Pending', 'User sign-ups', 'wp-user-signups' ),
			'count' => $pending
		),
		(object) array(
			'id'    => 'activated',
			'value' => 1,
			'name'  => _x( 'Activated', 'User sign-ups', 'wp-user-signups' ),
			'count' => $activated
		),
	) );
}

/**
 * Sanitize requested signup ID values
 *
 * @since 1.0.0
 *
 * @param bool $single
 * @return mixed
 */
function wp_signups_sanitize_signup_ids( $single = false ) {

	// Default value
	$retval = array();

	// Map to int
	if ( isset( $_REQUEST['signup_ids'] ) ) {
		$retval = array_map( 'absint', (array) $_REQUEST['signup_ids'] );
	}

	// Return the first item
	if ( true === $single ) {
		$retval = reset( $retval );
	}

	// Filter & return
	return (array) apply_filters( 'wp_signups_sanitize_signup_ids', $retval );
}

/**
 * A wrapper for is_multisite() to expose multisite specific interface elements.
 *
 * This is useful for multi-installation environments that might be connected to
 * other multi-site environments, where global user-tables might be shared
 * between them.
 *
 * @since 1.3.0
 */
function wp_signups_is_multisite() {
	return (bool) apply_filters( 'wp_signups_is_multisite', is_multisite() );
}

/**
 * Retrieves signup data given a signup ID or signup object.
 *
 * Signup data will be cached and returned after being passed through a filter.
 *
 * @since 3.1.0
 *
 * @param WP_Signup|int|null $signup Optional. Signup to retrieve.
 * @return WP_Signup|null The signup object or null if not found.
 */
function get_signup( $signup = null ) {
	if ( empty( $signup ) ) {
		return null;
	}

	if ( $signup instanceof WP_Signup ) {
		$_signup = $signup;
	} elseif ( is_object( $signup ) ) {
		$_signup = new WP_Signup( $signup );
	} else {
		$_signup = WP_Signup::get_instance( $signup );
	}

	if ( ! $_signup ) {
		return null;
	}

	/**
	 * Fires after a signup is retrieved.
	 *
	 * @since 3.1.0
	 *
	 * @param WP_Signup $_signup Signup data.
	 */
	$_signup = apply_filters( 'get_signup', $_signup );

	return $_signup;
}
