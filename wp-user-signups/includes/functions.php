<?php

/**
 * User Sign-ups Functions
 *
 * @package Plugins/User/Signups/Functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the site ID being modified
 *
 * @since 1.0.0
 *
 * @return int
 */
function wp_user_signups_get_site_id() {

	// Set the default
	$default_id = is_blog_admin()
		? get_current_blog_id()
		: 0;

	// Get site ID being requested
	$site_id = isset( $_REQUEST['id'] )
		? intval( $_REQUEST['id'] )
		: $default_id;

	// Return the blog ID
	return (int) $site_id;
}

/**
 * Wrapper for admin URLs
 *
 * @since 1.0.0
 *
 * @param array $args
 * @return array
 */
function wp_user_signups_admin_url( $args = array() ) {

	// Action
	if ( is_network_admin() ) {
		$admin_url = network_admin_url( 'users.php' );
	} else {
		$admin_url = admin_url( 'index.php' );
	}

	// Add args and return
	return add_query_arg( $args, $admin_url );
}
