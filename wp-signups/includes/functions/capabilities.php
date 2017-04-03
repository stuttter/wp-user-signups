<?php

/**
 * User Sign-ups Capabilities
 *
 * @package Plugins/Signups/Capabilities
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Map signup meta capabilites
 *
 * @since 1.0.0
 *
 * @param array   $caps
 * @param string  $cap
 * @param int     $user_id
 */
function wp_signups_map_meta_cap( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// Maybe map to 'manage_options' for single-site installations
	switch ( $cap ) {

		// All
		case 'create_signups' :
		case 'edit_signups' :
		case 'manage_signups' :

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
	return apply_filters( 'wp_signups_map_meta_cap', $caps, $cap, $user_id, $args );
}
