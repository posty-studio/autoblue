<?php

namespace Autoblue;

class Comments {
	private function convert_bsky_url_to_at_uri( $url ) {
		if ( ! $url ) {
			return false;
		}

		if ( strpos( $url, 'bsky.app/profile/' ) === false ) {
			return false;
		}

		$path = wp_parse_url( $url, PHP_URL_PATH );

		if ( ! $path ) {
			return false;
		}

		$parts  = explode( '/', trim( $path, '/' ) );
		$handle = $parts[1] ?? false;

		if ( ! $handle ) {
			return false;
		}

		$rkey = explode( '/', $url );
		$rkey = end( $rkey );

		if ( ! $rkey ) {
			return false;
		}

		$transient = get_transient( 'autoblue_at_uri_' . $rkey );

		if ( $transient ) {
			return $transient;
		}

		$request_url = add_query_arg( [ 'actor' => $handle ], 'https://public.api.bsky.app/xrpc/app.bsky.actor.getProfile' );
		$response    = wp_safe_remote_get(
			$request_url,
			[
				'headers' => [ 'Content-Type' => 'application/json' ],
			]
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			Utils::error_log( 'Failed to get profile from Bluesky: ' . $response->get_error_message() );
			return false;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $response['did'] ) ) {
			return false;
		}

		$did = $response['did'];

		$uri = 'at://' . $did . '/app.bsky.feed.post/' . $rkey;

		set_transient( 'autoblue_at_uri_' . $rkey, $uri, DAY_IN_SECONDS );

		return $uri;
	}

	private function get_share( $post_id ) {
		if ( ! $post_id || ! is_numeric( $post_id ) ) {
			return false;
		}

		$post = get_post( $post_id );

		if ( ! $post || ! in_array( $post->post_type, [ 'post' ], true ) ) {
			return false;
		}

		$shares = get_post_meta( $post_id, 'autoblue_shares', true );

		if ( empty( $shares ) ) {
			return false;
		}

		return end( $shares );
	}

	public function get_comments( $post_id, $url = '' ) {
		// Short-circuit the post check if a URL is provided.
		if ( ! empty( $url ) ) {
			$uri = $this->convert_bsky_url_to_at_uri( $url );
		} else {
			$share     = $this->get_share( $post_id );
			$uri       = $share['uri'];
			$rkey      = explode( '/', $uri );
			$rkey      = end( $rkey );
			$share_url = 'https://bsky.app/profile/' . $share['did'] . '/post/' . $rkey;
		}

		if ( ! $uri ) {
			return false;
		}

		$transient = get_transient( 'autoblue_comments_' . $uri );

		if ( $transient ) {
			return $transient;
		}

		$comments = wp_remote_get( 'https://public.api.bsky.app/xrpc/app.bsky.feed.getPostThread?uri=' . $uri );

		if ( is_wp_error( $comments ) || 200 !== wp_remote_retrieve_response_code( $comments ) ) {
			Utils::error_log( 'Failed to get comments from Bluesky: ' . $comments->get_error_message() );
			return false;
		}

		$comments = json_decode( wp_remote_retrieve_body( $comments ), true );

		$return = [
			'comments' => $comments['thread'],
			'url'      => $url ? $url : $share_url,
		];

		// TODO: Strip the relevant parts from the comments and only cache that.
		set_transient( 'autoblue_comments_' . $uri, $return, MINUTE_IN_SECONDS );

		return $return;
	}
}
