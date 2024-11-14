<?php

namespace BSKY4WP\Endpoints;

use WP_REST_Controller;
use WP_REST_Server;

class SearchController extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = 'bsky4wp/v1';
		$this->rest_base = 'search';
	}

	public function register_routes() {
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
	public function get_search_results_permissions_check() {
		return true;
		return current_user_can( 'edit_posts' );
	}

	/**
	 * GET `/bsky4wp/v1/search`
	 *
	 * @param WP_REST_Request $request The API request.
	 * @return WP_REST_Response
	 */
	public function get_search_results( $request ) {
		$q = $request->get_param( 'q' );

		$endpoint = 'https://public.api.bsky.app/xrpc/app.bsky.actor.searchActorsTypeahead';

		$url = add_query_arg(
			[
				'q'     => $q,
				'limit' => 10,
			],
			$endpoint
		);

		$response = wp_safe_remote_get(
			$url,
			[
				'headers' => [
					'Content-Type' => 'application/json',
				],
			]
		);

		return rest_ensure_response( $response );
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
			'title'      => 'bsky4wp-search',
			'type'       => 'object',
			'properties' => [
				'q' => [
					'description' => __( 'Search query prefix', 'bsky4wp' ),
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
