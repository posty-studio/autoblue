<?php

namespace Tests\Standard;

use Autoblue\Standard\Publication;
use lucatume\WPBrowser\TestCase\WPTestCase;

class PublicationTest extends WPTestCase {
	private const CONNECTION = [
		'did'         => 'did:plc:testuser',
		'access_jwt'  => 'test-access',
		'refresh_jwt' => 'test-refresh',
	];

	public function test_build_record_uses_wp_defaults_when_no_overrides(): void {
		update_option( 'blogname', 'My Blog' );
		update_option( 'blogdescription', 'Just my thoughts' );
		delete_option( 'autoblue_publication_overrides' );

		$record = ( new Publication() )->build_record( self::CONNECTION );

		$this->assertSame( 'site.standard.publication', $record['$type'] );
		$this->assertSame( 'My Blog', $record['name'] );
		$this->assertSame( 'Just my thoughts', $record['description'] );
		$this->assertSame( untrailingslashit( home_url( '/' ) ), $record['url'] );
	}

	public function test_build_record_uses_overrides_when_set(): void {
		update_option( 'blogname', 'My Blog' );
		update_option( 'blogdescription', 'Just my thoughts' );
		update_option(
			'autoblue_publication_overrides',
			[
				'name'        => 'Custom Publication',
				'description' => 'Custom description',
			]
		);

		$record = ( new Publication() )->build_record( self::CONNECTION );

		$this->assertSame( 'Custom Publication', $record['name'] );
		$this->assertSame( 'Custom description', $record['description'] );
	}

	public function test_build_record_falls_back_per_field(): void {
		update_option( 'blogname', 'My Blog' );
		update_option( 'blogdescription', 'Just my thoughts' );
		update_option(
			'autoblue_publication_overrides',
			[
				'name' => 'Custom Publication',
					// Description not overridden.
			]
		);

		$record = ( new Publication() )->build_record( self::CONNECTION );

		$this->assertSame( 'Custom Publication', $record['name'] );
		$this->assertSame( 'Just my thoughts', $record['description'] );
	}

	public function test_build_record_treats_empty_string_override_as_unset(): void {
		update_option( 'blogname', 'My Blog' );
		update_option(
			'autoblue_publication_overrides',
			[
				'name' => '   ',
			]
		);

		$record = ( new Publication() )->build_record( self::CONNECTION );

		$this->assertSame( 'My Blog', $record['name'] );
	}

	public function test_build_record_omits_description_when_blank(): void {
		update_option( 'blogname', 'My Blog' );
		update_option( 'blogdescription', '' );
		delete_option( 'autoblue_publication_overrides' );

		$record = ( new Publication() )->build_record( self::CONNECTION );

		$this->assertArrayNotHasKey( 'description', $record );
	}

	public function test_get_uri_returns_stored_uri(): void {
		update_option(
			'autoblue_publication_record',
			[
				'did' => self::CONNECTION['did'],
				'uri' => 'at://did:plc:testuser/site.standard.publication/abc123',
			]
		);

		$this->assertSame(
			'at://did:plc:testuser/site.standard.publication/abc123',
			( new Publication() )->get_uri()
		);
	}

	public function test_get_uri_returns_null_when_unset(): void {
		delete_option( 'autoblue_publication_record' );

		$this->assertNull( ( new Publication() )->get_uri() );
	}

	public function test_clear_removes_stored_record(): void {
		update_option(
			'autoblue_publication_record',
			[
				'did' => self::CONNECTION['did'],
				'uri' => 'at://did:plc:testuser/site.standard.publication/abc123',
			]
		);

		( new Publication() )->clear();

		$this->assertSame( [], get_option( 'autoblue_publication_record', [] ) );
	}

	protected function tearDown(): void {
		delete_option( 'autoblue_publication_overrides' );
		delete_option( 'autoblue_publication_record' );
		parent::tearDown();
	}
}
