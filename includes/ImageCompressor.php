<?php
namespace Autoblue;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/file.php';

class ImageCompressor {
	/**
	 * Compress an image if it exceeds the maximum size.
	 *
	 * @param string $path     Path to the image file.
	 * @param string $mime_type MIME type of the image.
	 * @param int    $max_size Maximum size in bytes.
	 *
	 * @return bool|string Compressed image data or false on failure.
	 */
	public function compress_image( $path, $mime_type, $max_size = 500000 ) {
		if ( ! file_exists( $path ) ) {
			return false;
		}

		if ( ! $mime_type || ! in_array( $mime_type, [ 'image/jpeg', 'image/png' ] ) ) {
			return false;
		}

		$size = filesize( $path );

		if ( $size <= $max_size ) {
			return file_get_contents( $path );
		}

		$editor = wp_get_image_editor( $path );

		if ( is_wp_error( $editor ) ) {
			return false;
		}

		$image_size      = $editor->get_size();
		$original_width  = $image_size['width'];
		$original_height = $image_size['height'];
		$extension       = wp_get_default_extension_for_mime_type( $mime_type );

		if ( $original_width > 1020 && $original_height > 534 ) {
			$editor->resize( 1020, 534, true );

			$temp_file          = wp_tempnam();
			$temp_file_with_ext = $temp_file . $extension;
			rename( $temp_file, $temp_file_with_ext );

			$result = $editor->save( $temp_file_with_ext, $mime_type );

			if ( ! is_wp_error( $result ) ) {
				$resized_size = filesize( $result['path'] );

				// If the resized image is already small enough, return it
				if ( $resized_size <= $max_size ) {
					$contents = file_get_contents( $result['path'] );
					unlink( $result['path'] );
					return $contents;
				}

				unlink( $result['path'] );
			}

			// Update dimensions for further processing
			$image_size      = $editor->get_size();
			$original_width  = $image_size['width'];
			$original_height = $image_size['height'];
		}

		do {
			// Use proper extension based on mime type
			$extension = $mime_type === 'image/png' ? '.png' : '.jpg';
			$temp_file = wp_tempnam( $extension );

			// Ensure temp file has correct extension
			$temp_file_with_ext = $temp_file . $extension;
			rename( $temp_file, $temp_file_with_ext );

			$quality_level = $mime_type === 'image/png' ?
				min( 9, max( 0, floor( ( 100 - $quality ) / 11.111111 ) ) ) :
				$quality;

			$editor->set_quality( $quality_level );

			// Save with explicit mime type and proper extension
			$result = $editor->save( $temp_file_with_ext, $mime_type );

			if ( is_wp_error( $result ) ) {
				if ( file_exists( $temp_file_with_ext ) ) {
					unlink( $temp_file_with_ext );
				}
				return false;
			}

			$saved_path = $result['path'];

			// Use binary safe reading
			$current_contents = file_get_contents( $saved_path, false );
			if ( $current_contents === false ) {
				if ( file_exists( $saved_path ) ) {
					unlink( $saved_path );
				}
				return false;
			}

			$current_size = strlen( $current_contents );

			if ( file_exists( $saved_path ) ) {
				unlink( $saved_path );
			}

			if ( $current_size <= $max_size ) {
				$optimized_contents = $current_contents;
				break;
			}

			$quality -= 5;

			if ( $quality < 30 ) {
				$quality    = 90;
				$scale      = 0.9;
				$new_width  = max( 1, floor( $original_width * $scale ) );
				$new_height = max( 1, floor( $original_height * $scale ) );

				$editor->resize( $new_width, $new_height, true );

				$original_width  = $new_width;
				$original_height = $new_height;

				if ( $new_width < 200 || $new_height < 200 ) {
					break;
				}
			}
		} while ( $quality >= 30 || ( $original_width >= 200 && $original_height >= 200 ) );

		// Final attempt with minimal quality
		if ( ! $optimized_contents || strlen( $optimized_contents ) > $max_size ) {
			$temp_file          = wp_tempnam( $extension );
			$temp_file_with_ext = $temp_file . $extension;
			rename( $temp_file, $temp_file_with_ext );

			$editor->set_quality( $mime_type === 'image/png' ? 9 : 20 );
			$editor->resize( 800, 800, false );
			$result = $editor->save( $temp_file_with_ext, $mime_type );

			if ( ! is_wp_error( $result ) ) {
				$final_contents = file_get_contents( $result['path'] );
				if ( $final_contents !== false && strlen( $final_contents ) <= $max_size ) {
					$optimized_contents = $final_contents;
				}
				unlink( $result['path'] );
			}
		}

		return $optimized_contents ?: false;
	}
}
