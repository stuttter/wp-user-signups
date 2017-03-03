<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Main WP User Sign-ups class
 *
 * This class facilitates the following functionality:
 *
 * - Creates & maintains the `wp_signups` table
 * - Adds `wp_signups` to the main database object when appropriate
 *
 * @since 1.0.0
 */
final class WP_User_Signup_DB {

	/**
	 * @var string Plugin version
	 */
	public $version = '1.0.0';

	/**
	 * @var string Database version
	 */
	public $db_version = 201603100001;

	/**
	 * @var string Database version key
	 */
	public $db_version_key = 'wpdb_user_signups_version';

	/**
	 * @var object Database object (usually $GLOBALS['wpdb'])
	 */
	private $db = false;

	/** Methods ***************************************************************/

	/**
	 * Hook into queries, admin screens, and more!
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Activation hook
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// Setup plugin
		$this->db = $GLOBALS['wpdb'];

		// Force table on to the global database object
		add_action( 'init',           array( $this, 'add_table_to_db_object' ) );
		add_action( 'switch_to_blog', array( $this, 'add_table_to_db_object' ) );

		// Check if DB needs upgrading
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}
	}

	/**
	 * Administration area hooks
	 *
	 * @since 1.0.0
	 */
	public function admin_init() {
		$this->maybe_upgrade_database();
	}

	/**
	 * Modify the database object and add the table to it
	 *
	 * This is necessary to do directly because WordPress does have a mechanism
	 * for manipulating them safely. It's pretty fragile, but oh well.
	 *
	 * @since 1.0.0
	 */
	public function add_table_to_db_object() {
		$this->db->signups       = "{$this->db->base_prefix}signups";
		$this->db->ms_global_tables[] = "signups";
	}

	/**
	 * Install this plugin on a specific site
	 *
	 * @since 1.0.0
	 */
	public function install() {
		$this->upgrade_database();
	}

	/**
	 * Activation hook
	 *
	 * Handles both single & multi site installations
	 *
	 * @since 1.0.0
	 *
	 * @param   bool    $network_wide
	 */
	public function activate() {
		$this->install();
	}

	/**
	 * Should a database update occur
	 *
	 * Runs on `admin_init`
	 *
	 * @since 1.0.0
	 */
	private function maybe_upgrade_database() {

		// Check DB for version
		$db_version = get_network_option( -1, $this->db_version_key );

		// Needs
		if ( (int) $db_version < $this->db_version ) {
			$this->upgrade_database( $db_version );
		}
	}

	/**
	 * Create the database table
	 *
	 * @since 1.0.0
	 *
	 * @param  int $old_version
	 */
	private function upgrade_database( $old_version = 0 ) {

		// The main column alter
		if ( version_compare( (int) $old_version, $this->db_version, '>=' ) ) {
			return;
		}

		// Create term table
		$this->create_table();

		// Update the DB version
		update_network_option( -1, $this->db_version_key, $this->db_version );
	}

	/**
	 * Create the table
	 *
	 * @since 1.0.0
	 */
	private function create_table() {

		$charset_collate = '';
		if ( ! empty( $this->db->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$this->db->charset}";
		}

		if ( ! empty( $this->db->collate ) ) {
			$charset_collate .= " COLLATE {$this->db->collate}";
		}

		// Check for `dbDelta`
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta( array(
			"CREATE TABLE {$this->db->signups} (
				signup_id bigint(20) NOT NULL auto_increment,
				domain varchar(200) NOT NULL default '',
				path varchar(100) NOT NULL default '',
				title longtext NOT NULL,
				user_login varchar(60) NOT NULL default '',
				user_email varchar(100) NOT NULL default '',
				registered datetime NOT NULL default '0000-00-00 00:00:00',
				activated datetime NOT NULL default '0000-00-00 00:00:00',
				active tinyint(1) NOT NULL default '0',
				activation_key varchar(50) NOT NULL default '',
				meta longtext,
				PRIMARY KEY  (signup_id),
				KEY activation_key (activation_key),
				KEY user_email (user_email),
				KEY user_login_email (user_login,user_email),
				KEY domain_path (domain(140),path(51))
			) {$charset_collate};"
		) );

		// Make doubly sure the global database object is modified
		$this->add_table_to_db_object();
	}
}

/**
 * Load the DB as early as possible, but after WordPress core is included
 *
 * @since 1.0.0
 */
function wp_user_signups_db() {
	new WP_User_Signup_DB();
}
add_action( 'plugins_loaded', 'wp_user_signups_db', -PHP_INT_MAX );
