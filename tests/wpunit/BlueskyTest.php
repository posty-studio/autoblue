<?php

namespace Tests\Bluesky;

use lucatume\WPBrowser\TestCase\WPTestCase;
use Autoblue\Bluesky;
use Autoblue\Bluesky\API;
use Autoblue\Logging\Log;
use Mockery;

class BlueskyTest extends WPTestCase {
	private const MOCK_CONNECTION = [
		'did'         => 'mock-did',
		'access_jwt'  => 'mock-access-jwt',
		'refresh_jwt' => 'mock-refresh-jwt',
	];

	private const ORIGINAL_MESSAGE = 'This is a post excerpt.';
	private const CUSTOM_MESSAGE   = 'This is a custom message.';
	private const FILTERED_MESSAGE = 'Filtered message content.';

	/**
	 * @var Bluesky
	 */
	private $bluesky;

	/**
	 * @var Log|Mockery\MockInterface
	 */
	private $log_mock;

	/**
	 * @var API|Mockery\MockInterface
	 */
	private $api_mock;

	/**
	 * Sets up the test case environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->log_mock = Mockery::mock( Log::class );
		$this->api_mock = Mockery::mock( API::class );
		$this->bluesky  = Mockery::mock( Bluesky::class, [ $this->api_mock, $this->log_mock ] )->makePartial();
		$this->bluesky->shouldReceive( 'get_connection' )->andReturn( self::MOCK_CONNECTION );
		$this->bluesky->shouldReceive( 'refresh_connection' )->andReturn( self::MOCK_CONNECTION );
	}

	public function test_excerpt_gets_used_as_message_by_default() {
		$post_id = $this->create_test_post( self::ORIGINAL_MESSAGE );
		$this->expect_api_call_with_message( self::ORIGINAL_MESSAGE );
		$this->bluesky->share_to_bluesky( $post_id );
	}

	public function test_custom_message_gets_used_if_set() {
		$post_id = $this->create_test_post( self::ORIGINAL_MESSAGE );
		update_post_meta( $post_id, 'autoblue_custom_message', self::CUSTOM_MESSAGE );
		$this->expect_api_call_with_message( self::CUSTOM_MESSAGE );
		$this->bluesky->share_to_bluesky( $post_id );
	}

	public function test_share_message_filter_applies() {
		$post_id = $this->create_test_post( self::ORIGINAL_MESSAGE );
		add_filter( 'autoblue/share_message', fn() => self::FILTERED_MESSAGE );
		$this->expect_api_call_with_message( self::FILTERED_MESSAGE );
		$this->bluesky->share_to_bluesky( $post_id );
	}

	public function test_standard_site_share_writes_document_before_bluesky_post_with_associated_refs() {
		$post_id = $this->create_test_post( self::ORIGINAL_MESSAGE );
		$this->enable_standard_site_for_post( $post_id );

		$this->log_mock->shouldReceive( 'success' );
		$this->api_mock->shouldReceive( 'create_record' )
			->once()
			->ordered()
			->withArgs(
				static function ( $writes ) {
					return $writes['collection'] === 'site.standard.document'
						&& ! isset( $writes['rkey'] )
						&& ! isset( $writes['record']['bskyPostRef'] );
				}
			)
			->andReturn(
				[
					'uri' => 'at://mock-did/site.standard.document/doc123',
					'cid' => 'doc-cid',
				]
			);
		$this->api_mock->shouldReceive( 'create_record' )
			->once()
			->ordered()
			->withArgs(
				static function ( $body ) {
					$refs = $body['record']['embed']['external']['associatedRefs'] ?? [];

					return $body['collection'] === 'app.bsky.feed.post'
						&& count( $refs ) === 2
						&& $refs[0]['uri'] === 'at://mock-did/site.standard.document/doc123'
						&& $refs[0]['cid'] === 'doc-cid'
						&& $refs[1]['uri'] === 'at://mock-did/site.standard.publication/pub123'
						&& $refs[1]['cid'] === 'pub-cid';
				}
			)
			->andReturn(
				[
					'uri' => 'at://mock-did/app.bsky.feed.post/bsky123',
					'cid' => 'bsky-cid',
				]
			);
		$this->api_mock->shouldReceive( 'put_record' )
			->once()
			->ordered()
			->withArgs(
				static function ( $body ) {
					return $body['collection'] === 'site.standard.document'
						&& $body['rkey'] === 'doc123'
						&& $body['record']['bskyPostRef']['uri'] === 'at://mock-did/app.bsky.feed.post/bsky123'
						&& $body['record']['bskyPostRef']['cid'] === 'bsky-cid';
				}
			)
			->andReturn(
				[
					'uri' => 'at://mock-did/site.standard.document/doc123',
					'cid' => 'doc-cid-with-bsky-ref',
				]
			);

		$share = $this->bluesky->share_to_bluesky( $post_id );
		$doc   = get_post_meta( $post_id, 'autoblue_document', true );

		$this->assertSame( 'at://mock-did/app.bsky.feed.post/bsky123', $share['uri'] );
		$this->assertSame( 'at://mock-did/site.standard.document/doc123', $doc['uri'] );
	}

	public function test_standard_site_bluesky_failure_leaves_created_document_for_retry_or_cleanup() {
		$post_id = $this->create_test_post( self::ORIGINAL_MESSAGE );
		$this->enable_standard_site_for_post( $post_id );

		$this->log_mock->shouldReceive( 'error' )->once();
		$this->log_mock->shouldReceive( 'success' )->once();
		$this->api_mock->shouldReceive( 'create_record' )
			->once()
			->ordered()
			->withArgs(
				static function ( $body ) {
					return $body['collection'] === 'site.standard.document';
				}
			)
			->andReturn(
				[
					'uri' => 'at://mock-did/site.standard.document/doc123',
					'cid' => 'doc-cid',
				]
			);
		$this->api_mock->shouldReceive( 'create_record' )
			->once()
			->ordered()
			->withArgs(
				static function ( $body ) {
					return $body['collection'] === 'app.bsky.feed.post';
				}
			)
			->andReturn( new \WP_Error( 'mock_bsky_create_failed', 'Bluesky post was not written.' ) );
		$this->api_mock->shouldReceive( 'put_record' )->never();

		$this->assertFalse( $this->bluesky->share_to_bluesky( $post_id ) );

		$doc = get_post_meta( $post_id, 'autoblue_document', true );
		$this->assertIsArray( $doc );
		$this->assertSame( 'at://mock-did/site.standard.document/doc123', $doc['uri'] );
	}

	private function create_test_post( string $original_message ): int {
		return wp_insert_post(
			[
				'post_content' => $original_message,
				'post_excerpt' => $original_message,
				'post_title'   => 'Test Post',
				'post_status'  => 'publish',
			]
		);
	}

	private function expect_api_call_with_message( string $expected_message ): void {
		$this->log_mock->shouldReceive( 'success' );
		$this->api_mock->shouldReceive( 'create_record' )
			->once()
			->withArgs(
				static function ( $body ) use ( $expected_message ) {
					return isset( $body['record']['text'] ) && $body['record']['text'] === $expected_message;
				}
			)
			->andReturn(
				[
					'uri' => 'mock-uri',
				]
			);
	}

	private function enable_standard_site_for_post( int $post_id ): void {
		update_option( 'autoblue_publish_documents_enabled', true );
		update_option( 'autoblue_connections', [ self::MOCK_CONNECTION ] );
		update_option(
			'autoblue_publication_record',
			[
				'did'  => self::MOCK_CONNECTION['did'],
				'uri'  => 'at://mock-did/site.standard.publication/pub123',
				'cid'  => 'pub-cid',
				'rkey' => 'pub123',
			]
		);
		update_post_meta( $post_id, 'autoblue_publish_document', true );
	}

	protected function tearDown(): void {
		remove_all_filters( 'autoblue/share_message' );
		delete_option( 'autoblue_publish_documents_enabled' );
		delete_option( 'autoblue_connections' );
		delete_option( 'autoblue_publication_record' );
		Mockery::close();
		parent::tearDown();
	}
}
