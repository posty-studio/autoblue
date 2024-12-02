<?php

namespace Autoblue\Endpoints;

use WP_REST_Controller;
use WP_REST_Server;

class LogsController extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = 'autoblue/v1';
		$this->rest_base = 'logs';
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_logs' ],
					'permission_callback' => [ $this, 'get_logs_permissions_check' ],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * @return bool
	 */
	public function get_logs_permissions_check(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * GET `/autoblue/v1/logs`
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_logs( \WP_REST_Request $request ) {
		$logger = new \Autoblue\Logging\LogRepository();
		$logs   = $logger->get_logs();
		return rest_ensure_response( $logs );
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
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'autoblue-logs',
			'type'    => 'object',
		];

		$schema = rest_default_additional_properties_to_false( $schema );

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}
}
