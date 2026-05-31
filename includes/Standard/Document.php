<?php

namespace Autoblue\Standard;

use Autoblue\Bluesky\API;
use Autoblue\Logging\Log;

/**
 * Publishes WordPress posts as `site.standard.document` records on the PDS.
 */
class Document {
	private const META_KEY   = 'autoblue_document';
	private const COLLECTION = 'site.standard.document';

	/** @var API */
	private $api_client;

	/** @var Log */
	private $log;

	/** @var Publication */
	private $publication;

	public function __construct( ?API $api_client = null, ?Log $log = null, ?Publication $publication = null ) {
		$this->api_client  = $api_client ?: new API();
		$this->log         = $log ?: new Log();
		$this->publication = $publication ?: new Publication( $this->api_client, $this->log );
	}

	/**
	 * Publish a post as a standard.site document.
	 *
	 * @param int                                   $post_id
	 * @param array{uri:string,cid:string}|null     $bsky_post_ref
	 *        Optional Bluesky strongRef. Pass null to publish a document without a
	 *        back-reference (e.g. when writing the document BEFORE the bsky post so
	 *        the bsky post can embed it via app.bsky.embed.record).
	 * @param array<string,mixed>|null              $cover_blob_ref
	 *        Optional reusable blob ref from the bsky post upload.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function publish( int $post_id, ?array $bsky_post_ref, ?array $cover_blob_ref = null ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'autoblue_document_post_not_found', __( 'Post not found.', 'autoblue' ) );
		}

		if ( null !== $bsky_post_ref && ( empty( $bsky_post_ref['uri'] ) || empty( $bsky_post_ref['cid'] ) ) ) {
			return new \WP_Error( 'autoblue_document_invalid_bsky_ref', __( 'A Bluesky post URI and CID are required.', 'autoblue' ) );
		}

		$publication_uri = $this->publication->ensure_exists();
		if ( is_wp_error( $publication_uri ) ) {
			$this->log->error(
				__( 'Skipping standard.site document for post `{post_title}` with ID `{post_id}`: publication unavailable.', 'autoblue' ),
				[
					'post_id'    => $post_id,
					'post_title' => $post->post_title,
					'message'    => $publication_uri->get_error_message(),
				]
			);
			return $publication_uri;
		}

		$connection = $this->get_active_connection();
		if ( is_wp_error( $connection ) ) {
			return $connection;
		}

		$record = $this->build_record( $post, $publication_uri, $bsky_post_ref, $cover_blob_ref );
		$rkey   = $this->get_rkey( $post_id );

		$response = $this->api_client->create_record(
			[
				'repo'       => $connection['did'],
				'collection' => self::COLLECTION,
				'rkey'       => $rkey,
				'record'     => $record,
			],
			$connection['access_jwt'],
			$connection['did']
		);

		if ( is_wp_error( $response ) ) {
			$this->log->error(
				__( 'Failed to publish standard.site document for post `{post_title}` with ID `{post_id}`: {message}', 'autoblue' ),
				[
					'post_id'    => $post_id,
					'post_title' => $post->post_title,
					'message'    => $response->get_error_message(),
				]
			);
			return $response;
		}

		if ( empty( $response['uri'] ) ) {
			return new \WP_Error( 'autoblue_invalid_document_response', __( 'Document record was created but no URI was returned.', 'autoblue' ) );
		}

		$stored = $this->store_meta( $post_id, $response, $rkey );

		$this->log->success(
			__( 'Published standard.site document for post `{post_title}` with ID `{post_id}` at {uri}.', 'autoblue' ),
			[
				'post_id'    => $post_id,
				'post_title' => $post->post_title,
				'uri'        => $stored['uri'],
			]
		);

		return $stored;
	}

	/**
	 * Prepare the document record for an external batch write.
	 *
	 * @param array{uri:string,cid:string}|null $bsky_post_ref
	 * @param array<string,mixed>|null          $cover_blob_ref
	 * @return array{rkey:string,record:array<string,mixed>}|\WP_Error
	 */
	public function prepare_record( int $post_id, string $publication_uri, ?array $bsky_post_ref, ?array $cover_blob_ref = null ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'autoblue_document_post_not_found', __( 'Post not found.', 'autoblue' ) );
		}

		if ( null !== $bsky_post_ref && ( empty( $bsky_post_ref['uri'] ) || empty( $bsky_post_ref['cid'] ) ) ) {
			return new \WP_Error( 'autoblue_document_invalid_bsky_ref', __( 'A Bluesky post URI and CID are required.', 'autoblue' ) );
		}

		return [
			'rkey'   => $this->get_rkey( $post_id ),
			'record' => $this->build_record( $post, $publication_uri, $bsky_post_ref, $cover_blob_ref ),
		];
	}

	/**
	 * Persist document metadata from a PDS write response.
	 *
	 * @param array<string,mixed> $response
	 * @return array{uri:string,cid:string,rkey:string}
	 */
	public function store_meta( int $post_id, array $response, ?string $rkey = null ): array {
		$uri    = (string) ( $response['uri'] ?? '' );
		$stored = [
			'uri'  => $uri,
			'cid'  => (string) ( $response['cid'] ?? '' ),
			'rkey' => $rkey ?: $this->extract_rkey( $uri ),
		];

		update_post_meta( $post_id, self::META_KEY, $stored );

		return $stored;
	}

	public function get_rkey( int $post_id ): string {
		$stored = get_post_meta( $post_id, self::META_KEY, true );
		if ( is_array( $stored ) && ! empty( $stored['rkey'] ) ) {
			return (string) $stored['rkey'];
		}

		$rkey = 'ab' . strtolower( str_replace( '-', '', wp_generate_uuid4() ) );
		update_post_meta(
			$post_id,
			self::META_KEY,
			array_merge(
				is_array( $stored ) ? $stored : [],
				[ 'rkey' => $rkey ]
			)
		);

		return $rkey;
	}

	/**
	 * Back-fill bskyPostRef onto an already-published document.
	 *
	 * Called after the Bluesky post lands so we can point the document at
	 * its corresponding bsky post (useful for comment threading and
	 * reverse navigation in standard.site-aware readers).
	 *
	 * @param array{uri:string,cid:string} $bsky_post_ref
	 * @param array<string,mixed>|null     $cover_blob_ref Reused so coverImage stays intact.
	 * @param array<string,mixed>|null     $connection Already-refreshed connection.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function update_bsky_ref( int $post_id, array $bsky_post_ref, ?array $cover_blob_ref = null, ?array $connection = null ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'autoblue_document_post_not_found', __( 'Post not found.', 'autoblue' ) );
		}

		$stored = get_post_meta( $post_id, self::META_KEY, true );
		if ( ! is_array( $stored ) || empty( $stored['rkey'] ) ) {
			return new \WP_Error( 'autoblue_document_not_published', __( 'Document not yet published.', 'autoblue' ) );
		}

		$publication_uri = $this->publication->get_uri();
		if ( ! $publication_uri ) {
			return new \WP_Error( 'autoblue_publication_missing', __( 'Publication unavailable.', 'autoblue' ) );
		}

		if ( null === $connection ) {
			$connection = $this->get_active_connection();
			if ( is_wp_error( $connection ) ) {
				return $connection;
			}
		}

		$record = $this->build_record( $post, $publication_uri, $bsky_post_ref, $cover_blob_ref );

		$response = $this->api_client->put_record(
			[
				'repo'       => $connection['did'],
				'collection' => self::COLLECTION,
				'rkey'       => $stored['rkey'],
				'record'     => $record,
			],
			$connection['access_jwt'],
			$connection['did']
		);

		if ( is_wp_error( $response ) ) {
			$this->log->error(
				__( 'Failed to back-fill bskyPostRef on standard.site document for post `{post_title}` with ID `{post_id}`: {message}', 'autoblue' ),
				[
					'post_id'    => $post_id,
					'post_title' => $post->post_title,
					'message'    => $response->get_error_message(),
				]
			);
			return $response;
		}

		update_post_meta(
			$post_id,
			self::META_KEY,
			array_merge(
				$stored,
				[ 'cid' => (string) ( $response['cid'] ?? $stored['cid'] ) ]
			)
		);

		return $response;
	}

	/**
	 * @param \WP_Post                              $post
	 * @param string                                $publication_uri
	 * @param array{uri:string,cid:string}|null     $bsky_post_ref
	 * @param array<string,mixed>|null              $cover_blob_ref
	 * @return array<string,mixed>
	 */
	public function build_record( \WP_Post $post, string $publication_uri, ?array $bsky_post_ref, ?array $cover_blob_ref ): array {
		$record = [
			'$type'       => self::COLLECTION,
			'site'        => $publication_uri,
			'title'       => html_entity_decode( get_the_title( $post ), ENT_QUOTES ),
			'publishedAt' => gmdate( 'c', strtotime( $post->post_date_gmt ) ?: time() ),
		];

		if ( $bsky_post_ref ) {
			$record['bskyPostRef'] = [
				'uri' => $bsky_post_ref['uri'],
				'cid' => $bsky_post_ref['cid'],
			];
		}

		$path = wp_parse_url( get_permalink( $post ), PHP_URL_PATH );
		if ( is_string( $path ) && '' !== $path ) {
			$record['path'] = $path;
		}

		$description = $this->build_description( $post );
		if ( '' !== $description ) {
			$record['description'] = $description;
		}

		$text = $this->render_plain_text( $post );
		if ( '' !== $text ) {
			$record['textContent'] = $text;
		}

		$tags = $this->build_tags( $post );
		if ( ! empty( $tags ) ) {
			$record['tags'] = $tags;
		}

		if ( $cover_blob_ref ) {
			$record['coverImage'] = $cover_blob_ref;
		}

		if ( $post->post_modified_gmt && $post->post_modified_gmt !== $post->post_date_gmt ) {
			$record['updatedAt'] = gmdate( 'c', strtotime( $post->post_modified_gmt ) ?: time() );
		}

		return $record;
	}

	private function build_description( \WP_Post $post ): string {
		$excerpt = get_the_excerpt( $post );
		return trim( html_entity_decode( wp_strip_all_tags( (string) $excerpt ), ENT_QUOTES ) );
	}

	private function render_plain_text( \WP_Post $post ): string {
		$content = apply_filters( 'the_content', $post->post_content );
		$content = wp_strip_all_tags( (string) $content );
		$content = html_entity_decode( $content, ENT_QUOTES );
		$content = preg_replace( '/\s+/u', ' ', $content );
		return trim( (string) $content );
	}

	/**
	 * @return array<int,string>
	 */
	private function build_tags( \WP_Post $post ): array {
		$tags  = [];
		$terms = wp_get_post_tags( $post->ID );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$tags[] = $term->name;
			}
		}

		$cats = wp_get_post_categories( $post->ID, [ 'fields' => 'all' ] );
		if ( ! is_wp_error( $cats ) ) {
			foreach ( $cats as $cat ) {
				if ( 'uncategorized' === $cat->slug ) {
					continue;
				}
				$tags[] = $cat->name;
			}
		}

		$tags = array_values( array_unique( array_filter( array_map( 'strval', $tags ) ) ) );
		return array_slice( $tags, 0, 8 );
	}

	/**
	 * @return array<string,mixed>|\WP_Error
	 */
	private function get_active_connection() {
		$connections_manager = new \Autoblue\ConnectionsManager();
		$connections         = $connections_manager->get_all_connections();
		if ( empty( $connections ) ) {
			return new \WP_Error( 'autoblue_no_connection', __( 'No Bluesky connection found.', 'autoblue' ) );
		}

		$connection = $connections_manager->refresh_tokens( $connections[0]['did'] );
		if ( is_wp_error( $connection ) ) {
			return $connection;
		}

		return $connection;
	}

	private function extract_rkey( string $uri ): string {
		$parts = explode( '/', $uri );
		return (string) end( $parts );
	}
}
