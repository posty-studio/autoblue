<?php

namespace Autoblue;

class Bluesky {
	private function get_account() {
		$accounts_class = new Accounts();
		$accounts       = $accounts_class->get_accounts();

		if ( empty( $accounts ) ) {
			return false;
		}

		$account = $accounts[0];

		return $account;
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

		$args = [
			'headers' => [
				'Content-Type'  => $mime_type,
				'Authorization' => 'Bearer ' . $access_token,
			],
			'body'    => $image_blob,
		];

		$blob_response = wp_remote_post( 'https://bsky.social/xrpc/com.atproto.repo.uploadBlob', $args );

		if ( is_wp_error( $blob_response ) ) {
			Utils::error_log( 'Failed to upload image to Bluesky: ' . $blob_response->get_error_message() );
			return false;
		}

		$response_body = json_decode( wp_remote_retrieve_body( $blob_response ), true );

		if ( ! isset( $response_body['blob'] ) ) {
			return false;
		}

		return $response_body['blob'];
	}

	public function share_to_bluesky( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		// For now, get the first account we have.
		$account = $this->get_account();

		if ( ! $account ) {
			return false;
		}

		$account = ( new Accounts() )->refresh_tokens_for_account_by_did( $account['did'] );

		$user_agent = apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ) );

		$message = get_post_meta( $post_id, 'autoblue_custom_message', true );
		$message = ! empty( $message ) ? sanitize_text_field( $message ) : '';

		$image_blob = false;
		if ( has_post_thumbnail( $post->ID ) ) {
			$image_blob = $this->upload_image( get_post_thumbnail_id( $post->ID ), $account['access_jwt'] );
		}

		$body = [
			'collection' => 'app.bsky.feed.post',
			'did'        => esc_html( $account['did'] ),
			'repo'       => esc_html( $account['did'] ),
			'record'     => [
				'$type'     => 'app.bsky.feed.post',
				'text'      => $message,
				'createdAt' => gmdate( 'c', strtotime( $post->post_date_gmt ) ),
				'embed'     => [
					'$type'    => 'app.bsky.embed.external',
					'external' => [
						'uri'         => get_permalink( $post->ID ),
						'title'       => get_the_title( $post->ID ),
						'description' => get_the_excerpt( $post->ID ),
					],
				],
			],
		];

		if ( ! empty( $image_blob ) ) {
			$body['record']['embed']['external']['thumb'] = $image_blob;
		}

		$response = wp_safe_remote_post(
			'https://bsky.social/xrpc/com.atproto.repo.createRecord',
			[
				'user-agent' => "$user_agent; Autoblue",
				'headers'    => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $account['access_jwt'],
				],
				'body'       => wp_json_encode( $body ),
			]
		);

		$status = wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );

		if ( 200 !== $status ) {
			Utils::error_log( 'Failed to share post to Bluesky: ' . $body );
			return false;
		}

		$encoded_body = $body;
		$body         = json_decode( $body );

		$share = [
			'did'      => $account['did'],
			'date'     => gmdate( 'c' ),
			'uri'      => $body->uri,
			'response' => $encoded_body,
		];

		$shares   = get_post_meta( $post_id, 'autoblue_shares', true );
		$shares   = ! empty( $shares ) ? $shares : [];
		$shares[] = $share;
		update_post_meta( $post_id, 'autoblue_shares', $shares );

		return $share;
	}
}
