<?php

namespace Autoblue;

class Comments {
	/**
	 * The Bluesky API client.
	 *
	 * @var Bluesky\API
	 */
	private Bluesky\API $api_client;

	public function __construct() {
		$this->api_client = new Bluesky\API();
	}

	/**
	 * Convert a Bluesky URL to an AT URI.
	 *
	 * @param string $url The Bluesky URL.
	 * @return string|false The AT URI or false if the URL is invalid.
	 */
	private function convert_bsky_url_to_at_uri( string $url ) {
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

		// TODO: Improve.
		if ( strpos( $handle, 'did:' ) === 0 ) {
			return 'at://' . $handle . '/app.bsky.feed.post/' . $rkey;
		}

		$transient = get_transient( 'autoblue_at_uri_' . $rkey );

		if ( $transient ) {
			return $transient;
		}

		$did = $this->api_client->get_did_for_handle( $handle );

		if ( ! $did ) {
			return false;
		}

		$uri = 'at://' . $did . '/app.bsky.feed.post/' . $rkey;

		set_transient( 'autoblue_at_uri_' . $rkey, $uri, DAY_IN_SECONDS );

		return $uri;
	}

	/**
	 * Get the share data for a post.
	 *
	 * @param int $post_id The post ID.
	 * @return array<string,mixed>|false The share data or false if the post is invalid.
	 */
	private function get_share( int $post_id ) {
		if ( ! $post_id ) {
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

	/**
	 * Get the comments URL for a post.
	 *
	 * @param int $post_id The post ID.
	 * @return string|false The comments URL or false if the post is invalid.
	 */
	private function get_comments_url( int $post_id ) {
		$meta = get_post_meta( $post_id, 'autoblue_post_url', true );

		if ( $meta ) {
			return $meta;
		}

		$share = $this->get_share( $post_id );

		if ( ! $share ) {
			return false;
		}

		$rkey = explode( '/', $share['uri'] );
		$rkey = end( $rkey );

		if ( ! $rkey ) {
			return false;
		}

		return 'https://bsky.app/profile/' . $share['did'] . '/post/' . $rkey;
	}

	/**
	 * Get the comments for a post.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $url The comments URL.
	 * @return array<string,mixed>|false The comments or false if the post is invalid.
	 */
	public function get_comments( int $post_id, string $url = '' ) {
		// Short-circuit the post check if a URL is provided.
		$url = $url ? $url : $this->get_comments_url( $post_id );

		if ( ! $url ) {
			return false;
		}

		$uri = $this->convert_bsky_url_to_at_uri( $url );

		if ( ! $uri ) {
			return false;
		}

		$transient = get_transient( 'autoblue_comments_' . $uri );

		if ( $transient ) {
			return $transient;
		}

		$comments = $this->api_client->get_post_thread( $uri );

		if ( ! $comments ) {
			return false;
		}

		$return = [
			'comments' => $comments['thread'],
			'url'      => $url,
		];

		// TODO: Strip the relevant parts from the comments and only cache that.
		set_transient( 'autoblue_comments_' . $uri, $return, 5 * MINUTE_IN_SECONDS );

		return $return;
	}
}
