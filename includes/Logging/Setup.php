<?php

namespace Autoblue\Logging;

class Setup {
	private const DB_VERSION = '20241202'; // YYYYMMDD.

	public function register_hooks(): void {
		add_action( 'admin_init', [ $this, 'maybe_create_table' ] );
	}

	public function maybe_create_table(): void {
		if ( get_option( 'autoblue_db_version' ) !== self::DB_VERSION ) {
			$this->create_table();
		}
	}

	public function create_table(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . DatabaseHandler::TABLE_NAME;

		$sql = "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			level varchar(20) NOT NULL,
			message text NOT NULL,
			context LONGTEXT,
			extra LONGTEXT,
			PRIMARY KEY  (id),
			KEY created_at (created_at),
			KEY level (level)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'autoblue_db_version', self::DB_VERSION );
	}
}
