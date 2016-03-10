<?php

/**
 * User Signups Capabilities
 *
 * @package Plugins/User/Signups/Capabilities
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Map site alias meta capabilites
 *
 * @since 0.1.0
 *
 * @param array   $caps
 * @param string  $cap
 * @param int     $user_id
 */
function wp_user_signups_map_meta_cap( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	if ( 'manage_signups' === $cap ) {
		$caps = array( 'create_users' );
	}

	// Filter and return
	return apply_filters( 'wp_user_signups_map_meta_cap', $caps, $cap, $user_id, $args );
}
