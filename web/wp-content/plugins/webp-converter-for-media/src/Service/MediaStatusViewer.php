<?php

namespace WebpConverter\Service;

use WebpConverter\Conversion\AttachmentPathsGenerator;
use WebpConverter\Conversion\Endpoint\RegenerateAttachmentEndpoint;
use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\Conversion\LargerFilesOperator;
use WebpConverter\Conversion\OutputPathGenerator;
use WebpConverter\HookableInterface;
use WebpConverter\Model\Token;
use WebpConverter\PluginData;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\Settings\Option\MediaStatsOption;
use WebpConverter\Settings\Page\PageIntegrator;

/**
 * Generates information about conversion status in Media Library.
 */
class MediaStatusViewer implements HookableInterface {

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * @var TokenRepository
	 */
	private $token_repository;

	/**
	 * @var OutputPathGenerator
	 */
	private $output_path;

	/**
	 * @var AttachmentPathsGenerator|null
	 */
	private $attachment = null;

	/**
	 * @var Token|null
	 */
	private $token = null;

	public function __construct(
		PluginData $plugin_data,
		TokenRepository $token_repository,
		FormatFactory $format_factory,
		OutputPathGenerator $output_path = null
	) {
		$this->plugin_data      = $plugin_data;
		$this->token_repository = $token_repository;
		$this->output_path      = $output_path ?: new OutputPathGenerator( $format_factory );
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		add_action( 'admin_init', [ $this, 'init_hooks_after_setup' ] );
		add_filter( 'webpc_attachment_stats', [ $this, 'get_conversion_stats_for_attachment' ], 10, 3 );
	}

	/**
	 * @return void
	 * @internal
	 */
	public function init_hooks_after_setup() {
		$plugin_settings = $this->plugin_data->get_plugin_settings();
		if ( ! $plugin_settings[ MediaStatsOption::OPTION_NAME ] ) {
			return;
		}

		add_filter( 'manage_media_columns', [ $this, 'add_custom_table_column' ] );
		add_action( 'manage_media_custom_column', [ $this, 'print_table_column_value' ], 10, 2 );
		add_action( 'attachment_submitbox_misc_actions', [ $this, 'print_attachment_sidebar_value' ], 20 );
		add_filter( 'wp_prepare_attachment_for_js', [ $this, 'add_status_for_attachment_data' ], 10, 2 );
	}

	/**
	 * @param string   $current_value  .
	 * @param int      $post_id        .
	 * @param int|null $strategy_level .
	 *
	 * @return string|null
	 * @internal
	 */
	public function get_conversion_stats_for_attachment( string $current_value, int $post_id, int $strategy_level = null ) {
		$conversion_status = $this->get_conversion_status( $post_id, $strategy_level );
		if ( $conversion_status === null ) {
			return null;
		}

		return wp_kses( implode( PHP_EOL, $conversion_status ), $this->get_allowed_html_tags() );
	}

	/**
	 * @param string[] $columns .
	 *
	 * @return string[]
	 * @internal
	 */
	public function add_custom_table_column( array $columns ): array {
		$columns['webpc_status'] = 'Converter for Media';
		return $columns;
	}

	/**
	 * @param string $column_name .
	 * @param int    $post_id     .
	 *
	 * @return void
	 * @internal
	 */
	public function print_table_column_value( string $column_name, int $post_id ) {
		if ( $column_name !== 'webpc_status' ) {
			return;
		}

		$conversion_stats = $this->get_conversion_stats_for_attachment( '', $post_id );
		if ( $conversion_stats === null ) {
			return;
		}

		echo sprintf(
			'<div id="webpc-attachment-trigger-%1$s-wrapper">%2$s</div>',
			esc_attr( (string) $post_id ),
			wp_kses( $conversion_stats, $this->get_allowed_html_tags() )
		);
	}

	/**
	 * @param \WP_Post $post .
	 *
	 * @return void
	 * @internal
	 */
	public function print_attachment_sidebar_value( \WP_Post $post ) {
		$conversion_stats = $this->get_conversion_stats_for_attachment( '', $post->ID );
		if ( $conversion_stats === null ) {
			return;
		}

		?>
		<div class="misc-pub-section misc-pub-webpc">
			<div id="webpc-attachment-trigger-<?php echo esc_attr( (string) $post->ID ); ?>-wrapper">
				<?php echo wp_kses( $conversion_stats, $this->get_allowed_html_tags() ); ?>
			</div>
			<small>
				<?php
				echo wp_kses_post(
					sprintf(
					/* translators: %s: plugin name */
						__( 'Optimized by: %s', 'webp-converter-for-media' ),
						sprintf( '<a href="%1$s">%2$s</a>', esc_attr( PageIntegrator::get_settings_page_url() ), 'Converter for Media' )
					)
				);
				?>
			</small>
		</div>
		<?php
	}

