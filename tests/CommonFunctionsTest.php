<?php

use PHPUnit\Framework\TestCase;

final class CommonFunctionsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['wpus_test'] = array();
		$_GET  = array();
		$_REQUEST = array();
	}

	public function test_admin_url_uses_the_current_installation_scope(): void {
		$this->assertSame(
			'https://example.test/wp-admin/admin.php?page=signups',
			wp_signups_admin_url()
		);

		$GLOBALS['wpus_test']['multisite'] = true;

		$this->assertSame(
			'https://network.example.test/wp-admin/network/admin.php?page=signups',
			wp_signups_admin_url()
		);
	}

	public function test_screen_helpers_only_match_the_expected_request_values(): void {
		$this->assertFalse( wp_signups_is_list_page() );
		$this->assertFalse( wp_signups_is_network_edit() );

		$_GET['page']     = 'signups';
		$_GET['referrer'] = 'network';

		$this->assertTrue( wp_signups_is_list_page() );
		$this->assertTrue( wp_signups_is_network_edit() );
	}

	public function test_signup_ids_are_normalized_to_positive_integers(): void {
		$_REQUEST['signup_ids'] = array( '-7', '12', 'invalid' );

		$this->assertSame( array( 7, 12, 0 ), wp_signups_sanitize_signup_ids() );
		$this->assertSame( array( 7 ), wp_signups_sanitize_signup_ids( true ) );
	}

	/** Database result objects should be converted into signup instances. */
	public function test_get_signup_accepts_a_database_row_object(): void {
		$row = (object) array(
			'signup_id'  => 42,
			'user_login' => 'person',
		);

		$signup = get_signup( $row );

		$this->assertInstanceOf( WP_Signup::class, $signup );
		$this->assertSame( 42, $signup->signup_id );
		$this->assertSame( 'person', $signup->user_login );
	}

	/** Custom row objects retain their public properties. */
	public function test_get_signup_accepts_a_custom_row_object(): void {
		$row = new class() {
			/**
			 * Signup ID.
			 *
			 * @var int
			 */
			public $signup_id = 43;
			/**
			 * Signup login.
			 *
			 * @var string
			 */
			public $user_login = 'other';
		};

		$signup = get_signup( $row );

		$this->assertInstanceOf( WP_Signup::class, $signup );
		$this->assertSame( 43, $signup->signup_id );
		$this->assertSame( 'other', $signup->user_login );
	}
}
