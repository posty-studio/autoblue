<?php

namespace Autoblue;

class Utils {
	public const DEFAULT_ENABLED_POST_TYPES = [ 'post' ];

	public static function is_autoblue_enabled_by_default(): bool {
		return (bool) get_option( 'autoblue_enabled', Admin::AUTOBLUE_ENABLED_BY_DEFAULT );
	}

	/**
	 * @return array<int,string>
	 */
	public static function get_enabled_post_types(): array {
		$stored = get_option( 'autoblue_enabled_post_types', self::DEFAULT_ENABLED_POST_TYPES );

		if ( ! is_array( $stored ) ) {
			return self::DEFAULT_ENABLED_POST_TYPES;
		}

		return array_values( array_filter( array_map( 'strval', $stored ) ) );
	}

	/**
	 * @return array<int,array{slug:string,label:string}>
	 */
	public static function get_available_post_types(): array {
		$post_types = get_post_types(
			[
				'public'       => true,
				'show_in_rest' => true,
			],
			'objects'
		);

		$filtered = array_filter(
			$post_types,
			static fn( $pt ) => $pt->name !== 'attachment'
		);

		return array_values(
			array_map(
				static fn( $pt ) => [
					'slug'  => $pt->name,
					'label' => $pt->labels->name ?? $pt->name,
				],
				$filtered
			)
		);
	}


	public static function error_log( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
