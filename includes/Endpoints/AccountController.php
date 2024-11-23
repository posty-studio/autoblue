<?php

namespace Autoblue\Endpoints;

use WP_REST_Controller;
use WP_REST_Server;

class AccountController extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = 'autoblue/v1';
		$this->rest_base = 'account';
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_account_info' ],
					'permission_callback' => [ $this, 'get_account_info_permissions_check' ],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * @return bool
	 */
	public function get_account_info_permissions_check() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * GET `/autoblue/v1/account`
	 *
	 * @param WP_REST_Request $request The API request.
	 * @return WP_REST_Response
	 */
	public function get_account_info( $request ) {
		$url = add_query_arg(
			[
				'actor' => $request->get_param( 'did' ),
			],
			'https://public.api.bsky.app/xrpc/app.bsky.actor.getProfile'
		);

		$response = wp_safe_remote_get(
			$url,
			[
				'headers' => [
					'Content-Type' => 'application/json',
				],
			]
		);

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 300 ) {
			return new \WP_Error( 'autoblue_profile_error', $body );
		}

		return rest_ensure_response( $body );
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
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'autoblue-account',
			'type'       => 'object',
			'properties' => [
				'did' => [
					'description' => __( 'DID of the account to fetch.', 'autoblue' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'default'     => '',
				],
			],
		];

		$schema = rest_default_additional_properties_to_false( $schema );

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}
}
