<?php

/**
 * User Signups Functions
 *
 * @package Plugins/User/Signups/Functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the site ID being modified
 *
 * @since 0.1.0
 *
 * @return int
 */
function wp_user_signups_get_site_id() {

	// Set the default
	$default_id = is_blog_admin()
		? get_current_blog_id()
		: 0;

	// Get site ID being requested
	$site_id = isset( $_REQUEST['id'] )
		? intval( $_REQUEST['id'] )
		: $default_id;

	// Return the blog ID
	return (int) $site_id;
}

/**
 * Validate alias parameters
 *
 * @since 0.1.0
 *
 * @param  array  $params  Raw input parameters
 *
 * @return array|WP_Error Validated parameters on success, WP_Error otherwise
 */
function wp_user_signups_validate_alias_parameters( $params = array() ) {
	$valid = array();

	// Prevent debug notices
	if ( empty( $params['domain'] ) || empty( $params['id'] ) ) {
		return new WP_Error( 'wp_user_signups_no_domain', esc_html__( 'Signupes require a domain name', 'wp-user-signups' ) );
	}

	// Strip schemes from domain
	$params['domain'] = preg_replace( '#^https?://#', '', rtrim( $params['domain'], '/' ) );

	// Bail if no domain name
	if ( empty( $params['domain'] ) || ! strpos( $params['domain'], '.' ) ) {
		return new WP_Error( 'wp_user_signups_no_domain', esc_html__( 'Signupes require a domain name', 'wp-user-signups' ) );
	}

	// Bail if domain name using invalid characters
	if ( ! preg_match( '#^[a-z0-9\-.]+$#i', $params['domain'] ) ) {
		return new WP_Error( 'wp_user_signups_domain_invalid_chars', esc_html__( 'Domains can only contain alphanumeric characters, dashes (-) and periods (.)', 'wp-user-signups' ) );
	}

	$valid['domain'] = $params['domain'];

	// Bail if site ID is not valid
	$valid['site'] = absint( $params['id'] );
	if ( empty( $valid['site'] ) ) {
		return new WP_Error( 'wp_user_signups_invalid_site', esc_html__( 'Invalid site ID', 'wp-user-signups' ) );
	}

	// Validate status
	$valid['status'] = empty( $params['status'] )
		? 'inactive'
		: 'active';

	return $valid;
}

/**
 * Wrapper for admin URLs
 *
 * @since 0.1.0
 *
 * @param array $args
 * @return array
 */
function wp_user_signups_admin_url( $args = array() ) {

	// Action
	if ( is_network_admin() ) {
		$admin_url = network_admin_url( 'users.php' );
	} else {
		$admin_url = admin_url( 'index.php' );
	}

	// Add args and return
	return add_query_arg( $args, $admin_url );
}

/**
 * Check if a domain has a alias available
 *
 * @since 0.1.0
 *
 * @param stdClass|null $site Site object if already found, null otherwise
 *
 * @param string $domain Domain we're looking for
 *
 * @return stdClass|null Site object if already found, null otherwise
 */
function wp_user_signups_check_domain_alias( $site, $domain ) {

	// Have we already matched? (Allows other plugins to match first)
	if ( ! empty( $site ) ) {
		return $site;
	}

	// Grab both WWW and no-WWW
	if ( strpos( $domain, 'www.' ) === 0 ) {
		$www    = $domain;
		$no_www = substr( $domain, 4 );
	} else {
		$no_www = $domain;
		$www    = 'www.' . $domain;
	}

	// Get the alias
	$signup = WP_User_Signups::get_by_domain( array( $www, $no_www ) );

	// Bail if no alias
	if ( empty( $signup ) || is_wp_error( $signup ) ) {
		return $site;
	}

	// Ignore non-active aliases
	if ( 'active' !== $signup->get_status() ) {
		return $site;
	}

	// Fetch the actual data for the site
	$signuped_site = get_blog_details( $signup->get_site_id() );
	if ( empty( $signuped_site ) ) {
		return $site;
	}

	return $signuped_site;
}

