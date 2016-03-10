<?php

/**
 * User Signups Hooks
 *
 * @package Plugins/User/Signups/Hooks
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Assets
add_action( 'admin_enqueue_scripts', 'wp_user_signups_admin_enqueue_scripts' );

// Capabilities
add_filter( 'map_meta_cap', 'wp_user_signups_map_meta_cap', 10, 4 );

// Columns
add_action( 'manage_sites_custom_column', 'wp_user_signups_output_site_list_column', 10, 2 );
add_filter( 'wpmu_blogs_columns',         'wp_user_signups_add_site_list_column'  );

// Navigation
add_filter( 'network_edit_site_tabs', 'wp_user_signups_add_site_tab' );
add_action( 'admin_menu',             'wp_user_signups_add_menu_item', 30 );
add_action( 'network_admin_menu',     'wp_user_signups_add_menu_item', 10 );

// Notices
add_action( 'wp_user_signups_admin_notices', 'wp_user_signups_output_admin_notices' );
