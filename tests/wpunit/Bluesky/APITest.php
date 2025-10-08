<?php

namespace Tests\Bluesky;

use lucatume\WPBrowser\TestCase\WPTestCase;
use Autoblue\Bluesky\API;
use WP_Error;

class APITest extends WPTestCase {
	/** @var API */
	private $api;

	public function setUp(): void {
		parent::setUp();
		$this->api = new API();
	}

	public function test_get_did_for_handle_with_valid_handle() {
		$handle = 'test.bsky.social';

		add_filter(
			'pre_http_request',
			fn () => [
				'response' => [ 'code' => 200 ],
				'body'     => wp_json_encode( [ 'did' => 'did:plc:testuser123' ] ),
			],
		);

		$result = $this->api->get_did_for_handle( $handle );

		$this->assertEquals( 'did:plc:testuser123', $result );
	}

	public function test_get_did_for_handle_with_empty_handle() {
		$result = $this->api->get_did_for_handle( '' );
		$this->assertNull( $result );
	}

	public function test_get_profiles_with_valid_dids() {
		$dids = [ 'did:plc:user1', 'did:plc:user2' ];

		add_filter(
			'pre_http_request',
			fn () => [
				'response' => [ 'code' => 200 ],
				'body'     => wp_json_encode(
					[
						'profiles' => [
							[
								'did'    => 'did:plc:user1',
								'handle' => 'user1.bsky.social',
							],
							[
								'did'    => 'did:plc:user2',
								'handle' => 'user2.bsky.social',
							],
						],
					]
				),
			],
		);

		$result = $this->api->get_profiles( $dids );

		$this->assertCount( 2, $result );
		$this->assertEquals( 'user1.bsky.social', $result[0]['handle'] );
	}

	public function test_create_session_with_invalid_credentials() {
		$result = $this->api->create_session( '', '' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_create_session_with_valid_credentials() {
		$request_count = 0;

		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) use ( &$request_count ) {
				$request_count++;

				if ( strpos( $url, 'plc.directory' ) !== false ) {
					return [
						'response' => [ 'code' => 200 ],
						'body'     => wp_json_encode(
							[
								'id'      => 'did:plc:testuser123',
								'service' => [
									[
										'id'              => '#atproto_pds',
										'type'            => 'AtprotoPersonalDataServer',
										'serviceEndpoint' => 'https://bsky.social',
									],
								],
							]
						),
					];
				}

				if ( strpos( $url, 'createSession' ) !== false ) {
					return [
						'response' => [ 'code' => 200 ],
						'body'     => wp_json_encode(
							[
								'accessJwt'  => 'test-jwt-token',
								'refreshJwt' => 'test-refresh-token',
							]
						),
					];
				}

				return $response;
			},
			10,
			3
		);

		$result = $this->api->create_session( 'did:plc:testuser123', 'test-password' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'accessJwt', $result );
		$this->assertArrayHasKey( 'refreshJwt', $result );
		$this->assertEquals( 2, $request_count, 'Should make 2 HTTP requests: PLC directory + createSession' );
	}

	public function test_upload_blob_with_invalid_inputs() {
		$result = $this->api->upload_blob( '', '', '', '' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_upload_blob_with_valid_inputs() {
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) {
				if ( strpos( $url, 'plc.directory' ) !== false ) {
					return [
						'response' => [ 'code' => 200 ],
						'body'     => wp_json_encode(
							[
								'id'      => 'did:plc:testuser123',
								'service' => [
									[
										'id'              => '#atproto_pds',
										'type'            => 'AtprotoPersonalDataServer',
										'serviceEndpoint' => 'https://bsky.social',
									],
								],
							]
						),
					];
				}

				if ( strpos( $url, 'uploadBlob' ) !== false ) {
					return [
						'response' => [ 'code' => 200 ],
						'body'     => wp_json_encode(
							[
								'blob' => [
									'ref'      => [ '$link' => 'test-blob-ref' ],
									'mimeType' => 'image/jpeg',
									'size'     => 1024,
								],
							]
						),
					];
				}

				return $response;
			},
			10,
			3
		);

		$result = $this->api->upload_blob(
			'test-blob-data',
			'image/jpeg',
			'test-access-token',
			'did:plc:testuser123'
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'ref', $result );
	}

	public function test_search_actors_typeahead() {
		add_filter(
			'pre_http_request',
			fn () => [
				'response' => [ 'code' => 200 ],
				'body'     => wp_json_encode(
					[
						'actors' => [
							[
								'did'    => 'did:plc:user1',
								'handle' => 'user1.bsky.social',
							],
							[
								'did'    => 'did:plc:user2',
								'handle' => 'user2.bsky.social',
							],
						],
					]
				),
			],
		);

		$result = $this->api->search_actors_typeahead( 'test' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'actors', $result );
	}

	public function test_get_post_thread() {
		add_filter(
			'pre_http_request',
			fn () => [
				'response' => [ 'code' => 200 ],
				'body'     => wp_json_encode(
					[
						'thread' => [
							'post' => [
								'uri'  => 'test-uri',
								'text' => 'Test post',
							],
						],
					]
				),
			],
		);

		$result = $this->api->get_post_thread( 'test-uri' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'thread', $result );
	}

	public function test_create_session_with_self_hosted_pds() {
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) {
				if ( strpos( $url, 'plc.directory' ) !== false ) {
					return [
						'response' => [ 'code' => 200 ],
						'body'     => wp_json_encode(
							[
								'id'      => 'did:plc:testuser123',
								'service' => [
									[
										'id'              => '#atproto_pds',
										'type'            => 'AtprotoPersonalDataServer',
										'serviceEndpoint' => 'https://example.com',
									],
								],
							]
						),
					];
				}

				if ( strpos( $url, 'example.com' ) !== false && strpos( $url, 'createSession' ) !== false ) {
					return [
						'response' => [ 'code' => 200 ],
						'body'     => wp_json_encode(
							[
								'accessJwt'  => 'self-hosted-jwt-token',
								'refreshJwt' => 'self-hosted-refresh-token',
							]
						),
					];
				}

				return $response;
			},
			10,
			3
		);

		$result = $this->api->create_session( 'did:plc:testuser123', 'test-password' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'accessJwt', $result );
		$this->assertEquals( 'self-hosted-jwt-token', $result['accessJwt'] );
	}

	public function test_pds_resolution_with_missing_service() {
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) {
				if ( strpos( $url, 'plc.directory' ) !== false ) {
					return [
						'response' => [ 'code' => 200 ],
						'body'     => wp_json_encode(
							[
								'id'      => 'did:plc:testuser123',
								'service' => [], // No services.
							]
						),
					];
				}

				return $response;
			},
			10,
			3
		);

		$result = $this->api->create_session( 'did:plc:testuser123', 'test-password' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'autoblue_did_no_pds', $result->get_error_code() );
	}

	public function test_pds_resolution_with_plc_directory_error() {
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) {
				if ( strpos( $url, 'plc.directory' ) !== false ) {
					return [
						'response' => [ 'code' => 404 ],
						'body'     => wp_json_encode( [ 'error' => 'Not Found' ] ),
					];
				}

				return $response;
			},
			10,
			3
		);

		$result = $this->api->create_session( 'did:plc:nonexistent', 'test-password' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'autoblue_did_resolve_error', $result->get_error_code() );
	}

	protected function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'pre_http_request' );
	}
}
