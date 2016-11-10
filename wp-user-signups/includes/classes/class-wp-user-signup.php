<?php

/**
 * User Sign-ups Class
 *
 * @package Plugins/User/Signups/Class
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Site Signup Class
 *
 * @since 1.0.0
 */
class WP_User_Signups {

	/**
	 * Signup data
	 *
	 * @var array
	 */
	public $data;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Signup data
	 */
	protected function __construct( $data = array() ) {
		$this->data = $data;
	}

	/**
	 * Clone magic method when clone( self ) is called.
	 *
	 * As the internal data is stored in an object, we have to make a copy
	 * when this object is cloned.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		$this->data = clone( $this->data );
	}

	/**
	 * Update the signup
	 *
	 * See also, {@see set_domain} and {@see set_status} as convenience methods.
	 *
	 * @since 1.0.0
	 *
	 * @global WPDB $wpdb
	 * @param array|stdClass $data Signup fields (associative array or object properties)
	 *
	 * @return bool|WP_Error True if we updated, false if we didn't need to, or WP_Error if an error occurred
	 */
	public function update( $data = array() ) {
		global $wpdb;

		$data    = (array) $data;
		$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' );
		$fields  = wp_parse_args( $data, array(
			'domain'         => $this->data->domain,
			'path'           => $this->data->path,
			'title'          => $this->data->title,
			'user_login'     => $this->data->user_login,
			'user_email'     => $this->data->user_email,
			'registered'     => $this->data->registered,
			'activated'      => $this->data->activated,
			'active'         => $this->data->active,
			'activation_key' => $this->data->activation_key,
			'meta'           => $this->data->meta
		) );

		// Maybe serialize meta
		$fields['meta'] = maybe_serialize( $fields['meta'] );

		// Query
		$where        = array( 'signup_id' => (int) $this->data->signup_id );
		$where_format = array( '%d' );
		$result       = $wpdb->update( $wpdb->signups, $fields, $where, $formats, $where_format );

		// Check for errors
		if ( empty( $result ) && ! empty( $wpdb->last_error ) ) {
			return new WP_Error( 'wp_user_signups_update_failed' );
		}

		// Clone object to pass into object later
		$old_signup = clone( $this );

		// Update internal state
		foreach ( $fields as $key => $val ) {
			$this->data->{$key} = $val;
		}

		// Update the sign-up cache
		wp_cache_set( $result, $this->data, 'user_signups' );

		/**
		 * Fires after a signup has been updated.
		 *
		 * @param  WP_User_Signups  $signup  The signup object.
		 * @param  WP_User_Signups  $signup  The previous signup object.
		 */
		do_action( 'wp_user_signups_updated', $this, $old_signup );

		return true;
	}

	/**
	 * Delete the signup
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error True if we updated, false if we didn't need to, or WP_Error if an error occurred
	 */
	public function delete() {
		global $wpdb;

		// Delete
		$where        = array( 'signup_id' => $this->data->signup_id );
		$where_format = array( '%d' );
		$result       = $wpdb->delete( $wpdb->signups, $where, $where_format );

		// Bail with error
		if ( empty( $result ) ) {
			return new WP_Error( 'wp_user_signups_delete_failed' );
		}

		// Delete cache
		wp_cache_delete( $this->data->signup_id, 'user_signups' );

		/**
		 * Fires after a signup has been delete.
		 *
		 * @param  WP_User_Signups  $signup The signup object.
		 */
		do_action( 'wp_user_signups_deleted', $this );

		return true;
	}

	/**
	 * Convert data to Signup instance
	 *
	 * Allows use as a callback, such as in `array_map`
	 *
	 * @since 1.0.0
	 *
	 * @param stdClass $data Raw signup data
	 * @return Signup
	 */
	protected static function to_instance( $data ) {
		return new static( $data );
	}

	/**
	 * Convert list of data to Signup instances
	 *
	 * @since 1.0.0
	 *
	 * @param stdClass[] $data Raw signup rows
	 * @return Signup[]
	 */
	protected static function to_instances( $data ) {
		return array_map( array( get_called_class(), 'to_instance' ), $data );
	}

