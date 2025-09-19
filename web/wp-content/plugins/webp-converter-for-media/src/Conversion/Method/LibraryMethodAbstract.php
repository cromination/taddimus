<?php

namespace WebpConverter\Conversion\Method;

use WebpConverter\Exception\FilesizeOversizeException;
use WebpConverter\Exception\LargerThanOriginalException;
use WebpConverter\Exception\OutputPathException;
use WebpConverter\Exception\SourcePathException;
use WebpConverter\Settings\Option\OutputFormatsOption;

/**
 * Abstract class for class that converts images using the PHP library.
 */
abstract class LibraryMethodAbstract extends MethodAbstract implements LibraryMethodInterface {

	/**
	 * {@inheritdoc}
	 */
	public function convert_paths( array $paths, array $plugin_settings, bool $regenerate_force ) {
		$output_formats = $plugin_settings[ OutputFormatsOption::OPTION_NAME ];
		foreach ( $output_formats as $output_format ) {
			foreach ( $paths as $path ) {
				$this->files_statuses[ $output_format ][ $path ] = false;
				$this->convert_path( $path, $output_format, $plugin_settings );
			}
		}
	}

	/**
	 * Converts source path to output formats.
	 *
	 * @param string  $source_path     Server path of source image.
	 * @param string  $output_format   Extension of output format.
	 * @param mixed[] $plugin_settings .
	 *
	 * @return void
	 */
	private function convert_path( string $source_path, string $output_format, array $plugin_settings ) {
		$this->server_configurator->set_memory_limit();
		$this->server_configurator->set_execution_time();

		try {
			$output_path = $this->get_image_output_path( $source_path, $output_format );
			$this->check_image_source_path( $source_path );

			$image = $this->create_image_by_path( $source_path, $plugin_settings );
			$this->convert_image_to_output( $image, $source_path, $output_path, $output_format, $plugin_settings );
			do_action( 'webpc_after_conversion', $output_path, $source_path );

			$this->skip_crashed->delete_crashed_file( $output_path );
			$this->skip_larger->remove_image_if_is_larger( $output_path, $source_path, $plugin_settings );
			$this->update_conversion_stats( $source_path, $output_path, $output_format );

			$this->files_statuses[ $output_format ][ $source_path ] = true;
		} catch ( OutputPathException $e ) {
			$this->save_conversion_error( $e->getMessage(), $plugin_settings );
		} catch ( SourcePathException|FilesizeOversizeException $e ) {
			$this->save_conversion_error( $e->getMessage(), $plugin_settings );
			$this->skip_crashed->create_crashed_file( $output_path );
		} catch ( LargerThanOriginalException $e ) {
			return;
		}
	}
}
