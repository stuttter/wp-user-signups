<?php

/**
 * Plugin Name: WP Signups
 * Plugin URI:  https://wordpress.org/plugins/wp-user-signups/
 * Author:      John James Jacoby
 * Author URI:  https://profiles.wordpress.org/johnjamesjacoby/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: Signup management for WordPress
 * Version:     5.0.0
 * Text Domain: wp-user-signups
 * Domain Path: /wp-user-signups/assets/languages/
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Bail if core already supports this
if ( class_exists( 'WP_Signup' ) ) {
	return;
}

// Execute immediately
_wp_signups();

/**
 * Include the required files
 *
 * @since 1.0.0
 */
function _wp_signups() {

	// Get the plugin path
	$plugin_path = wp_signups_get_plugin_path();

	// Classes
	require_once $plugin_path . 'includes/classes/class-wp-db-table.php';
	require_once $plugin_path . 'includes/classes/class-wp-db-table-signups.php';
	require_once $plugin_path . 'includes/classes/class-wp-db-table-signupmeta.php';
	require_once $plugin_path . 'includes/classes/class-wp-signup.php';
	require_once $plugin_path . 'includes/classes/class-wp-signup-query.php';

	// Required Files
	require_once $plugin_path . 'includes/functions/admin.php';
	require_once $plugin_path . 'includes/functions/assets.php';
	require_once $plugin_path . 'includes/functions/cache.php';
	require_once $plugin_path . 'includes/functions/common.php';
	require_once $plugin_path . 'includes/functions/capabilities.php';
	require_once $plugin_path . 'includes/functions/metadata.php';
	require_once $plugin_path . 'includes/functions/hooks.php';

	// Tables
	new WP_DB_Table_Signups();
	new WP_DB_Table_Signupmeta();

	// Ensure cache is shared
	wp_cache_add_global_groups( array( 'signups', 'signupmeta' ) );
}

/**
 * Return the plugin root file
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_signups_get_plugin_file() {
	return __FILE__;
}

/**
 * Return the plugin path
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_signups_get_plugin_path() {
	return plugin_dir_path( __FILE__ ) . 'wp-user-signups/';
}

/**
 * Return the plugin URL
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_signups_get_plugin_url() {
	return plugin_dir_url( wp_signups_get_plugin_file() ) . 'wp-user-signups/';
}

/**
 * Return the asset version
 *
 * @since 1.0.0
 *
 * @return int
 */
function wp_signups_get_asset_version() {
	return 202005050001;
}
