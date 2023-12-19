<?php

namespace WebpConverter\Service;

use WebpConverter\Conversion\FilesTreeFinder;
use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\Conversion\Method\MethodFactory;
use WebpConverter\Conversion\Method\MethodIntegrator;
use WebpConverter\Conversion\PathsFinder;
use WebpConverter\HookableInterface;
use WebpConverter\PluginData;
use WebpConverter\Repository\TokenRepository;

/**
 * Registers the commands handled by WP_CLI.
 *
 * @see https://wp-cli.org
 */
class WpCliManager implements HookableInterface {

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * @var TokenRepository
	 */
	private $token_repository;

	/**
	 * @var MethodFactory
	 */
	private $method_factory;

	/**
	 * @var FormatFactory
	 */
	private $format_factory;

	public function __construct(
		PluginData $plugin_data,
		TokenRepository $token_repository,
		MethodFactory $method_factory,
		FormatFactory $format_factory
	) {
		$this->plugin_data      = $plugin_data;
		$this->token_repository = $token_repository;
		$this->method_factory   = $method_factory;
		$this->format_factory   = $format_factory;
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			'converter-for-media calculate',
			[ $this, 'calculate_images' ],
			[]
		);
		\WP_CLI::add_command(
			'converter-for-media regenerate',
			[ $this, 'regenerate_images' ],
			[
				'synopsis' => [
					'type'        => 'flag',
					'name'        => 'force',
					'description' => __( 'Force the conversion of all images again', 'webp-converter-for-media' ),
				],
			]
		);

		\WP_CLI::add_command( 'webp-converter calculate', [ $this, 'calculate_images' ] );
		\WP_CLI::add_command( 'webp-converter regenerate', [ $this, 'regenerate_images' ] );
	}

	/**
	 * @return void
	 */
	public function calculate_images() {
		\WP_Cli::log(
			__( 'How many images to convert are remaining on my website?', 'webp-converter-for-media' )
		);

		$stats_data = ( new FilesTreeFinder( $this->plugin_data, $this->format_factory ) )
			->get_tree( [ WebpFormat::FORMAT_EXTENSION, AvifFormat::FORMAT_EXTENSION ] );

		\WP_CLI::success(
			sprintf(
			/* translators: %1$s: images count, %2$s: images count */
				__( '%1$s for AVIF and %2$s for WebP', 'webp-converter-for-media' ),
				number_format( $stats_data['files_unconverted'][ AvifFormat::FORMAT_EXTENSION ], 0, '', ' ' ),
				number_format( $stats_data['files_unconverted'][ WebpFormat::FORMAT_EXTENSION ], 0, '', ' ' )
			)
		);
	}

	/**
	 * @param string[] $args       .
	 * @param string[] $assoc_args .
	 *
	 * @return void
	 */
	public function regenerate_images( array $args, array $assoc_args = [] ) {
		$force_flag        = ( isset( $assoc_args['force'] ) || in_array( '-force', $args ) );
		$conversion_method = ( new MethodIntegrator( $this->plugin_data, $this->method_factory ) );
		$method_used       = $conversion_method->get_method_used();

		if ( $method_used === null ) {
			\WP_CLI::error(
				sprintf(
				/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
					__( 'GD or Imagick library is not installed on your server.', 'webp-converter-for-media' ) . ' ' . __( 'This means that you cannot convert images to the WebP format on your server, because it does not meet the plugin requirements described in %1$sthe plugin FAQ%2$s. This issue is not dependent on the plugin.', 'webp-converter-for-media' ),
					'<a href="https://url.mattplugins.com/converter-error-libs-not-installed-faq" target="_blank">',
					'</a>'
				)
			);
		}

		$paths_chunks = ( new PathsFinder( $this->plugin_data, $this->token_repository, $this->format_factory ) )
			->get_paths_by_chunks( ! $force_flag );

		$count = 0;
		foreach ( $paths_chunks as $chunk_data ) {
			$count += count( $chunk_data['files'] );
		}

		$progress        = \WP_CLI\Utils\make_progress_bar(
			__( 'Bulk Optimization', 'webp-converter-for-media' ),
			$count
		);
		$size_before     = 0;
		$size_after      = 0;
		$files_all       = 0;
		$files_converted = 0;

		foreach ( $paths_chunks as $chunk_data ) {
			foreach ( $chunk_data['files'] as $images_paths ) {
				$response = $conversion_method->init_conversion(
					$this->parse_files_paths( $images_paths, $chunk_data['path'] ),
					$force_flag,
					true
				);

				if ( $response !== null ) {
					foreach ( $response['errors'] as $error_message ) {
						if ( ! $response['is_fatal_error'] ) {
							\WP_CLI::warning( $error_message );
						} else {
							\WP_CLI::error( $error_message );
						}
					}

					if ( $response['is_fatal_error'] ) {
						return;
					}

					$size_before     = $response['size']['before'];
					$size_after      = $response['size']['after'];
					$files_all       = $response['files']['webp_available'] + $response['files']['avif_available'];
					$files_converted = $response['files']['webp_converted'] + $response['files']['avif_converted'];
				}

				$progress->tick();
			}
		}

		$progress->finish();
		\WP_CLI::success(
			__( 'The process was completed successfully. Your images have been converted!', 'webp-converter-for-media' )
		);

		if ( $size_before > $size_after ) {
			\WP_CLI::log(
				sprintf(
				/* translators: %s progress value */
					__( 'Saving the weight of your images: %s', 'webp-converter-for-media' ),
					$this->format_bytes( $size_before - $size_after )
				)
			);
		}
		\WP_CLI::log(
			sprintf(
			/* translators: %s images count */
				__( 'Successfully converted files: %s', 'webp-converter-for-media' ),
				$files_converted
			)
		);
		\WP_CLI::log(
			sprintf(
			/* translators: %s images count */
				__( 'Failed or skipped file conversion attempts: %s', 'webp-converter-for-media' ),
				( $files_all - $files_converted )
			)
		);
	}

	/**
	 * @param string[] $paths       .
	 * @param string   $path_prefix .
	 *
	 * @return string[]
	 */
	private function parse_files_paths( array $paths, string $path_prefix ): array {
		$items = [];
		foreach ( $paths as $path ) {
			$items[] = $path_prefix . '/' . $path;
		}
		return $items;
	}

	private function format_bytes( int $size ): string {
		$suffixes = [ 'B', 'KB', 'MB', 'GB' ];
		$base     = floor( log( $size ) / log( 1024 ) );

		return sprintf( '%.2f ' . $suffixes[ $base ], ( $size / pow( 1024, floor( $base ) ) ) );
	}
}
