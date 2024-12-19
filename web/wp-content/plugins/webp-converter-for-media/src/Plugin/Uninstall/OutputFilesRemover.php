<?php

namespace WebpConverter\Plugin\Uninstall;

use WebpConverter\Conversion\CrashedFilesOperator;
use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\Conversion\LargerFilesOperator;

/**
 * Removes output files from /uploads-webpc directory.
 */
class OutputFilesRemover {

	/**
	 * Removes output images from output directory.
	 *
	 * @return void
	 */
	public static function remove_webp_files() {
		$path  = apply_filters( 'webpc_dir_path', '', 'webp' );
		$paths = self::get_paths_from_location( $path );

		$paths[] = $path . '/.htaccess';
		$paths[] = $path;

		self::remove_files( $paths );
	}

	/**
	 * Searches list of paths to remove from given directory.
	 *
	 * @param string   $path  Server path.
	 * @param string[] $paths Server paths already found.
	 *
	 * @return string[] Server paths.
	 */
	public static function get_paths_from_location( string $path, array $paths = [] ): array {
		if ( ! file_exists( $path ) ) {
			return $paths;
		}

		$files = glob( $path . '/*' ) ?: [];
		foreach ( $files as $file ) {
			if ( is_dir( $file ) ) {
				$paths = self::get_paths_from_location( $file, $paths );
			}
			$paths[] = $file;
		}
		return $paths;
	}

	/**
	 * Removes selected paths from disc.
	 *
	 * @param string[]      $paths        Server paths.
	 * @param string[]|null $file_formats .
	 *
	 * @return void
	 */
	public static function remove_files( array $paths, ?array $file_formats = null ) {
		if ( ! $paths ) {
			return;
		}

		$regex = sprintf(
			'/((jpe?g|png|gif|png2|webp)\.(%1$s)(\.(%2$s))?|\.htaccess)$/i',
			implode(
				'|',
				$file_formats ?: [ WebpFormat::FORMAT_EXTENSION, AvifFormat::FORMAT_EXTENSION ]
			),
			implode(
				'|',
				[ LargerFilesOperator::DELETED_FILE_EXTENSION, CrashedFilesOperator::CRASHED_FILE_EXTENSION ]
			)
		);

		foreach ( $paths as $path ) {
			if ( ! is_writable( $path ) || ! is_writable( dirname( $path ) ) ) {
				continue;
			}

			if ( is_file( $path ) && ( preg_match( $regex, basename( $path ) ) ) ) {
				unlink( $path );
			} elseif ( ( $file_formats === null ) && is_dir( $path ) ) {
				rmdir( $path );
			}
		}
	}
}
