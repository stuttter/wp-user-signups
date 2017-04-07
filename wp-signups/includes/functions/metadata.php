<?php

/**
 * Signup Meta Functions
 *
 * @package Plugins/Signups/Meta/Functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add metadata to a signup.
 *
 * @since 3.1.0
 *
 * @param int    $id         Alias ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @param bool   $unique     Optional. Whether the same key should not be added.
 *                           Default false.
 * @return int|false Meta ID on success, false on failure.
 */
function add_signup_meta( $id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'signup', $id, $meta_key, $meta_value, $unique );
}

/**
 * Remove from a signup, metadata matching key and/or value.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @since 3.1.0
 *
 * @param int    $id         Alias ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Optional. Metadata value. Must be serializable if
 *                           non-scalar. Default empty.
 * @return bool True on success, false on failure.
 */
function delete_signup_meta( $id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'signup', $id, $meta_key, $meta_value );
}

/**
 * Retrieve from a signup, metadata value by key.
 *
 * @since 3.1.0
 *
 * @param int    $id        Alias ID.
 * @param string $meta_key  Optional. The meta key to retrieve. By default, returns
 *                          data for all keys. Default empty.
 * @param bool   $single    Optional. Whether to return a single value. Default false.
 * @return mixed Will be an array if $single is false. Will be value of meta data
 *               field if $single is true.
 */
function get_signup_meta( $id, $meta_key = '', $single = false ) {
	return get_metadata( 'signup', $id, $meta_key, $single );
}

/**
 * Update metadata for a signup ID, and/or key, and/or value.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and signup ID.
 *
 * If the meta field for the signup does not exist, it will be added.
 *
 * @since 3.1.0
 *
 * @param int    $id         Alias ID.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @param mixed  $prev_value Optional. Previous value to check before removing.
 *                           Default empty.
 * @return int|bool Meta ID if the key didn't exist, true on successful update,
 *                  false on failure.
 */
function update_signup_meta( $id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'signup', $id, $meta_key, $meta_value, $prev_value );
}

/**
 * Updates metadata cache for list of signup IDs.
 *
 * Performs SQL query to retrieve the metadata for the signup IDs and
 * updates the metadata cache for the signup. Therefore, the functions,
 * which call this function, do not need to perform SQL queries on their own.
 *
 * @since 3.1.0
 *
 * @param array $ids List of signup IDs.
 * @return array|false Returns false if there is nothing to update or an array
 *                     of metadata.
 */
function update_signupmeta_cache( $ids ) {
	return update_meta_cache( 'signup', $ids );
}
