<?php

/**
 * User Sign-ups Hooks
 *
 * @package Plugins/User/Signups/Hooks
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Assets
add_action( 'admin_print_styles-users_page_user_signups',         'wp_user_signups_admin_enqueue_scripts' );
add_action( 'admin_print_styles-users_page_user_signup_edit',     'wp_user_signups_admin_enqueue_scripts' );
add_action( 'admin_print_styles-users_page_user_signups', 'wp_user_signups_admin_enqueue_scripts' );

// Capabilities
add_filter( 'map_meta_cap', 'wp_user_signups_map_meta_cap', 10, 4 );

// Navigation
add_filter( 'network_edit_site_nav_links', 'wp_user_signups_add_signups_tab'   );
add_action( 'network_admin_menu',          'wp_user_signups_add_menu_item', 20 );
add_action( 'admin_menu',                  'wp_user_signups_add_menu_item', 20 );

// Screen Options
add_filter( 'set-screen-option', 'wp_user_signups_set_screen_option', 10, 3 );

// New Style
//add_action( 'admin_menu',                  'wp_user_signups_add_menu_item', 30 );

// Notices
add_action( 'wp_user_signups_admin_notices', 'wp_user_signups_output_admin_notices' );
