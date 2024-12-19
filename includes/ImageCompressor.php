<?php
namespace Autoblue;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// @phpstan-ignore requireOnce.fileNotFound
require_once ABSPATH . 'wp-admin/includes/file.php';

class ImageCompressor {
	private const MAX_WIDTH          = 1200;
	private const MAX_QUALITY        = 100;
	private const ALLOWED_MIME_TYPES = [ 'image/jpeg', 'image/png', 'image/webp' ];

	private \WP_Image_Editor $editor;
	private string $path;
	private string $mime_type;
	private int $max_size;

	public function __construct( string $path, string $mime_type, int $max_size = 1000000 ) {
		$this->path      = $path;
		$this->mime_type = $mime_type;
		$this->max_size  = $max_size;
	}

	/**
	 * Get the contents of a file.
	 *
	 * @param string $path The path to the file.
	 * @return string|false The contents of the file, or false on failure.
	 */
	private function get_contents( string $path ) {
		global $wp_filesystem;

		WP_Filesystem();

		if ( ! $wp_filesystem->exists( $path ) ) {
			return false;
		}

		return $wp_filesystem->get_contents( $path );
	}

	/**
	 * Compress an image if it exceeds the maximum size.
	 *
	 * @return string|false The compressed image contents, or false on failure.
	 */
	public function compress_image() {
		if ( ! $this->is_valid_image_file() ) {
			return false;
		}

		if ( filesize( $this->path ) <= $this->max_size ) {
			return $this->get_contents( $this->path );
		}

		$editor = wp_get_image_editor( $this->path );
		if ( is_wp_error( $editor ) ) {
			return false;
		}
		$this->editor = $editor;

		$this->initial_resize();

		return $this->get_compressed_image();
	}

	/**
	 * Checks if the image file is valid.
	 */
	private function is_valid_image_file(): bool {
		return file_exists( $this->path ) && in_array( $this->mime_type, self::ALLOWED_MIME_TYPES, true );
	}

	/**
	 * Performs initial resize if image is wider than MAX_WIDTH.
	 */
	private function initial_resize(): void {
		$size = $this->editor->get_size();

		if ( $size['width'] <= self::MAX_WIDTH ) {
			return;
		}

		$ratio      = self::MAX_WIDTH / $size['width'];
		$new_height = (int) round( $size['height'] * $ratio );
		$this->editor->resize( self::MAX_WIDTH, $new_height, true );
	}

	/**
	 * Gets a compressed version of the image that fits within max_size.
	 *
	 * @return string|false The compressed image contents, or false if compression failed.
	 */
	private function get_compressed_image() {
		$contents = $this->save_compressed_contents( self::MAX_QUALITY );
		if ( ! $contents ) {
			return false;
		}

		if ( strlen( $contents ) <= $this->max_size ) {
			return $contents;
		}

		$initial_size = strlen( $contents );
		$size_ratio   = $this->max_size / $initial_size;

		// Define quality steps based on size ratio. The smaller the ratio, the more aggressive the compression.
		// This prevents us from trying quality levels that are unlikely to produce a small enough file.
		if ( $size_ratio > 0.7 ) {
			$quality_steps = [ 90, 80, 70, 60, 50, 40, 30, 20 ];
		} elseif ( $size_ratio > 0.4 ) {
			$quality_steps = [ 70, 50, 35, 20 ];
		} else {
			$quality_steps = [ 50, 35, 20 ];
		}

		foreach ( $quality_steps as $quality ) {
			$contents = $this->save_compressed_contents( $quality );
			if ( ! $contents ) {
				continue;
			}

			if ( strlen( $contents ) <= $this->max_size ) {
				return $contents;
			}
		}

		return false;
	}

	private function save_compressed_contents( int $quality ): ?string {
		$temp_file     = wp_tempnam();
		$quality_level = $this->get_quality_level( $quality );

		$this->editor->set_quality( $quality_level );

		$result = $this->editor->save( $temp_file, $this->mime_type );

		if ( is_wp_error( $result ) ) {
			$this->cleanup_file( $temp_file );
			return null;
		}

		$contents = $this->get_contents( $result['path'] );
		$this->cleanup_file( $result['path'] );

		if ( $contents === false ) {
			return null;
		}

		return $contents;
	}

	private function get_quality_level( int $quality ): int {
		switch ( $this->mime_type ) {
			case 'image/png':
				return (int) min( 9, max( 0, floor( ( 100 - $quality ) / 11.111111 ) ) );
			default:
				return $quality;
		}
	}

	private function cleanup_file( string $path ): void {
		if ( file_exists( $path ) ) {
			wp_delete_file( $path );
		}
	}
}
