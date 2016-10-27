<?php

/**
 * User Sign-ups Functions
 *
 * @package Plugins/Users/Signups/Functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Is this the all aliases screen?
 *
 * @since 1.0.0
 *
 * @return bool
 */
function wp_user_signups_is_network_list() {
	return isset( $_GET['page'] ) && ( 'network_user_signups' === $_GET['page'] );
}

/**
 * Is this the network alias edit screen?
 *
 * @since 1.0.0
 *
 * @return bool
 */
function wp_user_signups_is_network_edit() {
	return isset( $_GET['referrer'] ) && ( 'network' === $_GET['referrer'] );
}

/**
 * Get all available site alias statuses
 *
 * @since 1.0.0
 *
 * @return array
 */
function wp_user_signups_get_statuses() {
	return apply_filters( 'wp_user_signups_get_statuses', array(
		(object) array(
			'id'    => 'pending',
			'value' => 0,
			'name'  => _x( 'Pending', 'user sign-ups', 'wp-user-signups' )
		),
		(object) array(
			'id'    => 'active',
			'value' => 1,
			'name'  => _x( 'Active', 'user sign-ups', 'wp-user-signups' )
		),
	) );
}

/**
 * Sanitize requested alias ID values
 *
 * @since 1.0.0
 *
 * @param bool $single
 * @return mixed
 */
function wp_user_signups_sanitize_alias_ids( $single = false ) {

	// Default value
	$retval = array();

	//
	if ( isset( $_REQUEST['alias_ids'] ) ) {
		$retval = array_map( 'absint', (array) $_REQUEST['alias_ids'] );
	}

	// Return the first item
	if ( true === $single ) {
		$retval = reset( $retval );
	}

	// Filter & return
	return apply_filters( 'wp_user_signups_sanitize_alias_ids', $retval );
}