	/**
	 * @param mixed[]  $response   .
	 * @param \WP_Post $attachment .
	 *
	 * @return mixed[]
	 * @internal
	 */
	public function add_status_for_attachment_data( array $response, \WP_Post $attachment ): array {
		$source_post_id = (string) ( $_REQUEST['post_id'] ?? '' ); // phpcs:ignore WordPress.Security
		if ( $source_post_id !== '0' ) {
			return $response;
		}

		$conversion_stats = $this->get_conversion_stats_for_attachment( '', $attachment->ID );
		if ( $conversion_stats === null ) {
			return $response;
		}

		$response['compat']         = $response['compat'] ?? [];
		$response['compat']['meta'] = $response['compat']['meta'] ?? '';

		$response['compat']['meta'] .= sprintf(
			'<br><div id="webpc-attachment-trigger-%1$s-wrapper">%2$s</div><small>%3$s</small>',
			$attachment->ID,
			$conversion_stats,
			wp_kses_post(
				sprintf(
				/* translators: %s: plugin name */
					__( 'Optimized by: %s', 'webp-converter-for-media' ),
					sprintf( '<a href="%1$s">%2$s</a>', esc_attr( PageIntegrator::get_settings_page_url() ), 'Converter for Media' )
				)
			)
		);

		return $response;
	}

