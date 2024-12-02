<?php

namespace Autoblue\Bluesky;

class API {
	private const BASE_URL        = 'https://bsky.social';
	private const PUBLIC_BASE_URL = 'https://public.api.bsky.app';

	public function get_did_for_handle( string $handle ): ?string {
		if ( ! $handle ) {
			return null;
		}

		$data = $this->send_request(
			[
				'endpoint' => 'com.atproto.identity.resolveHandle',
				'body'     => [ 'handle' => sanitize_text_field( $handle ) ],
				'base_url' => self::BASE_URL,
			]
		);

		if ( is_wp_error( $data ) ) {
			return null;
		}

		return $data['did'] ?? null;
	}

	/**
	 * @param array<string> $dids
	 * @return array<int,array<string,mixed>>
	 */
	public function get_profiles( array $dids = [] ): array {
		if ( empty( $dids ) ) {
			return [];
		}

		$data = $this->send_request(
			[
				'endpoint' => 'app.bsky.actor.getProfiles',
				'body'     => [ 'actors' => $dids ],
				'base_url' => self::PUBLIC_BASE_URL,
			]
		);

		if ( is_wp_error( $data ) ) {
			return [];
		}

		return $data['profiles'] ?? [];
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_profile( string $did ): ?array {
		if ( ! $did ) {
			return null;
		}

		$data = $this->send_request(
			[
				'endpoint' => 'app.bsky.actor.getProfile',
				'body'     => [ 'actor' => $did ],
				'base_url' => self::PUBLIC_BASE_URL,
			]
		);

		if ( is_wp_error( $data ) ) {
			return null;
		}

		return $data;
	}

	/**
	 * @return array<string,mixed>|\WP_Error
	 */
	public function search_actors_typeahead( string $query ) {
		if ( ! $query ) {
			return [];
		}

		$data = $this->send_request(
			[
				'endpoint' => 'app.bsky.actor.searchActorsTypeahead',
				'body'     => [
					'q'     => sanitize_text_field( $query ),
					'limit' => 8,
				],
				'base_url' => self::PUBLIC_BASE_URL,
			]
		);

		return $data;
	}

	/**
	 * @return array<string,mixed>|\WP_Error
	 */
	public function create_session( string $did, string $app_password ) {
		if ( ! $did || ! $app_password ) {
			return new \WP_Error( 'autoblue_invalid_did_or_password', __( 'Invalid DID or password.', 'autoblue' ) );
		}

		return $this->send_request(
			[
				'endpoint' => 'com.atproto.server.createSession',
				'method'   => 'POST',
				'body'     => [
					'identifier' => $did,
					'password'   => $app_password,
				],
				'base_url' => self::BASE_URL,
			]
		);
	}

	/**
	 * @return array<string,mixed>|\WP_Error
	 */
	public function refresh_session( string $refresh_jwt ) {
		if ( ! $refresh_jwt ) {
			return new \WP_Error( 'autoblue_invalid_refresh_jwt', __( 'Invalid refresh JWT.', 'autoblue' ) );
		}

		return $this->send_request(
			[
				'endpoint' => 'com.atproto.server.refreshSession',
				'method'   => 'POST',
				'headers'  => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $refresh_jwt,
				],
				'base_url' => self::BASE_URL,
			]
		);
	}

	/**
	 * @param array<string, mixed> $record
	 * @return array<string,mixed>|\WP_Error
	 */
	public function create_record( array $record, string $access_token ) {
		if ( ! $record || ! $access_token ) {
			return new \WP_Error( 'autoblue_invalid_record_or_access_token', __( 'Invalid record or access token.', 'autoblue' ) );
		}

		return $this->send_request(
			[
				'endpoint' => 'com.atproto.repo.createRecord',
				'method'   => 'POST',
				'headers'  => [
					'Authorization' => 'Bearer ' . $access_token,
				],
				'body'     => $record,
				'base_url' => self::BASE_URL,
			]
		);
	}

	/**
	 * @return array<string,mixed>|\WP_Error
	 */
	public function upload_blob( string $blob, string $mime_type, string $access_token ) {
		if ( ! $blob || ! $mime_type ) {
			return new \WP_Error( 'autoblue_invalid_blob_or_mime_type', __( 'Invalid blob or MIME type.', 'autoblue' ) );
		}

		if ( ! $access_token ) {
			return new \WP_Error( 'autoblue_invalid_access_token', __( 'Invalid access token.', 'autoblue' ) );
		}

		$data = $this->send_request(
			[
				'endpoint' => 'com.atproto.repo.uploadBlob',
				'method'   => 'POST',
				'headers'  => [
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => $mime_type,
				],
				'body'     => $blob,
				'base_url' => self::BASE_URL,
			]
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return $data['blob'] ?? new \WP_Error( 'autoblue_upload_blob_error', __( 'Error uploading blob.', 'autoblue' ) );
	}

	/**
	 * @param array<string, mixed> $args
	 * @return array<string,mixed>|\WP_Error
	 */
	private function send_request( $args = [] ) {
		$response = $this->do_xrpc_call( $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( 200 !== $code ) {
			$error   = $data['error'] ?? __( 'Unknown error.', 'autoblue' );
			$message = $data['message'] ?? __( 'No message', 'autoblue' );
			return new \WP_Error( 'autoblue_api_error', $error . ': ' . $message );
		}

		return $data;
	}

	/**
	 * @param array<string, mixed> $args
	 * @return array<string,mixed>|\WP_Error
	 */
	private function do_xrpc_call( $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'endpoint' => '',
				'method'   => 'GET',
				'body'     => [],
				'headers'  => [],
				'base_url' => self::BASE_URL,
			]
		);

		$url = trailingslashit( $args['base_url'] ) . 'xrpc/' . $args['endpoint'];

		if ( 'GET' === $args['method'] && ! empty( $args['body'] ) ) {
			$url = add_query_arg( $args['body'], $url );
		}

		$default_headers = [
			'Content-Type' => 'application/json',
		];

		$headers = array_merge( $default_headers, $args['headers'] );

		$request_args = [
			'method'  => $args['method'],
			'headers' => $headers,
		];

		if ( $args['method'] === 'POST' && ! empty( $args['body'] ) ) {
			$request_args['body'] = $headers['Content-Type'] === 'application/json' ? wp_json_encode( $args['body'] ) : $args['body'];
		}

		return wp_safe_remote_request( $url, $request_args );
	}
}
