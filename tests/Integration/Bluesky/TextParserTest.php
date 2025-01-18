<?php

namespace Tests\Bluesky;

use lucatume\WPBrowser\TestCase\WPTestCase;
use Autoblue\Bluesky\TextParser;
use Autoblue\Bluesky\API;
use Mockery;

class TextParserTest extends WPTestCase {
	private TextParser $parser;
	private $api_mock;

	protected function setUp(): void {
		$this->api_mock = Mockery::mock( API::class );
		$this->parser   = new TextParser( $this->api_mock );
	}

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_parse_mentions() {
		$text     = 'Hello @alice.bsky.social and @bob.test!';
		$mentions = $this->parser->parse_mentions( $text );

		$this->assertCount( 2, $mentions );

		$this->assertEquals( 5, $mentions[0]['start'] );
		$this->assertEquals( 24, $mentions[0]['end'] );
		$this->assertEquals( 'alice.bsky.social', $mentions[0]['handle'] );

		$this->assertEquals( 28, $mentions[1]['start'] );
		$this->assertEquals( 38, $mentions[1]['end'] );
		$this->assertEquals( 'bob.test', $mentions[1]['handle'] );
	}

	public function test_parse_mentions_invalid_handles() {
		$text     = 'Hello @not__valid and @123.numeric';
		$mentions = $this->parser->parse_mentions( $text );

		$this->assertEmpty( $mentions );
	}

	public function test_parse_urls() {
		$text = 'Check out https://example.com and http://test.org/page?q=1!';
		$urls = $this->parser->parse_urls( $text );

		$this->assertCount( 2, $urls );

		$this->assertEquals( 10, $urls[0]['start'] );
		$this->assertEquals( 29, $urls[0]['end'] );
		$this->assertEquals( 'https://example.com', $urls[0]['url'] );

		$this->assertEquals( 34, $urls[1]['start'] );
		$this->assertEquals( 58, $urls[1]['end'] );
		$this->assertEquals( 'http://test.org/page?q=1', $urls[1]['url'] );
	}

	public function test_parse_tags() {
		$text = 'Check out #bluesky and #atproto! #tag1 #tag2';
		$tags = $this->parser->parse_tags( $text );

		$this->assertCount( 4, $tags );

		$this->assertEquals( 10, $tags[0]['start'] );
		$this->assertEquals( 18, $tags[0]['end'] );
		$this->assertEquals( 'bluesky', $tags[0]['tag'] );

		$this->assertEquals( 23, $tags[1]['start'] );
		$this->assertEquals( 31, $tags[1]['end'] );
		$this->assertEquals( 'atproto', $tags[1]['tag'] );

		$this->assertEquals( 33, $tags[2]['start'] );
		$this->assertEquals( 38, $tags[2]['end'] );
		$this->assertEquals( 'tag1', $tags[2]['tag'] );

		$this->assertEquals( 39, $tags[3]['start'] );
		$this->assertEquals( 44, $tags[3]['end'] );
		$this->assertEquals( 'tag2', $tags[3]['tag'] );
	}

	public function test_parse_tags_with_invalid_tags() {
		$text = 'Invalid tags: # sample#tag';
		$tags = $this->parser->parse_tags( $text );

		$this->assertEmpty( $tags );
	}

	public function test_parse_tags_with_trailing_punctuation() {
		$text = 'Check #tag. and #tag! and #tag?';
		$tags = $this->parser->parse_tags( $text );

		$this->assertCount( 3, $tags );
		foreach ( $tags as $tag ) {
			$this->assertEquals( 'tag', $tag['tag'] );
		}
	}

	public function test_parse_facets() {
		$this->api_mock
			->shouldReceive( 'get_did_for_handle' )
			->with( 'alice.bsky.social' )
			->andReturn( 'did:plc:alice123' );

		$text   = 'Hello @alice.bsky.social! Check https://example.com and #bluesky';
		$facets = $this->parser->parse_facets( $text );

		$this->assertIsArray( $facets );

		$this->assertEquals( 'app.bsky.richtext.facet#mention', $facets[0]['features'][0]['$type'] );
		$this->assertEquals( 'did:plc:alice123', $facets[0]['features'][0]['did'] );

		$this->assertEquals( 'app.bsky.richtext.facet#link', $facets[1]['features'][0]['$type'] );
		$this->assertEquals( 'https://example.com', $facets[1]['features'][0]['uri'] );

		$this->assertEquals( 'app.bsky.richtext.facet#tag', $facets[2]['features'][0]['$type'] );
		$this->assertEquals( 'bluesky', $facets[2]['features'][0]['tag'] );
	}

	public function test_unicode_handling() {
		$text = 'Hello! 👋 Check #emoji🎉';
		$tags = $this->parser->parse_tags( $text );

		$this->assertIsArray( $tags );

		$this->assertEquals( 18, $tags[0]['start'] );
		$this->assertEquals( 28, $tags[0]['end'] );
		$this->assertEquals( 'emoji🎉', $tags[0]['tag'] );
	}

	public function test_empty_text() {
		$text   = '';
		$facets = $this->parser->parse_facets( $text );

		$this->assertEmpty( $facets );
	}

	public function test_text_without_facets() {
		$text   = 'Just some plain text without any mentions, URLs, or tags.';
		$facets = $this->parser->parse_facets( $text );

		$this->assertEmpty( $facets );
	}
}