	/**
	 * @param int      $post_id        .
	 * @param int|null $strategy_level .
	 *
	 * @return string[]|null
	 */
	private function get_conversion_status( int $post_id, int $strategy_level = null ) {
		$this->attachment = $this->attachment ?: new AttachmentPathsGenerator( $this->plugin_data );
		$this->token      = $this->token ?: $this->token_repository->get_token();

		$source_paths = $this->attachment->get_attachment_paths( $post_id );
		if ( ! $source_paths ) {
			return null;
		}

		$images_stats = $this->get_images_stats( $source_paths, $post_id );
		if ( ! $images_stats ) {
			return null;
		}

		$percent_values  = array_filter(
			array_column( $images_stats, 'optimized_percent' ),
			function ( $value ) {
				return ! is_null( $value );
			}
		);
		$percent_average = ( $percent_values )
			? -( 100 - round( array_sum( $percent_values ) / count( $percent_values ) ) )
			: null;

		$rows = [
			sprintf(
			/* translators: %s: percent value */
				__( 'Average image size reduction: %s', 'webp-converter-for-media' ),
				( $percent_average !== null )
					?
					sprintf(
						'<abbr title="%1$s">%2$s</abbr>',
						esc_html__( 'File size reduction of all thumbnails compared to the original ones.', 'webp-converter-for-media' ),
						( '<strong>' . ( ( $percent_average > 0 ) ? ( '+' . $percent_average ) : $percent_average ) . '%</strong>' )
					)
					: '<strong>—</strong>'
			),
			'<br>',
			'<div class="webpcMediaStat">',
			sprintf(
				'<input type="checkbox" class="webpcMediaStat__button" id="stats-webp-converter-for-media-attachment-%s">',
				$post_id
			),
			sprintf(
				'<label for="stats-webp-converter-for-media-attachment-%1$s" class="webpcMediaStat__buttonLabel webpcMediaStat__buttonLabel--unchecked button button-small">%2$s</label>',
				$post_id,
				sprintf(
				/* translators: %s: files count */
					__( 'Show stats for all thumbnails (%s)', 'webp-converter-for-media' ),
					count( $images_stats )
				)
			),
			sprintf(
				'<label for="stats-webp-converter-for-media-attachment-%1$s" class="webpcMediaStat__buttonLabel webpcMediaStat__buttonLabel--checked button button-small">%2$s</label>',
				$post_id,
				__( 'Hide stats', 'webp-converter-for-media' )
			),
			'<div class="webpcMediaStat__wrapper">',
		];

		if ( ! $this->token->get_valid_status() ) {
			$rows[] = '<div class="webpcMediaStat__notice">';
			$rows[] = sprintf(
			/* translators: %1$s: call to action, %2$s: format name, %3$s: percent value, %4$s: format name */
				__( '%1$s and convert your images to the %2$s format, making them weigh about %3$s less than images converted only to the %4$s format.', 'webp-converter-for-media' ),
				sprintf(
				/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
					__( '%1$sUpgrade to PRO%2$s', 'webp-converter-for-media' ),
					'<a href="https://url.mattplugins.com/converter-media-stats-notice-upgrade" target="_blank">',
					' </a>'
				),
				'AVIF',
				'50%',
				'WebP'
			);
			$rows[] = '</div>';
		}

		foreach ( $images_stats as $images_stat ) {
			$percent_value = -( 100 - $images_stat['optimized_percent'] );

			$rows[] = sprintf(
				'<div class="webpcMediaStat__item">
					<div class="webpcMediaStat__itemProgress">
						<div class="webpcMediaStat__itemProgressInner" style="width: %5$s%%;"></div>
					</div>
					<a href="%1$s" target="_blank" class="webpcMediaStat__itemLink">%2$s</a>
					<br>
					%3$s
					<br>
					%4$s
				</div>',
				$images_stat['file_url'],
				basename( $images_stat['file_url'] ),
				sprintf(
				/* translators: %s: file size */
					__( 'Original file size: %s', 'webp-converter-for-media' ),
					sprintf( '<strong>%s</strong>', size_format( $images_stat['original_size'] ) )
				),
				( $images_stat['output_format'] )
					?
					sprintf(
					/* translators: %1$s: format name, %2$s: file size */
						__( 'Optimized file size in the %1$s format: %2$s', 'webp-converter-for-media' ),
						$images_stat['output_format'],
						sprintf(
							'<strong>%1$s <abbr title="%2$s">(%3$s)</abbr></strong>',
							size_format( $images_stat['optimized_size'] ),
							sprintf(
							/* translators: %s: format name */
								__( 'Image size reduction after conversion to the %s format compared to the original one.', 'webp-converter-for-media' ),
								$images_stat['output_format']
							),
							( $percent_value > 0 )
								? sprintf( '+%s%%', $percent_value )
								: sprintf( '%s%%', $percent_value )
						)
					)
					:
					sprintf(
					/* translators: %s: file size */
						__( 'Optimized file size: %s', 'webp-converter-for-media' ),
						'<strong>-</strong>'
					),
				( $images_stat['output_format'] )
					? $images_stat['optimized_percent']
					: 0
			);
		}

		$rows[] = '</div>';
		$rows[] = '</div>';

		$quality_levels = apply_filters( 'webpc_option_quality_levels', [ 75, 80, 85, 90, 95 ] );
		$quality_levels = [
			intval( $quality_levels[0] ?? 75 ),
			intval( $quality_levels[1] ?? 80 ),
			intval( $quality_levels[2] ?? 85 ),
			intval( $quality_levels[3] ?? 90 ),
			intval( $quality_levels[4] ?? 95 ),
			0,
		];

		$rows[] = '<br>';
		$rows[] = sprintf(
			'<select id="webpc-attachment-trigger-%1$s" onchange="webpcConvertAttachment(this,%1$s);" data-api-path="%2$s|%3$s">%4$s</select><span id="webpc-attachment-trigger-%1$s-spinner" class="spinner no-float" hidden></span>',
			$post_id,
			RegenerateAttachmentEndpoint::get_route_url(),
			RegenerateAttachmentEndpoint::get_route_nonce(),
			implode(
				'',
				[
					sprintf(
						'<option value="%1$s" %2$s disabled>%3$s</option>',
						'',
						( $strategy_level === null ) ? 'selected' : '',
						( $percent_average !== null )
							? __( 'Re-optimize Now', 'webp-converter-for-media' )
							: __( 'Optimize Now', 'webp-converter-for-media' )
					),
					sprintf(
						'<option value="%1$s" %2$s>%3$s</option>',
						$quality_levels[0],
						( $strategy_level === $quality_levels[0] ) ? 'selected' : '',
						sprintf(
						/* translators: %s: strategy level */
							'— ' . __( 'using Strategy %s', 'webp-converter-for-media' ),
							sprintf( '%1$s (%2$s)', '#1', __( 'Lossy', 'webp-converter-for-media' ) )
						)
					),
					sprintf(
						'<option value="%1$s" %2$s>%3$s</option>',
						$quality_levels[1],
						( $strategy_level === $quality_levels[1] ) ? 'selected' : '',
						sprintf(
						/* translators: %s: strategy level */
							'— ' . __( 'using Strategy %s', 'webp-converter-for-media' ),
							'#2'
						)
					),
					sprintf(
						'<option value="%1$s" %2$s>%3$s</option>',
						$quality_levels[2],
						( $strategy_level === $quality_levels[2] ) ? 'selected' : '',
						sprintf(
						/* translators: %s: strategy level */
							'— ' . __( 'using Strategy %s', 'webp-converter-for-media' ),
							sprintf( '%1$s (%2$s)', '#3', __( 'Optimal', 'webp-converter-for-media' ) )
						)
					),
					sprintf(
						'<option value="%1$s" %2$s>%3$s</option>',
						$quality_levels[3],
						( $strategy_level === $quality_levels[3] ) ? 'selected' : '',
						sprintf(
						/* translators: %s: strategy level */
							'— ' . __( 'using Strategy %s', 'webp-converter-for-media' ),
							'#4'
						)
					),
					sprintf(
						'<option value="%1$s" %2$s>%3$s</option>',
						$quality_levels[4],
						( $strategy_level === $quality_levels[4] ) ? 'selected' : '',
						sprintf(
						/* translators: %s: strategy level */
							'— ' . __( 'using Strategy %s', 'webp-converter-for-media' ),
							sprintf( '%1$s (%2$s)', '#5', __( 'Lossless', 'webp-converter-for-media' ) )
						)
					),
					( $percent_average !== null )
						?
						sprintf(
							'<option value="%1$s" %2$s>%3$s</option>',
							$quality_levels[5],
							( $strategy_level === $quality_levels[5] ) ? 'selected' : '',
							__( 'Restore Originals', 'webp-converter-for-media' )
						)
						: '',
				]
			)
		);

		return $rows;
	}

