<?php

namespace WebpConverter\Conversion;

/**
 * Excludes re-conversion of files that caused converting error.
 */
class CrashedFilesOperator {

	const CRASHED_FILE_EXTENSION = 'crashed';

	public function create_crashed_file( string $output_path ): void {
		$file = fopen( $output_path . '.' . self::CRASHED_FILE_EXTENSION, 'w' );
		if ( $file === false ) {
			return;
		}

		fclose( $file );
	}

	public function delete_crashed_file( string $output_path ): void {
		if ( ! file_exists( $output_path ) || ! file_exists( $output_path . '.' . self::CRASHED_FILE_EXTENSION ) ) {
			return;
		}

		unlink( $output_path . '.' . self::CRASHED_FILE_EXTENSION );
	}
}
