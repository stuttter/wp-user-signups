<?php

/**
 * User Sign-ups Functions
 *
 * @package Plugins/Users/Signups/Functions
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
function wp_user_signups_admin_url( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'page' => 'user_signups'
	) );

	// Location
	$admin_url = is_multisite()
		? network_admin_url( 'admin.php' )
		: admin_url( 'index.php' );

	// Add query args
	$url = add_query_arg( $r, $admin_url );

	// Add args and return
	return apply_filters( 'wp_user_signups_admin_url', $url, $admin_url, $r, $args );
}

/**
 * Is this the all signups screen?
 *
 * @since 1.0.0
 *
 * @return bool
 */
function wp_user_signups_is_list_page() {
	return isset( $_GET['page'] ) && ( 'user_signups' === $_GET['page'] );
}

/**
 * Is this the network signup edit screen?
 *
 * @since 1.0.0
 *
 * @return bool
 */
function wp_user_signups_is_network_edit() {
	return isset( $_GET['referrer'] ) && ( 'network' === $_GET['referrer'] );
}

/**
 * Get all available site signup statuses
 *
 * @since 1.0.0
 *
 * @return array
 */
function wp_user_signups_get_statuses() {

	// Pending count
	$query = new WP_User_Signup_Query();

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
	return apply_filters( 'wp_user_signups_get_statuses', array(
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
function wp_user_signups_sanitize_signup_ids( $single = false ) {

	// Default value
	$retval = array();

	//
	if ( isset( $_REQUEST['signup_ids'] ) ) {
		$retval = array_map( 'absint', (array) $_REQUEST['signup_ids'] );
	}

	// Return the first item
	if ( true === $single ) {
		$retval = reset( $retval );
	}

	// Filter & return
	return apply_filters( 'wp_user_signups_sanitize_signup_ids', $retval );
}
