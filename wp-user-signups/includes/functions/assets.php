<?php

/**
 * User Sign-ups Assets
 *
 * @package Plugins/Signups/Assets
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue admin scripts
 *
 * @since 1.0.0
 */
function wp_signups_admin_enqueue_scripts() {

	// Set location & version for scripts & styles
	$src = wp_signups_get_plugin_url();
	$ver = wp_signups_get_asset_version();

	// Styles
	wp_enqueue_style( 'wp-user-signups', $src . 'assets/css/signups.css', array(), $ver );
}
