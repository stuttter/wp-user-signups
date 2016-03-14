<?php

/**
 * User Signups Class
 *
 * @package Plugins/User/Signups/Class
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Site Signup Class
 *
 * @since 0.1.0
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
	 * @since 0.1.0
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
	 * @since 0.1.0
	 */
	public function __clone() {
		$this->data = clone( $this->data );
	}

	/**
	 * Update the alias
	 *
	 * See also, {@see set_domain} and {@see set_status} as convenience methods.
	 *
	 * @since 0.1.0
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

		$id           = $this->signup_id;
		$where        = array( 'signup_id' => $id );
		$where_format = array( '%d' );
		$result       = $wpdb->update( $wpdb->signups, $fields, $where, $formats, $where_format );

		if ( empty( $result ) && ! empty( $wpdb->last_error ) ) {
			return new WP_Error( 'wp_user_signups_update_failed' );
		}

		$old_alias = clone( $this );

		// Update internal state
		foreach ( $fields as $key => $val ) {
			$this->data->{$key} = $val;
		}

		// Update the domain cache
		wp_cache_set( $result, $this->data, 'user_signups' );

		/**
		 * Fires after a alias has been updated.
		 *
		 * @param  WP_User_Signups  $signup  The alias object.
		 * @param  WP_User_Signups  $signup  The previous alias object.
		 */
		do_action( 'wp_user_signups_updated', $this, $old_alias );

		return true;
	}

	/**
	 * Delete the alias
	 *
	 * @since 0.1.0
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
		 * Fires after a alias has been delete.
		 *
		 * @param  WP_User_Signups  $signup The alias object.
		 */
		do_action( 'wp_user_signups_deleted', $this );

		return true;
	}

	/**
	 * Convert data to Signup instance
	 *
	 * Allows use as a callback, such as in `array_map`
	 *
	 * @since 0.1.0
	 *
	 * @param stdClass $data Raw alias data
	 * @return Signup
	 */
	protected static function to_instance( $data ) {
		return new static( $data );
	}

	/**
	 * Convert list of data to Signup instances
	 *
	 * @since 0.1.0
	 *
	 * @param stdClass[] $data Raw alias rows
	 * @return Signup[]
	 */
	protected static function to_instances( $data ) {
		return array_map( array( get_called_class(), 'to_instance' ), $data );
	}

	/**
	 * Get alias by alias ID
	 *
	 * @since 0.1.0
	 *
	 * @param int|WP_User_Signups $signup Signup ID or instance
	 * @return WP_User_Signups|WP_Error|null Signup on success, WP_Error if error occurred, or null if no alias found
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
	 * Get alias by alias ID
	 *
	 * @since 0.1.0
	 *
	 * @param int|WP_User_Signups $signup Signup ID or instance
	 * @return WP_User_Signups|WP_Error|null Signup on success, WP_Error if error occurred, or null if no alias found
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
	 * Get alias by domain(s)
	 *
	 * @since 0.1.0
	 *
	 * @param string $domain Domain to match against
	 * @param string $path   Path to match against
	 *
	 * @return WP_User_Signups|WP_Error|null Signup on success, WP_Error if error occurred, or null if no alias found
	 */
	public static function get_by_domain_and_path( $domain = '', $path = '' ) {
		global $wpdb;

		// Check cache first
		$data = wp_cache_get( "{$domain}:{$path}", 'user_signups' );

		if ( ! empty( $data ) && ( 'notexists' !== $data ) ) {
			return new static( $data );
		} elseif ( 'notexists' === $data ) {
			return null;
		}

		// Prepare the query
		$query = "SELECT * FROM {$wpdb->signups} WHERE domain = %s AND path = %s ORDER BY CHAR_LENGTH(domain) DESC LIMIT 1";
		$query = $wpdb->prepare( $query, $domain, $path );

		// Suppress errors in case the table doesn't exist
		$suppress = $wpdb->suppress_errors();
		$signup    = $wpdb->get_row( $query );

		$wpdb->suppress_errors( $suppress );

		// Cache that it doesn't exist
		if ( empty( $signup ) ) {
			wp_cache_set( "{$domain}:{$path}", 'notexists', 'user_signups' );

			return null;
		}

		wp_cache_set( "{$domain}:{$path}", $signup, 'user_signups' );

		return new static( $signup );
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

		if ( empty( $r['user_login'] ) || empty( $r['user_email'] ) ) {
			return new WP_Error( 'wp_user_signups_invalid_id' );
		}

		$existing = false;

		// Domain exists already...
		if ( ! empty( $existing ) ) {
			return new WP_Error( 'wp_user_signups_domain_exists', esc_html__( 'That alias is already in use.', 'wp-user-signups' ) );
		}

		// Create the alias!
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

		$signup = static::get( $wpdb->insert_id );

		/**
		 * Fires after a alias has been created.
		 *
		 * @param  WP_User_Signups  $signup  The alias object.
		 */
		do_action( 'wp_user_signups_created', $signup );

		return $signup;
	}
}
