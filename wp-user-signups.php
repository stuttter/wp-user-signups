<?php

/**
 * Plugin Name: WP User Signups
 * Plugin URI:  https://wordpress.org/plugins/wp-user-signups/
 * Author:      John James Jacoby
 * Author URI:  https://profiles.wordpress.org/johnjamesjacoby/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: User signup management for WordPress
 * Version:     2.0.0
 * Text Domain: wp-user-signups
 * Domain Path: /wp-user-signups/assets/languages/
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Bail if core already supports this
if ( class_exists( 'WP_User_Signup' ) ) {
	return;
}

// Execute immediately
_wp_user_signups();

/**
 * Include the required files
 *
 * @since 1.0.0
 */
function _wp_user_signups() {

	// Get the plugin path
	$plugin_path = wp_user_signups_get_plugin_path();

	// Classes
	require_once $plugin_path . 'includes/classes/class-wp-db-table.php';
	require_once $plugin_path . 'includes/classes/class-wp-db-table-user-signups.php';
	require_once $plugin_path . 'includes/classes/class-wp-user-signup.php';
	require_once $plugin_path . 'includes/classes/class-wp-user-signup-query.php';

	// Required Files
	require_once $plugin_path . 'includes/functions/admin.php';
	require_once $plugin_path . 'includes/functions/assets.php';
	require_once $plugin_path . 'includes/functions/cache.php';
	require_once $plugin_path . 'includes/functions/common.php';
	require_once $plugin_path . 'includes/functions/capabilities.php';
	require_once $plugin_path . 'includes/functions/hooks.php';

	// Tables
	new WP_DB_Table_User_Signups();

	// Ensure cache is shared
	wp_cache_add_global_groups( array( 'user_signups' ) );
}

/**
 * Return the plugin root file
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_user_signups_get_plugin_file() {
	return __FILE__;
}

/**
 * Return the plugin path
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_user_signups_get_plugin_path() {
	return plugin_dir_path( __FILE__ ) . 'wp-user-signups/';
}

/**
 * Return the plugin URL
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_user_signups_get_plugin_url() {
	return plugin_dir_url( wp_user_signups_get_plugin_file() ) . 'wp-user-signups/';
}

/**
 * Return the asset version
 *
 * @since 1.0.0
 *
 * @return int
 */
function wp_user_signups_get_asset_version() {
	return 201703150001;
}
