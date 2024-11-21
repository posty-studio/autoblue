<?php

namespace BSKY4WP;

class Accounts {
	private const TRANSIENT_KEY = 'bsky4wp_accounts';
	private const OPTION_KEY    = 'bsky4wp_accounts';
	private const API_ENDPOINT  = 'https://public.api.bsky.app/xrpc/app.bsky.actor.getProfiles';

	private function is_valid_did( $did ) {
		return preg_match( '/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/', $did );
	}

	/**
	 * Check if an account with the given DID already exists
	 *
	 * @param string $did      The DID to check
	 * @return boolean True if account exists, false otherwise
	 */
	private function account_exists( $did ) {
		$accounts = get_option( 'bsky4wp_accounts', [] );

		return array_reduce(
			$accounts,
			function ( $exists, $account ) use ( $did ) {
				return $exists || $account['did'] === $did;
			},
			false
		);
	}


	/**
	 * Get accounts with optional profile data from API
	 *
	 * @param bool $force_refresh Whether to force a refresh of the cached data
	 * @return array Array of sanitized account data
	 */
	public function get_accounts( $force_refresh = false ) {
		$accounts = get_option( self::OPTION_KEY, [] );

		if ( empty( $accounts ) ) {
			return [];
		}

		$accounts = array_map(
			function ( $account ) {
				unset( $account['access_jwt'], $account['refresh_jwt'] );
				return $account;
			},
			$accounts
		);

		if ( ! $force_refresh ) {
			$cached_profiles = get_transient( self::TRANSIENT_KEY );
			if ( $cached_profiles !== false ) {
				return $cached_profiles;
			}
		}

		$dids = array_column( $accounts, 'did' );

		$profiles = $this->fetch_profiles( $dids );
		if ( $profiles === false ) {
			return $accounts;
		}

		$accounts = $this->merge_profile_data( $accounts, $profiles );

		set_transient( self::TRANSIENT_KEY, $accounts, DAY_IN_SECONDS );

		return $accounts;
	}

	/**
	 * Fetch profiles from API
	 *
	 * @param array $dids Array of DIDs to fetch
	 * @return object|false Profile data or false on failure
	 */
	private function fetch_profiles( $dids ) {
		$url = add_query_arg( [ 'actors' => $dids ], self::API_ENDPOINT );

		$response = wp_safe_remote_get(
			$url,
			[
				'headers' => [ 'Content-Type' => 'application/json' ],
			]
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! $body || ! isset( $body->profiles ) ) {
			return false;
		}

		return $body->profiles;
	}

	/**
	 * Merge profile data with existing accounts
	 *
	 * @param array  $accounts Original accounts array
	 * @param object $profiles Profile data from API
	 * @return array Updated accounts array
	 */
	private function merge_profile_data( array $accounts, $profiles ): array {
		return array_map(
			function ( $account ) use ( $profiles ) {
				$meta = $this->find_matching_profile( $account['did'], $profiles );
				if ( ! $meta ) {
					return $account;
				}

				$account['meta'] = [
					'handle' => sanitize_text_field( $meta->handle ?? '' ),
					'name'   => sanitize_text_field( $meta->displayName ?? '' ),
					'avatar' => esc_url_raw( $meta->avatar ?? '' ),
				];

				return $account;
			},
			$accounts
		);
	}

	/**
	 * Find matching profile for a DID
	 *
	 * @param string $did DID to find profile for
	 * @param object $profiles Profiles to search through
	 * @return object|null Matching profile or null
	 */
	private function find_matching_profile( string $did, $profiles ) {
		$matches = array_filter(
			$profiles,
			function ( $profile ) use ( $did ) {
				return $profile->did === $did;
			}
		);

		return ! empty( $matches ) ? reset( $matches ) : null;
	}

	public function add_account( $did, $app_password ) {
		if ( ! $did || ! $this->is_valid_did( $did ) ) {
			return new \WP_Error( 'bsky4wp_invalid_did', __( 'Invalid DID.', 'bsky4wp' ) );
		}

		if ( ! $app_password ) {
			return new \WP_Error( 'bsky4wp_invalid_app_password', __( 'Invalid App Password.', 'bsky4wp' ) );
		}

		$url      = 'https://bsky.social/xrpc/com.atproto.server.createSession';
		$response = wp_remote_post(
			$url,
			[
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'identifier' => $did,
						'password'   => $app_password,
					]
				),
			]
		);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 300 ) {
			return new \WP_Error( 'bsky4wp_new_account_error', __( 'Error creating new account.', 'bsky4wp' ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		$accounts = get_option( 'bsky4wp_accounts', [] );

		if ( ! empty( $data['accessJwt'] ) && ! empty( $data['refreshJwt'] ) && ! empty( $data['did'] ) ) {
			if ( $this->account_exists( $data['did'] ) ) {
				return new \WP_Error( 'bsky4wp_account_exists', __( 'Account already exists.', 'bsky4wp' ) );
			}

			$accounts[] = [
				'did'         => sanitize_text_field( $data['did'] ),
				'access_jwt'  => sanitize_text_field( $data['accessJwt'] ),
				'refresh_jwt' => sanitize_text_field( $data['refreshJwt'] ),
			];

			update_option( 'bsky4wp_accounts', $accounts );
			delete_transient( 'bsky4wp_accounts' );
		}

		return $this->get_accounts();
	}

	public function delete_account( $did ) {
		if ( ! $did || ! $this->is_valid_did( $did ) ) {
			return new \WP_Error( 'bsky4wp_invalid_did', __( 'Invalid DID.', 'bsky4wp' ) );
		}

		$accounts = get_option( 'bsky4wp_accounts', [] );

		$accounts = array_filter(
			$accounts,
			function ( $account ) use ( $did ) {
				return $account['did'] !== $did;
			}
		);

		update_option( 'bsky4wp_accounts', $accounts );
		delete_transient( 'bsky4wp_accounts' );

		return $this->get_accounts();
	}
}
