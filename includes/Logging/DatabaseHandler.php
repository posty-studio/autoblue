<?php

namespace Autoblue\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Writes log records to the database.
 *
 * Inspired by: https://felipe.lavin.blog/2023/04/19/integrating-monolog-with-wordpress/
 */
class DatabaseHandler extends AbstractProcessingHandler {
	public const TABLE_NAME      = 'autoblue_logs';
	private const MAX_ROWS       = 500;
	private const TRUNCATE_BATCH = 10;

	public function __construct() {
		parent::__construct();
		$this->set_level_from_option();
	}

	private function set_level_from_option(): void {
		$level = get_option( 'autoblue_log_level' );

		switch ( $level ) {
			case 'debug':
				$this->setLevel( Logger::DEBUG );
				break;
			case 'info':
				$this->setLevel( Logger::INFO );
				break;
			case 'error':
				$this->setLevel( Logger::ERROR );
				break;
			case 'off':
				$this->setLevel( Logger::EMERGENCY );
				break;
		}
	}

	public function write( array $record ): void {
		global $wpdb;

		$data = [
			'level'   => strtolower( $record['level_name'] ),
			'message' => sanitize_text_field( $record['message'] ),
			'context' => ! empty( $record['context'] ) ? wp_json_encode( $record['context'] ) : null,
			'extra'   => ! empty( $record['extra'] ) ? wp_json_encode( $record['extra'] ) : null,
		];

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		if ( ! $wpdb->insert( $table_name, $data, [ '%s', '%s', '%s', '%s' ] ) ) {
			error_log( 'Autoblue Logger Error: Failed to write log record: ' . $wpdb->last_error ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		$this->maybe_truncate();
	}

	private function maybe_truncate(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i',
				$table_name
			)
		);

		if ( is_numeric( $count ) && self::MAX_ROWS <= (int) $count ) {
			$offset = self::MAX_ROWS - self::TRUNCATE_BATCH;

			$wpdb->query(
				$wpdb->prepare(
					'DELETE FROM %i WHERE id <= (
						SELECT id FROM (
							SELECT id FROM %i
							ORDER BY created_at DESC
							LIMIT 1 OFFSET %d
						) tmp
					)',
					$table_name,
					$table_name,
					$offset
				)
			);
		}
	}
}
