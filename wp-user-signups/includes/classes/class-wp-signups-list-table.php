<?php

/**
 * User Sign-ups List Table
 *
 * @package Plugins/Signups/ListTable
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * List table for sign-ups
 */
final class WP_Signups_List_Table extends WP_List_Table {

	/**
	 * Which status is selected
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	public $active = 0;

	/**
	 * Array of statuses
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	public $statuses = array();

	/**
	 * Set some variables on construct
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->active   = (int) ! empty( $_GET['active'] );
		$this->statuses = wp_signups_get_statuses();

		parent::__construct();
	}

	/**
	 * Prepare items for the list table
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {

		// Orderby
		$orderby = isset( $_GET['orderby'] )
			? sanitize_key( $_GET['orderby'] )
			: '';

		// Orderby
		$order = isset( $_GET['order'] )
			? sanitize_key( $_GET['order'] )
			: 'asc';

		// Limit
		$per_page = $this->get_items_per_page( 'edit_signups_per_page' );
		$page_num = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;
		$paged    = max( 1, $page_num );

		// Total
		$type  = wp_filter_object_list( $this->statuses, array( 'value' => $this->active ), 'and', 'count' );
		$total = reset( $type );

		// Query for signups
		$query = new WP_Signup_Query( array(
			'active'  => $this->active,
			'orderby' => $orderby,
			'order'   => $order,
			'offset'  => ( $paged * $per_page ) - $per_page,
			'number'  => $per_page
		) );

		// Set items if any are found
		if ( ! empty( $query->signups ) && ! is_wp_error( $query->signups ) ) {
			$this->items = $query->signups;
		}

		// Pagination
		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $per_page
		) );
	}

	/**
	 * Get columns for the table
	 *
	 * @since 1.0.0
	 *
	 * @return array Map of column ID => title
	 */
	public function get_columns() {

		// All columns
		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'user'           => _x( 'User',       'wp-user-signups' ),
			'site'           => _x( 'Site',       'wp-user-signups' ),
			'activation_key' => _x( 'Key',        'wp-user-signups' ),
			'registered'     => _x( 'Registered', 'wp-user-signups' ),
			'activated'      => _x( 'Activated',  'wp-user-signups' )
		);

		// Remove site column if single-site
		if ( ! wp_signups_is_multisite() ) {
			unset( $columns['site'] );
		}

