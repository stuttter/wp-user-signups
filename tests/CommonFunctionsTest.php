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

	/** Invalid scalar input retains the established error result. */
	public function test_get_signup_returns_an_error_for_invalid_scalar_input(): void {
		$this->assertInstanceOf( WP_Error::class, get_signup( 'invalid' ) );
	}

	/** Objects without a signup ID are not treated as database rows. */
	public function test_get_signup_returns_an_error_for_invalid_object_input(): void {
		$this->assertInstanceOf( WP_Error::class, get_signup( (object) array( 'user_login' => 'invalid' ) ) );
	}

	/** Missing signups retain their established empty-array data shape. */
	public function test_missing_signup_preserves_empty_array_data(): void {
		$reflection  = new ReflectionClass( WP_Signup::class );
		$constructor = $reflection->getConstructor();
		$signup      = $reflection->newInstanceWithoutConstructor();
		if ( PHP_VERSION_ID < 80100 ) {
			$constructor->setAccessible( true );
		}
		$constructor->invoke( $signup );
		$clone = clone $signup;

		$this->assertSame( array(), $signup->data );
		$this->assertSame( array(), $clone->data );
		$this->assertNull( $signup->user_login );
	}

	/** User creation errors must not mark a signup active. */
	public function test_activate_returns_user_creation_errors_without_updating_signup(): void {
		$error = new WP_Error();
		$GLOBALS['wpus_test']['returns']['username_exists'] = false;
		$GLOBALS['wpus_test']['returns']['email_exists']    = false;
		$GLOBALS['wpus_test']['returns']['wp_create_user']  = $error;

		$signup = get_signup(
			(object) array(
				'signup_id'  => 44,
				'user_login' => 'person',
				'user_email' => 'person@example.test',
				'active'     => 0,
				'domain'     => '',
				'meta'       => array(),
			)
		);

		$this->assertSame( $error, $signup->activate() );
		$this->assertSame( $signup, $error->data );
		$this->assertArrayNotHasKey( 'do_action:wp_signups_updated', $GLOBALS['wpus_test']['calls'] ?? array() );
	}

	/** Multisite user creation failures must not mark a signup active. */
	public function test_activate_handles_multisite_user_creation_failure_without_updating_signup(): void {
		$GLOBALS['wpus_test']['multisite']                   = true;
		$GLOBALS['wpus_test']['returns']['username_exists']  = false;
		$GLOBALS['wpus_test']['returns']['email_exists']     = false;
		$GLOBALS['wpus_test']['returns']['wpmu_create_user'] = false;

		$signup = get_signup(
			(object) array(
				'signup_id'  => 45,
				'user_login' => 'network-person',
				'user_email' => 'network-person@example.test',
				'active'     => 0,
				'domain'     => 'site.example.test',
				'meta'       => array(),
			)
		);

		$this->assertInstanceOf( WP_Error::class, $signup->activate() );
		$this->assertArrayNotHasKey( 'do_action:wp_signups_updated', $GLOBALS['wpus_test']['calls'] ?? array() );
	}
}
