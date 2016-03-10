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
	protected $data;

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
	 * @param array|stdClass $data Signup fields (associative array or object properties)
	 *
	 * @return bool|WP_Error True if we updated, false if we didn't need to, or WP_Error if an error occurred
	 */
	public function update( $data = array() ) {
		global $wpdb;

		$data    = (array) $data;
		$fields  = array();
		$formats = array();

		// Were we given a domain (and is it not the current one?)
		if ( ! empty( $data['domain'] ) && ( $this->data->domain !== $data['domain'] ) ) {

			// Does this domain exist already?
			$existing = static::get_by_domain( $data['domain'] );
			if ( is_wp_error( $existing ) ) {
				return $existing;
			}

			// Domain exists already and points to another site
			if ( ! empty( $existing ) ) {
				return new WP_Error( 'wp_user_signups_alias_domain_exists' );
			}

			// No uppercase letters in domains
			$fields['domain'] = strtolower( $data['domain'] );
			$formats[]        = '%s';
		}

		// Were we given a status (and is it not the current one?)
		if ( ! empty( $data['status'] ) && ( $this->data->status !== $data['status'] ) ) {
			$fields['status'] = sanitize_key( $data['status'] );
			$formats[]        = '%s';
		}

		// Do we have things to update?
		if ( empty( $fields ) ) {
			return false;
		}

		$id           = $this->get_id();
		$where        = array( 'id' => $id );
		$where_format = array( '%d' );
		$result       = $wpdb->update( $wpdb->signups, $fields, $where, $formats, $where_format );

		if ( empty( $result ) && ! empty( $wpdb->last_error ) ) {
			return new WP_Error( 'wp_user_signups_alias_update_failed' );
		}

		$old_alias = clone( $this );

		// Update internal state
		foreach ( $fields as $key => $val ) {
			$this->data->{$key} = $val;
		}

		// Update the domain cache
		wp_cache_set( "{$domain}:{$path}", $this->data, 'user_signups' );

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

		$where        = array( 'id' => $this->get_id() );
		$where_format = array( '%d' );
		$result       = $wpdb->delete( $wpdb->signups, $where, $where_format );

		if ( empty( $result ) ) {
			return new WP_Error( 'wp_user_signups_alias_delete_failed' );
		}

		// Delete the cache

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
			return new WP_Error( 'wp_user_signups_alias_invalid_id' );
		}

		$signup = absint( $signup );

		// Suppress errors in case the table doesn't exist
		$suppress = $wpdb->suppress_errors();
		$signup    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->signups} WHERE id = %d", $signup ) );

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
		$signup    = $wpdb->get_results( "SELECT * FROM {$wpdb->signups}" );

		$wpdb->suppress_errors( $suppress );

		if ( empty( $signup ) ) {
			return null;
		}

		return new static( $signup );
	}

	/**
	 * Get alias by domain(s)
	 *
	 * @since 0.1.0
	 *
	 * @param string|array $domains Domain(s) to match against
	 * @return WP_User_Signups|WP_Error|null Signup on success, WP_Error if error occurred, or null if no alias found
	 */
	public static function get_by_domain_and_path( $domains = array(), $paths = array() ) {
		global $wpdb;

		$domains = (array) $domains;

		// Check cache first
		$not_exists = 0;
		foreach ( $domains as $domain ) {
			$data = wp_cache_get( "{$domain}:{$path}", 'user_signups' );

			if ( ! empty( $data ) && ( 'notexists' !== $data ) ) {
				return new static( $data );
			} elseif ( 'notexists' === $data ) {
				$not_exists++;
			}
		}

		// Every domain was found in the cache, but doesn't exist
		if ( $not_exists === count( $domains ) ) {
			return null;
		}

		$placeholders    = array_fill( 0, count( $domains ), '%s' );
		$placeholders_in = implode( ',', $placeholders );

		// Prepare the query
		$query = "SELECT * FROM {$wpdb->signups} WHERE domain IN ($placeholders_in) ORDER BY CHAR_LENGTH(domain) DESC LIMIT 1";
		$query = $wpdb->prepare( $query, $domains );

		// Suppress errors in case the table doesn't exist
		$suppress = $wpdb->suppress_errors();
		$signup    = $wpdb->get_row( $query );

		$wpdb->suppress_errors( $suppress );

		// Cache that it doesn't exist
		if ( empty( $signup ) ) {
			foreach ( $domains as $domain ) {
				wp_cache_set( "{$domain}:{$path}", 'notexists', 'user_signups' );
			}

			return null;
		}

		wp_cache_set( "{$domain}:{$path}", $signup, 'user_signups' );

		return new static( $signup );
	}

	/**
	 * Create a new signup
	 *
	 * @param $site Site ID, or site object from {@see get_blog_details}
	 * @return WP_User_Signups|WP_Error
	 */
	public static function create( $site, $domain, $status ) {
		global $wpdb;

		// Allow passing a site object in
		if ( is_object( $site ) && isset( $site->blog_id ) ) {
			$site = $site->blog_id;
		}

		if ( ! is_numeric( $site ) ) {
			return new WP_Error( 'wp_user_signups_alias_invalid_id' );
		}

		$site   = absint( $site );
		$status = sanitize_key( $status );

		// Did we get a full URL?
		if ( strpos( $domain, '://' ) !== false ) {
			$domain = parse_url( $domain, PHP_URL_HOST );
		}

		// Does this domain exist already?
		$existing = static::get_by_domain( $domain );
		if ( is_wp_error( $existing ) ) {
			return $existing;
		}

		// Domain exists already...
		if ( ! empty( $existing ) ) {

			if ( $site !== $existing->get_site_id() ) {
				return new WP_Error( 'wp_user_signups_alias_domain_exists', esc_html__( 'That alias is already in use.', 'wp-user-signups' ) );
			}

			// ...and points to this site, so nothing to do
			return $existing;
		}

		// Create the alias!
		$prev_errors = ! empty( $GLOBALS['EZSQL_ERROR'] ) ? $GLOBALS['EZSQL_ERROR'] : array();
		$suppress    = $wpdb->suppress_errors( true );
		$result      = $wpdb->insert(
			$wpdb->signups,
			array(
				'blog_id' => $site,
				'domain'  => $domain,
				'created' => current_time( 'mysql' ),
				'status'  => $status
			),
			array( '%d', '%s', '%s', '%d' )
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

			return new WP_Error( 'wp_user_signups_alias_insert_failed' );
		}

		// Ensure the cache is flushed
		wp_cache_delete( "{$domain}:{$path}", 'user_signups' );

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
