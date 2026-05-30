<?php

namespace Tests;

use Autoblue\ConnectionsManager;
use lucatume\WPBrowser\TestCase\WPTestCase;

class ConnectionsManagerTest extends WPTestCase {
	private const DID = 'did:plc:testuser123';

	public function test_public_connections_do_not_include_tokens(): void {
		update_option(
			'autoblue_connections',
			[
				[
					'did'         => self::DID,
					'access_jwt'  => 'mock-access-jwt',
					'refresh_jwt' => 'mock-refresh-jwt',
				],
			]
		);

		set_transient(
			'autoblue_connection_' . md5( self::DID ),
			[
				'handle' => 'test.bsky.social',
				'name'   => 'Test User',
				'avatar' => 'https://example.com/avatar.jpg',
			],
			DAY_IN_SECONDS
		);

		$connections = ( new ConnectionsManager() )->get_public_connections();

		$this->assertCount( 1, $connections );
		$this->assertSame( self::DID, $connections[0]['did'] );
		$this->assertSame( 'test.bsky.social', $connections[0]['meta']['handle'] );
		$this->assertArrayNotHasKey( 'access_jwt', $connections[0] );
		$this->assertArrayNotHasKey( 'refresh_jwt', $connections[0] );
	}

	public function test_public_connection_by_did_does_not_include_tokens(): void {
		update_option(
			'autoblue_connections',
			[
				[
					'did'         => self::DID,
					'access_jwt'  => 'mock-access-jwt',
					'refresh_jwt' => 'mock-refresh-jwt',
				],
			]
		);

		set_transient(
			'autoblue_connection_' . md5( self::DID ),
			[
				'handle' => 'test.bsky.social',
				'name'   => 'Test User',
				'avatar' => 'https://example.com/avatar.jpg',
			],
			DAY_IN_SECONDS
		);

		$connection = ( new ConnectionsManager() )->get_public_connection_by_did( self::DID );

		$this->assertIsArray( $connection );
		$this->assertSame( self::DID, $connection['did'] );
		$this->assertArrayNotHasKey( 'access_jwt', $connection );
		$this->assertArrayNotHasKey( 'refresh_jwt', $connection );
	}

	protected function tearDown(): void {
		delete_option( 'autoblue_connections' );
		delete_transient( 'autoblue_connection_' . md5( self::DID ) );

		parent::tearDown();
	}
}
