<?php

/**
 * User Sign-ups Hooks
 *
 * @package Plugins/Signups/Hooks
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Assets
add_action( 'admin_print_styles-toplevel_page_signups',     'wp_signups_admin_enqueue_scripts' );
add_action( 'admin_print_styles-toplevel_page_signup_edit', 'wp_signups_admin_enqueue_scripts' );
add_action( 'admin_print_styles-users_page_signups',        'wp_signups_admin_enqueue_scripts' );
add_action( 'admin_print_styles-users_page_signup_edit',    'wp_signups_admin_enqueue_scripts' );

// Capabilities
add_filter( 'map_meta_cap', 'wp_signups_map_meta_cap', 10, 4 );

// Navigation
add_action( 'network_admin_menu', 'wp_signups_add_menu_item', 20 );
add_action( 'admin_menu',         'wp_signups_add_menu_item', 20 );

// Screen Options
add_filter( 'set-screen-option', 'wp_signups_set_screen_option', 10, 3 );

// Notices
add_action( 'wp_signups_admin_notices', 'wp_signups_output_admin_notices' );
