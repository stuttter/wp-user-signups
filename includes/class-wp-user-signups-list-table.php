<?php

/**
 * User Signups List Table
 *
 * @package Plugins/User/Signups/ListTable
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * List table for signups
 */
final class WP_User_Signups_List_Table extends WP_List_Table {

	/**
	 * Prepare items for the list table
	 *
	 * @since 0.1.0
	 */
	public function prepare_items() {
		$this->items = array();
		$signups = WP_User_Signups::get_all();

		if ( ! empty( $signups ) && ! is_wp_error( $signups ) ) {
			$this->items = $signups;
		}
	}

	/**
	 * Get columns for the table
	 *
	 * @since 0.1.0
	 *
	 * @return array Map of column ID => title
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'user'       => _x( 'User',       'wp-user-signups' ),
			'email'      => _x( 'Email',      'wp-user-signups' ),
			'domain'     => _x( 'Domain',     'wp-user-signups' ),
			'path'       => _x( 'Path',       'wp-user-signups' ),
			'key'        => _x( 'Key',        'wp-user-signups' ),
			'registered' => _x( 'Registered', 'wp-user-signups' ),
			'activated'  => _x( 'Activated',  'wp-user-signups' )
		);
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return apply_filters( 'wp_user_signups_bulk_actions', array(
			'activate' => esc_html__( 'Activate', 'wp-user-signups' ),
			'resend'   => esc_html__( 'Resend',   'wp-user-signups' ),
			'delete'   => esc_html__( 'Delete',   'wp-user-signups' )
		) );
	}

	/**
	 * Display the bulk actions dropdown.
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
	 *                      This is designated as optional for backwards-compatibility.
	 */
	protected function bulk_actions( $which = '' ) {
		if ( is_null( $this->_actions ) ) {
			$no_new_actions = $this->_actions = $this->get_bulk_actions();
			/**
			 * Filter the list table Bulk Actions drop-down.
			 *
			 * The dynamic portion of the hook name, $this->screen->id, refers
			 * to the ID of the current screen, usually a string.
			 *
			 * This filter can currently only be used to remove bulk actions.
			 *
			 * @since 3.5.0
			 *
			 * @param array $actions An array of the available bulk actions.
			 */
			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
			$this->_actions = array_intersect_assoc( $this->_actions, $no_new_actions );
			$two = '';
			echo '<input type="hidden" name="id" value="' . esc_attr( $this->_args['site_id'] ) . '" />';
			wp_nonce_field( "user_signups-bulk-{$this->_args['site_id']}" );
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) ) {
			return;
		}

		echo "<label for='bulk-action-selector-" . esc_attr( $which ) . "' class='screen-reader-text'>" . __( 'Select bulk action' ) . "</label>";
		echo "<select name='bulk_action$two' id='bulk-action-selector-" . esc_attr( $which ) . "'>\n";
		echo "<option value='-1' selected='selected'>" . __( 'Bulk Actions' ) . "</option>\n";

		foreach ( $this->_actions as $name => $title ) {
			$class = 'edit' == $name ? ' class="hide-if-no-js"' : '';

			echo "\t<option value='{$name}'{$class}>{$title}</option>\n";
		}

