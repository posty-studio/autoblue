<?php

namespace Autoblue;

class Bluesky {
	/**
	 * The Bluesky API client.
	 *
	 * @var Bluesky\API
	 */
	private Bluesky\API $api_client;

	/** @var Logging\Log */
	private Logging\Log $log;

	public function __construct() {
		$this->api_client = new Bluesky\API();
		$this->log        = new Logging\Log();
	}

	private function convert_at_uri_to_bluesky_url( string $did, string $at_uri ): string {
		$rkey = end( explode( '/', $at_uri ) );

		return "https://bsky.app/profile/{$did}/post/{$rkey}";
	}

	/**
	 * @return array<string,mixed>|false The connection data or false if no connection is found.
	 */
	private function get_connection() {
		$connections = ( new ConnectionsManager() )->get_all_connections();

		if ( empty( $connections ) ) {
			return false;
		}

		return $connections[0];
	}

	/**
	 * @return array<string,mixed>|false
	 */
	private function upload_image( int $image_id, string $access_token ) {
		if ( ! $image_id || ! $access_token ) {
			return false;
		}

		$image_path = get_attached_file( $image_id );

		if ( ! $image_path || ! file_exists( $image_path ) ) {
			$this->log->error( __( 'Skipping image: Failed to find image with ID `{attachment_id}`.', 'autoblue' ), [ 'attachment_id' => $image_id ] );
			return false;
		}

		$mime_type     = get_post_mime_type( $image_id );
		$allowed_types = [ 'image/jpeg', 'image/png' ];

		// if ( ! $mime_type || ! in_array( $mime_type, $allowed_types, true ) ) {
		// $this->log->error(
		// __( 'Skipping image: Invalid mime type for image with ID `{attachment_id}`. Allowed types are `{allowed_types}`, but got `{mime_type}`.', 'autoblue' ),
		// [
		// 'attachment_id' => $image_id,
		// 'allowed_types' => $allowed_types,
		// 'mime_type'     => $mime_type,
		// ]
		// );
		// return false;
		// }

		$image_compressor = new ImageCompressor( $image_path, $mime_type );
		$image_blob       = $image_compressor->compress_image();

		if ( ! $image_blob ) {
			$this->log->error( __( 'Skipping image: Compression failed for image with ID `{attachment_id}`.', 'autoblue' ), [ 'attachment_id' => $image_id ] );
			return false;
		}

		$blob = $this->api_client->upload_blob( $image_blob, $mime_type, $access_token );

		if ( is_wp_error( $blob ) ) {
			$this->log->error( __( 'Skipping image: Failed to upload image with ID `{attachment_id}` to Bluesky:', 'autoblue' ) . ' ' . $blob->get_error_message(), [ 'attachment_id' => $image_id ] );
			return false;
		}

		$this->log->debug(
			__( 'Uploaded image with ID `{attachment_id}` to Bluesky.', 'autoblue' ),
			[
				'attachment_id' => $image_id,
			]
		);

		return $blob;
	}

	/**
	 * @return array<string,mixed>|false
	 */
	public function share_to_bluesky( int $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			$this->log->error( sprintf( __( 'Share failed: Post with ID `%1$d` not found.', 'autoblue' ), $post_id ), [ 'post_id' => $post_id ] );
			return false;
		}

		$connection = $this->get_connection();

		if ( ! $connection ) {
			$this->log->error( sprintf( __( 'Share failed: No Bluesky connection found.' ) ), [ 'post_id' => $post_id ] );
			return false;
		}

		$connections_manager = new ConnectionsManager();
		$connection          = $connections_manager->refresh_tokens( $connection['did'] );

		if ( is_wp_error( $connection ) ) {
			$this->log->error( __( 'Failed to refresh Bluesky connection tokens before sharing:', 'autoblue' ) . ' ' . $connection->get_error_message(), [ 'post_id' => $post_id ] );
			return false;
		}

		$message = get_post_meta( $post_id, 'autoblue_custom_message', true );
		$excerpt = html_entity_decode( get_the_excerpt( $post->ID ) );
		$message = ! empty( $message ) ? wp_strip_all_tags( html_entity_decode( $message ) ) : $excerpt;

		$body = [
			'collection' => 'app.bsky.feed.post',
			'did'        => esc_html( $connection['did'] ),
			'repo'       => esc_html( $connection['did'] ),
			'record'     => [
				'$type'     => 'app.bsky.feed.post',
				'text'      => $message,
				'createdAt' => gmdate( 'c', strtotime( $post->post_date_gmt ) ?: time() ),
				'embed'     => [
					'$type'    => 'app.bsky.embed.external',
					'external' => [
						'uri'         => get_permalink( $post->ID ),
						'title'       => get_the_title( $post->ID ),
						'description' => $excerpt,
					],
				],
			],
		];

		$facets = ( new Bluesky\TextParser() )->parse_facets( $message );

		if ( ! empty( $facets ) ) {
			$body['record']['facets'] = $facets;
		}

		$image_blob = false;
		if ( has_post_thumbnail( $post->ID ) ) {
			$image_blob = $this->upload_image( get_post_thumbnail_id( $post->ID ), $connection['access_jwt'] ); // @phpstan-ignore argument.type
		}

		if ( ! empty( $image_blob ) ) {
			$body['record']['embed']['external']['thumb'] = $image_blob;
		}

		$response = $this->api_client->create_record( $body, $connection['access_jwt'] );

		if ( is_wp_error( $response ) ) {
			$this->log->error(
				sprintf( __( 'Failed to share post with ID `%1$d` to Bluesky:', 'autoblue' ), $post_id ) . ' ' . $response->get_error_message(),
				[
					'post_id'  => $post_id,
					'body'     => $body,
					'response' => $response,
				]
			);
			return false;
		}

		$share = [
			'did'      => $connection['did'],
			'date'     => gmdate( 'c' ),
			'uri'      => $response['uri'],
			'response' => wp_json_encode( $response ),
		];

		$shares   = get_post_meta( $post_id, 'autoblue_shares', true );
		$shares   = ! empty( $shares ) ? $shares : [];
		$shares[] = $share;
		update_post_meta( $post_id, 'autoblue_shares', $shares );

		$bluesky_url = $this->convert_at_uri_to_bluesky_url( $connection['did'], $response['uri'] );

		$this->log->success(
			sprintf( __( 'Shared post `%1$s` with ID `%2$d` to Bluesky: %3$s', 'autoblue' ), $post->post_title, $post_id, $bluesky_url ),
			[
				'post_id'     => $post_id,
				'bluesky_url' => $this->convert_at_uri_to_bluesky_url( $connection['did'], $response['uri'] ),
				'body'        => $body,
				'response'    => $response,
			]
		);

		return $share;
	}
}
