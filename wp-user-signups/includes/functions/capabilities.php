<?php

/**
 * User Sign-ups Capabilities
 *
 * @package Plugins/User/Signups/Capabilities
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Map site signup meta capabilites
 *
 * @since 1.0.0
 *
 * @param array   $caps
 * @param string  $cap
 * @param int     $user_id
 */
function wp_user_signups_map_meta_cap( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// Map to 'create_users' for now
	switch ( $cap ) {

		// All
		case 'create_user_signups' :
		case 'edit_user_signups' :
		case 'manage_user_signups' :

		// Single
		case 'activate_signup' :
		case 'delete_signup' :
		case 'edit_signup' :
		case 'resend_signup' :
			$caps = is_multisite()
				? array( $cap )
				: array( 'manage_options' );
	}

	// Filter and return
	return apply_filters( 'wp_user_signups_map_meta_cap', $caps, $cap, $user_id, $args );
}
