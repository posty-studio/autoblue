<?php

namespace Autoblue\Endpoints;

use WP_REST_Controller;
use WP_REST_Server;

class ConnectionsController extends WP_REST_Controller {
	private const DID_REGEX = '^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$';

	public function __construct() {
		$this->namespace = 'autoblue/v1';
		$this->rest_base = 'connections';
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_connections' ],
					'permission_callback' => [ $this, 'manage_connections_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'add_connection' ],
					'permission_callback' => [ $this, 'manage_connections_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_connection' ],
					'permission_callback' => [ $this, 'manage_connections_permissions_check' ],
					'args'                => [
						'did' => [
							'type'        => 'string',
							'description' => __( 'DID of the connection to be deleted.', 'autoblue' ),
							'required'    => true,
							'pattern'     => self::DID_REGEX,
						],
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * @return bool
	 */
	public function manage_connections_permissions_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET `/autoblue/v1/connections`
	 *
	 * @return WP_REST_Response
	 */
	public function get_connections() {
		$connections = new \Autoblue\ConnectionsManager();

		return rest_ensure_response( $connections->get_all_connections() );
	}

	/**
	 * POST `/autoblue/v1/connections`
	 *
	 * @param WP_REST_Request $request The API request.
	 * @return WP_REST_Response
	 */
	public function add_connection( $request ) {
		$connections  = new \Autoblue\ConnectionsManager();
		$did          = $request->get_param( 'did' );
		$app_password = $request->get_param( 'app_password' );

		return rest_ensure_response( $connections->add_connection( $did, $app_password ) );
	}

	/**
	 * DELETE `/autoblue/v1/connections`
	 *
	 * @param WP_REST_Request $request The API request.
	 * @return WP_REST_Response
	 */
	public function delete_connection( $request ) {
		$connections = new \Autoblue\ConnectionsManager();
		$did         = $request->get_param( 'did' );

		return rest_ensure_response( $connections->delete_connection( $did ) );
	}

	/**
	 * Retrieves the endpoint schema, conforming to JSON Schema.
	 *
	 * @return array Schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = [
			'$schema'    => 'https://json-schema.org/draft-04/schema#',
			'title'      => 'autoblue-connections',
			'type'       => 'object',
			'properties' => [
				'did'          => [
					'description' => __( 'DID of the Bluesky account.', 'autoblue' ),
					'type'        => 'string',
					'pattern'     => self::DID_REGEX,
					'context'     => [ 'view', 'edit' ],
				],
				'app_password' => [
					'description' => __( 'An app password linked to the Bluesky account to be added.', 'autoblue' ),
					'type'        => 'string',
					'pattern'     => '^[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}$',
					'context'     => [ 'edit' ],
					'required'    => true,
				],
			],
		];

		$schema = rest_default_additional_properties_to_false( $schema );

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}
}