	/**
	 * @param string[]   $source_paths      .
	 * @param int        $attachment_id     .
	 *
	 * @return mixed[] {
	 * @type int         $original_size     .
	 * @type int|null    $optimized_size    .
	 * @type int|null    $optimized_percent Size of optimized file compared to the original one (from >0 to <=100).
	 * @type string|null $output_format     .
	 * @type string      $file_url          .
	 *                                      }
	 */
	private function get_images_stats( array $source_paths, int $attachment_id ): array {
		$file_url = wp_get_attachment_url( $attachment_id ) ?: null;
		if ( $file_url ) {
			$file_url = dirname( $file_url );
		}

		$items = [];
		foreach ( $source_paths as $source_path ) {
			$filesize_original = ( file_exists( $source_path ) ) ? ( filesize( $source_path ) ?: null ) : null;
			if ( $filesize_original === null ) {
				continue;
			}

			$output_path_webp = $this->output_path->get_path( $source_path, false, WebpFormat::FORMAT_EXTENSION );
			$output_path_avif = $this->output_path->get_path( $source_path, false, AvifFormat::FORMAT_EXTENSION );

			$filesize_avif = ( $output_path_avif )
				? ( ( file_exists( $output_path_avif ) ) ? ( filesize( $output_path_avif ) ?: null ) : null )
				: null;
			$filesize_webp = ( $output_path_webp )
				? ( ( file_exists( $output_path_webp ) ) ? ( filesize( $output_path_webp ) ?: null ) : null )
				: null;

			$status_avif = ( ( $filesize_avif !== null ) || file_exists( $output_path_avif . '.' . LargerFilesOperator::DELETED_FILE_EXTENSION ) );
			$status_webp = ( ( $filesize_webp !== null ) || file_exists( $output_path_webp . '.' . LargerFilesOperator::DELETED_FILE_EXTENSION ) );

			$items[] = [
				'original_size'     => $filesize_original,
				'optimized_size'    => ( $filesize_avif !== null )
					? $filesize_avif
					: ( ( $filesize_webp !== null )
						? $filesize_webp
						: ( ( $status_avif || $status_webp ) ? $filesize_original : null )
					),
				'optimized_percent' => ( $filesize_avif !== null )
					? round( $filesize_avif / $filesize_original * 100 )
					: ( ( $filesize_webp !== null )
						? round( $filesize_webp / $filesize_original * 100 )
						: ( ( $status_avif || $status_webp ) ? 100 : null )
					),
				'output_format'     => ( $filesize_avif !== null )
					? 'AVIF'
					: ( ( ( $filesize_webp !== null ) || $status_webp )
						? 'WebP'
						: null
					),
				'file_url'          => sprintf( '%1$s/%2$s', $file_url, basename( $source_path ) ),
			];
		}

		return $items;
	}

	/**
	 * @return mixed[]
	 */
	private function get_allowed_html_tags(): array {
		return [
			'a'      => [
				'href'   => [],
				'class'  => [],
				'target' => [],
			],
			'abbr'   => [
				'title' => [],
			],
			'br'     => [],
			'div'    => [
				'id'    => [],
				'class' => [],
				'style' => [],
			],
			'input'  => [
				'id'    => [],
				'type'  => [],
				'class' => [],
			],
			'label'  => [
				'for'   => [],
				'class' => [],
			],
			'option' => [
				'value'    => [],
				'selected' => [],
				'disabled' => [],
			],
			'select' => [
				'id'            => [],
				'onchange'      => [],
				'data-api-path' => [],
			],
			'span'   => [
				'id'     => [],
				'class'  => [],
				'hidden' => [],
			],
			'strong' => [
				'class'          => [],
				'titleyik mnb  ' => [],
			],
		];
	}
}
