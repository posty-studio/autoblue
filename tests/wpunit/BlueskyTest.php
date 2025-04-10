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

	protected function tearDown(): void {
		remove_all_filters( 'autoblue/share_message' );
		Mockery::close();
		parent::tearDown();
	}
}
