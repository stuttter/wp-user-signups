<?php

/**
 * User Sign-ups Admin
 *
 * @package Plugins/User/Signups/Admin
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add menus in network and site dashboards
 *
 * @since 1.0.0
 */
function wp_user_signups_add_menu_item() {

	// Style switcher
	$style = 'core';

	// Define empty array
	$hooks = array();

	// Core style
	if ( ( 'core' === $style ) && is_network_admin() ) {

		// Network management of all signups
		$hooks[] = add_menu_page( esc_html__( 'Sign ups', 'wp-user-signups' ), esc_html__( 'Sign ups', 'wp-user-signups' ), 'manage_network_signups', 'network_user_signups', 'wp_user_signups_output_network_list_page', 'dashicons-flag', 11 );
		$hooks[] = add_submenu_page( 'network_user_signups', esc_html__( 'Add New Signup', 'wp-user-signups' ), esc_html__( 'Add New', 'wp-user-signups' ), 'edit_user_signups',      'user_signup_edit',     'wp_user_signups_output_edit_page'                                   );

		// Remove if user cannot create
		if ( ! current_user_can( 'create_user_signups' ) ) {
			remove_submenu_page( 'users.php', 'user_signup_edit' );
		}

		// Load the list table
		foreach ( $hooks as $hook ) {
			add_action( "load-{$hook}", 'wp_user_signups_handle_actions'            );
			add_action( "load-{$hook}", 'wp_user_signups_load_list_table'           );
			//add_action( "load-{$hook}", 'wp_user_signups_fix_hidden_menu_highlight' );
		}

	// New style
	} elseif ( 'new' === $style ) {

		// Network admin page
		if ( is_network_admin() ) {
			$hooks[] = add_submenu_page( 'sites.php', esc_html__( 'Sign ups', 'wp-user-signups' ), esc_html__( 'Sign ups', 'wp-user-signups' ), 'manage_user_signups', 'user_signups',     'wp_user_signups_output_list_page' );
			$hooks[] = add_submenu_page( 'users.php', esc_html__( 'Sign ups', 'wp-user-signups' ), esc_html__( 'Sign ups', 'wp-user-signups' ), 'edit_user_signups',   'user_signup_edit', 'wp_user_signups_output_edit_page' );
			remove_submenu_page( 'sites.php', 'user_signups'     );
			remove_submenu_page( 'users.php', 'user_signup_edit' );

			// Network management of all signups
			$hooks[] = add_submenu_page( 'users.php', esc_html__( 'Sign ups', 'wp-user-signups' ), esc_html__( 'Sign ups', 'wp-user-signups' ), 'manage_network_signups', 'network_user_signups', 'wp_user_signups_output_network_list_page', 'dashicons-randomize', 6 );

		// Blog admin page
		} elseif ( is_blog_admin() ) {
			$hooks[] = add_users_page( esc_html__( 'Sign ups', 'wp-user-signups' ), esc_html__( 'Sign ups', 'wp-user-signups' ), 'manage_signups', 'user_signups',     'wp_user_signups_output_list_page' );
			$hooks[] = add_users_page( esc_html__( 'Sign ups', 'wp-user-signups' ), esc_html__( 'Sign ups', 'wp-user-signups' ), 'edit_signups',   'user_signup_edit', 'wp_user_signups_output_edit_page' );
			remove_submenu_page( 'users.php', 'user_signup_edit' );
		}

		// Load the list table
		foreach ( $hooks as $hook ) {
			add_action( "load-{$hook}", 'wp_user_signups_handle_actions'            );
			add_action( "load-{$hook}", 'wp_user_signups_load_list_table'           );
			add_action( "load-{$hook}", 'wp_user_signups_fix_hidden_menu_highlight' );
		}
	}
}

/**
 * Load the list table and populate some essentials
 *
 * @since 1.0.0
 */
function wp_user_signups_load_list_table() {
	global $wp_list_table;

	// Include the list table class
	require_once wp_user_signups_get_plugin_path() . 'includes/classes/class-wp-user-signups-list-table.php';

	// Get site ID being requested
	$site_id = wp_user_signups_get_site_id();

	// Create a new list table object
	$wp_list_table = new WP_User_Signup_List_Table( array(
		'site_id' => $site_id
	) );

	$wp_list_table->prepare_items( $site_id );
}

