<?php

/**
 * User Sign-ups Cache
 *
 * @package Plugins/User/Signups/Cache
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Adds any user signups from the given ids to the cache that do not already
 * exist in cache.
 *
 * @since 1.0.0
 * @access private
 *
 * @see update_user_signup_cache()
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array $ids ID list.
 */
function _prime_user_signup_caches( $ids = array() ) {
	global $wpdb;

	$non_cached_ids = _get_non_cached_ids( $ids, 'user_signups' );
	if ( ! empty( $non_cached_ids ) ) {
		$fresh_signups = $wpdb->get_results( sprintf( "SELECT * FROM {$wpdb->signups} WHERE signup_id IN (%d)", join( ",", array_map( 'intval', $non_cached_ids ) ) ) );

		update_user_signup_cache( $fresh_signups );
	}
}

/**
 * Updates user sign-ups in cache.
 *
 * @since 1.0.0
 *
 * @param array $signups Array of user signup objects.
 */
function update_user_signup_cache( $signups = array() ) {

	// Bail if no signups
	if ( empty( $signups ) ) {
		return;
	}

	// Loop through signups & add them to cache group
	foreach ( $signups as $signup ) {
		wp_cache_set( $signup->signup_id, $signup, 'user_signups' );
	}
}

/**
 * Clean the user signup cache
 *
 * @since 1.0.0
 *
 * @param int|WP_User_Signup $ignup Signup ID or signup object to remove from the cache
 */
function clean_user_signup_cache( $signup ) {
	global $_wp_suspend_cache_invalidation;

	// Bail if cache invalidation is suspended
	if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
		return;
	}

	// Get signup, and bail if not found
	$signup = WP_User_Signup::get_instance( $signup );
	if ( empty( $signup ) || is_wp_error( $signup ) ) {
		return;
	}

	// Delete signup from cache group
	wp_cache_delete( $signup->signup_id , 'user_signups' );

	/**
	 * Fires immediately after a user signup has been removed from the object cache.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $signup_id Alias ID.
	 * @param WP_Site $signup    Alias object.
	 */
	do_action( 'clean_user_signup_cache', $signup->signup_id, $signup );

	wp_cache_set( 'last_changed', microtime(), 'user_signups' );
}