/**
 * Clear aliases for a site when it's deleted
 *
 * @param int $site_id Site being deleted
 */
function wp_user_signups_clear_aliases_on_delete( $site_id = 0 ) {
	$signups = WP_User_Signups::get_by_domain_and_path( $site_id );

	if ( empty( $signups ) ) {
		return;
	}

	foreach ( $signups as $signup ) {
		$error = $signup->delete();

		if ( is_wp_error( $error ) ) {
			$message = sprintf(
				__( 'Unable to delete alias %d for site %d', 'wp-user-signups' ),
				$signup->signup_id,
				$site_id
			);
			trigger_error( $message, E_USER_WARNING );
		}
	}
}

/**
 * Register filters for URLs, if we've mapped
 *
 * @since 0.1.0
 */
function wp_user_signups_register_url_filters() {

	// Look for aliases
	$current_site = $GLOBALS['current_blog'];
	$real_domain  = $current_site->domain;
	$domain       = $_SERVER['HTTP_HOST'];

	// Bail if not mapped
	if ( $domain === $real_domain ) {
		return;
	}

	// Grab both WWW and no-WWW
	if ( strpos( $domain, 'www.' ) === 0 ) {
		$www   = $domain;
		$nowww = substr( $domain, 4 );
	} else {
		$nowww = $domain;
		$www   = 'www.' . $domain;
	}

	$signup = WP_User_Signups::get_by_domain( array( $www, $nowww ) );
	if ( empty( $signup ) || is_wp_error( $signup ) || ( 'active' !== $signup->get_status() ) ) {
		return;
	}

	// Set global for future mappings
	$GLOBALS['wp_current_site_alias'] = $signup;

	// Skip canonical for now
	remove_filter( 'template_redirect', 'redirect_canonical' );

	// Filter home & site URLs
	add_filter( 'site_url', 'wp_user_signups_mangle_site_url', -PHP_INT_MAX, 4 );
	add_filter( 'home_url', 'wp_user_signups_mangle_site_url', -PHP_INT_MAX, 4 );

	// If on main site of network, also filter network urls
	if ( is_main_site() ) {
		add_filter( 'network_site_url', 'wp_user_signups_mangle_network_url', -PHP_INT_MAX, 3 );
		add_filter( 'network_home_url', 'wp_user_signups_mangle_network_url', -PHP_INT_MAX, 3 );
	}
}

/**
 * Mangle the home URL to give our primary domain
 *
 * @since 0.1.0
 *
 * @param  string       $url          The complete home URL including scheme and path.
 * @param  string       $path         Path relative to the home URL. Blank string if no path is specified.
 * @param  string|null  $orig_scheme  Scheme to give the home URL context. Accepts 'http', 'https', 'relative' or null.
 * @param  int|null     $site_id      Blog ID, or null for the current blog.
 *
 * @return string Mangled URL
 */
function wp_user_signups_mangle_site_url( $url, $path, $orig_scheme, $site_id = 0 ) {

	// Set to current site if empty
	if ( empty( $site_id ) ) {
		$site_id = get_current_blog_id();
	}

	// Get the current alias
	$current_alias = $GLOBALS['wp_current_site_alias'];

	// Bail if no alias
	if ( empty( $current_alias ) || ( $site_id !== $current_alias->get_site_id() ) ) {
		return $url;
	}

	// Signup the URLs
	$current_home = $GLOBALS['current_blog']->domain . $GLOBALS['current_blog']->path;
	$signup_home   = $current_alias->get_domain() . '/';
	$url          = str_replace( $current_home, $signup_home, $url );

	return $url;
}

/**
 * Check if a domain belongs to a mapped site
 *
 * @since 0.1.0
 *
 * @param  stdClass|null  $network  Site object if already found, null otherwise
 * @param  string         $domain   Domain we're looking for
 *
 * @return stdClass|null Site object if already found, null otherwise
 */
