<?php

use PHPUnit\Framework\TestCase;

class WP_List_Table {
	protected $_actions;
	public $screen;

	public function __construct() {
		$this->screen = (object) array( 'id' => 'signups' );
	}
}

function __( $text, $domain = 'default' ) {
	$result = wpus_test_call( __FUNCTION__, array( $text, $domain ) );

	return null === $result ? $text : $result;
}

function _x( $text, $context, $domain = 'default' ) {
	$result = wpus_test_call( __FUNCTION__, array( $text, $context, $domain ) );

	return null === $result ? $text : $result;
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $text ) {
	return esc_html( $text );
}

function esc_url( $url ) {
	return $url;
}

function number_format_i18n( $number ) {
	return number_format( $number );
}

function submit_button() {}

require_once dirname( __DIR__ ) . '/wp-user-signups/includes/classes/class-wp-signups-list-table.php';

final class ListTableTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['wpus_test'] = array();
		$_GET                 = array();
	}

	public function test_bulk_action_translation_is_escaped_once_at_output(): void {
		$GLOBALS['wpus_test']['callbacks']['__'] = static function( $text ) {
			return 'Activate' === $text ? 'Activate & Archive' : $text;
		};
		$table = $this->new_table();

		$get_actions = new ReflectionMethod( $table, 'get_bulk_actions' );
		if ( PHP_VERSION_ID < 80100 ) {
			$get_actions->setAccessible( true );
		}
		$actions = $get_actions->invoke( $table );

		$property = new ReflectionProperty( WP_List_Table::class, '_actions' );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}
		$property->setValue( $table, $actions );

		$render = new ReflectionMethod( $table, 'bulk_actions' );
		if ( PHP_VERSION_ID < 80100 ) {
			$render->setAccessible( true );
		}
		ob_start();
		$render->invoke( $table, 'top' );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Activate &amp; Archive', $output );
		$this->assertStringNotContainsString( 'Activate &amp;amp; Archive', $output );
	}

	public function test_status_count_wrapper_can_be_reordered_by_translation(): void {
		$GLOBALS['wpus_test']['callbacks']['_x'] = static function( $text, $context ) {
			return 'signup status and count' === $context
				? '<span class="count">(%2$s)</span> %1$s'
				: $text;
		};
		$table = $this->new_table();
		$table->statuses = array(
			(object) array(
				'id'    => 'pending',
				'value' => 0,
				'name'  => 'Pending',
				'count' => 3,
			),
		);

		$get_views = new ReflectionMethod( $table, 'get_views' );
		if ( PHP_VERSION_ID < 80100 ) {
			$get_views->setAccessible( true );
		}
		$views = $get_views->invoke( $table );

		$this->assertStringContainsString( '<span class="count">(3)</span> Pending', $views['pending'] );
		$this->assertSame(
			array( '%1$s <span class="count">(%2$s)</span>', 'signup status and count', 'wp-user-signups' ),
			$GLOBALS['wpus_test']['calls']['_x'][0]
		);
	}

	private function new_table(): WP_Signups_List_Table {
		$reflection = new ReflectionClass( WP_Signups_List_Table::class );
		$table      = $reflection->newInstanceWithoutConstructor();
		$table->screen = (object) array( 'id' => 'signups' );
		$table->active = 0;

		return $table;
	}
}
