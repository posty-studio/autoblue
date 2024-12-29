<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

delete_option( 'autoblue_connections' );
delete_option( 'autoblue_enabled' );
delete_option( 'autoblue_log_level' );
delete_option( 'autoblue_db_version' );

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}autoblue_logs" );
