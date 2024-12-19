<?php

namespace Autoblue\Logging;

class LogRepository {
	/**
	 * Process a log record before returning it.
	 *
	 * @param array<string, mixed> $log
	 * @return array<string, mixed>
	 */
	private function process_log( array $log ): array {
		$is_success     = strpos( $log['message'], '[Success]' ) === 0;
		$log['id']      = (int) $log['id'];
		$log['context'] = $log['context'] ? json_decode( $log['context'], true ) : null;
		$log['extra']   = $log['extra'] ? json_decode( $log['extra'], true ) : null;
		$log['level']   = $is_success ? 'success' : $log['level'];
		$log['message'] = $is_success ? substr( $log['message'], 10 ) : $log['message'];

		return $log;
	}

	/**
	 * Get a single log by ID.
	 *
	 * @param int $id
	 * @return array<string, mixed>|null
	 */
	public function get_log_by_id( int $id ): ?array {
		global $wpdb;

		$log = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE ID = %d',
				$wpdb->prefix . DatabaseHandler::TABLE_NAME,
				$id
			),
			ARRAY_A
		);

		if ( ! $log ) {
			return null;
		}

		return $this->process_log( $log );
	}

	/**
	 * Get logs.
	 *
	 * @param int $per_page
	 * @param int $page
	 * @return array<string, mixed>
	 */
	public function get_logs( int $per_page = 10, int $page = 1 ): array {
		global $wpdb;

		$page   = max( 1, $page );
		$offset = ( $page - 1 ) * $per_page;
		$table  = $wpdb->prefix . DatabaseHandler::TABLE_NAME;

		$logs = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i
				ORDER BY created_at DESC, ID DESC
				LIMIT %d OFFSET %d',
				$table,
				$per_page,
				$offset
			),
			ARRAY_A
		);

		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i',
				$table
			)
		);

		$total_pages = ceil( $total / $per_page );

		$logs = array_map( [ $this, 'process_log' ], $logs );

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


	/**
	 * Clear all logs.
	 *
	 * @return bool True if logs were cleared, false otherwise.
	 */
	public function clear_logs(): bool {
		global $wpdb;

		$truncated = $wpdb->query(
			$wpdb->prepare(
				'TRUNCATE TABLE %i',
				$wpdb->prefix . DatabaseHandler::TABLE_NAME
			)
		);

		return $truncated !== false;
	}
}
