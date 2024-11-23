<?php

namespace Autoblue\Endpoints;

use WP_REST_Controller;
use WP_REST_Server;

class AccountsController extends WP_REST_Controller {
	private const DID_REGEX = '^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$';

	public function __construct() {
		$this->namespace = 'autoblue/v1';
		$this->rest_base = 'accounts';
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_accounts' ],
					'permission_callback' => [ $this, 'manage_accounts_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'add_account' ],
					'permission_callback' => [ $this, 'manage_accounts_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_account' ],
					'permission_callback' => [ $this, 'manage_accounts_permissions_check' ],
					'args'                => [
						'did' => [
							'type'        => 'string',
							'description' => __( 'DID of the account to be deleted.', 'autoblue' ),
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
	public function manage_accounts_permissions_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET `/autoblue/v1/accounts`
	 *
	 * @param WP_REST_Request $request The API request.
	 * @return WP_REST_Response
	 */
	public function get_accounts() {
		$accounts = new \Autoblue\Accounts();

		return rest_ensure_response( $accounts->get_accounts() );
	}

	/**
	 * POST `/autoblue/v1/accounts`
	 *
	 * @param WP_REST_Request $request The API request.
	 * @return WP_REST_Response
	 */
	public function add_account( $request ) {
		$accounts     = new \Autoblue\Accounts();
		$did          = $request->get_param( 'did' );
		$app_password = $request->get_param( 'app_password' );

		return rest_ensure_response( $accounts->add_account( $did, $app_password ) );
	}


	/**
	 * DELETE `/autoblue/v1/accounts`
	 *
	 * @param WP_REST_Request $request The API request.
	 * @return WP_REST_Response
	 */
	public function delete_account( $request ) {
		$accounts = new \Autoblue\Accounts();
		$did      = $request->get_param( 'did' );

		return rest_ensure_response( $accounts->delete_account( $did ) );
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
			'title'      => 'autoblue-accounts',
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
