<?php

namespace Autoblue;

class ConnectionsManager {
	public const REFRESH_CONNECTIONS_HOOK = 'autoblue_refresh_connections';
	private const OPTION_KEY              = 'autoblue_connections';
	private const TRANSIENT_PREFIX        = 'autoblue_connection_';

	/**
	 * The Bluesky API client.
	 *
	 * @var Bluesky\API
	 */
	private $api_client;

	public function __construct() {
		$this->api_client = new Bluesky\API();
	}

	public function register_hooks(): void {
		add_action( self::REFRESH_CONNECTIONS_HOOK, [ $this, 'refresh_all_connections' ] );
	}

	/**
	 * @return array<string,mixed>|\WP_Error The added connection with profile data or error object.
	 */
	public function add_connection( string $did, string $app_password ) {
		if ( $this->connection_exists( $did ) ) {
			return new \WP_Error( 'autoblue_connection_exists', __( 'Connection already exists.', 'autoblue' ) );
		}

		if ( ! $this->is_valid_did( $did ) ) {
			return new \WP_Error( 'autoblue_invalid_did', __( 'Invalid DID.', 'autoblue' ) );
		}

		if ( ! $app_password ) {
			return new \WP_Error( 'autoblue_invalid_password', __( 'Invalid app password.', 'autoblue' ) );
		}

		$auth_response = $this->api_client->create_session( $did, $app_password );

		if ( is_wp_error( $auth_response ) ) {
			return $auth_response;
		}

		$new_connection = [
			'did'         => sanitize_text_field( $did ),
			'access_jwt'  => sanitize_text_field( $auth_response['accessJwt'] ),
			'refresh_jwt' => sanitize_text_field( $auth_response['refreshJwt'] ),
		];

		$this->store_connection( $new_connection );

		$profile_data = $this->fetch_and_cache_profile( $new_connection['did'], true );

		if ( $profile_data ) {
			$new_connection['meta'] = $profile_data;
		}

		return $new_connection;
	}

	/**
	 * @return bool|\WP_Error
	 */
	public function delete_connection( string $did ) {
		if ( ! $did || ! $this->is_valid_did( $did ) ) {
			return new \WP_Error( 'autoblue_invalid_did', __( 'Invalid DID.', 'autoblue' ) );
		}

		$connections          = get_option( self::OPTION_KEY, [] );
		$filtered_connections = array_filter( $connections, fn( $connection ) => $connection['did'] !== $did );

		if ( count( $connections ) === count( $filtered_connections ) ) {
			return new \WP_Error( 'autoblue_connection_not_found', __( 'Connection not found.', 'autoblue' ) );
		}

		update_option( self::OPTION_KEY, $filtered_connections );
		delete_transient( $this->get_transient_key( $did ) );

		return true;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_connection_by_did( string $did, bool $force_refresh = false ): ?array {
		$connections = get_option( self::OPTION_KEY, [] );
		$connection  = current( array_filter( $connections, fn( $c ) => $c['did'] === $did ) );

		if ( ! $connection ) {
			return null;
		}

		$profile_data = $this->fetch_and_cache_profile( $did, $force_refresh );

		if ( $profile_data ) {
			$connection['meta'] = $profile_data;
		}

		return $connection;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_all_connections( bool $force_refresh = false ): array {
		$connections = get_option( self::OPTION_KEY, [] );

		if ( empty( $connections ) ) {
			return [];
		}

		// TODO: When we have multiple accounts (in the future), fetch this all in one call instead.
		foreach ( $connections as &$connection ) {
			$profile_data = $this->fetch_and_cache_profile( $connection['did'], $force_refresh );

			if ( $profile_data ) {
				$connection['meta'] = $profile_data;
			}
		}

		return $connections;
	}

	/**
	 * @return array<string,mixed>|\WP_Error The refreshed connection data or error object.
	 */
	public function refresh_tokens( string $did ) {
		$connection = $this->get_connection_by_did( $did );

		if ( ! $connection ) {
			return new \WP_Error( 'autoblue_connection_not_found', __( 'Connection not found.', 'autoblue' ) );
		}

		$response = $this->api_client->refresh_session( $connection['refresh_jwt'] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$connection = [
			'did'         => sanitize_text_field( $did ),
			'access_jwt'  => sanitize_text_field( $response['accessJwt'] ),
			'refresh_jwt' => sanitize_text_field( $response['refreshJwt'] ),
		];

		$this->store_connection( $connection, true );

		return $connection;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	private function fetch_and_cache_profile( string $did, bool $force_refresh = false ): ?array {
		$transient_key  = $this->get_transient_key( $did );
		$cached_profile = get_transient( $transient_key );

		if ( ! $force_refresh && $cached_profile ) {
			return $cached_profile;
		}

		$profile_data = $this->api_client->get_profiles( [ $did ] );

		// If something went wrong, return the cached profile if available.
		if ( ! $profile_data || empty( $profile_data[0] ) ) {
			return $cached_profile ?: null;
		}

		$sanitized_profile = $this->sanitize_profile( $profile_data[0] );

		set_transient( $transient_key, $sanitized_profile, DAY_IN_SECONDS );
		return $sanitized_profile;
	}

	/**
	 * @param array<string,mixed> $connection
	 */
	private function store_connection( array $connection, bool $update = false ): void {
		$connections = get_option( self::OPTION_KEY, [] );

		if ( $update ) {
			foreach ( $connections as &$stored_connection ) {
				if ( $stored_connection['did'] === $connection['did'] ) {
					$stored_connection = $connection;
					break;
				}
			}
		} else {
			$connections[] = $connection;
		}

		update_option( self::OPTION_KEY, $connections );
	}

	public function refresh_all_connections(): void {
		$connections = $this->get_all_connections( true );

		if ( empty( $connections ) ) {
			return;
		}

		foreach ( $connections as $connection ) {
			$this->refresh_tokens( $connection['did'] );
		}
	}

	private function connection_exists( string $did ): bool {
		$connections = get_option( self::OPTION_KEY, [] );
		return in_array( $did, array_column( $connections, 'did' ), true );
	}

	private function is_valid_did( string $did ): bool {
		return preg_match( '/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/', $did ) === 1;
	}

	/**
	 * @param array<string,mixed> $profile
	 * @return array<string,mixed>
	 */
	private function sanitize_profile( array $profile ): array {
		return [
			'handle' => sanitize_text_field( $profile['handle'] ?? '' ),
			'name'   => sanitize_text_field( $profile['displayName'] ?? '' ),
			'avatar' => esc_url_raw( $profile['avatar'] ?? '' ),
		];
	}

	private function get_transient_key( string $did ): string {
		return self::TRANSIENT_PREFIX . md5( $did );
	}
}
