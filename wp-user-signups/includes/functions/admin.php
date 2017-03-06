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

	// Define empty array
	$hooks = array();

	// Bail if not the correct admin for the installation type
	if ( ( is_multisite() && ! is_network_admin() ) || ( ! is_multisite() && ! is_admin() ) ) {
		return;
	}

	// Reposition based on install type
	$position = is_multisite()
		? 11
		: 70;

	// Network management of all signups
	$hooks[] = add_menu_page( esc_html__( 'Sign ups', 'wp-user-signups' ), esc_html__( 'Sign ups', 'wp-user-signups' ), 'manage_user_signups', 'user_signups', 'wp_user_signups_output_list_page', 'dashicons-flag', $position );
	$hooks[] = add_submenu_page( 'user_signups', esc_html__( 'Add New Signup', 'wp-user-signups' ), esc_html__( 'Add New', 'wp-user-signups' ), 'edit_user_signups', 'user_signup_edit', 'wp_user_signups_output_edit_page' );

	// Remove if user cannot create
	if ( ! current_user_can( 'create_user_signups' ) ) {
		remove_submenu_page( 'user_signups', 'user_signup_edit' );
	}

	// Load the list table
	foreach ( $hooks as $hook ) {
		add_action( "load-{$hook}", 'wp_user_signups_handle_actions'     );
		add_action( "load-{$hook}", 'wp_user_signups_load_list_table'    );
		add_action( "load-{$hook}", 'wp_user_signups_add_screen_options' );
	}
}

/**
 * Add screen options, mostly for pagination
 *
 * @since 1.0.0
 */
function wp_user_signups_add_screen_options() {
	add_screen_option(
		'per_page',
		array(
			'default' => 20,
			'option' => 'edit_user_signups_per_page',
			'label'   => _x( 'Sign ups', 'Signups per page (screen options)', 'wp-user-signups' )
		)
	);
}

/**
 * Help save the per_page screen option
 *
 * @since 1.0.0
 *
 * @param string $status
 * @param string $option
 * @param int $value
 *
 * @return string
 */
