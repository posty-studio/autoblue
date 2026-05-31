<?php

namespace Autoblue\Standard;

use Autoblue\Utils;

/**
 * Serves the standard.site verification surface:
 *  - /.well-known/site.standard.publication (plain-text publication AT-URI)
 *  - <link rel="site.standard.document"> tags in singular post <head>s
 */
class Verification {
	public const WELL_KNOWN_PATH = '/.well-known/site.standard.publication';
	public const DOCUMENT_META   = 'autoblue_document';

	public function register_hooks(): void {
		add_action( 'parse_request', [ $this, 'maybe_serve_well_known' ] );
		add_action( 'wp_head', [ $this, 'inject_document_link' ] );
	}

	public function maybe_serve_well_known(): void {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		if ( self::WELL_KNOWN_PATH !== $request_uri ) {
			return;
		}

		if ( ! Utils::is_standard_site_enabled() ) {
			$this->respond_not_found();
			return;
		}

		$uri = ( new Publication() )->get_uri();
		if ( ! $uri ) {
			$this->respond_not_found();
			return;
		}

		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo esc_html( $uri );
		exit;
	}

	public function inject_document_link(): void {
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		$post_type = get_post_type( $post_id );
		if ( ! $post_type || ! in_array( $post_type, Utils::get_enabled_post_types(), true ) ) {
			return;
		}

		$document = get_post_meta( $post_id, self::DOCUMENT_META, true );
		if ( ! is_array( $document ) || empty( $document['uri'] ) ) {
			return;
		}

		printf(
			'<link rel="site.standard.document" href="%s" />' . "\n",
			esc_attr( (string) $document['uri'] )
		);
	}

	private function respond_not_found(): void {
		status_header( 404 );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo 'Not found';
		exit;
	}
}
