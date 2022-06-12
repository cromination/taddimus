<?php

namespace WebpConverter\Conversion;

/**
 * Handles checking if paths are supported for image conversion.
 */
trait PathsValidator {

	/**
	 * @param string        $file_path              .
	 * @param bool          $skip_converted         Skip images already converted?
	 * @param string[]|null $allowed_output_formats List of extensions or use selected in plugin settings.
	 */
	public function is_supported_source_file( string $file_path, bool $skip_converted = false, array $allowed_output_formats = null ): bool {
		if ( ! $this->is_supported_source_directory( dirname( $file_path ) ) ) {
			return false;
		}

		$server_path = $this->convert_server_path( $file_path );
		return apply_filters( 'webpc_supported_source_file', true, basename( $server_path ), $server_path, $skip_converted, $allowed_output_formats );
	}

	public function is_supported_source_directory( string $directory_path ): bool {
		$server_path = $this->convert_server_path( $directory_path );
		return apply_filters( 'webpc_supported_source_directory', true, basename( $server_path ), $server_path );
	}

	private function convert_server_path( string $original_path ): string {
		$server_path = urldecode( $original_path );
		return (string) preg_replace( '/[\\\\\/]+/', DIRECTORY_SEPARATOR, $server_path );
	}
}