		return $columns;
	}

	/**
	 * Return sortable columns
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'user'       => 'user_login',
			'registered' => 'registered',
			'activated'  => 'activated'
		);
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {

		// Default actions
		$actions = array(
			'activate' => esc_html__( 'Activate', 'wp-user-signups' ),
			'resend'   => esc_html__( 'Resend',   'wp-user-signups' ),
			'delete'   => esc_html__( 'Delete',   'wp-user-signups' )
		);

		// Remove activate action of already viewing active sign-ups
		if ( 1 === $this->active ) {
			unset( $actions['activate'] );
		}

		return apply_filters( 'wp_signups_bulk_actions', $actions );
	}

	/**
	 * Display the bulk actions dropdown.
	 *
	 * @since 1.0.0
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
			wp_nonce_field( 'signups-bulk' );
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) ) {
			return;
		}

		echo "<label for='bulk-action-selector-" . esc_attr( $which ) . "' class='screen-reader-text'>" . esc_html__( 'Select bulk action', 'wp-user-signups' ) . "</label>";
		echo "<select name='bulk_action{$two}' id='bulk-action-selector-" . esc_attr( $which ) . "'>\n";
		echo "<option value='-1' selected='selected'>" . __( 'Bulk Actions' ) . "</option>\n";

		foreach ( $this->_actions as $name => $title ) {
			$class = ( 'edit' === $name )
				? ' class="hide-if-no-js"'
				: '';

			echo "\t<option value='{$name}'{$class}>{$title}</option>\n";
		}

		echo "</select>\n";
		submit_button( __( 'Apply', 'wp-user-signups' ), 'action', false, false, array( 'id' => "doaction{$two}" ) );
		echo "\n";
	}

	/**
	 * Get the current action selected from the bulk actions dropdown.
	 *
	 * @since 1.0.0
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
	 * Custom no items text
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function no_items() {
		if ( 1 === $this->active ) {
			esc_html_e( 'No active sign-ups.', 'wp-user-signups' );
		} elseif ( 0 === $this->active ) {
			esc_html_e( 'No pending sign-ups.', 'wp-user-signups' );
		} else {
			esc_html_e( 'No sign-ups found.', 'wp-user-signups' );
		}
	}

	/**
	 * Get an array of sign-ups views
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_views() {

		// Get statuses
		$view_links = array();

		// Loop through statuses
		foreach ( $this->statuses as $status ) {

			// Current class
			$class = ( $status->value === $this->active )
				? 'current'
				: '';

			// Status args
			$args = ( 'activated' === $status->id )
				? array( 'active' => (int) $status->value )
				: array();

			// Build the URL for the status
			$url = wp_signups_admin_url( $args );

			// Add link to array
			$view_links[ $status->id ] = "<a href='" . esc_url( $url ) . "' class='" . esc_attr( $class ) . "'>" . sprintf( _nx( $status->name . ' <span class="count">(%s)</span>', $status->name . ' <span class="count">(%s)</span>', $status->count, 'users' ), number_format_i18n( $status->count ) ) . '</a>';
		}

		// Return links
		return $view_links;
	}

	/**
	 * Get cell value for the checkbox column
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param WP_Signup $signup Current sign-up item
	 * @return string HTML for the cell
	 */
	protected function column_cb( $signup ) {
		$signup_id = $signup->signup_id;
		$domain    = $signup->domain;

		return '<label class="screen-reader-text" for="cb-select-' . esc_attr( $signup_id ) . '">'
			. sprintf( __( 'Select %s', 'wp-user-signups' ), esc_html( $domain ) ) . '</label>'
			. '<input type="checkbox" name="signup_ids[]" value="' . esc_attr( $signup_id )
			. '" id="cb-select-' . esc_attr( $signup_id ) . '" />';
	}

	/**
	 * Get cell value for the domain column
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param WP_Signup $signup Current sign-up item
	 * @return string HTML for the cell
	 */
	protected function column_user( $signup ) {

		// Default empty actions
		$actions = array();

		// Get vars
		$login     =        $signup->user_login;
		$email     =        $signup->user_email;
		$signup_id = (int)  $signup->signup_id;
		$active    = (bool) $signup->active;

		// Edit
		$edit_link = wp_signups_admin_url( array(
			'signup_ids' => $signup_id,
			'page'       => 'signup_edit',
			'referrer'   => wp_signups_is_list_page()
				? 'network'
				: 'site'
		) );

		// Active
		$text   = __( 'Activate', 'wp-user-signups' );
		$action = 'activate';

		// Default args
		$args = array(
			'action'     => $action,
			'signup_ids' => $signup_id,
			'_wpnonce'   => wp_create_nonce( 'signups-bulk' )
		);

		$status_link = wp_signups_admin_url( $args );

		// Resend
		$resend_args           = $args;
		$resend_args['action'] = 'resend';
		$resend_link           = wp_signups_admin_url( $resend_args );

		// Delete
		$delete_args           = $args;
		$delete_args['action'] = 'delete';
		$delete_link           = wp_signups_admin_url( $delete_args );

		// Edit
		if ( current_user_can( 'edit_signup', $signup_id ) ) {
			$actions['edit'] = sprintf( '<a href="%s">%s</a>', esc_url( $edit_link ), esc_html__( 'Edit', 'wp-user-signups' ) );
		}

		// Resend
		if ( current_user_can( 'resend_signup', $signup_id ) ) {
			$actions['resend'] = sprintf( '<a href="%s">%s</a>', esc_url( $resend_link ), esc_html__( 'Resend', 'wp-user-signups' ) );
		}

		// Activate
		if ( ( false === $active ) && current_user_can( "{$action}_signup", $signup_id ) ) {
			$actions[ $action ] = sprintf( '<a href="%s">%s</a>', esc_url( $status_link ), esc_html( $text ) );
		}

		// Delete
		if ( current_user_can( 'delete_signup', $signup_id ) ) {
			$actions['delete'] = sprintf( '<a href="%s" class="submitdelete">%s</a>', esc_url( $delete_link ), esc_html__( 'Delete', 'wp-user-signups' ) );
		}

		// Get HTML from actions
		$action_html = $this->row_actions( $actions, false );

		return '<strong>' . esc_html( $login ) . '</strong> &mdash; ' . esc_html( $email ) . $action_html;
	}

	/**
	 * Get value for the email column
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param WP_Signup $signup Current sign-up item
	 * @return string HTML for the cell
	 */
	protected function column_user_email( $signup ) {
		return $signup->user_email;
	}

	/**
	 * Get value for the site column, made of domain & path
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param WP_Signup $signup Current sign-up item
	 * @return string HTML for the cell
	 */
	protected function column_site( $signup ) {
		return ! empty( $signup->domain ) && ! empty( $signup->path )
			? $signup->domain . $signup->path
			: '&mdash;';
	}

	/**
	 * Get value for the key column
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param WP_Signup $signup Current sign-up item
	 * @return string HTML for the cell
	 */
	protected function column_activation_key( $signup ) {
		return '<code>' . $signup->activation_key . '</code>';
	}

	/**
	 * Get value for the status column
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param WP_Signup $signup Current sign-up item
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
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param WP_Signup $signup Current sign-up item
	 *
	 * @return string HTML for the cell
	 */
	protected function column_activated( $signup ) {

		// Not yet active
		if (  '0000-00-00 00:00:00' === $signup->activated ) {
			return esc_html__( '&mdash;', 'wp-user-signups' );

		// Activated
		} else {
			return mysql2date( get_option( 'date_format' ), $signup->activated ) . '<br>' .
				   mysql2date( get_option( 'time_format' ), $signup->activated );
		}
	}
}
