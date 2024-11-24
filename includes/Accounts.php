<?php

namespace Autoblue;

class Accounts {
	private const TRANSIENT_KEY = 'autoblue_accounts';
	private const OPTION_KEY    = 'autoblue_accounts';
	private const API_ENDPOINT  = 'https://public.api.bsky.app/xrpc/app.bsky.actor.getProfiles';

	private function is_valid_did( $did ) {
		return preg_match( '/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/', $did );
	}

	/**
	 * Check if an account with the given DID already exists.
	 *
	 * @param string $did      The DID to check
	 * @return boolean True if account exists, false otherwise
	 */
	private function account_exists( $did ) {
		$accounts = get_option( 'autoblue_accounts', [] );

		return in_array( $did, array_column( $accounts, 'did' ) );
	}


	/**
	 * Get accounts with profile data from API.
	 *
	 * @param bool $force_refresh Whether to force a refresh of the cached data
	 * @return array Array of sanitized account data
	 */
	public function get_accounts( $force_refresh = false ) {
		$accounts = get_option( self::OPTION_KEY, [] );

		if ( empty( $accounts ) ) {
			return [];
		}

		if ( ! $force_refresh ) {
			$cached_accounts = get_transient( self::TRANSIENT_KEY );
			if ( $cached_accounts !== false ) {
				return $cached_accounts;
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

	public function get_account_by_did( $did ) {
		$accounts = $this->get_accounts();

		foreach ( $accounts as $account ) {
			if ( $account['did'] === $did ) {
				return $account;
			}
		}

		return null;
	}

	/**
	 * Fetch profiles from API.
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
			return new \WP_Error( 'autoblue_invalid_did', __( 'Invalid DID.', 'autoblue' ) );
		}

		if ( ! $app_password ) {
			return new \WP_Error( 'autoblue_invalid_app_password', __( 'Invalid App Password.', 'autoblue' ) );
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
			return new \WP_Error( 'autoblue_new_account_error', wp_remote_retrieve_body( $response ) );
			return new \WP_Error( 'autoblue_new_account_error', __( 'Error creating new account.', 'autoblue' ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $data || empty( $data['accessJwt'] ) || empty( $data['refreshJwt'] ) || empty( $data['did'] ) ) {
			return new \WP_Error( 'autoblue_new_account_error', __( 'Error creating new account.', 'autoblue' ) );
		}

		if ( $this->account_exists( $data['did'] ) ) {
			return new \WP_Error( 'autoblue_account_exists', __( 'Account already exists.', 'autoblue' ) );
		}

		$accounts   = get_option( 'autoblue_accounts', [] );
		$account    = [
			'did'         => sanitize_text_field( $data['did'] ),
			'access_jwt'  => sanitize_text_field( $data['accessJwt'] ),
			'refresh_jwt' => sanitize_text_field( $data['refreshJwt'] ),
		];
		$accounts[] = $account;

		update_option( 'autoblue_accounts', $accounts );
		delete_transient( self::TRANSIENT_KEY );

		// TODO: This is not pretty at all. Refactor this.
		$profiles = $this->fetch_profiles( [ $data['did'] ] );
		$account  = $this->merge_profile_data( [ $account ], $profiles )[0];

		return $account;
	}

	public function delete_account( $did ) {
		if ( ! $did || ! $this->is_valid_did( $did ) ) {
			return new \WP_Error( 'autoblue_invalid_did', __( 'Invalid DID.', 'autoblue' ) );
		}

		$accounts = get_option( 'autoblue_accounts', [] );

		$accounts = array_filter(
			$accounts,
			function ( $account ) use ( $did ) {
				return $account['did'] !== $did;
			}
		);

		update_option( 'autoblue_accounts', $accounts );
		delete_transient( 'autoblue_accounts' );

		return true;
	}

	// TODO: Rework the logic for updating accounts.
	public function refresh_tokens_for_account_by_did( $did ) {
		$accounts = get_option( 'autoblue_accounts', [] );

		$account = array_filter(
			$accounts,
			function ( $account ) use ( $did ) {
				return $account['did'] === $did;
			}
		);

		if ( empty( $account ) ) {
			return new \WP_Error( 'autoblue_account_not_found', __( 'Account not found.', 'autoblue' ) );
		}

		$account = reset( $account );

		$url      = 'https://bsky.social/xrpc/com.atproto.server.refreshSession';
		$response = wp_safe_remote_post(
			$url,
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $account['refresh_jwt'],
				],
			]
		);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 300 ) {
			return new \WP_Error( 'autoblue_refresh_error', __( 'Error refreshing account.', 'autoblue' ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $data || empty( $data['accessJwt'] ) || empty( $data['refreshJwt'] ) ) {
			return new \WP_Error( 'autoblue_refresh_error', __( 'Error refreshing account.', 'autoblue' ) );
		}

		$account['access_jwt']  = sanitize_text_field( $data['accessJwt'] );
		$account['refresh_jwt'] = sanitize_text_field( $data['refreshJwt'] );

		$accounts = get_option( 'autoblue_accounts', [] );
		$accounts = array_map(
			function ( $a ) use ( $account ) {
				if ( $a['did'] === $account['did'] ) {
					return $account;
				}

				return $a;
			},
			$accounts
		);

		update_option( 'autoblue_accounts', $accounts );
		delete_transient( 'autoblue_accounts' );

		return $account;
	}
}
