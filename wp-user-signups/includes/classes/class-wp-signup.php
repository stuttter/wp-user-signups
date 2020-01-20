<?php

/**
 * Sign-ups Class
 *
 * @package Plugins/Signups/Class
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Site Signup Class
 *
 * @since 1.0.0
 */
class WP_Signup {

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
	 * Magic getter to retrieve from data array
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 * @return mixed Value if in data array. Null if not.
	 */
	public function __get( $key ) {
		return isset( $this->data->{$key} )
			? $this->data->{$key}
			: null;
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

		// Query
		$signup_id    = (int) $this->signup_id;
		$where        = array( 'signup_id' => $signup_id );
		$where_format = array( '%d' );
		$formats      = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' );
		$fields       = self::validate( (array) $data );
		$result       = $wpdb->update( $wpdb->signups, $fields, $where, $formats, $where_format );

		// Check for errors
		if ( empty( $result ) && ! empty( $wpdb->last_error ) ) {
			return new WP_Error( 'wp_signups_update_failed', esc_html__( 'An error has occurred.', 'wp-user-signups' ), $this );
		}

		// Clone object to pass into object later
		$old_signup = clone( $this );

		// Update internal state
		foreach ( $fields as $key => $val ) {
			$this->data->{$key} = $val;
		}

		// Clean item cache
		clean_signup_cache( $this );

		/**
		 * Fires after a signup has been updated.
		 *
		 * @param  WP_Signup  $signup  The signup object.
		 * @param  WP_Signup  $signup  The previous signup object.
		 */
		do_action( 'wp_signups_updated', $this, $old_signup );

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
		$signup_id    = (int) $this->signup_id;
		$where        = array( 'signup_id' => $signup_id );
		$where_format = array( '%d' );
		$result       = $wpdb->delete( $wpdb->signups, $where, $where_format );

		// Bail with error
		if ( empty( $result ) ) {
			return new WP_Error( 'wp_signups_delete_failed', esc_html__( 'Delete failed.', 'wp-user-signups' ), $this );
		}

		// Delete signup meta
		$signup_meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->signups} WHERE signup_id = %d", $signup_id ) );
		foreach ( $signup_meta_ids as $mid ) {
			delete_metadata_by_mid( 'signup', $mid );
		}

		// Delete cache
		clean_signup_cache( $this );

		/**
		 * Fires after a signup has been deleted.
		 *
		 * @param  WP_Signup  $signup The signup object.
		 */
		do_action( 'wp_signups_deleted', $this );