function wp_user_signups_check_aliases_for_site( $site, $domain, $path, $path_segments ) {
	global $current_blog, $current_site;

	// Have we already matched? (Allows other plugins to match first)
	if ( ! empty( $site ) ) {
		return $site;
	}

	// Get possible domains and look for aliases
	$domains = wp_user_signups_get_possible_domains( $domain );
	$signup   = WP_User_Signups::get_by_domain( $domains );

	// Bail if no alias
	if ( empty( $signup ) || is_wp_error( $signup ) ) {
		return $site;
	}

	// Ignore non-active aliases
	if ( 'active' !== $signup->get_status() ) {
		return $site;
	}

	// Set site & network
	$site         = get_blog_details( $signup->get_site_id() );
	$current_site = wp_get_network( $site->site_id );

	// We found a network, now check for the site. Replace mapped domain with
	// network's original to find.
	$mapped_domain = $signup->get_domain();
	$subdomain     = substr( $domain, 0, -strlen( $mapped_domain ) );
	$domain        = $subdomain . $current_site->domain;
	$current_blog  = get_site_by_path( $domain, $path, $path_segments );

	// Return site or network
	switch ( current_filter() ) {
		case 'pre_get_site_by_path' :
			return $current_blog;
		case 'pre_get_network_by_path' :
			return $current_site;
		default :
			return $site;
	}
}

/**
 * Mangle the home URL to give our primary domain
 *
 * @since 0.1.0
 *
 * @param  string       $url          The complete home URL including scheme and path.
 * @param  string       $path         Path relative to the home URL. Blank string if no path is specified.
 * @param  string|null  $orig_scheme  Scheme to give the home URL context. Accepts 'http', 'https', 'relative' or null.
 * @param  int|null     $site_id      Blog ID, or null for the current blog.
 *
 * @return string Mangled URL
 */
function wp_user_signups_mangle_network_url( $url, $path, $orig_scheme, $site_id ) {

	if ( empty( $site_id ) ) {
		$site_id = get_current_blog_id();
	}

	$current_alias   = $GLOBALS['wp_current_site_alias'];
	$current_network = get_current_site();

	if ( empty( $current_alias ) || (int) $current_network->id !== (int) $current_alias->get_network_id() ) {
		return $url;
	}

	// Signup the URLs
	$current_home = $GLOBALS['current_blog']->domain . $GLOBALS['current_blog']->path;
	$signup_home   = $current_alias->get_domain() . '/';
	$url          = str_replace( $current_home, $signup_home, $url );
}

/**
 * Get all possible aliases which may be in use and apply to the supplied domain
 *
 * This will return an array of domains which might have been mapped but also apply to the current domain
 * i.e. a given url of site.network.com should return both site.network.com and network.com
 *
 * @since 0.1.0
 *
 * @param  $domain
 *
 * @return array
 */
function wp_user_signups_get_possible_domains( $domain = '' ) {

	$no_www = maybe_strip_www( $domain );

	// Explode domain on tld and return an array element for each explode point
	// Ensures subdomains of a mapped network are matched
	$domains   = wp_user_signups_explode_domain( $no_www );
	$additions = array();

	// Also look for www variant of each possible domain
	foreach ( $domains as $current ) {
		$additions[] = 'www.' . $current ;
	}

	$domains = array_merge( $domains, $additions );

	return $domains;
}

/**
 * Explode a given domain into an array of domains with decreasing number of segments
 *
 * site.network.com should return site.network.com and network.com
 *
 * @since 0.1.0
 *
 * @param  string  $domain    A url to explode, i.e. site.example.com
 * @param  int     $segments  Number of segments to explode and return
 *
 * @return array Exploded urls
 */
function wp_user_signups_explode_domain( $domain, $segments = 1 ) {

	$host_segments = explode( '.', trim( $domain, '.' ), (int) $segments );

	// Determine what domains to search for. Grab as many segments of the host
	// as asked for.
	$domains = array();

	while ( count( $host_segments ) > 1 ) {
		$domains[] = array_shift( $host_segments ) . '.' . implode( '.', $host_segments );
	}

	// Add the last part, avoiding trailing dot
	$domains[] = array_shift( $host_segments );

	return $domains;
}
