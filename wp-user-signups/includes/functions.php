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

	// Network admin?
	$network_signups = wp_user_signups_is_network_list();

	// Parse args
	$r = wp_parse_args( $args, array(
		'id'   => wp_user_signups_get_site_id(),
		'page' => ( true === $network_signups )
			? 'network_user_signups'
			: 'user_signups',
	) );

	// File
	$file = ( true === $network_signups )
		? 'users.php'
		: 'sites.php';

	// Override for network edit
	if ( wp_user_signups_is_network_edit() ) {
		$file = 'admin.php';
		$r['page'] = 'network_user_signups';
	}

	// Location
	$admin_url = is_network_admin()
		? network_admin_url( $file )
		: admin_url( 'index.php' );

	// Unset ID if viewing network admin
	if ( true === $network_signups ) {
		unset( $r['id'] );
	}

	// Add query args
	$url = add_query_arg( $r, $admin_url );

	// Add args and return
	return apply_filters( 'wp_user_signups_admin_url', $url, $admin_url, $r, $args );
}
