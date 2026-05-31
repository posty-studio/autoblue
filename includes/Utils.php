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

	/**
	 * Whether WordPress is installed at the root of its domain.
	 *
	 * The standard.site verification endpoint must live at
	 * https://example.com/.well-known/site.standard.publication. WordPress can
	 * only serve that path when home_url() and site_url() are both at '/'.
	 */
	public static function is_root_install(): bool {
		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$site_path = wp_parse_url( site_url( '/' ), PHP_URL_PATH );

		return '/' === $home_path && '/' === $site_path;
	}

	/**
	 * Whether the standard.site publishing feature is fully usable on this install.
	 *
	 * Requires the global toggle to be on, the install to be at the domain root,
	 * and at least one Bluesky connection to write records under.
	 */
	public static function is_standard_site_enabled(): bool {
		if ( ! get_option( 'autoblue_publish_documents_enabled', false ) ) {
			return false;
		}

		if ( ! self::is_root_install() ) {
			return false;
		}

		$connections = get_option( 'autoblue_connections', [] );
		return ! empty( $connections );
	}
}
