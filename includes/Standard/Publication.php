<?php

namespace Autoblue\Standard;

use Autoblue\Bluesky\API;
use Autoblue\ConnectionsManager;
use Autoblue\ImageCompressor;
use Autoblue\Logging\Log;
use Autoblue\Utils;

/**
 * Manages the site's `site.standard.publication` record on the connected PDS.
 */
class Publication {
	private const OPTION_KEY    = 'autoblue_publication_record';
	private const OVERRIDES_KEY = 'autoblue_publication_overrides';
	private const COLLECTION    = 'site.standard.publication';

	/** @var API */
	private $api_client;

	/** @var Log */
	private $log;

	public function __construct( ?API $api_client = null, ?Log $log = null ) {
		$this->api_client = $api_client ?: new API();
		$this->log        = $log ?: new Log();
	}

	public function register_hooks(): void {
		add_action( 'update_option_blogname', [ $this, 'sync' ] );
		add_action( 'update_option_blogdescription', [ $this, 'sync' ] );
		add_action( 'update_option_site_icon', [ $this, 'sync' ] );
		add_action( 'update_option_' . self::OVERRIDES_KEY, [ $this, 'sync' ] );
	}

	/**
	 * Return the publication's AT-URI, creating the record if missing.
	 *
	 * @return string|\WP_Error
	 */
	public function ensure_exists() {
		$connection = $this->get_active_connection();
		if ( is_wp_error( $connection ) ) {
			return $connection;
		}

		$stored = get_option( self::OPTION_KEY, [] );
		if ( ! empty( $stored['uri'] ) && ! empty( $stored['did'] ) && $stored['did'] === $connection['did'] ) {
			return $stored['uri'];
		}

		return $this->create( $connection );
	}

	/**
	 * Get the cached publication AT-URI without making network calls.
	 */
	public function get_uri(): ?string {
		$stored = get_option( self::OPTION_KEY, [] );
		return ! empty( $stored['uri'] ) ? (string) $stored['uri'] : null;
	}

	/**
	 * Reactively update the publication record when WP settings or overrides change.
	 */
	public function sync(): void {
		if ( ! Utils::is_standard_site_enabled() ) {
			return;
		}

		$stored = get_option( self::OPTION_KEY, [] );
		if ( empty( $stored['uri'] ) || empty( $stored['rkey'] ) || empty( $stored['did'] ) ) {
			return;
		}

		$connection = $this->get_active_connection();
		if ( is_wp_error( $connection ) || $connection['did'] !== $stored['did'] ) {
			return;
		}

		$record      = $this->build_record( $connection );
		$fingerprint = $this->fingerprint( $record );
		if ( ! empty( $stored['fingerprint'] ) && $stored['fingerprint'] === $fingerprint ) {
			return;
		}

		$response = $this->api_client->put_record(
			[
				'repo'       => $connection['did'],
				'collection' => self::COLLECTION,
				'rkey'       => $stored['rkey'],
				'record'     => $record,
			],
			$connection['access_jwt'],
			$connection['did']
		);

		if ( is_wp_error( $response ) ) {
			$this->log->error(
				__( 'Failed to update standard.site publication record: {message}', 'autoblue' ),
				[ 'message' => $response->get_error_message() ]
			);
			return;
		}

		update_option(
			self::OPTION_KEY,
			[
				'did'         => $connection['did'],
				'uri'         => (string) ( $response['uri'] ?? $stored['uri'] ),
				'cid'         => (string) ( $response['cid'] ?? '' ),
				'rkey'        => $stored['rkey'],
				'fingerprint' => $fingerprint,
			]
		);
	}

	/**
	 * Clear the cached publication record (e.g. on disconnect).
	 */
	public function clear(): void {
		delete_option( self::OPTION_KEY );
	}

