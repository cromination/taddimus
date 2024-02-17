<?php

namespace WebpConverter\Conversion\Method;

use WebpConverter\Conversion\CrashedFilesOperator;
use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\Conversion\LargerFilesOperator;
use WebpConverter\Conversion\OutputPathGenerator;
use WebpConverter\Exception;
use WebpConverter\Service\ServerConfigurator;

/**
 * Abstract class for class that converts images.
 */
abstract class MethodAbstract implements MethodInterface {

	/**
	 * @var CrashedFilesOperator
	 */
	protected $skip_crashed;

	/**
	 * @var LargerFilesOperator
	 */
	protected $skip_larger;

	/**
	 * @var ServerConfigurator
	 */
	protected $server_configurator;

	/**
	 * @var OutputPathGenerator
	 */
	private $output_path;

	public function __construct(
		FormatFactory $format_factory,
		CrashedFilesOperator $skip_crashed,
		LargerFilesOperator $skip_larger,
		ServerConfigurator $server_configurator,
		OutputPathGenerator $output_path = null
	) {
		$this->skip_crashed        = $skip_crashed;
		$this->skip_larger         = $skip_larger;
		$this->server_configurator = $server_configurator;
		$this->output_path         = $output_path ?: new OutputPathGenerator( $format_factory );
	}

	/**
	 * @var bool
	 */
	protected $is_fatal_error = false;

	/**
	 * Messages of errors that occurred during conversion.
	 *
	 * @var string[]
	 */
	protected $errors = [];

	/**
	 * Sum of size of source images before conversion.
	 *
	 * @var int
	 */
	protected $size_before = 0;

	/**
	 * Sum of size of output images after conversion.
	 *
	 * @var int
	 */
	protected $size_after = 0;

	/**
	 * @var mixed[]
	 */
	protected $files_statuses = [
		WebpFormat::FORMAT_EXTENSION => [],
		AvifFormat::FORMAT_EXTENSION => [],
	];

	/**
	 * @return bool
	 */
	public static function is_pro_feature(): bool {
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_fatal_error(): bool {
		return $this->is_fatal_error;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_size_before(): int {
		return $this->size_before;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_files_available( string $output_format ): int {
		return count( $this->files_statuses[ $output_format ] );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_files_converted( string $output_format ): int {
		return count(
			array_filter(
				$this->files_statuses[ $output_format ],
				function ( $value ) {
					return ( $value === true );
				}
			)
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_size_after(): int {
		return $this->size_after;
	}

	/**
	 * Checks server path of source image.
	 *
	 * @param string $source_path Server path of source image.
	 *
	 * @return string Server path of source image.
	 *
	 * @throws Exception\SourcePathException
	 */
	protected function get_image_source_path( string $source_path ): string {
		if ( ! is_readable( $source_path ) ) {
			throw new Exception\SourcePathException( $source_path );
		}

		return $source_path;
	}

	/**
	 * Returns server path for output image.
	 *
	 * @param string $source_path Server path of source image.
	 * @param string $format      Extension of output format.
	 *
	 * @return string Server path of output image.
	 *
	 * @throws Exception\OutputPathException
	 */
	protected function get_image_output_path( string $source_path, string $format ): string {
		if ( ! $output_path = $this->output_path->get_path( $source_path, true, $format ) ) {
			throw new Exception\OutputPathException( $source_path );
		}

		return $output_path;
	}

	/**
	 * @param string $source_path   Server path of source image.
	 * @param string $output_path   Server path of output image.
	 * @param string $output_format .
	 *
	 * @return void
	 */
	protected function update_conversion_stats( string $source_path, string $output_path, string $output_format ) {
		$output_exist = file_exists( $output_path );
		$size_before  = filesize( $source_path );
		$size_after   = ( $output_exist ) ? filesize( $output_path ) : $size_before;

		$this->size_before += $size_before ?: 0;
		$this->size_after  += $size_after ?: 0;
	}

	/**
	 * @param string  $error_message   .
	 * @param mixed[] $plugin_settings .
	 * @param bool    $is_fatal_error  .
	 *
	 * @return void
	 */
	protected function save_conversion_error( string $error_message, array $plugin_settings, bool $is_fatal_error = false ) {
		if ( $is_fatal_error ) {
			$this->is_fatal_error = true;
		}

		$this->errors[] = $error_message;
	}
}
