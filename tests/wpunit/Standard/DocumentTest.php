<?php

namespace Tests\Standard;

use Autoblue\Standard\Document;
use lucatume\WPBrowser\TestCase\WPTestCase;

class DocumentTest extends WPTestCase {
	private const PUBLICATION_URI = 'at://did:plc:testuser/site.standard.publication/pub123';
	private const BSKY_REF        = [
		'uri' => 'at://did:plc:testuser/app.bsky.feed.post/post123',
		'cid' => 'bafyreitestcid',
	];

	public function test_build_record_has_required_fields(): void {
		$post_id = $this->insert_post( 'Article Title', 'Body content here.' );
		$post    = get_post( $post_id );

		$record = ( new Document() )->build_record( $post, self::PUBLICATION_URI, self::BSKY_REF, null );

		$this->assertSame( 'site.standard.document', $record['$type'] );
		$this->assertSame( self::PUBLICATION_URI, $record['site'] );
		$this->assertSame( 'Article Title', $record['title'] );
		$this->assertArrayHasKey( 'publishedAt', $record );
		$this->assertNotEmpty( $record['publishedAt'] );
	}

	public function test_build_record_includes_bsky_post_ref(): void {
		$post_id = $this->insert_post( 'Article Title', 'Body content here.' );
		$post    = get_post( $post_id );

		$record = ( new Document() )->build_record( $post, self::PUBLICATION_URI, self::BSKY_REF, null );

		$this->assertSame( self::BSKY_REF['uri'], $record['bskyPostRef']['uri'] );
		$this->assertSame( self::BSKY_REF['cid'], $record['bskyPostRef']['cid'] );
	}

	public function test_build_record_truncates_tags_to_8(): void {
		$post_id = $this->insert_post( 'Article', 'Body.' );

		$slugs = [];
		for ( $i = 1; $i <= 12; $i++ ) {
			$slugs[] = "tag-{$i}";
		}
		wp_set_post_tags( $post_id, $slugs );

		$post   = get_post( $post_id );
		$record = ( new Document() )->build_record( $post, self::PUBLICATION_URI, self::BSKY_REF, null );

		$this->assertCount( 8, $record['tags'] );
	}

	public function test_build_record_omits_cover_image_when_missing(): void {
		$post_id = $this->insert_post( 'Article', 'Body.' );
		$post    = get_post( $post_id );

		$record = ( new Document() )->build_record( $post, self::PUBLICATION_URI, self::BSKY_REF, null );

		$this->assertArrayNotHasKey( 'coverImage', $record );
	}

	public function test_build_record_includes_cover_image_when_provided(): void {
		$post_id = $this->insert_post( 'Article', 'Body.' );
		$post    = get_post( $post_id );

		$blob   = [
			'$type'    => 'blob',
			'ref'      => [ '$link' => 'bafyreiblob' ],
			'mimeType' => 'image/jpeg',
			'size'     => 1024,
		];
		$record = ( new Document() )->build_record( $post, self::PUBLICATION_URI, self::BSKY_REF, $blob );

		$this->assertSame( $blob, $record['coverImage'] );
	}

	public function test_build_record_includes_text_content(): void {
		$post_id = $this->insert_post( 'Article', '<p>Hello <strong>world</strong>.</p>' );
		$post    = get_post( $post_id );

		$record = ( new Document() )->build_record( $post, self::PUBLICATION_URI, self::BSKY_REF, null );

		$this->assertArrayHasKey( 'textContent', $record );
		$this->assertStringContainsString( 'Hello world.', $record['textContent'] );
		$this->assertStringNotContainsString( '<strong>', $record['textContent'] );
	}

	public function test_build_record_includes_path(): void {
		$post_id = $this->insert_post( 'Article', 'Body.' );
		$post    = get_post( $post_id );

		$record = ( new Document() )->build_record( $post, self::PUBLICATION_URI, self::BSKY_REF, null );

		$this->assertArrayHasKey( 'path', $record );
		$this->assertStringStartsWith( '/', $record['path'] );
	}

	private function insert_post( string $title, string $content ): int {
		return wp_insert_post(
			[
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'post',
			]
		);
	}
}
