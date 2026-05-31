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

	public function test_is_root_install_true_for_root(): void {
		// Default test install is at root.
		$this->assertTrue( Utils::is_root_install() );
	}

	public function test_is_standard_site_enabled_requires_global_toggle(): void {
		update_option(
			'autoblue_connections',
			[
				[
					'did'         => 'did:plc:abc',
					'access_jwt'  => 'a',
					'refresh_jwt' => 'r',
				],
			]
		);

		delete_option( 'autoblue_publish_documents_enabled' );
		$this->assertFalse( Utils::is_standard_site_enabled() );

		update_option( 'autoblue_publish_documents_enabled', true );
		$this->assertTrue( Utils::is_standard_site_enabled() );
	}

	public function test_is_standard_site_enabled_requires_a_connection(): void {
		update_option( 'autoblue_publish_documents_enabled', true );
		update_option( 'autoblue_connections', [] );

		$this->assertFalse( Utils::is_standard_site_enabled() );
	}

	protected function tearDown(): void {
		delete_option( 'autoblue_enabled_post_types' );
		delete_option( 'autoblue_publish_documents_enabled' );
		delete_option( 'autoblue_connections' );
		parent::tearDown();
	}
}
