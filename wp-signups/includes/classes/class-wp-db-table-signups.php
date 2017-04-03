<?php

/**
 * User Signups Database: WP_DB_Table_Signups class
 *
 * @package Plugins/Signups/Database/Object
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Setup the global "blog_aliases" database table
 *
 * @since 2.0.0
 */
final class WP_DB_Table_Signups extends WP_DB_Table {

	/**
	 * @var string Table name
	 */
	protected $name = 'signups';

	/**
	 * @var string Database version
	 */
	protected $version = 201704030001;

	/**
	 * @var boolean This is a global table
	 */
	protected $global = true;

	/**
	 * Setup the database schema
	 *
	 * @since 2.0.0
	 */
	protected function set_schema() {
		$this->schema = "signup_id bigint(20) NOT NULL auto_increment,
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
			KEY domain_path (domain(140),path(51))";
	}

	/**
	 * Handle schema changes
	 *
	 * @since 2.0.0
	 */
	protected function upgrade() {

	}
}
