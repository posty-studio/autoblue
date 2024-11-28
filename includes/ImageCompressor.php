<?php

namespace Autoblue;

class ImageCompressor {
	/**
	 * Compress an image if it exceeds the maximum size.
	 *
	 * @param string $path     Path to the image file.
	 * @param int    $max_size Maximum size in bytes.
	 *
	 * @return bool|string Compressed image data or false on failure.
	 */
	public function compress_image( $path, $max_size = 1000000 ) {
		if ( ! extension_loaded( 'gd' ) ) {
			return false;
		}

		if ( ! file_exists( $path ) ) {
			return false;
		}

		$mime_type = mime_content_type( $path );

		if ( ! $mime_type ) {
			return false;
		}

		$size = filesize( $path );

		if ( $size <= $max_size ) {
			return file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}

		// For now, we only support jpeg, png, and gif.
		switch ( $mime_type ) {
			case 'image/jpeg':
				$image = imagecreatefromjpeg( $path );
				break;
			case 'image/png':
				$image = imagecreatefrompng( $path );
				break;
			case 'image/gif':
				$image = imagecreatefromgif( $path );
				break;
			default:
				return false;
		}

		if ( $image === false ) {
			return false;
		}

		$min_quality        = 0;
		$max_quality        = 100;
		$optimized_contents = null;

		while ( $max_quality - $min_quality > 5 ) {
			$current_quality = floor( ( $min_quality + $max_quality ) / 2 );

			ob_start();

			switch ( $mime_type ) {
				case 'image/jpeg':
					imagejpeg( $image, null, $current_quality );
					break;
				case 'image/png':
					$png_quality = floor( ( 100 - $current_quality ) / 11.111111 );
					imagepng( $image, null, $png_quality );
					break;
				case 'image/gif':
					imagegif( $image );
					break;
			}

			$current_contents = ob_get_clean();
			$current_size     = strlen( $current_contents );

			if ( $current_size > $max_size ) {
				$max_quality = $current_quality;
			} else {
				$optimized_contents = $current_contents;
				$min_quality        = $current_quality;
			}
		}

		// If quality reduction alone didn't work, try reducing dimensions.
		if ( ! $optimized_contents || strlen( $optimized_contents ) > $max_size ) {
			$min_scale       = 0.1;
			$max_scale       = 1.0;
			$original_width  = imagesx( $image );
			$original_height = imagesy( $image );

			while ( $max_scale - $min_scale > 0.05 ) {
				$current_scale = ( $min_scale + $max_scale ) / 2;
				$new_width     = floor( $original_width * $current_scale );
				$new_height    = floor( $original_height * $current_scale );

				$resized = imagecreatetruecolor( $new_width, $new_height );

				if ( $mime_type === 'image/png' ) {
					imagealphablending( $resized, false );
					imagesavealpha( $resized, true );
				}

				imagecopyresampled(
					$resized,
					$image,
					0,
					0,
					0,
					0,
					$new_width,
					$new_height,
					$original_width,
					$original_height
				);

				ob_start();

				switch ( $mime_type ) {
					case 'image/jpeg':
						imagejpeg( $resized, null, 90 );
						break;
					case 'image/png':
						imagepng( $resized, null, 1 );
						break;
					case 'image/gif':
						imagegif( $resized );
						break;
				}

				$current_contents = ob_get_clean();
				$current_size     = strlen( $current_contents );

				if ( $current_size > $max_size ) {
					$max_scale = $current_scale;
				} else {
					$optimized_contents = $current_contents;
					$min_scale          = $current_scale;
				}

				imagedestroy( $resized );
			}
		}

		imagedestroy( $image );

		return $optimized_contents ?: false;
	}
}