/**
 * Override network files, to correct main submenu navigation highlighting
 *
 * @since 1.0.0
 *
 * @global string $parent_file
 * @global string $submenu_file
 */
function wp_user_signups_fix_hidden_menu_highlight() {
	global $parent_file, $submenu_file;

	// Network admin
	if ( is_network_admin() ) {
		if ( wp_user_signups_is_network_edit() ) {
			$parent_file  = 'network_user_signups';
			$submenu_file = null;
		} elseif ( ! wp_user_signups_is_network_list() ) {
			$parent_file  = 'sites.php';
			$submenu_file = 'sites.php';
		}

	// Blog admin
	} elseif ( is_blog_admin() ) {
		$parent_file  = 'index.php';
		$submenu_file = 'user_signups';
	}
}

/**
 * Add tab to end of tabs array
 *
 * @since 1.0.0
 *
 * @param array $tabs
 * @return array
 */
function wp_user_signups_add_signups_tab( $tabs = array() ) {

	// "Sign ups" tab
	$tabs['user-signups'] = array(
		'label' => esc_html__( 'Sign ups', 'wp-user-signups' ),
		'url'   => add_query_arg( array( 'page' => 'user_signups' ), 'sites.php' ),
		'cap'   => 'manage_user_signups'
	);

	// Return tabs
	return $tabs;
}

/**
 * Output the admin page header
 *
 * @since 1.0.0
 *
 * @param  int  $signup_id  Signup ID
 */
function wp_user_signups_output_page_header( $signup_id = 0 ) {
	global $title;

	// List
	if ( wp_user_signups_is_network_list() ) {

		// With "Add new" link
		if ( current_user_can( 'create_user_signups' ) ) {
			$link_url = wp_user_signups_admin_url( array(
				'page' => 'user_signup_edit'
			) );
			$title_link = '<a href="' . esc_url( $link_url ) . '" class="page-title-action">' . esc_html__( 'Add New', 'wp-user-signups' ) . '</a>';
			$title =  sprintf( esc_html__( 'Sign ups %s', 'wp-user-signups' ), $title_link );

		// Without "Add new" link
		} else {
			$title = esc_html__( 'Sign ups', 'wp-user-signups' );
		}

	// Add/Edit
	} else {

		// Add
		if ( empty( $signup_id ) || ! empty( $_POST['_wpnonce'] ) ) {
			$title = esc_html__( 'Add New Sign up', 'wp-user-signups' );

		// Edit
		} else {
			$title = esc_html__( 'Edit Sign up', 'wp-user-signups' );
		}
	}

	// This is copied from WordPress core
	?><div class="wrap">
		<h1 id="edit-signup"><?php echo $title; // may contain HTML ?></h1><?php

		// Admin notices
		do_action( 'wp_user_signups_admin_notices' );
}

/**
 * Close the .wrap div
 *
 * @since 1.0.0
 */
function wp_user_signups_output_page_footer() {
	?></div><?php
}

/**
 * Handle submission of the list page
 *
 * Handles bulk actions for the list page. Redirects back to itself after
 * processing, and exits.
 *
 * @since 1.0.0
 *
 * @param  string  $action  Action to perform
 */