		return true;
	}

	/**
	 * Get signup by signup ID
	 *
	 * @since 1.0.0
	 *
	 * @param int|WP_Signup $signup Signup ID or instance
	 * @return WP_Signup Signup always, even if empty
	 */
	public static function get_instance( $signup ) {
		global $wpdb;

		// Allow passing a site object in
		if ( $signup instanceof WP_Signup ) {
			return $signup;
		}

		if ( ! is_numeric( $signup ) ) {
			return new WP_Error( 'wp_signups_invalid_id', esc_html__( 'Signup not found.', 'wp-user-signups' ), $this );
		}

		// Check cache first
		$_signup = wp_cache_get( $signup, 'signups' );

		// No cached alias
		if ( false === $_signup ) {

			// Suppress errors in case the table doesn't exist
			$suppress = $wpdb->suppress_errors();
			$_signup  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->signups} WHERE signup_id = %d", absint( $signup ) ) );
			$wpdb->suppress_errors( $suppress );

			// Add alias to cache
			if ( ! empty( $_signup ) && ! is_wp_error( $_signup ) ) {
				wp_cache_add( $signup, $_signup, 'signups' );
			} else {
				$_signup = array();
			}
		}

		// Signup exists
		return new WP_Signup( $_signup );
	}

	/**
	 * Create a new signup
	 *
	 * @param array $args Array of signup details
	 *
	 * @return WP_Signup|WP_Error
	 */
	public static function create( $args = array() ) {
		global $wpdb;

		$r = self::validate( $args );

		// Bail if missing login or email
		if ( empty( $r['user_login'] ) || empty( $r['user_email'] ) ) {
			return new WP_Error( 'wp_signups_empty_id', esc_html__( 'Signup not found.', 'wp-user-signups' ), $this );
		}

		// Check for previous signup
		$query    = new WP_Signup_Query();
		$existing = $query->query( array(
			'user_email' => $r['user_email'],
			'number'     => 1
		) );

		// Domain exists already...
		if ( ! empty( $existing ) ) {
			return new WP_Error( 'wp_signups_domain_exists', esc_html__( 'That signup already exists.', 'wp-user-signups' ), $this );
		}

		// Create the signup!
		$prev_errors = ! empty( $GLOBALS['EZSQL_ERROR'] ) ? $GLOBALS['EZSQL_ERROR'] : array();
		$suppress    = $wpdb->suppress_errors( true );
		$format      = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' );
		$result      = $wpdb->insert( $wpdb->signups, $r, $format );

		$wpdb->suppress_errors( $suppress );

		// Other error. We suppressed errors before, so we need to make sure
		// we handle that now.
		if ( empty( $result ) ) {
			$recent_errors = array_diff_key( $GLOBALS['EZSQL_ERROR'], $prev_errors );

			while ( count( $recent_errors ) > 0 ) {
				$error = array_shift( $recent_errors );
				$wpdb->print_error( $error['error_str'] );
			}

			return new WP_Error( 'wp_signups_insert_failed', esc_html__( 'Signup creation failed.', 'wp-user-signups' ), $this );
		}

		// Ensure the cache is flushed
		clean_signup_cache( $wpdb->insert_id );

		// Prime the cache
		$signup = static::get_instance( $wpdb->insert_id );

		/**
		 * Fires after a signup has been created.
		 *
		 * @param  WP_Signup  $signup  The signup object.
		 */
		do_action( 'wp_signups_created', $signup );

		// Sent notifications
		$signup->notify();

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
		if ( true === (bool) $this->active ) {
			return empty( $this->domain )
				? new WP_Error( 'already_active', esc_html__( 'The user is already active.', 'wp-user-signups' ), $this )
				: new WP_Error( 'already_active', esc_html__( 'The site is already active.', 'wp-user-signups' ), $this );
		}

		// Prepare some signup info
		$meta     = maybe_unserialize( $this->meta );
		$password = wp_generate_password( 12, false );

		// Check for username & email addresses
		$un_id = username_exists( $this->user_login );
		$em_id =    email_exists( $this->user_email );

		// Try to create user
		if ( ( false === $un_id ) && ( false === $em_id ) ) {
			$user_id = is_multisite()
				? wpmu_create_user( $this->user_login, $password, $this->user_email )
				:   wp_create_user( $this->user_login, $password, $this->user_email );

			// Bail if no user was created
			if ( empty( $user_id ) ) {
				return new WP_Error( 'already_active', esc_html__( 'The user is already active.', 'wp-user-signups' ), $this );
			}

		// Username is already registered
		} elseif ( false !== $un_id ) {
			return new WP_Error( 'already_active', esc_html__( 'This username is already in use.', 'wp-user-signups' ), $this );

		// Email is already registered
		} elseif ( false !== $em_id ) {
			return new WP_Error( 'already_active', esc_html__( 'This email address is already in use.', 'wp-user-signups' ), $this );
		}

		// Get the current time, we'll use it in a few places
		$now = current_time( 'mysql', true );

		// Parse args
		$args = wp_parse_args( array(
			'active'    => 1,
			'activated' => $now
		), (array) $this->data );

		// Update the signup
		$updated = $this->update( $args );

		// Bail if update failed
		if ( is_wp_error( $updated ) ) {
			return new WP_Error( 'activation_failed', esc_html__( 'Sign up activation failed.', 'wp-user-signups' ), $this );
		}

		// Default return value
		$retval = array(
			'user_id'  => $user_id,
			'password' => $password,
			'meta'     => $meta
		);

		// Try to create a site
		if ( empty( $this->domain ) ) {

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
		} elseif ( is_multisite() ) {
			$blog_id = wpmu_create_blog( $this->domain, $this->path, $this->title, $user_id, $meta, $wpdb->siteid );

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
			do_action( 'wpmu_activate_blog', $blog_id, $user_id, $password, $this->title, $meta );

			// Add site-specific data to return value
			$retval['blog_id'] = $blog_id;
			$retval['title']   = $this->title;
		}

		return $retval;
	}

	/**
	 * Execute actions responsible for triggering notification emails to users
	 * who have signed up for accounts, maybe with a new site too.
	 *
	 * @since 1.0.0
	 */
	public function notify() {

		// Site action
		if ( ! empty( $this->domain ) && ! empty( $this->path ) ) {

			/**
			 * Fires after signup information has been written to the database.
			 *
			 * @since 4.4.0
			 *
			 * @param string $domain     The requested domain.
			 * @param string $path       The requested path.
			 * @param string $title      The requested site title.
			 * @param string $user       The user's requested login name.
			 * @param string $user_email The user's email address.
			 * @param string $key        The user's activation key
			 * @param array  $meta       By default, contains the requested privacy setting and lang_id.
			 */
			do_action( 'after_signup_site', $this->domain, $this->path, $this->title, $this->user_login, $this->user_email, $this->key, $this->meta );

		// User action
		} else {

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
			do_action( 'after_signup_user', $this->user_login, $this->user_email, $this->key, $this->meta );
		}
	}

	/**
	 * Validate array of data used for editing or creating a sign-up
	 *
	 * @since 1.0.0
	 *
	 * @param type $params
	 */
	public static function validate( $params = array() ) {

		// Whitelist keys
		$r = array_intersect_key( $params, array(
			'domain'         => '',
			'path'           => '/',
			'title'          => '',
			'user_login'     => '',
			'user_email'     => '',
			'registered'     => '',
			'activated'      => '',
			'active'         => '',
			'activation_key' => '',
			'meta'           => ''
		) );

		// Get current date for use in `registered` and `activated` values
		$now = date( 'Y-m-d H:i:s' );

		// User login
		if ( isset( $r['user_login'] ) ) {
			$r['user_login'] = preg_replace( '/\s+/', '', sanitize_user( $r['user_login'], true ) );
		}

		// Sanitize email
		if ( isset( $r['user_email'] ) ) {
			$r['user_email'] = sanitize_email( $r['user_email'] );
		}

		// Registered date
		if ( ! empty( $r['registered'] ) ) {
			$r['registered'] = date( 'Y-m-d H:i:s', strtotime( $r['registered'] ) );
		} else {
			$r['registered'] = $now;
		}

		// Activated date
		if ( ! empty( $r['activated'] ) && ( '0000-00-00 00:00:00' !== $r['activated'] ) ) {
			$r['activated'] = date( 'Y-m-d H:i:s', strtotime( $r['activated'] ) );
		} else {
			$r['activated'] = '0000-00-00 00:00:00';
		}

		// Activated
		if ( isset( $r['active'] ) ) {
			$r['active'] = (int) $r['active'];

			// Set activated to now if activating for the first time
			if ( ! empty( $r['active'] ) && ( '0000-00-00 00:00:00' === $r['activated'] ) ) {
				$r['activated'] = $now;
			}
		}

		// Activation key
		if ( empty( $r['activation_key'] ) ) {

			$base = false;

			// Site & User keys are based on different things
			if ( ! empty( $r['domain'] ) && ! empty( $r['path'] ) ) {
				$base = $r['domain'];
			} elseif ( ! empty( $r['user_email'] ) ) {
				$base = $r['user_email'];
			}

			// Maybe set key if base is good
			if ( ! empty( $base ) ) {
				$r['activation_key'] = substr( md5( time() . rand() . $base ), 0, 16 );
			}
		}

		// Meta array (this is wack)
		if ( isset( $r['meta'] ) ) {

			// Sanitize meta
			if ( is_array( $r['meta'] ) ) {
				array_walk( $r['meta'], 'sanitize_text_field' );
			} else {
				$r['meta'] = sanitize_text_field( $r['meta'] );
			}

			// Serialize for saving
			$r['meta'] = maybe_serialize( $r['meta'] );
		}

		return $r;
	}
}
