<?php

/**
 * User Sign-ups Cache
 *
 * @package Plugins/Signups/Cache
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
 * @see update_signup_cache()
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array $ids               ID list.
 * @param bool  $update_meta_cache Whether to update site alias cache. Default true.
 */
function _prime_signup_caches( $ids = array(), $update_meta_cache = true ) {
	global $wpdb;

	$non_cached_ids = _get_non_cached_ids( $ids, 'signups' );
	if ( ! empty( $non_cached_ids ) ) {
		$fresh_signups = $wpdb->get_results( sprintf( "SELECT * FROM {$wpdb->signups} WHERE signup_id IN (%d)", join( ",", array_map( 'intval', $non_cached_ids ) ) ) );

		update_signup_cache( $fresh_signups, $update_meta_cache );
	}
}

/**
 * Updates user sign-ups in cache.
 *
 * @since 1.0.0
 *
 * @param array $signups           Array of user signup objects.
 * @param bool  $update_meta_cache Whether to update site alias cache. Default true.
 */
function update_signup_cache( $signups = array(), $update_meta_cache = true ) {

	// Bail if no signups
	if ( empty( $signups ) ) {
		return;
	}

	// Loop through signups & add them to cache group
	foreach ( $signups as $signup ) {
		wp_cache_set( $signup->signup_id, $signup, 'signups' );
	}

	// Maybe update signup meta cache
	if ( true === $update_meta_cache ) {
		update_signupmeta_cache( wp_list_pluck( $signups, 'signup_id' ) );
	}
}

/**
 * Clean the user signup cache
 *
 * @since 1.0.0
 *
 * @param int|WP_Signup $signup Signup ID or signup object to remove from the cache
 */
function clean_signup_cache( $signup ) {
	global $_wp_suspend_cache_invalidation;

	// Bail if cache invalidation is suspended
	if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
		return;
	}

	// Get signup, and bail if not found
	$signup = WP_Signup::get_instance( $signup );
	if ( empty( $signup ) || is_wp_error( $signup ) ) {
		return;
	}

	// Delete signup from cache group
	wp_cache_delete( $signup->signup_id , 'signups' );

	/**
	 * Fires immediately after a user signup has been removed from the object cache.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $signup_id Alias ID.
	 * @param WP_Site $signup    Alias object.
	 */
	do_action( 'clean_signup_cache', $signup->signup_id, $signup );

	wp_cache_set( 'last_changed', microtime(), 'signups' );
}
