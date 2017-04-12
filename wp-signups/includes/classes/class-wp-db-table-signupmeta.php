<?php

/**
 * Signup Meta: WP_DB_Table_Signupmeta class
 *
 * @package Plugins/Signups/Database/Meta
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Setup the global "signupmeta" database table
 *
 * @since 1.0.0
 */
final class WP_DB_Table_Signupmeta extends WP_DB_Table {

	/**
	 * @var string Table name
	 */
	protected $name = 'signupmeta';

	/**
	 * @var string Database version
	 */
	protected $version = 201704110001;

	/**
	 * @var boolean This is a global table
	 */
	protected $global = true;

	/**
	 * Setup the database schema
	 *
	 * @since 1.0.0
	 */
	protected function set_schema() {
		$max_index_length = 191;
		$this->schema     = "meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			signup_id bigint(20) unsigned NOT NULL default '0',
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext DEFAULT NULL,
			KEY signup_id (signup_id),
			KEY meta_key (meta_key({$max_index_length}))";
	}

	/**
	 * Handle schema changes
	 *
	 * @since 1.0.0
	 */
	protected function upgrade() {

		// 3.0.0 to 4.0.0
		if ( version_compare( (int) $this->db_version, 201704110001, '<=' ) ) {
			$this->db->query( "ALTER TABLE {$this->table_name} MODIFY `meta_id` BIGINT(20) unsigned NOT NULL AUTO_INCREMENT;" );
			$this->db->query( "ALTER TABLE {$this->table_name} MODIFY `signup_id` BIGINT(20) unsigned NOT NULL default 0;" );
		}
	}
}
