<?php

namespace Autoblue;

class Utils {
	public static function is_autoblue_enabled_by_default() {
		return (bool) get_option( 'autoblue_enabled', Admin::AUTOBLUE_ENABLED_BY_DEFAULT );
	}

	public static function error_log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
