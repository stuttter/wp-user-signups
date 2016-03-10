<?php

/**
 * User Signups Assets
 *
 * @package Plugins/User/Signups/Assets
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue admin scripts
 *
 * @since 0.1.0
 */
function wp_user_signups_admin_enqueue_scripts() {

	// Set location & version for scripts & styles
	$src = wp_user_signups_get_plugin_url();
	$ver = wp_user_signups_get_asset_version();

	// Styles
	wp_enqueue_style( 'wp-user-signups', $src . 'assets/css/user-signups.css', array(), $ver );
}