	/**
	 * Get signup by signup ID
	 *
	 * @since 1.0.0
	 *
	 * @param int|WP_User_Signups $signup Signup ID or instance
	 * @return WP_User_Signups|WP_Error|null Signup on success, WP_Error if error occurred, or null if no signup found
	 */
	public static function get( $signup ) {
		global $wpdb;

		// Allow passing a site object in
		if ( $signup instanceof WP_User_Signups ) {
			return $signup;
		}

		if ( ! is_numeric( $signup ) ) {
			return new WP_Error( 'wp_user_signups_invalid_id' );
		}

		$signup = absint( $signup );

		// Suppress errors in case the table doesn't exist
		$suppress = $wpdb->suppress_errors();
		$signup   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->signups} WHERE signup_id = %d", $signup ) );

		$wpdb->suppress_errors( $suppress );

		if ( empty( $signup ) ) {
			return null;
		}

		return new static( $signup );
	}

	/**
	 * Get signup by signup ID
	 *
	 * @todo use WP_User_Signup_Query
	 *
	 * @since 1.0.0
	 *
	 * @param int|WP_User_Signups $signup Signup ID or instance
	 * @return WP_User_Signups|WP_Error|null Signup on success, WP_Error if error occurred, or null if no signup found
	 */
	public static function get_all() {
		global $wpdb;

		// Suppress errors in case the table doesn't exist
		$suppress = $wpdb->suppress_errors();
		$signups  = $wpdb->get_results( "SELECT * FROM {$wpdb->signups}" );

		$wpdb->suppress_errors( $suppress );

		if ( empty( $signups ) ) {
			return null;
		}

		return static::to_instances( $signups );
	}

	/**
	 * Create a new signup
	 *
	 * @param array $args Array of signup details
	 *
	 * @return WP_User_Signups|WP_Error
	 */
	public static function create( $args = array() ) {
		global $wpdb;

		// Parse arguments
		$r = wp_parse_args( $args, array(
			'domain'         => '',
			'path'           => '',
			'title'          => '',
			'user_login'     => '',
			'user_email'     => '',
			'registered'     => '',
			'activated'      => '',
			'active'         => '',
			'activation_key' => '',
			'meta'           => array()
		) );

		// Bail if missing login or email
		if ( empty( $r['user_login'] ) || empty( $r['user_email'] ) ) {
			return new WP_Error( 'wp_user_signups_invalid_id' );
		}

		// Check for previous signup
		$existing = false; // new WP_User_Signups_Query

		// Domain exists already...
		if ( ! empty( $existing ) ) {
			return new WP_Error( 'wp_user_signups_domain_exists', esc_html__( 'That signup already exists.', 'wp-user-signups' ) );
		}

		// Sanitize submitted data
		if ( empty( $r['activation_key'] ) ) {
			$r['activation_key'] = substr( md5( time() . rand() . $r['user_email'] ), 0, 16 );
		}
		$r['user_login'] = preg_replace( '/\s+/', '', sanitize_user( $r['user_login'], true ) );
		$r['user_email'] = sanitize_email( $r['user_email'] );		
		$r['user_login'] = maybe_serialize( $r['user_login'] );

		// Create the signup!
		$prev_errors = ! empty( $GLOBALS['EZSQL_ERROR'] ) ? $GLOBALS['EZSQL_ERROR'] : array();
		$suppress    = $wpdb->suppress_errors( true );
		$result      = $wpdb->insert(
			$wpdb->signups,
			$r,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		$wpdb->suppress_errors( $suppress );

		// Other error. We suppressed errors before, so we need to make sure
		// we handle that now.
		if ( empty( $result ) ) {
			$recent_errors = array_diff_key( $GLOBALS['EZSQL_ERROR'], $prev_errors );

			while ( count( $recent_errors ) > 0 ) {
				$error = array_shift( $recent_errors );
				$wpdb->print_error( $error['error_str'] );
			}

			return new WP_Error( 'wp_user_signups_insert_failed' );
		}

		// Ensure the cache is flushed
		wp_cache_delete( $result, 'user_signups' );

		// Prime the cache
		$signup = static::get( $wpdb->insert_id );

		/**
		 * Fires after a signup has been created.
		 *
		 * @param  WP_User_Signups  $signup  The signup object.
		 */
		do_action( 'wp_user_signups_created', $signup );

		/**
		 * Fires after a user's signup information has been written to the database.
		 *
		 * @since 4.4.0
		 *
		 * @param string $user       The user's requested login name.
		 * @param string $user_email The user's email address.
		 * @param string $key        The user's activation key
		 * @param array  $meta       Additional signup meta. By default, this is an empty array.
		 */
		do_action( 'after_signup_user', $signup, $signup->data->user_email, $signup->data->key, $signup->data->meta );

		return $signup;
	}

	/**
	 * Activate a sign-up
	 *
	 * @see wpmu_activate_signup()
	 *
	 * @since 1.0.0
	 *
	 * @global WPDB $wpdb
	 * @return WP_Error
	 */
	public function activate() {
		global $wpdb;

		// Already active
		if ( true === (bool) $this->data->active ) {
			return empty( $this->domain )
				? new WP_Error( 'already_active', __( 'The user is already active.', 'wp-user-signups' ), $this )
				: new WP_Error( 'already_active', __( 'The site is already active.', 'wp-user-signups' ), $this );
		}

		// Prepare some signup info
		$meta     = maybe_unserialize( $this->data->meta );
		$password = wp_generate_password( 12, false );
		$user_id  = username_exists( $this->data->user_login );

		// Does the user already exist?
		$user_already_exists = ( false !== $user_id );

		// Try to create user
		if ( false === $user_already_exists ) {
			$user_id = is_multisite()
				? wpmu_create_user( $this->data->user_login, $password, $this->data->user_email )
				: wp_create_user( $this->data->user_login, $password, $this->data->user_email );
		}

		// Bail if no user was created
		if ( empty( $user_id ) ) {
			return new WP_Error( 'create_user', __( 'Could not create user', 'wp-user-signups' ), $this );
		}

		// Get the current time, we'll use it in a few places
		$now = current_time( 'mysql', true );

		// Update the signup
		$this->update( array(
			'active'    => 1,
			'activated' => $now
		) );

		// Default return value
		$retval = array(
			'user_id'  => $user_id,
			'password' => $password,
			'meta'     => $meta
		);

		// Try to create a site
		if ( empty( $this->data->domain ) ) {

			// Bail if user already exists
			if ( true === $user_already_exists ) {
				return new WP_Error( 'user_already_exists', __( 'That username is already activated.' ), $this );
			}

			/**
			 * Fires immediately after a new user is activated.
			 *
			 * @since MU
			 *
			 * @param int   $user_id  User ID.
			 * @param int   $password User password.
			 * @param array $meta     Signup meta data.
			 */
			do_action( 'wpmu_activate_user', $user_id, $password, $meta );

		// Try to create a site
		} else {
			$blog_id = wpmu_create_blog( $this->data->domain, $this->data->path, $this->data->title, $user_id, $meta, $wpdb->siteid );

			// Created a user but cannot create a site
			if ( is_wp_error( $blog_id ) ) {
				$blog_id->add_data( $this );
				return $blog_id;
			}

			/**
			 * Fires immediately after a site is activated.
			 *
			 * @since MU
			 *
			 * @param int    $blog_id  Blog ID.
			 * @param int    $user_id  User ID.
			 * @param int    $password User password.
			 * @param string $title    Site title.
			 * @param array  $meta     Signup meta data.
			 */
			do_action( 'wpmu_activate_blog', $blog_id, $user_id, $password, $this->data->title, $meta );

			// Add site-specific data to return value
			$retval['blog_id'] = $blog_id;
			$retval['title']   = $this->data->title;
		}

		return $retval;
	}
}