function wp_user_signups_handle_actions() {

	// Bail if no action
	if ( ! empty( $_REQUEST['action'] ) && empty(  $_REQUEST['bulk_action2'] ) ) {
		$request_action = $_REQUEST['action'];
	} elseif ( ! empty( $_REQUEST['bulk_action'] ) ) {
		$request_action = $_REQUEST['bulk_action'];
	} elseif ( ! empty( $_REQUEST['bulk_action2'] ) ) {
		$request_action = $_REQUEST['bulk_action2'];
	} else {
		return;
	}

	// Get action
	$action      = sanitize_key( $request_action );
	$site_id     = wp_user_signups_get_site_id();
	$redirect_to = remove_query_arg( array( 'did_action', 'processed', 'signups', '_wpnonce' ), wp_get_referer() );

	// Maybe fallback redirect
	if ( empty( $redirect_to ) ) {
		$redirect_to = wp_user_signups_admin_url();
	}

	// Get signups being bulk actioned
	$processed = array();
	$signups   = ! empty( $_REQUEST['signups'] )
		? array_map( 'absint', (array) $_REQUEST['signups'] )
		: array();

	// Redirect args
	if ( is_network_admin() ) {
		if ( wp_user_signups_is_network_list() ) {
			$args = array(
				'page'       => 'network_user_signups',
				'did_action' => $action
			);
		} else {
			$args = array(
				'page'       => 'user_signups',
				'id'         => $site_id,
				'did_action' => $action
			);
		}
	} else {
		$args = array(
			'page'       => 'network_user_signups',
			'did_action' => $action
		);
	}

	// What's the action?
	switch ( $action ) {

		// Bulk activate
		case 'activate' :
			foreach ( $signups as $signup_id ) {
				$signup = WP_User_Signup::get( $signup_id );

				// Skip erroneous signups
				if ( is_wp_error( $signup ) ) {
					continue;
				}

				// Try to activate
				$activated = $signup->activate();

				// Maybe add to processed
				if ( ! is_wp_error( $activated ) ) {
					$processed[] = $signup_id;
				}
			}
			break;

		// Bulk resend
		case 'resend':
			foreach ( $signups as $signup_id ) {
				$signup = WP_User_Signup::get( $signup_id );

				// Skip erroneous signups
				if ( is_wp_error( $signup ) ) {
					continue;
				}

				// Resend activation emails
				$signup->notify();
				$processed[] = $signup_id;
			}
			break;

		// Single/Bulk Delete
		case 'delete':
			$args['signups'] = array();

			foreach ( $signups as $signup_id ) {
				$signup = WP_User_Signup::get( $signup_id );

				// Skip erroneous signups
				if ( is_wp_error( $signup ) ) {
					continue;
				}

				// Try to delete
				$deleted = $signup->delete();

				// Signups don't exist after we delete them
				if ( true === $deleted ) {
					$args['signups'][] = $signup->signup_id;
					$processed[]       = $signup_id;
				}
			}

			break;

		// Single Edit
		case 'edit' :
			check_admin_referer( "user_signup_edit-{$site_id}" );

			$signup_id = $signups[0];
			$signup    = WP_User_Signup::get( $signup_id );

			if ( is_wp_error( $signup ) ) {
				$messages[] = $signup->get_error_message();
				return $messages;
			}

			// Update
			$values = wp_unslash( $_POST );
			$result = $signup->update( $values );

			// Bail if an error occurred
			if ( is_wp_error( $result ) ) {
				$messages[] = $result->get_error_message();
				return $messages;
			}

			$processed[] = $signup_id;

			break;

		// Single Add
		case 'add' :
			check_admin_referer( "user_signup_add-{$site_id}" );

			// Update
			$values = wp_unslash( $_POST );
			$result = WP_User_Signup::create( $values );

			// Bail if an error occurred
			if ( is_wp_error( $result ) ) {
				$messages[] = $result->get_error_message();
				return $messages;
			}

			$processed[] = $result->signup_id;

			break;

		// Any other bingos
		default:
			check_admin_referer( "user_signups-bulk-{$site_id}" );
			do_action_ref_array( "signups_bulk_action-{$action}", array( $signups, &$processed, $action ) );

			break;
	}

	// Add processed signups to redirection
	$args['processed'] = $processed;
	$redirect_to = add_query_arg( $args, $redirect_to );

	// Redirect
	wp_safe_redirect( $redirect_to );
	exit();
}

/**
 * Output signup editing page
 *
 * @since 1.0.0
 */
