<?php


namespace WebpConverter\Settings\Page;

use WebpConverter\Conversion\Cron\CronEventGenerator;
use WebpConverter\Conversion\Endpoint\FilesStatsEndpoint;
use WebpConverter\Conversion\Endpoint\PathsEndpoint;
use WebpConverter\Conversion\Endpoint\RegenerateEndpoint;
use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\Loader\LoaderAbstract;
use WebpConverter\PluginData;
use WebpConverter\PluginInfo;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\Settings\Option\OptionAbstract;
use WebpConverter\Settings\SettingsManager;

/**
 * {@inheritdoc}
 */
class GeneralSettingsPage extends PageAbstract {

	const PAGE_VIEW_PATH = 'views/settings.php';

	/**
	 * @var PluginInfo
	 */
	private $plugin_info;

	/**
	 * @var PluginData
	 */
	protected $plugin_data;

	/**
	 * @var TokenRepository
	 */
	private $token_repository;

	/**
	 * @var FormatFactory
	 */
	private $format_factory;

	public function __construct(
		PluginInfo $plugin_info,
		PluginData $plugin_data,
		TokenRepository $token_repository,
		FormatFactory $format_factory
	) {
		$this->plugin_info      = $plugin_info;
		$this->plugin_data      = $plugin_data;
		$this->token_repository = $token_repository;
		$this->format_factory   = $format_factory;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_slug(): ?string {
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'General Settings', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_template_path(): string {
		return self::PAGE_VIEW_PATH;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_template_vars(): array {
		$token = $this->token_repository->get_token();

		return [
			'logo_url'                 => $this->plugin_info->get_plugin_directory_url() . 'assets/img/logo-headline.png',
			'author_image_url'         => $this->plugin_info->get_plugin_directory_url() . 'assets/img/author.png',
			'form_options'             => $this->plugin_data->get_plugin_options( OptionAbstract::FORM_TYPE_BASIC ),
			'form_sidebar_options'     => $this->plugin_data->get_plugin_options( OptionAbstract::FORM_TYPE_SIDEBAR ),
			'form_input_name'          => SettingsManager::FORM_TYPE_PARAM_KEY,
			'form_input_value'         => OptionAbstract::FORM_TYPE_BASIC,
			'form_sidebar_input_value' => OptionAbstract::FORM_TYPE_SIDEBAR,
			'nonce_input_name'         => SettingsManager::NONCE_PARAM_KEY,
			'nonce_input_value'        => wp_create_nonce( SettingsManager::NONCE_PARAM_VALUE ),
			'token_valid_status'       => $token->get_valid_status(),
			'token_active_status'      => $token->is_active(),
			'api_paths_url'            => PathsEndpoint::get_route_url(),
			'api_paths_nonce'          => PathsEndpoint::get_route_nonce(),
			'api_regenerate_url'       => RegenerateEndpoint::get_route_url(),
			'api_regenerate_nonce'     => RegenerateEndpoint::get_route_nonce(),
			'api_stats_url'            => FilesStatsEndpoint::get_route_url(),
			'api_stats_nonce'          => FilesStatsEndpoint::get_route_nonce(),
			'url_debug_page'           => PageIntegrator::get_settings_page_url( DebugPage::PAGE_SLUG ),
			'output_formats'           => [
				'webp' => [
					'label' => 'WebP',
					'desc'  => ( ! $token->get_valid_status() )
						? __( 'available in the free version', 'webp-converter-for-media' )
						: null,
				],
				'avif' => [
					'label' => 'AVIF',
					'desc'  => ( ! $token->get_valid_status() )
						? sprintf(
						/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
							__( 'available in %1$sthe PRO version%2$s', 'webp-converter-for-media' ),
							'<a href="https://url.mattplugins.com/converter-regeneration-widget-avif-upgrade" target="_blank">',
							'</a>'
						)
						: null,
				],
			],
			'errors_messages'          => apply_filters( 'webpc_server_errors_messages', [] ),
			'errors_codes'             => apply_filters( 'webpc_server_errors', [] ),
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function do_action_before_load() {
		$post_data = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $post_data[ SettingsManager::FORM_TYPE_PARAM_KEY ] )
			&& wp_verify_nonce( $post_data[ SettingsManager::NONCE_PARAM_KEY ] ?? '', SettingsManager::NONCE_PARAM_VALUE ) ) {
			( new SettingsManager( $this->plugin_data, $this->token_repository ) )->save_settings( $post_data );
		}

		do_action( LoaderAbstract::ACTION_NAME, true );
		wp_clear_scheduled_hook( CronEventGenerator::CRON_PATHS_ACTION );

		$this->format_factory->reset_available_formats();
	}

	/**
	 * {@inheritdoc}
	 */
	public function do_action_after_load() {
		do_action( LoaderAbstract::ACTION_NAME, true );
	}
}
