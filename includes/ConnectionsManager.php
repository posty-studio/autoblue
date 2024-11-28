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

	public function register_hooks() {
		add_action( self::REFRESH_CONNECTIONS_HOOK, [ $this, 'refresh_all_connections' ] );
	}

	/**
	 * Add a new connection with DID and app password.
	 *
	 * @param string $did The DID of the connection.
	 * @param string $app_password The app password for authentication.
	 * @return array|\WP_Error The added connection with profile data or error object.
	 */
	public function add_connection( $did, $app_password ) {
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
	 * Delete a connection by DID.
	 *
	 * @param string $did The DID of the connection to delete.
	 * @return bool|\WP_Error True on success, error otherwise.
	 */
	public function delete_connection( $did ) {
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
	 * Get a connection by DID, with an option to force a profile refresh.
	 *
	 * @param string $did The DID of the connection.
	 * @param bool   $force_refresh Force API call to refresh profile cache.
	 * @return array|null The connection data or null if not found.
	 */
	public function get_connection_by_did( $did, $force_refresh = false ) {
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
	 * Get all connections, optionally forcing a profile refresh.
	 *
	 * @param bool $force_refresh Force API call for all profiles.
	 * @return array List of connections with profile data.
	 */
	public function get_all_connections( $force_refresh = false ) {
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
	 * Refresh a connection's access JWT using its refresh JWT.
	 *
	 * @param string $did The DID of the connection to refresh.
	 * @return array|\WP_Error The refreshed connection data or error object.
	 */
	public function refresh_tokens( $did ) {
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
	 * Fetch profile data for a DID, with caching support.
	 *
	 * @param string $did The DID to fetch profile for.
	 * @param bool   $force_refresh Force profile fetch from the API.
	 * @return array|null Sanitized profile data or null if not found.
	 */
	private function fetch_and_cache_profile( $did, $force_refresh = false ) {
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
	 * Store or update a connection in the database.
	 *
	 * @param array $connection The connection data.
	 * @param bool  $update Whether to perform an update (otherwise add).
	 */
	private function store_connection( array $connection, $update = false ) {
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

	/**
	 * Refresh all connections.
	 */
	public function refresh_all_connections() {
		$connections = $this->get_all_connections( true );

		if ( empty( $connections ) ) {
			return;
		}

		foreach ( $connections as $connection ) {
			$this->refresh_tokens( $connection['did'] );
		}
	}

	/**
	 * Check if a connection with the given DID already exists.
	 *
	 * @param string $did The DID to check.
	 * @return bool True if the connection exists, false otherwise.
	 */
	private function connection_exists( $did ) {
		$connections = get_option( self::OPTION_KEY, [] );
		return in_array( $did, array_column( $connections, 'did' ), true );
	}

	/**
	 * Validate DID format.
	 *
	 * @param string $did The DID to validate.
	 * @return bool True if the DID is valid, false otherwise.
	 */
	private function is_valid_did( $did ) {
		return preg_match( '/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/', $did );
	}

	/**
	 * Sanitize profile data.
	 *
	 * @param array $profile The profile data to sanitize.
	 * @return array The sanitized profile data.
	 */
	private function sanitize_profile( $profile ) {
		return [
			'handle' => sanitize_text_field( $profile['handle'] ?? '' ),
			'name'   => sanitize_text_field( $profile['displayName'] ?? '' ),
			'avatar' => esc_url_raw( $profile['avatar'] ?? '' ),
		];
	}

	/**
	 * Get the transient key for a specific DID.
	 *
	 * @param string $did The DID to generate the key for.
	 * @return string The transient key.
	 */
	private function get_transient_key( $did ) {
		return self::TRANSIENT_PREFIX . md5( $did );
	}
}
