<?php

namespace Autoblue\Logging;

use Monolog\Processor\ProcessorInterface;

/**
 * Adds WordPress-specific information to the log record.
 */
class WPProcessor implements ProcessorInterface {
	public function __invoke( array $record ): array {
		if ( ! $record['extra'] ) {
			$record['extra'] = [];
		}

		$record['extra'] = array_merge(
			$record['extra'],
			[
				'request_uri'        => filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL ),
				'doing_cron'         => defined( 'DOING_CRON' ) ? (bool) DOING_CRON : null,
				'doing_ajax'         => defined( 'DOING_AJAX' ) ? (bool) DOING_AJAX : null,
				'doing_autosave'     => defined( 'DOING_AUTOSAVE' ) ? (bool) DOING_AUTOSAVE : null,
				'is_admin'           => is_admin(),
				'doing_rest'         => defined( 'REST_REQUEST' ) ? (bool) REST_REQUEST : null,
				'user_id'            => wp_get_current_user()->ID,
				'ms_switched'        => ms_is_switched(),
				'current_blog_id'    => get_current_blog_id(),
				'current_network_id' => get_current_network_id(),
				'is_ssl'             => is_ssl(),
				'environment'        => wp_get_environment_type(),
			]
		);

		return $record;
	}
}
