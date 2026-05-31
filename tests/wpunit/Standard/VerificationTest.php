<?php

namespace Tests\Standard;

use Autoblue\Standard\Verification;
use lucatume\WPBrowser\TestCase\WPTestCase;

class VerificationTest extends WPTestCase {
	public function test_inject_document_link_renders_on_singular_with_document(): void {
		$post_id = wp_insert_post(
			[
				'post_title'   => 'Article',
				'post_content' => 'Body',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			]
		);
		update_post_meta(
			$post_id,
			Verification::DOCUMENT_META,
			[
				'uri'  => 'at://did:plc:testuser/site.standard.document/doc123',
				'cid'  => 'bafyreitest',
				'rkey' => 'doc123',
			]
		);

		$this->go_to( get_permalink( $post_id ) );

		ob_start();
		( new Verification() )->inject_document_link();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'rel="site.standard.document"', $output );
		$this->assertStringContainsString( 'at://did:plc:testuser/site.standard.document/doc123', $output );
	}

	public function test_inject_document_link_skipped_on_singular_without_document(): void {
		$post_id = wp_insert_post(
			[
				'post_title'   => 'Article',
				'post_content' => 'Body',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			]
		);

		$this->go_to( get_permalink( $post_id ) );

		ob_start();
		( new Verification() )->inject_document_link();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_inject_document_link_skipped_when_not_singular(): void {
		$this->go_to( home_url( '/' ) );

		ob_start();
		( new Verification() )->inject_document_link();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}
}
