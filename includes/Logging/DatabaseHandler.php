<?php

namespace Autoblue\Logging;

use Monolog\Handler\AbstractProcessingHandler;

/**
 * Writes log records to the database.
 *
 * Inspired by: https://felipe.lavin.blog/2023/04/19/integrating-monolog-with-wordpress/
 */
class DatabaseHandler extends AbstractProcessingHandler {
	public const TABLE_NAME      = 'autoblue_logs';
	private const MAX_ROWS       = 100;
	private const TRUNCATE_BATCH = 10;
	private $wpdb;

	public function __construct( $wpdb ) {
		parent::__construct();
		$this->wpdb = $wpdb;
	}

	protected function write( array $record ): void {
		$data = [
			'level'   => strtolower( $record['level_name'] ),
			'message' => sanitize_text_field( $record['message'] ),
			'context' => ! empty( $record['context'] ) ? wp_json_encode( $record['context'] ) : null,
			'extra'   => ! empty( $record['extra'] ) ? wp_json_encode( $record['extra'] ) : null,
		];

		$table_name = $this->wpdb->prefix . self::TABLE_NAME;

		if ( ! $this->wpdb->insert( $table_name, $data, [ '%s', '%s', '%s', '%s' ] ) ) {
			error_log( 'Autoblue Logger Error: Failed to write log record: ' . $this->wpdb->last_error ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		$this->maybe_truncate();
	}

	private function maybe_truncate(): void {
		$table_name = $this->wpdb->prefix . self::TABLE_NAME;
		$count      = $this->wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

		if ( is_numeric( $count ) && self::MAX_ROWS <= (int) $count ) {
			$offset = self::MAX_ROWS - self::TRUNCATE_BATCH;

			$this->wpdb->query(
				$this->wpdb->prepare(
					"DELETE FROM $table_name WHERE id <= (
                        SELECT id FROM (
                            SELECT id FROM $table_name
                            ORDER BY created_at DESC
                            LIMIT 1 OFFSET %d
                        ) tmp
                    )",
					$offset
				)
			);
		}
	}
}
