<?php

namespace Tests;

use Autoblue\ConnectionsManager;
use lucatume\WPBrowser\TestCase\WPTestCase;

class ConnectionsManagerTest extends WPTestCase {
	private const DID = 'did:plc:testuser123';

	protected function setUp(): void {
		parent::setUp();

		set_transient( 'autoblue_pds_endpoint_' . self::DID, 'https://bsky.social', DAY_IN_SECONDS );
		set_transient(
			'autoblue_connection_' . md5( self::DID ),
			[
				'handle' => 'test.bsky.social',
				'name'   => 'Test User',
				'avatar' => 'https://example.com/avatar.jpg',
			],
			DAY_IN_SECONDS
		);
	}

	public function test_public_connections_do_not_include_tokens(): void {
		$this->seed_connection();

		$connections = ( new ConnectionsManager() )->get_public_connections();

		$this->assertCount( 1, $connections );
		$this->assertSame( self::DID, $connections[0]['did'] );
		$this->assertSame( 'test.bsky.social', $connections[0]['meta']['handle'] );
		$this->assertArrayNotHasKey( 'access_jwt', $connections[0] );
		$this->assertArrayNotHasKey( 'refresh_jwt', $connections[0] );
	}

	public function test_public_connection_by_did_does_not_include_tokens(): void {
		$this->seed_connection();

		$connection = ( new ConnectionsManager() )->get_public_connection_by_did( self::DID );

		$this->assertIsArray( $connection );
		$this->assertSame( self::DID, $connection['did'] );
		$this->assertArrayNotHasKey( 'access_jwt', $connection );
		$this->assertArrayNotHasKey( 'refresh_jwt', $connection );
	}

	public function test_public_connection_data_includes_needs_reauth(): void {
		$this->seed_connection( [ 'needs_reauth' => true ] );

		$connections = ( new ConnectionsManager() )->get_public_connections();

		$this->assertTrue( $connections[0]['needs_reauth'] );
	}

	public function test_public_connection_data_defaults_needs_reauth_to_false(): void {
		$this->seed_connection();

		$connections = ( new ConnectionsManager() )->get_public_connections();

		$this->assertFalse( $connections[0]['needs_reauth'] );
	}

	public function test_refresh_tokens_sets_needs_reauth_on_expired_token(): void {
		$this->seed_connection();
		$this->mock_refresh_session_response(
			400,
			[
				'error'   => 'ExpiredToken',
				'message' => 'Token has been revoked',
			]
		);

		$result = ( new ConnectionsManager() )->refresh_tokens( self::DID );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$stored = get_option( 'autoblue_connections' );
		$this->assertTrue( $stored[0]['needs_reauth'] );
	}

	public function test_refresh_tokens_does_not_set_needs_reauth_on_network_error(): void {
		$this->seed_connection();
		add_filter( 'pre_http_request', fn() => new \WP_Error( 'http_request_failed', 'Connection timed out' ), 10, 0 );

		$result = ( new ConnectionsManager() )->refresh_tokens( self::DID );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$stored = get_option( 'autoblue_connections' );
		$this->assertArrayNotHasKey( 'needs_reauth', $stored[0] );
	}

	public function test_successful_refresh_clears_needs_reauth(): void {
		$this->seed_connection( [ 'needs_reauth' => true ] );
		$this->mock_refresh_session_response(
			200,
			[
				'accessJwt'  => 'fresh-access',
				'refreshJwt' => 'fresh-refresh',
			]
		);

		$result = ( new ConnectionsManager() )->refresh_tokens( self::DID );

		$this->assertIsArray( $result );
		$stored = get_option( 'autoblue_connections' );
		$this->assertArrayNotHasKey( 'needs_reauth', $stored[0] );
		$this->assertSame( 'fresh-access', $stored[0]['access_jwt'] );
	}

	public function test_add_connection_upserts_when_did_exists(): void {
		$this->seed_connection( [ 'needs_reauth' => true ] );
		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) {
				if ( strpos( $url, 'createSession' ) !== false ) {
					return [
						'response' => [ 'code' => 200 ],
						'body'     => wp_json_encode(
							[
								'accessJwt'  => 'reconnect-access',
								'refreshJwt' => 'reconnect-refresh',
							]
						),
					];
				}
				return $response;
			},
			10,
			3
		);

		$result = ( new ConnectionsManager() )->add_connection( self::DID, 'aaaa-bbbb-cccc-dddd' );

		$this->assertIsArray( $result );
		$stored = get_option( 'autoblue_connections' );
		$this->assertCount( 1, $stored, 'add_connection should upsert, not duplicate' );
		$this->assertSame( 'reconnect-access', $stored[0]['access_jwt'] );
		$this->assertArrayNotHasKey( 'needs_reauth', $stored[0] );
	}

	private function seed_connection( array $extra = [] ): void {
		update_option(
			'autoblue_connections',
			[
				array_merge(
					[
						'did'         => self::DID,
						'access_jwt'  => 'mock-access-jwt',
						'refresh_jwt' => 'mock-refresh-jwt',
					],
					$extra
				),
			]
		);
	}

	private function mock_refresh_session_response( int $code, array $body ): void {
		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) use ( $code, $body ) {
				if ( strpos( $url, 'refreshSession' ) !== false ) {
					return [
						'response' => [ 'code' => $code ],
						'body'     => wp_json_encode( $body ),
					];
				}
				return $response;
			},
			10,
			3
		);
	}

	protected function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		delete_option( 'autoblue_connections' );
		delete_transient( 'autoblue_connection_' . md5( self::DID ) );
		delete_transient( 'autoblue_pds_endpoint_' . self::DID );

		parent::tearDown();
	}
}
