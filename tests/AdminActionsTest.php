<?php

use PHPUnit\Framework\TestCase;

function sanitize_key( $value ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) );
}

function wp_unslash( $value ) {
	return $value;
}

function check_admin_referer( $action ) {
	$result = wpus_test_call( __FUNCTION__, array( $action ) );
	if ( $result instanceof Throwable ) {
		throw $result;
	}

	return null === $result ? true : $result;
}

require_once dirname( __DIR__ ) . '/wp-user-signups/includes/functions/admin.php';

final class AdminActionsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['wpus_test'] = array();
		$_GET                 = array();
		$_POST                = array();
		$_REQUEST             = array();
	}

	public function test_bulk_action_nonce_is_verified_with_the_list_table_action(): void {
		wp_signups_check_action_nonce( 'delete' );

		$this->assertSame(
			array( 'signups-bulk' ),
			$GLOBALS['wpus_test']['calls']['check_admin_referer'][0]
		);
	}

	public function test_invalid_bulk_action_nonce_stops_before_signup_lookup(): void {
		$_REQUEST = array(
			'bulk_action' => 'delete',
			'signup_ids'  => array( '7' ),
		);
		$GLOBALS['wpus_test']['returns']['check_admin_referer'] = new RuntimeException( 'Invalid nonce.' );

		try {
			wp_signups_handle_actions();
			$this->fail( 'The bulk action continued after nonce verification failed.' );
		} catch ( RuntimeException $exception ) {
			$this->assertSame( 'Invalid nonce.', $exception->getMessage() );
			$this->assertSame(
				array( 'signups-bulk' ),
				$GLOBALS['wpus_test']['calls']['check_admin_referer'][0]
			);
		}
	}

	public function test_form_actions_keep_their_dedicated_nonce_paths(): void {
		wp_signups_check_action_nonce( 'add' );
		wp_signups_check_action_nonce( 'edit' );

		$this->assertArrayNotHasKey( 'check_admin_referer', $GLOBALS['wpus_test']['calls'] ?? array() );
	}
}