function wp_user_signups_set_screen_option( $status = '', $option = '', $value = 20 ) {

    if ( 'edit_user_signups_per_page' === $option ) {
		return $value;
	}

    return $status;
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

	// Create a new list table object
	$wp_list_table = new WP_User_Signup_List_Table();

	$wp_list_table->prepare_items();
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
	if ( wp_user_signups_is_list_page() ) {

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
	$redirect_to = remove_query_arg( array( 'did_action', 'processed', 'signup_ids', '_wpnonce' ), wp_get_referer() );

	// Maybe fallback redirect
	if ( empty( $redirect_to ) ) {
		$redirect_to = wp_user_signups_admin_url();
	}

	// Get signups being bulk actioned
	$processed = array();
	$signups   = wp_user_signups_sanitize_signup_ids();

	// Redirect args
	$args = array(
		'page'       => 'user_signups',
		'did_action' => $action
	);

	// What's the action?
	switch ( $action ) {

		// Bulk activate
		case 'activate' :
			foreach ( $signups as $signup_id ) {
				$signup = WP_User_Signup::get_instance( $signup_id );

				// Skip erroneous signups
				if ( is_wp_error( $signup ) ) {
					$args['error'] = $signup->get_error_message();
					continue;
				}

				// Try to activate
				$activated = $signup->activate();

				// Maybe add to processed
				if ( is_wp_error( $activated ) ) {
					if ( 'already_active' !== $activated->get_error_code() ) {
						$processed[] = $signup_id;
					}
				} else {
					$processed[] = $signup_id;
				}
			}
			break;

		// Bulk resend
		case 'resend':
			foreach ( $signups as $signup_id ) {
				$signup = WP_User_Signup::get_instance( $signup_id );

				// Skip erroneous signups
				if ( is_wp_error( $signup ) ) {
					$args['error'] = $signup->get_error_message();
					continue;
				}

				// Resend activation emails
				$signup->notify();
				$processed[] = $signup_id;
			}
			break;

		// Single/Bulk Delete
		case 'delete':
			$args['signup_ids'] = array();

			foreach ( $signups as $signup_id ) {
				$signup = WP_User_Signup::get_instance( $signup_id );

				// Skip erroneous signups
				if ( is_wp_error( $signup ) ) {
					$args['error'] = $signup->get_error_message();
					continue;
				}

				// Try to delete
				$deleted = $signup->delete();

				// Signups don't exist after we delete them
				if ( true === $deleted ) {
					$args['signup_ids'][] = $signup->signup_id;
					$processed[]          = $signup_id;
				}
			}

			break;

		// Single Edit
		case 'edit' :
			check_admin_referer( 'user_signup_edit' );

			$signup_id = $signups[0];
			$signup    = WP_User_Signup::get_instance( $signup_id );

			if ( is_wp_error( $signup ) ) {
				$args['error'] = $signup->get_error_message();
				continue;
			}

			// Update
			$values = wp_unslash( $_POST );
			$result = $signup->update( $values );

			// Bail if an error occurred
			if ( is_wp_error( $result ) ) {
				$args['error'] = $result->get_error_message();
				continue;
			}

			$processed[] = $signup_id;

			break;

		// Single Add
		case 'add' :
			check_admin_referer( 'user_signup_add' );

			// Update
			$values = wp_unslash( $_POST );
			$result = WP_User_Signup::create( $values );

			// Bail if an error occurred
			if ( is_wp_error( $result ) ) {
				$args['error'] = $result->get_error_code();
				$args['page']  = 'user_signup_edit';
				continue;
			}

			$processed[] = $result->signup_id;

			break;

		// Any other bingos
		default:
			check_admin_referer( 'user_signups-bulk' );
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

	// Edit
	if ( ! empty( $_REQUEST['signup_ids'] ) ) {
		$signup_id = absint( $_REQUEST['signup_ids'] );
		$action   = 'edit';

	// Add
	} else {
		$signup_id = 0;
		$action    = 'add';
	}

	// Try to get a signup
	$signup = WP_User_Signup::get_instance( $signup_id );

	// URL
	$action_url = wp_user_signups_admin_url( array(
		'page'   => 'user_signup_edit',
		'action' => $action
	) );

	// Output the header, maybe with network site tabs
	wp_user_signups_output_page_header( $signup_id ); ?>

	<form method="post" action="<?php echo esc_url( $action_url ); ?>" novalidate="novalidate">
		<?php if ( wp_user_signups_is_multisite() ) : ?>
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
		<?php endif; ?>

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
						<p class="description"><?php esc_html_e( 'Leave blank to automatically generate to now.', 'wp-user-signups' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="activated"><?php echo esc_html_x( 'Activated', 'User signup', 'wp-user-signups' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text code" name="activated" id="activated" value="<?php echo esc_attr( $signup->activated ); ?>">
						<p class="description"><?php esc_html_e( 'Leave blank to automatically format.', 'wp-user-signups' ); ?></p>
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
						<p class="description"><?php esc_html_e( 'Leave blank to automatically generate.', 'wp-user-signups' ); ?></p>
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

		<input type="hidden" name="action"     value="<?php echo esc_attr( $action    ); ?>">
		<input type="hidden" name="signup_id"  value="<?php echo esc_attr( $signup_id ); ?>">
		<input type="hidden" name="signup_ids" value="<?php echo esc_attr( $signup_id ); ?>"><?php

		// Add
		if ( 'add' === $action ) {
			wp_nonce_field( 'user_signup_add' );
			$submit_text = esc_html__( 'Add Signup', 'wp-user-signups' );

		// Edit
		} else {
			wp_nonce_field( 'user_signup_edit' );
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

	// Notice variables
	$class      = 'notice-success';
	$did_action = sanitize_key( $_REQUEST['did_action'] );
	$processed  = ! empty( $_REQUEST['processed'] )
		? wp_parse_id_list( (array) $_REQUEST['processed'] )
		: array();

	// Changed count
	$count = count( $processed );

	// Special case for single, as it's not really a "bulk" action
	if ( empty( $count ) ) {
		$bulk_messages = array();
		$messages      = array(
			esc_html__( 'An error has occurred.', 'wp-user-signups' )
		);

	// 1 item
	} elseif ( 1 === $count ) {
		$bulk_messages = array(
			'activate' => esc_html__( 'Activated %s.', 'wp-user-signups' ),
			'resend'   => esc_html__( 'Resent to %s.', 'wp-user-signups' ),
			'delete'   => esc_html__( 'Deleted %s.',   'wp-user-signups' ),
			'add'      => esc_html__( 'Added %s.',     'wp-user-signups' ),
			'edit'     => esc_html__( 'Updated %s.',   'wp-user-signups' )
		);

		if ( 'delete' === $did_action ) {
			$placeholder = esc_html__( 'signup', 'wp-user-signups' );
		} else {
			$signup      = WP_User_Signup::get_instance( $processed[0] );
			$placeholder = '<code>' . esc_html( $signup->user_email ) . '</code>';
		}

	// Note: we still use _n for languages which have special cases on
	// e.g. 3, 5, 10, etc
	} else {
		$placeholder   = number_format_i18n( $count );
		$bulk_messages = array(
			'activate' => _n( '%s signup activated.', '%s signups activated.', $count, 'wp-user-signups' ),
			'resend'   => _n( '%s signup resent.',    '%s signups resent.',    $count, 'wp-user-signups' ),
			'delete'   => _n( '%s signup deleted.',   '%s signups deleted.',   $count, 'wp-user-signups' ),
			'add'      => _n( '%s signup added.',     '%s signups added.',     $count, 'wp-user-signups' ),
			'edit'     => _n( '%s signup updated.',   '%s signups updated.',   $count, 'wp-user-signups' )
		);

		// Warn if empty
		if ( empty( $count ) ) {
			$class = 'notice-warning';
		}
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

	// Output notices
	?><div id="message" class="notice <?php echo $class; ?>"><p><?php echo implode( '</p><p>', $messages ); ?></p></div><?php
}
