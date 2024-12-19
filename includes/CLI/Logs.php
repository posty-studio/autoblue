<?php

namespace Autoblue\CLI;

class Logs {
	/**
	 * Default fields to display for a log.
	 *
	 * @var array<string>
	 */
	protected $default_fields = [ 'id', 'created_at', 'level', 'message' ];

	/**
	 * Get a single log by ID.
	 *
	 * ## options
	 *
	 * <id>
	 * : The ID of the log to retrieve.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole log, returns the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - csv
	 *  - json
	 *  - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for a log:
	 *
	 * * id
	 * * created_at
	 * * level
	 * * message
	 *
	 * These fields are optionally available:
	 *
	 * * context
	 * * extra
	 *
	 * ## EXAMPLES
	 *
	 *     # Save the log context to a file.
	 *     $ wp autoblue logs get 123 --field=context > file.json
	 *
	 * @param array<mixed> $args
	 * @param array<mixed> $assoc_args
	 */
	public function get( $args, $assoc_args ): void {
		$log_id = (int) $args[0];
		$log    = ( new \Autoblue\Logging\LogRepository() )->get_log_by_id( $log_id );

		if ( ! $log ) {
			\WP_CLI::error( 'Log not found.' );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $log );
	}

	/**
	 * Gets a list of logs.
	 *
	 * ## OPTIONS
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each log.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each log:
	 *
	 * * id
	 * * created_at
	 * * level
	 * * message
	 *
	 * These fields are optionally available:
	 *
	 * * context
	 * * extra
	 *
	 * @subcommand list
	 *
	 * @param array<mixed> $args
	 * @param array<mixed> $assoc_args
	 * @return mixed
	 */
	public function list( $args, $assoc_args ) {
		$format = $assoc_args['format'];

		if ( ! empty( $assoc_args['fields'] ) ) {
			if ( is_string( $assoc_args['fields'] ) ) {
				$fields = explode( ',', $assoc_args['fields'] );
			} else {
				$fields = $assoc_args['fields'];
			}
		} else {
			$fields = $this->default_fields;
		}

		$logs = ( new \Autoblue\Logging\LogRepository() )->get_logs();
		$logs = $logs['data'];

		if ( $format === 'ids' ) {
			$ids = array_map( 'absint', wp_list_pluck( $logs, 'id' ) );
			echo implode( ' ', $ids ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		return \WP_CLI\Utils\format_items( $format, $logs, $fields );
	}


	/**
	 * Get Formatter object based on supplied parameters.
	 *
	 * @param array<mixed> $assoc_args Parameters passed to command. Determines formatting.
	 * @return \WP_CLI\Formatter
	 */
	protected function get_formatter( &$assoc_args ) {
		if ( ! empty( $assoc_args['fields'] ) ) {
			if ( is_string( $assoc_args['fields'] ) ) {
				$fields = explode( ',', $assoc_args['fields'] );
			} else {
				$fields = $assoc_args['fields'];
			}
		} else {
			$fields = $this->default_fields;
		}
		return new \WP_CLI\Formatter( $assoc_args, $fields );
	}
}
