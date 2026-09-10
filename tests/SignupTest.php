<?php

use PHPUnit\Framework\TestCase;

final class SignupTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['wpus_test'] = array();
	}

	public function test_validate_sanitizes_fields_and_generates_activation_key(): void {
		$result = WP_Signup::validate(
			array(
				'user_login' => ' Test User ',
				'user_email' => ' person@example.test ',
				'meta'       => ' <b>Hello</b> ',
				'unknown'    => 'discard me',
			)
		);

		$this->assertSame( 'testuser', $result['user_login'] );
		$this->assertSame( 'person@example.test', $result['user_email'] );
		$this->assertSame( 16, strlen( $result['activation_key'] ) );
		$this->assertSame( 'Hello', $result['meta'] );
		$this->assertArrayNotHasKey( 'unknown', $result );
	}

	public function test_user_notification_uses_activation_key_property(): void {
		$signup = $this->new_signup(
			array(
				'user_login'     => 'person',
				'user_email'     => 'person@example.test',
				'activation_key' => 'correct-key',
				'key'            => 'wrong-key',
				'meta'           => array(),
			)
		);

		$signup->notify();

		$call = $GLOBALS['wpus_test']['calls']['do_action:after_signup_user'][0];
		$this->assertSame( 'correct-key', $call[2] );
	}

	private function new_signup( array $data ): WP_Signup {
		$reflection = new ReflectionClass( WP_Signup::class );
		$signup     = $reflection->newInstanceWithoutConstructor();
		$signup->data = (object) $data;

		return $signup;
	}
}
