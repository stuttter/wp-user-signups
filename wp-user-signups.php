<?php

/**
 * Plugin Name: WP User Signups
 * Plugin URI:  https://wordpress.org/plugins/wp-user-signups/
 * Author:      John James Jacoby
 * Author URI:  https://profiles.wordpress.org/johnjamesjacoby/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: User signup management for WordPress
 * Version:     1.0.0
 * Text Domain: wp-user-signups
 * Domain Path: /assets/lang/
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Bail if core already supports this
if ( class_exists( 'WP_User_Signup' ) ) {
	return;
}

// Define the table variables
if ( empty( $GLOBALS['wpdb']->signups ) ) {
	$GLOBALS['wpdb']->signups            = $GLOBALS['wpdb']->base_prefix . 'signups';
	$GLOBALS['wpdb']->ms_global_tables[] = 'signups';
}

// Ensure cache is shared
wp_cache_add_global_groups( array( 'user_signups' ) );

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
	require_once $plugin_path . 'includes/classes/class-wp-user-signup.php';
	require_once $plugin_path . 'includes/classes/class-wp-user-signup-query.php';
	require_once $plugin_path . 'includes/classes/class-wp-user-signups-db-table.php';

	// Required Files
	require_once $plugin_path . 'includes/functions/admin.php';
	require_once $plugin_path . 'includes/functions/assets.php';
	require_once $plugin_path . 'includes/functions/cache.php';
	require_once $plugin_path . 'includes/functions/common.php';
	require_once $plugin_path . 'includes/functions/capabilities.php';
	require_once $plugin_path . 'includes/functions/hooks.php';
}

/**
 * Return the plugin's root file
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_user_signups_get_plugin_file() {
	return __FILE__;
}

/**
 * Return the plugin's path
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_user_signups_get_plugin_path() {
	return plugin_dir_path( __FILE__ ) . 'wp-user-signups/';
}

/**
 * Return the plugin's URL
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
	return 201611120001;
}