	/**
	 * Build the publication record payload from WP settings + overrides.
	 *
	 * @param array<string,mixed> $connection
	 * @return array<string,mixed>
	 */
	public function build_record( array $connection ): array {
		$overrides = get_option( self::OVERRIDES_KEY, [] );
		if ( ! is_array( $overrides ) ) {
			$overrides = [];
		}

		$name        = $this->resolve_string( $overrides['name'] ?? null, get_bloginfo( 'name' ) );
		$description = $this->resolve_string( $overrides['description'] ?? null, get_bloginfo( 'description' ) );

		$record = [
			'$type' => self::COLLECTION,
			'url'   => untrailingslashit( home_url( '/' ) ),
			'name'  => $name,
		];

		if ( '' !== $description ) {
			$record['description'] = $description;
		}

		$icon_blob = $this->resolve_icon_blob( $overrides['icon_id'] ?? null, $connection );
		if ( $icon_blob ) {
			$record['icon'] = $icon_blob;
		}

		return $record;
	}

	/**
	 * @param array<string,mixed> $connection
	 * @return string|\WP_Error
	 */
	private function create( array $connection ) {
		$record = $this->build_record( $connection );

		$response = $this->api_client->create_record(
			[
				'repo'       => $connection['did'],
				'collection' => self::COLLECTION,
				'record'     => $record,
			],
			$connection['access_jwt'],
			$connection['did']
		);

		if ( is_wp_error( $response ) ) {
			$this->log->error(
				__( 'Failed to create standard.site publication record: {message}', 'autoblue' ),
				[ 'message' => $response->get_error_message() ]
			);
			return $response;
		}

		if ( empty( $response['uri'] ) ) {
			return new \WP_Error( 'autoblue_invalid_publication_response', __( 'Publication record was created but no URI was returned.', 'autoblue' ) );
		}

		$rkey = $this->extract_rkey( (string) $response['uri'] );

		update_option(
			self::OPTION_KEY,
			[
				'did'         => $connection['did'],
				'uri'         => (string) $response['uri'],
				'cid'         => (string) ( $response['cid'] ?? '' ),
				'rkey'        => $rkey,
				'fingerprint' => $this->fingerprint( $record ),
			]
		);

		$this->log->success(
			__( 'Created standard.site publication record at {uri}.', 'autoblue' ),
			[ 'uri' => $response['uri'] ]
		);

		return (string) $response['uri'];
	}

	/**
	 * @return array<string,mixed>|\WP_Error
	 */
	private function get_active_connection() {
		$connections_manager = new ConnectionsManager();
		$connections         = $connections_manager->get_all_connections();
		if ( empty( $connections ) ) {
			return new \WP_Error( 'autoblue_no_connection', __( 'No Bluesky connection found.', 'autoblue' ) );
		}

		$connection = $connections_manager->refresh_tokens( $connections[0]['did'] );
		if ( is_wp_error( $connection ) ) {
			return $connection;
		}

		return $connection;
	}

	private function resolve_string( $override, string $fallback ): string {
		if ( is_string( $override ) && '' !== trim( $override ) ) {
			return $override;
		}
		return $fallback;
	}

	/**
	 * @param array<string,mixed> $connection
	 * @return array<string,mixed>|null
	 */
	private function resolve_icon_blob( $override_id, array $connection ): ?array {
		$attachment_id = is_int( $override_id ) && $override_id > 0
			? $override_id
			: (int) get_option( 'site_icon', 0 );

		if ( $attachment_id <= 0 ) {
			return null;
		}

		$path = get_attached_file( $attachment_id );
		if ( ! $path || ! file_exists( $path ) ) {
			return null;
		}

		$mime    = get_post_mime_type( $attachment_id );
		$allowed = [ 'image/jpeg', 'image/png', 'image/webp' ];
		if ( ! $mime || ! in_array( $mime, $allowed, true ) ) {
			return null;
		}

		$compressor = new ImageCompressor( $path, $mime );
		$bytes      = $compressor->compress_image();
		if ( ! $bytes ) {
			return null;
		}

		$blob = $this->api_client->upload_blob( $bytes, $mime, $connection['access_jwt'], $connection['did'] );
		if ( is_wp_error( $blob ) ) {
			$this->log->error(
				__( 'Failed to upload publication icon: {message}', 'autoblue' ),
				[ 'message' => $blob->get_error_message() ]
			);
			return null;
		}

		return $blob;
	}

	/**
	 * @param array<string,mixed> $record
	 */
	private function fingerprint( array $record ): string {
		return md5( (string) wp_json_encode( $record ) );
	}

	private function extract_rkey( string $uri ): string {
		$parts = explode( '/', $uri );
		return (string) end( $parts );
	}
}
