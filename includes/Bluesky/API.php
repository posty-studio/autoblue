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

		$handle   = sanitize_text_field( $handle );
		$response = $this->do_xrpc_call(
			[
				'endpoint' => 'com.atproto.identity.resolveHandle',
				'body'     => [ 'handle' => $handle ],
				'base_url' => self::BASE_URL,
			]
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['did'] ) ) {
			return false;
		}

		return $body['did'];
	}

	public function get_profiles( $dids = [] ) {
		if ( ! is_array( $dids ) || empty( $dids ) ) {
			return [];
		}

		$profiles = $this->do_xrpc_call(
			[
				'endpoint' => 'app.bsky.actor.getProfiles',
				'body'     => [ 'actors' => $dids ],
				'base_url' => self::PUBLIC_BASE_URL,
			]
		);

		if ( is_wp_error( $profiles ) || 200 !== wp_remote_retrieve_response_code( $profiles ) ) {
			return [];
		}

		$body = json_decode( wp_remote_retrieve_body( $profiles ) );

		if ( ! isset( $body->profiles ) ) {
			return [];
		}

		return $body->profiles;
	}

	public function create_session( $did, $app_password ) {
		if ( ! $did || ! $app_password ) {
			return false;
		}

		$response = $this->do_xrpc_call(
			[
				'endpoint' => 'com.atproto.server.createSession',
				'method'   => 'POST',
				'body'     => wp_json_encode(
					[
						'identifier' => $did,
						'password'   => $app_password,
					]
				),
				'base_url' => self::BASE_URL,
			]
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		return $data;
	}

	/**
	 * Perform an XRPC call to the Bluesky API.
	 *
	 * @param array<string, mixed> $args The request arguments.
	 * @return array|WP_Error The response or WP_Error on failure.
	 */
	public function do_xrpc_call( $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'endpoint' => '',
				'method'   => 'GET',
				'body'     => [],
				'headers'  => [
					'Content-Type' => 'application/json',
				],
				'base_url' => self::BASE_URL,
			]
		);
		$url  = $args['base_url'] . '/xrpc/' . $args['endpoint'];

		$request = wp_safe_remote_request(
			$url,
			[
				'method'  => $args['method'],
				'headers' => $args['headers'],
				'body'    => $args['body'],
			]
		);

		return $request;
	}
}
