<?php

namespace Autoblue\Endpoints;

use WP_REST_Controller;
use WP_REST_Server;

class SearchController extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = 'autoblue/v1';
		$this->rest_base = 'search';
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_search_results' ],
					'permission_callback' => [ $this, 'get_search_results_permissions_check' ],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * @return bool
	 */
	public function get_search_results_permissions_check(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * GET `/autoblue/v1/search`
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_search_results( \WP_REST_Request $request ) {
		$api     = new \Autoblue\Bluesky\API();
		$results = $api->search_actors_typeahead( $request['q'] );
		return rest_ensure_response( $results );
	}

	/**
	 * Retrieves the endpoint schema, conforming to JSON Schema.
	 *
	 * @return array<string,mixed> Schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'autoblue-search',
			'type'       => 'object',
			'properties' => [
				'q' => [
					'description' => __( 'Search query prefix', 'autoblue' ),
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