function wp_user_signups_output_edit_page() {

	// Get site ID
	$site_id = wp_user_signups_get_site_id();

	// Edit
	if ( ! empty( $_REQUEST['signups'] ) ) {
		$signup_id = absint( $_REQUEST['signups'] );
		$action   = 'edit';

	// Add
	} else {
		$signup_id = 0;
		$action    = 'add';
	}

	// Try to get a signup
	$signup = WP_User_Signup::get( $signup_id );

	// URL
	$action_url = wp_user_signups_admin_url( array(
		'page'   => 'network_user_signups',
		'action' => $action
	) );

	// Output the header, maybe with network site tabs
	wp_user_signups_output_page_header( $signup_id ); ?>

	<form method="post" action="<?php echo esc_url( $action_url ); ?>" novalidate="novalidate">
		<h3><?php esc_html_e( 'Site', 'wp-user-signups' ); ?></h3>
		<p><?php esc_html_e( 'These details are for a new site at the same time as a user. (Empty if there is no site.)', 'wp-user-signups' ); ?></p>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="domain"><?php echo esc_html_x( 'Domain', 'User signup', 'wp-user-signups' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" name="domain" id="domain" value="<?php echo esc_attr( $signup->domain ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="path"><?php echo esc_html_x( 'Path', 'User signup', 'wp-user-signups' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" name="path" id="path" value="<?php echo esc_attr( $signup->path ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="title"><?php echo esc_html_x( 'Title', 'User signup', 'wp-user-signups' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" name="title" id="title" value="<?php echo esc_attr( $signup->title ); ?>">
					</td>
				</tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'User', 'wp-user-signups' ); ?></h3>
		<p><?php esc_html_e( 'These details are for a new user account. (These fields are required.)', 'wp-user-signups' ); ?></p>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="user_login"><?php echo esc_html_x( 'User Login', 'User signup', 'wp-user-signups' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" name="user_login" id="user_login" value="<?php echo esc_attr( $signup->user_login ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="user_email"><?php echo esc_html_x( 'User Email', 'User signup', 'wp-user-signups' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" name="user_email" id="user_email" value="<?php echo esc_attr( $signup->user_email ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="registered"><?php echo esc_html_x( 'Registered', 'User signup', 'wp-user-signups' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text code" name="registered" id="registered" value="<?php echo esc_attr( $signup->registered ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="activated"><?php echo esc_html_x( 'Activated', 'User signup', 'wp-user-signups' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text code" name="activated" id="activated" value="<?php echo esc_attr( $signup->activated ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="active"><?php echo esc_html_x( 'Status', 'User signup', 'wp-user-signups' ); ?></label>
					</th>
					<td>
						<select name="active" id="active">
							<option value="0" <?php selected( false, (bool) $signup->active ); ?>><?php esc_html_e( 'Pending',   'wp-user-signups' ); ?></option>
							<option value="1" <?php selected( true,  (bool) $signup->active ); ?>><?php esc_html_e( 'Activated', 'wp-user-signups' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="activation_key"><?php echo esc_html_x( 'Activation Key', 'User signup', 'wp-user-signups' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text code" name="activation_key" id="activation_key" value="<?php echo esc_attr( $signup->activation_key ); ?>">
					</td>
				</tr>
			</tbody>
		</table><?php

		// Meta unserialize
		$metas = (array) maybe_unserialize( $signup->meta );

		// Meta
		if ( ! empty( $metas ) ) : ?>

			<h3><?php esc_html_e( 'Meta', 'wp-user-signups' ); ?></h3>
			<p><?php esc_html_e( 'These extra details help with activation. (Use caution when changing these values.)', 'wp-user-signups' ); ?></p>
			<table class="form-table">
				<tbody>

					<?php foreach ( $metas as $key => $value ) : ?>

						<tr>
							<th scope="row">
								<label for="meta[<?php echo esc_attr( $key ); ?>]"><?php echo esc_html( $key ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" name="meta[<?php echo esc_attr( $key ); ?>]" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
							</td>
						</tr>

					<?php endforeach; ?>

				</tbody>
			</table>

		<?php endif; ?>

		<input type="hidden"   name="action"    value="<?php echo esc_attr( $action   ); ?>">
		<input type="hidden"   name="signup_id" value="<?php echo esc_attr( $signup_id  ); ?>">
		<input type="hidden"   name="signups"   value="<?php echo esc_attr( $signup_id ); ?>"><?php

		// Add
		if ( 'add' === $action ) {
			wp_nonce_field( "user_signup_add-{$site_id}" );
			$submit_text = esc_html__( 'Add Signup', 'wp-user-signups' );

		// Edit
		} else {
			wp_nonce_field( "user_signup_edit-{$site_id}" );
			$submit_text = esc_html__( 'Save Signup', 'wp-user-signups' );
		}

		// Submit button
		submit_button( $submit_text );

	?></form><?php

	// Footer
	wp_user_signups_output_page_footer();
}

/**
 * Output signup editing page
 *
 * @since 1.0.0
 */
function wp_user_signups_output_list_page() {
	global $wp_list_table;

	// Action URLs
	$form_url = wp_user_signups_admin_url( array( 'page' => 'user_signups' ) );

	// Header
	wp_user_signups_output_page_header(); ?>

	<div class="form-wrap">
		<?php $wp_list_table->views(); ?>

		<form method="post" action="<?php echo esc_url( $form_url ); ?>">
			<?php $wp_list_table->display(); ?>
		</form>
	</div><?php

	// Footer
	wp_user_signups_output_page_footer();
}

/**
 * Output signup editing page
 *
 * @since 1.0.0
 */
function wp_user_signups_output_network_list_page() {
	global $wp_list_table;

	// Action URLs
	$form_url = wp_user_signups_admin_url( array( 'page' => 'network_user_signups' ) );

	// Header
	wp_user_signups_output_page_header(); ?>

	<div class="form-wrap">
		<?php $wp_list_table->views(); ?>

		<form method="post" action="<?php echo esc_url( $form_url ); ?>">
			<?php $wp_list_table->display(); ?>
		</form>
	</div>

	<?php

	// Footer
	wp_user_signups_output_page_footer();
}

/**
 * Output admin notices
 *
 * @since 1.0.0
 *
 * @global type $wp_list_table
 */
function wp_user_signups_output_admin_notices() {

	// Default messages array
	$messages = array();

	// Add messages for bulk actions
	if ( empty( $_REQUEST['did_action'] ) ) {
		return;
	}

	$did_action = sanitize_key( $_REQUEST['did_action'] );
	$processed  = ! empty( $_REQUEST['processed'] )
		? wp_parse_id_list( (array) $_REQUEST['processed'] )
		: array();

	// Special case for single, as it's not really a "bulk" action
	if ( $processed === 1 ) {
		$bulk_messages = array(
			'activated' => esc_html__( 'Activated %s', 'wp-user-signups' ),
			'resend'    => esc_html__( 'Resent %s',    'wp-user-signups' ),
			'deleted'   => esc_html__( 'Deleted %s',   'wp-user-signups' ),
			'add'       => esc_html__( 'Added %s',     'wp-user-signups' ),
			'edit'      => esc_html__( 'Updated %s',   'wp-user-signups' )
		);

		if ( 'delete' === $did_action ) {
			$domain = ! empty( $_REQUEST['domains'] )
				? $_REQUEST['domains'][0]
				: array();
		} else {
			$signup  = WP_User_Signup::get( $processed[0] );
			$domain = $signup->get_domain();
		}

		$placeholder = '<code>' . esc_html( $domain ) . '</code>';

	// Note: we still use _n for languages which have special cases on
	// e.g. 3, 5, 10, etc
	} else {
		$count         = count( $processed );
		$placeholder   = number_format_i18n( $count );
		$bulk_messages = array(
			'activated' => _n( '%s signup activated.', '%s signups activated.', $count, 'wp-user-signups' ),
			'resend'    => _n( '%s signup resent.',    '%s signups resent.',    $count, 'wp-user-signups' ),
			'deleted'   => _n( '%s signup deleted.',   '%s signups deleted.',   $count, 'wp-user-signups' ),
			'add'       => _n( '%s signup added.',     '%s signups added.',     $count, 'wp-user-signups' ),
			'edit'      => _n( '%s signup updated.',   '%s signups updated.',   $count, 'wp-user-signups' )
		);
	}

	// Filter bulk messages, allowing for custom ones
	$bulk_messages = apply_filters( 'wp_user_signups_bulk_messages', $bulk_messages, $processed );

	// Insert the placeholder
	if ( ! empty( $bulk_messages[ $did_action ] ) ) {
		$messages[] = sprintf( $bulk_messages[ $did_action ], $placeholder );
	}

	// Bail if no messages
	if ( empty( $messages ) ) {
		return;
	}

	// Start a buffer
	ob_start();

	?><div id="message" class="notice notice-success"><p><?php echo implode( '</p><p>', $messages ); ?></p></div><?php

	// Output the buffer
	ob_end_flush();
}
