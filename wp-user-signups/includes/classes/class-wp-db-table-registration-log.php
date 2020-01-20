<?php

/**
 * User Signups Database: WP_DB_Table_Registration_Log class
 *
 * @package Plugins/Signups/Database/Object
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Setup the global "registration_log" database table
 *
 * @since 2.0.0
 */
final class WP_DB_Table_Registration_Log extends WP_DB_Table {

	/**
	 * @var string Table name
	 */
	protected $name = 'registration_log';

	/**
	 * @var string Database version
	 */
	protected $version = 201705070001;

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
		$this->schema = "ID bigint(20) NOT NULL auto_increment,
			email varchar(255) NOT NULL default '',
			IP varchar(30) NOT NULL default '',
			blog_id bigint(20) NOT NULL default '0',
			date_registered datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY (ID),
			KEY IP (IP)";
	}

	/**
	 * Handle schema changes
	 *
	 * @since 2.0.0
	 */
	protected function upgrade() {

	}
}
