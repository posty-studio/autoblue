<?php

namespace Autoblue\Endpoints;

use Autoblue\Bluesky\API;
use Autoblue\ConnectionsManager;
use Autoblue\Standard\Publication;
use Autoblue\Utils;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * Surfaces the standard.site records currently on the connected PDS so the
 * admin "Records" tab can render them.
 */
class StandardRecordsController extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = 'autoblue/v1';
		$this->rest_base = 'standard/records';
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_records' ],
					'permission_callback' => [ $this, 'permissions_check' ],
					'args'                => [
						'cursor' => [
							'type'    => 'string',
							'default' => '',
						],
					],
				],
			]
		);
	}

	public function permissions_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /autoblue/v1/standard/records?cursor=...
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_records( \WP_REST_Request $request ) {
		if ( ! Utils::is_standard_site_enabled() ) {
			return new \WP_Error(
				'autoblue_standard_site_disabled',
				__( 'standard.site publishing is not enabled.', 'autoblue' ),
				[ 'status' => 400 ]
			);
		}

		$connections_manager = new ConnectionsManager();
		$connections         = $connections_manager->get_all_connections();
		if ( empty( $connections ) ) {
			return new \WP_Error( 'autoblue_no_connection', __( 'No Bluesky connection found.', 'autoblue' ), [ 'status' => 400 ] );
		}

		$connection = $connections_manager->refresh_tokens( $connections[0]['did'] );
		if ( is_wp_error( $connection ) ) {
			return $connection;
		}

		$api    = new API();
		$cursor = (string) $request->get_param( 'cursor' );

		$publication = null;
		if ( '' === $cursor ) {
			$pub_uri = ( new Publication() )->get_uri();
			if ( $pub_uri ) {
				$pub_response = $api->list_records(
					$connection['did'],
					'site.standard.publication',
					$connection['access_jwt']
				);
				if ( ! is_wp_error( $pub_response ) && ! empty( $pub_response['records'] ) ) {
					foreach ( $pub_response['records'] as $record ) {
						if ( isset( $record['uri'] ) && $record['uri'] === $pub_uri ) {
							$publication = $record;
							break;
						}
					}
				}
			}
		}

		$docs_response = $api->list_records(
			$connection['did'],
			'site.standard.document',
			$connection['access_jwt'],
			$cursor ?: null
		);

		if ( is_wp_error( $docs_response ) ) {
			return $docs_response;
		}

		return rest_ensure_response(
			[
				'publication' => $publication,
				'documents'   => $docs_response['records'] ?? [],
				'cursor'      => $docs_response['cursor'] ?? null,
			]
		);
	}
}
