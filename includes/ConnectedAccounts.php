<?php
namespace Autoblue;

class ConnectedAccounts {
	private const OPTION_KEY             = 'autoblue_accounts';
	private const PROFILES_TRANSIENT_KEY = 'autoblue_profiles';
	private const PROFILE_CACHE_TIME     = DAY_IN_SECONDS;

	private $accounts        = [];
	private $accounts_option = [];

	public function __construct() {
		$this->accounts_option = get_option( self::OPTION_KEY, [] );
	}

	private function is_valid_did( $did ) {
		return preg_match( '/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/', $did );
	}

	private function fetch_profiles( $force_refresh = false ) {
		if ( ! $force_refresh ) {
			$cached_profiles = get_transient( self::PROFILES_TRANSIENT_KEY );

			if ( false !== $cached_profiles ) {
				return $cached_profiles;
			}
		}

		$dids             = array_column( $this->accounts_option, 'did' );
		$fetched_profiles = ( new Bluesky\API() )->get_profiles( $dids );

		if ( empty( $fetched_profiles ) ) {
			return [];
		}

		$profiles = array_reduce(
			$fetched_profiles,
			function ( $carry, $profile ) {
				$carry[ $profile->did ] = [
					'handle' => $profile->handle,
					'name'   => $profile->displayName,
					'avatar' => $profile->avatar,
				];
				return $carry;
			},
			[]
		);

		set_transient( self::PROFILES_TRANSIENT_KEY, $profiles, self::PROFILE_CACHE_TIME );
		return $profiles;
	}

	public function get_connected_accounts( $force_refresh = false ) {
		if ( empty( $this->accounts_option ) ) {
			return [];
		}

		if ( ! empty( $this->accounts && ! $force_refresh ) ) {
			return $this->accounts;
		}

		$profiles = $this->fetch_profiles( $force_refresh );

		$this->accounts = array_map(
			function ( $account ) use ( $profiles ) {
				if ( isset( $profiles[ $account['did'] ] ) ) {
					$account['meta'] = $profiles[ $account['did'] ];
				}
				return new Models\ConnectedAccount( $account );
			},
			$this->accounts_option
		);

		return $this->accounts;
	}

	public function clear_cache() {
		delete_transient( self::PROFILES_TRANSIENT_KEY );
	}

	public function get_account_by_did( $did ) {
		$accounts = $this->get_connected_accounts();

		foreach ( $accounts as $account ) {
			if ( $account->get_did() === $did ) {
				return $account;
			}
		}

		return null;
	}

	public function add_connected_account( $did, $app_password ) {
		if ( ! $did || ! $this->is_valid_did( $did ) ) {
			return new \WP_Error( 'autoblue_invalid_did', __( 'Invalid DID.', 'autoblue' ) );
		}

		if ( ! $app_password ) {
			return new \WP_Error( 'autoblue_invalid_app_password', __( 'Invalid App Password.', 'autoblue' ) );
		}

		if ( $this->get_account_by_did( $did ) ) {
			return new \WP_Error( 'autoblue_account_exists', __( 'Account already exists.', 'autoblue' ) );
		}

		$session = ( new Bluesky\API() )->create_session( $did, $app_password );

		if ( ! $session ) {
			return new \WP_Error( 'autoblue_account_creation_failed', __( 'Account creation failed.', 'autoblue' ) );
		}

		$accounts   = $this->accounts_option;
		$account    = [
			'did'         => sanitize_text_field( $session['did'] ),
			'access_jwt'  => sanitize_text_field( $session['accessJwt'] ),
			'refresh_jwt' => sanitize_text_field( $session['refreshJwt'] ),
		];
		$accounts[] = $account;

		update_option( self::OPTION_KEY, $accounts );
		$this->clear_cache();
	}

	public function remove_connected_account( $did ) {
		$this->accounts_option = array_filter(
			$this->accounts_option,
			function ( $account ) use ( $did ) {
				return $account['did'] !== $did;
			}
		);

		update_option( self::OPTION_KEY, $this->accounts_option );
		$this->clear_cache();
	}
}