		echo "</select>\n";
		submit_button( __( 'Apply' ), 'action', false, false, array( 'id' => "doaction{$two}" ) );
		echo "\n";
	}

	/**
	 * Get the current action selected from the bulk actions dropdown.
	 *
	 * @since 0.1.0
	 *
	 * @return string|bool The action name or False if no action was selected
	 */
	public function current_action() {

		if ( isset( $_REQUEST['bulk_action'] ) && -1 != $_REQUEST['bulk_action'] ) {
			return $_REQUEST['bulk_action'];
		}

		if ( isset( $_REQUEST['bulk_action2'] ) && -1 != $_REQUEST['bulk_action2'] ) {
			return $_REQUEST['bulk_action2'];
		}

		return false;
	}

	/**
	 * Get cell value for the checkbox column
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @param WP_User_Signups $signup Current signup item
	 * @return string HTML for the cell
	 */
	protected function column_cb( $signup ) {
		$signup_id = $signup->get_id();
		$domain   = $signup->get_domain();

		return '<label class="screen-reader-text" for="cb-select-' . esc_attr( $signup_id ) . '">'
			. sprintf( __( 'Select %s' ), esc_html( $domain ) ) . '</label>'
			. '<input type="checkbox" name="signups[]" value="' . esc_attr( $signup_id )
			. '" id="cb-select-' . esc_attr( $signup_id ) . '" />';
	}

	/**
	 * Get cell value for the domain column
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @param WP_User_Signups $signup Current signup item
	 * @return string HTML for the cell
	 */
	protected function column_user( $signup ) {

		// Default empty actions
		$actions = array();

		// Get vars
		$domain    = $signup->user_login;
		$site_id   = 0;
		$signup_id = $signup->signup_id;

		// Edit
		$edit_link = wp_user_signups_admin_url( array(
			'page'    => 'site_signup_edit',
			'id'      => $site_id,
			'signups' => $signup_id,
		) );

		// Active
		$text   = __( 'Activate', 'wp-user-signups' );
		$action = 'activate';

		// Default args
		$args = array(
			'page'     => 'user_signups',
			'action'   => $action,
			'id'       => $site_id,
			'signups'  => $signup_id,
			'_wpnonce' => wp_create_nonce( "user_signups-bulk-{$this->_args['site_id']}" )
		);

		$status_link = wp_user_signups_admin_url( $args );

		// Delete
		$delete_args           = $args;
		$delete_args['action'] = 'delete';
		$delete_link           = wp_user_signups_admin_url( $delete_args );

		// Edit
		if ( current_user_can( 'edit_signup', $signup_id ) ) {
			$actions['edit'] = sprintf( '<a href="%s">%s</a>', esc_url( $edit_link ), esc_html__( 'Edit', 'wp-user-signups' ) );
		}

		// Activate/deactivate
		if ( current_user_can( "{$action}_signup", $signup_id ) ) {
			$actions[ $action ] = sprintf( '<a href="%s">%s</a>', esc_url( $status_link ), esc_html( $text ) );
		}

		// Delete
		if ( current_user_can( 'delete_signup', $signup_id ) ) {
			$actions['delete'] = sprintf( '<a href="%s" class="submitdelete">%s</a>', esc_url( $delete_link ), esc_html__( 'Delete', 'wp-user-signups' ) );
		}

		// Get HTML from actions
		$action_html = $this->row_actions( $actions, false );

		return '<strong>' . esc_html( $domain ) . '</strong>' . $action_html;
	}

	/**
	 * Get value for the email column
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @param WP_User_Signups $signup Current signup item
	 * @return string HTML for the cell
	 */
	protected function column_email( $signup ) {
		return $signup->user_email;
	}

	/**
	 * Get value for the key column
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @param WP_User_Signups $signup Current signup item
	 * @return string HTML for the cell
	 */
	protected function column_key( $signup ) {
		return $signup->key;
	}

	/**
	 * Get value for the status column
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @param WP_User_Signups $signup Current signup item
	 * @return string HTML for the cell
	 */
	protected function column_active( $signup ) {
		return ( 'active' === $signup->active )
			? esc_html__( 'Active',   'wp-user-signups' )
			: esc_html__( 'Inactive', 'wp-user-signups' );
	}

	/**
	 * Get value for the status column
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @param WP_User_Signups $signup Current signup item
	 *
	 * @return string HTML for the cell
	 */
	protected function column_registered( $signup ) {
		return mysql2date( get_option( 'date_format' ), $signup->registered ) . '<br>' .
			   mysql2date( get_option( 'time_format' ), $signup->registered );
	}

	/**
	 * Get value for the status column
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @param WP_User_Signups $signup Current signup item
	 *
	 * @return string HTML for the cell
	 */
	protected function column_activated( $signup ) {
		return mysql2date( get_option( 'date_format' ), $signup->activated ) . '<br>' .
			   mysql2date( get_option( 'time_format' ), $signup->activated );
	}
}
