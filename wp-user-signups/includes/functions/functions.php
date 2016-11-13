<?php

/**
 * User Sign-ups Functions
 *
 * @package Plugins/User/Signups/Functions
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

	// Network admin?
	$network_signups = wp_user_signups_is_network_list();

	// Parse args
	$r = wp_parse_args( $args, array(
		'page' => ( true === $network_signups )
			? 'user_signups'
			: 'user_signups',
	) );

	// File
	$file = ( true === $network_signups )
		? 'admin.php'
		: 'sites.php';

	// Override for network edit
	if ( wp_user_signups_is_network_edit() && empty( $args['page'] ) ) {
		$file = 'admin.php';
		$r['page'] = 'user_signups';
	}

	// Location
	$admin_url = is_network_admin()
		? network_admin_url( $file )
		: admin_url( 'index.php' );

	// Add query args
	$url = add_query_arg( $r, $admin_url );

	// Add args and return
	return apply_filters( 'wp_user_signups_admin_url', $url, $admin_url, $r, $args );
}
