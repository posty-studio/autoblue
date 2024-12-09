<?php

namespace Autoblue\Logging;

class LogRepository {
	private $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function get_logs( int $per_page = 10, int $page = 1 ): array {
		$page   = max( 1, $page );
		$offset = ( $page - 1 ) * $per_page;

		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->wpdb->prefix}" . DatabaseHandler::TABLE_NAME .
			' ORDER BY created_at DESC, ID DESC LIMIT %d OFFSET %d',
			$per_page,
			$offset
		);

		$logs        = $this->wpdb->get_results( $query, ARRAY_A );
		$total       = (int) $this->wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->wpdb->prefix}" . DatabaseHandler::TABLE_NAME
		);
		$total_pages = ceil( $total / $per_page );

		$logs = array_map(
			function ( $log ) {
				$is_success     = strpos( $log['message'], '[Success]' ) === 0;
				$log['id']      = (int) $log['id'];
				$log['context'] = $log['context'] ? json_decode( $log['context'], true ) : null;
				$log['extra']   = $log['extra'] ? json_decode( $log['extra'], true ) : null;
				$log['level']   = $is_success ? 'success' : $log['level'];
				$log['message'] = $is_success ? substr( $log['message'], 10 ) : $log['message'];

				return $log;
			},
			$logs
		);

		return [
			'data'       => $logs,
			'pagination' => [
				'page'        => $page,
				'per_page'    => $per_page,
				'total_items' => $total,
				'total_pages' => $total_pages,
			],
		];
	}

	public function clear_logs(): bool {
		return $this->wpdb->query(
			"TRUNCATE TABLE {$this->wpdb->prefix}" . DatabaseHandler::TABLE_NAME
		) !== false;
	}
}
