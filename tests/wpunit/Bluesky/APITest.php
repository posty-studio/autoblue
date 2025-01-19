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
		add_filter(
			'pre_http_request',
			fn () => [
				'response' => [ 'code' => 200 ],
				'body'     => wp_json_encode(
					[
						'accessJwt'  => 'test-jwt-token',
						'refreshJwt' => 'test-refresh-token',
					]
				),
			],
		);

		$result = $this->api->create_session( 'test-did', 'test-password' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'accessJwt', $result );
		$this->assertArrayHasKey( 'refreshJwt', $result );
	}

	public function test_upload_blob_with_invalid_inputs() {
		$result = $this->api->upload_blob( '', '', '' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_upload_blob_with_valid_inputs() {
		add_filter(
			'pre_http_request',
			fn () => [
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
			],
		);

		$result = $this->api->upload_blob(
			'test-blob-data',
			'image/jpeg',
			'test-access-token'
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

	protected function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'pre_http_request' );
	}
}
