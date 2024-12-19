<?php

namespace WebpConverter\Action;

use WebpConverter\Conversion\CrashedFilesOperator;
use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\Conversion\LargerFilesOperator;
use WebpConverter\Conversion\OutputPathGenerator;
use WebpConverter\HookableInterface;

/**
 * Deletes all images in list of paths.
 */
class DeletePathsAction implements HookableInterface {

	/**
	 * @var OutputPathGenerator
	 */
	private $output_path;

	public function __construct(
		FormatFactory $format_factory,
		?OutputPathGenerator $output_path = null
	) {
		$this->output_path = $output_path ?: new OutputPathGenerator( $format_factory );
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		add_action( 'webpc_delete_paths', [ $this, 'delete_files_by_paths' ], 10, 2 );
	}

	/**
	 * Deletes images from output directory.
	 *
	 * @param string[] $paths            Server paths of output images.
	 * @param bool     $set_skipped_flag .
	 *
	 * @return void
	 * @internal
	 */
	public function delete_files_by_paths( array $paths, bool $set_skipped_flag = false ) {
		foreach ( $paths as $path ) {
			$this->delete_file_by_path( $path, $set_skipped_flag );
		}
	}

	/**
	 * Deletes image from output directory.
	 *
	 * @param string $path             Server path of output image.
	 * @param bool   $set_skipped_flag .
	 *
	 * @return void
	 */
	private function delete_file_by_path( string $path, bool $set_skipped_flag ) {
		$output_formats = ( $set_skipped_flag ) ? [ AvifFormat::FORMAT_EXTENSION, WebpFormat::FORMAT_EXTENSION ] : null;

		if ( ! ( $output_paths = $this->output_path->get_paths( $path, $set_skipped_flag, $output_formats ) ) ) {
			return;
		}

		foreach ( $output_paths as $output_path ) {
			if ( is_writable( $output_path ) ) {
				unlink( $output_path );
			}

			if ( is_writable( $output_path . '.' . CrashedFilesOperator::CRASHED_FILE_EXTENSION ) ) {
				unlink( $output_path . '.' . CrashedFilesOperator::CRASHED_FILE_EXTENSION );
			}

			if ( $set_skipped_flag ) {
				$file = fopen( $output_path . '.' . LargerFilesOperator::DELETED_FILE_EXTENSION, 'w' );
				if ( $file !== false ) {
					fclose( $file );
				}
			} elseif ( is_writable( $output_path . '.' . LargerFilesOperator::DELETED_FILE_EXTENSION ) ) {
				unlink( $output_path . '.' . LargerFilesOperator::DELETED_FILE_EXTENSION );
			}
		}
	}
}
