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

	public function __construct( Bluesky\API $api_client = null, Logging\Log $log = null ) {
		$this->api_client = $api_client ?: new Bluesky\API();
		$this->log        = $log ?: new Logging\Log();
	}

	private function convert_at_uri_to_bluesky_url( string $did, string $at_uri ): string {
		$rkey = explode( '/', $at_uri );
		$rkey = end( $rkey );

		return "https://bsky.app/profile/{$did}/post/{$rkey}";
	}

	/**
	 * @return array<string,mixed>|false The connection data or false if no connection is found.
	 */
	public function get_connection() {
		$connections = ( new ConnectionsManager() )->get_all_connections();

		if ( empty( $connections ) ) {
			return false;
		}

		return $connections[0];
	}

	/**
	 * Refresh the connection tokens for a given DID.
	 *
	 * @param string $did The DID to refresh the connection for.
	 * @return array<string,mixed>|\WP_Error The refreshed connection data or error object.
	 */
	public function refresh_connection( string $did ) {
		$connections_manager = new ConnectionsManager();
		$connection          = $connections_manager->refresh_tokens( $did );

		return $connection;
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
		$allowed_types = [ 'image/jpeg', 'image/png', 'image/webp' ];

		if ( ! $mime_type || ! in_array( $mime_type, $allowed_types, true ) ) {
			$this->log->error(
				__( 'Skipping image: Invalid mime type for image with ID `{attachment_id}`. Allowed types are `{allowed_types}`, but got `{mime_type}`.', 'autoblue' ),
				[
					'attachment_id' => $image_id,
					'allowed_types' => $allowed_types,
					'mime_type'     => $mime_type,
				]
			);
			return false;
		}

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
			$this->log->error( __( 'Share failed: Post with ID {post_id} not found.', 'autoblue' ), [ 'post_id' => $post_id ] );
			return false;
		}

		$connection = $this->get_connection();

		if ( ! $connection ) {
			$this->log->error( __( 'Share failed: No Bluesky connection found.', 'autoblue' ), [ 'post_id' => $post_id ] );
			return false;
		}

		$connection = $this->refresh_connection( $connection['did'] );

		if ( is_wp_error( $connection ) ) {
			$this->log->error( __( 'Failed to refresh Bluesky connection tokens before sharing:', 'autoblue' ) . ' ' . $connection->get_error_message(), [ 'post_id' => $post_id ] );
			return false;
		}

		$message = get_post_meta( $post_id, 'autoblue_custom_message', true );
		$excerpt = get_the_excerpt( $post->ID );
		$message = ! empty( $message ) ? $message : $excerpt;

		/**
		 * Filters the message to be shared on Bluesky.
		 *
		 * @param string $message The message to be shared.
		 * @param int    $post_id The post ID of the post being shared.
		 */
		$message = apply_filters( 'autoblue/share_message', $message, $post_id );

		$message = html_entity_decode( wp_strip_all_tags( $message ), ENT_QUOTES );
		$message = ( new Utils\Text() )->trim_text( $message, 300 );

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
						'title'       => html_entity_decode( get_the_title( $post->ID ), ENT_QUOTES ),
						'description' => html_entity_decode( wp_strip_all_tags( $excerpt ), ENT_QUOTES ),
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
				__( 'Failed to share post with ID {post_id} to Bluesky:', 'autoblue' ) . ' ' . $response->get_error_message(),
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

		$this->log->success(
			__( 'Shared post {post_title} with ID {post_id} to Bluesky: {bluesky_url}', 'autoblue' ),
			[
				'post_id'     => $post_id,
				'post_title'  => $post->post_title,
				'bluesky_url' => $this->convert_at_uri_to_bluesky_url( $connection['did'], $response['uri'] ),
				'body'        => $body,
				'response'    => $response,
			]
		);

		return $share;
	}
}
