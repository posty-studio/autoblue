<?php

namespace Tests;

use Autoblue\Utils;
use lucatume\WPBrowser\TestCase\WPTestCase;

class UtilsTest extends WPTestCase {

	public function test_get_enabled_post_types_returns_post_by_default(): void {
		delete_option( 'autoblue_enabled_post_types' );

		$this->assertSame( [ 'post' ], Utils::get_enabled_post_types() );
	}

	public function test_get_enabled_post_types_returns_stored_value(): void {
		update_option( 'autoblue_enabled_post_types', [ 'post', 'page' ] );

		$this->assertSame( [ 'post', 'page' ], Utils::get_enabled_post_types() );
	}

	public function test_get_enabled_post_types_coerces_non_array_to_default(): void {
		update_option( 'autoblue_enabled_post_types', 'not-an-array' );

		$this->assertSame( [ 'post' ], Utils::get_enabled_post_types() );
	}

	protected function tearDown(): void {
		delete_option( 'autoblue_enabled_post_types' );
		parent::tearDown();
	}
}
