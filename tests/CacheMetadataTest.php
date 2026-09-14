<?php

use PHPUnit\Framework\TestCase;

function _get_non_cached_ids( $ids, $group ) {
	return wpus_test_call( __FUNCTION__, array( $ids, $group ) );
}

final class CacheMetadataTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['wpus_test'] = array();
	}

	public function test_after_signup_user_invalidates_the_signup_query_cache(): void {
		__wp_signups_after_signup_user();

		$this->assertSame(
			array( 'signups' ),
			$GLOBALS['wpus_test']['calls']['wp_cache_set_last_changed'][0]
		);
	}

	public function test_signup_metadata_wrappers_use_the_signup_object_type(): void {
		$GLOBALS['wpus_test']['returns']['add_metadata']    = 41;
		$GLOBALS['wpus_test']['returns']['get_metadata']    = 'blue';
		$GLOBALS['wpus_test']['returns']['update_metadata'] = true;
		$GLOBALS['wpus_test']['returns']['delete_metadata'] = true;

		$this->assertSame( 41, add_signup_meta( 7, 'color', 'blue', true ) );
		$this->assertSame( 'blue', get_signup_meta( 7, 'color', true ) );
		$this->assertTrue( update_signup_meta( 7, 'color', 'green', 'blue' ) );
		$this->assertTrue( delete_signup_meta( 7, 'color', 'green' ) );

		$this->assertSame( array( 'signup', 7, 'color', 'blue', true ), $GLOBALS['wpus_test']['calls']['add_metadata'][0] );
		$this->assertSame( array( 'signup', 7, 'color', true ), $GLOBALS['wpus_test']['calls']['get_metadata'][0] );
		$this->assertSame( array( 'signup', 7, 'color', 'green', 'blue' ), $GLOBALS['wpus_test']['calls']['update_metadata'][0] );
		$this->assertSame( array( 'signup', 7, 'color', 'green' ), $GLOBALS['wpus_test']['calls']['delete_metadata'][0] );
	}

	public function test_signup_cache_query_prepares_one_integer_placeholder_per_id(): void {
		$GLOBALS['wpus_test']['returns']['_get_non_cached_ids'] = array( 7, 12 );
		$GLOBALS['wpdb'] = new class() {
			public $signups = 'wp_signups';

			public function prepare( $query, $arguments ) {
				wpus_test_call( __FUNCTION__, array( $query, $arguments ) );

				return 'prepared signup query';
			}

			public function get_results( $query ) {
				wpus_test_call( __FUNCTION__, array( $query ) );

				return array();
			}
		};

		_prime_signup_caches( array( 7, 12 ), false );

		$this->assertSame(
			array( 'SELECT * FROM wp_signups WHERE signup_id IN (%d, %d)', array( 7, 12 ) ),
			$GLOBALS['wpus_test']['calls']['prepare'][0]
		);
		$this->assertSame( array( 'prepared signup query' ), $GLOBALS['wpus_test']['calls']['get_results'][0] );
	}
}
