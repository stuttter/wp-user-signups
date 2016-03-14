<?php

/**
 * User Signups Hooks
 *
 * @package Plugins/User/Signups/Hooks
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Assets
add_action( 'admin_print_styles-users_page_user_signups',         'wp_user_signups_admin_enqueue_scripts' );
add_action( 'admin_print_styles-users_page_user_signup_edit',     'wp_user_signups_admin_enqueue_scripts' );
add_action( 'admin_print_styles-users_page_network_user_signups', 'wp_user_signups_admin_enqueue_scripts' );

// Capabilities
add_filter( 'map_meta_cap', 'wp_user_signups_map_meta_cap', 10, 4 );

// Columns
add_action( 'manage_sites_custom_column', 'wp_user_signups_output_signups_column', 10, 2 );
add_filter( 'wpmu_blogs_columns',         'wp_user_signups_add_signups_column'  );

// Navigation
add_filter( 'network_edit_site_tabs', 'wp_user_signups_add_site_tab' );
add_action( 'admin_menu',             'wp_user_signups_add_menu_item', 30 );
add_action( 'network_admin_menu',     'wp_user_signups_add_menu_item', 20 );

// Notices
add_action( 'wp_user_signups_admin_notices', 'wp_user_signups_output_admin_notices' );
