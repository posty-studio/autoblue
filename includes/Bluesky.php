<?php

namespace Autoblue;

class Bluesky {
	/**
	 * The Bluesky API client.
	 *
	 * @var Bluesky\API
	 */
	private $api_client;

	public function __construct() {
		$this->api_client = new Bluesky\API();
	}

	private function get_connection() {
		$connections = ( new ConnectionsManager() )->get_all_connections();

		if ( empty( $connections ) ) {
			return false;
		}

		return $connections[0];
	}

	private function upload_image( $image_id, $access_token ) {
		if ( ! $image_id || ! $access_token ) {
			return false;
		}

		$image_path = get_attached_file( $image_id );

		if ( ! $image_path || ! file_exists( $image_path ) ) {
			return false;
		}

		$image_blob = ( new ImageCompressor() )->compress_image( $image_path, 1000000 );
		$mime_type  = get_post_mime_type( $image_id );

		if ( ! $image_blob || ! $mime_type ) {
			return false;
		}

		$blob = $this->api_client->upload_blob( $image_blob, $mime_type, $access_token );

		if ( is_wp_error( $blob ) ) {
			return false;
		}

		return $blob;
	}

	public function share_to_bluesky( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		$connection = $this->get_connection();

		if ( ! $connection ) {
			return false;
		}

		$connections_manager = new ConnectionsManager();
		$connection          = $connections_manager->refresh_tokens( $connection['did'] );

		if ( is_wp_error( $connection ) ) {
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
				'createdAt' => gmdate( 'c', strtotime( $post->post_date_gmt ) ),
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
			$image_blob = $this->upload_image( get_post_thumbnail_id( $post->ID ), $connection['access_jwt'] );
		}

		if ( ! empty( $image_blob ) ) {
			$body['record']['embed']['external']['thumb'] = $image_blob;
		}

		$response = $this->api_client->create_record( $body, $connection['access_jwt'] );

		if ( is_wp_error( $response ) ) {
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

		return $share;
	}
}
