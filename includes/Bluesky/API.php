<?php

namespace Autoblue\Bluesky;

class API {
	private const BASE_URL        = 'https://bsky.social';
	private const PUBLIC_BASE_URL = 'https://public.api.bsky.app';

	/**
	 * Get a Bluesky account DID from a handle.
	 *
	 * @param string $handle The handle of the account to search for.
	 * @return string|false The DID of the account or false if the account is not found.
	 */
	public function get_did_for_handle( $handle ) {
		if ( ! $handle ) {
			return false;
		}

		$data = $this->send_request(
			[
				'endpoint' => 'com.atproto.identity.resolveHandle',
				'body'     => [ 'handle' => sanitize_text_field( $handle ) ],
				'base_url' => self::BASE_URL,
			]
		);

		if ( is_wp_error( $data ) ) {
			return false;
		}

		return $data['did'] ?? false;
	}

	/**
	 * Get profiles for a list of DIDs.
	 *
	 * @param array $dids An array of DIDs to retrieve profiles for.
	 * @return array An array of profiles.
	 */
	public function get_profiles( $dids = [] ) {
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
	 * Create a new session.
	 *
	 * @param string $did          The DID of the account.
	 * @param string $app_password The app-specific password.
	 * @return array|WP_Error The session data or WP_Error on failure.
	 */
	public function create_session( $did, $app_password ) {
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
	 * Refresh an existing session.
	 *
	 * @param string $refresh_jwt The refresh token JWT.
	 * @return array|WP_Error The refreshed session data or WP_Error on failure.
	 */
	public function refresh_session( $refresh_jwt ) {
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
	 * Create a new record.
	 *
	 * @param array  $record       The record data.
	 * @param string $access_token The access token.
	 * @return array|WP_Error The created record data or WP_Error on failure.
	 */
	public function create_record( $record, $access_token ) {
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
	 * Upload a blob to the repository.
	 *
	 * @param string $blob         The blob data.
	 * @param string $mime_type    The MIME type of the blob.
	 * @param string $access_token The access token.
	 * @return array|WP_Error The blob reference or WP_Error on failure.
	 */
	public function upload_blob( $blob, $mime_type, $access_token ) {
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
	 * Send a request to the Bluesky API and handle the response.
	 *
	 * @param array<string, mixed> $args The request arguments.
	 * @return array|WP_Error The decoded response data or WP_Error on failure.
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
			$error_message = $data['error'] ?? __( 'Unknown error.', 'autoblue' );
			return new \WP_Error( 'autoblue_api_error', $error_message );
		}

		return $data;
	}

	/**
	 * Perform an XRPC call to the Bluesky API.
	 *
	 * @param array<string, mixed> $args The request arguments.
	 * @return array|WP_Error The response or WP_Error on failure.
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
			$request_args['body'] = wp_json_encode( $args['body'] );
		}

		return wp_safe_remote_request( $url, $request_args );
	}
}
