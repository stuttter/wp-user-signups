<?php

/**
 * Plugin Name: WP User Signups
 * Plugin URI:  http://wordpress.org/plugins/wp-user-signups/
 * Author:      John James Jacoby
 * Author URI:  https://profiles.wordpress.org/johnjamesjacoby/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: User signup management for WordPress
 * Version:     0.1.0
 * Text Domain: wp-user-signups
 * Domain Path: /assets/lang/
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Define the table variables
if ( empty( $GLOBALS['wpdb']->signups ) ) {
	$GLOBALS['wpdb']->signups            = $GLOBALS['wpdb']->base_prefix . 'signups';
	$GLOBALS['wpdb']->ms_global_tables[] = 'signups';
}

// Ensure cache is shared
wp_cache_add_global_groups( array( 'user_signups' ) );

// Get the plugin path
$plugin_path = dirname( __FILE__ ) . '/';

// Classes
require_once $plugin_path . 'includes/class-wp-user-signup.php';
require_once $plugin_path . 'includes/class-wp-user-signups-db-table.php';

// Required Files
require_once $plugin_path . 'includes/admin.php';
require_once $plugin_path . 'includes/assets.php';
require_once $plugin_path . 'includes/capabilities.php';
require_once $plugin_path . 'includes/functions.php';
require_once $plugin_path . 'includes/hooks.php';

/**
 * Return the plugin's root file
 *
 * @since 0.1.0
 *
 * @return string
 */
function wp_user_signups_get_plugin_file() {
	return __FILE__;
}

/**
 * Return the plugin's URL
 *
 * @since 0.1.0
 *
 * @return string
 */
function wp_user_signups_get_plugin_url() {
	return plugin_dir_url( wp_user_signups_get_plugin_file() );
}

/**
 * Return the asset version
 *
 * @since 0.1.0
 *
 * @return int
 */
function wp_user_signups_get_asset_version() {
	return 201603100001;
}
