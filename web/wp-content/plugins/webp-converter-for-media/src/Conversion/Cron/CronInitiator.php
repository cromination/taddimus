<?php

namespace WebpConverter\Conversion\Cron;

use WebpConverter\Conversion\Endpoint\CronConversionEndpoint;
use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\Conversion\PathsFinder;
use WebpConverter\PluginData;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\Settings\Option\ExtraFeaturesOption;
use WebpConverter\Settings\Option\ServiceModeOption;

/**
 * Manages automatic conversion of images.
 */
class CronInitiator {

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * @var CronStatusManager
	 */
	private $cron_status_manager;

	/**
	 * @var PathsFinder
	 */
	private $paths_finder;

	public function __construct(
		PluginData $plugin_data,
		TokenRepository $token_repository,
		FormatFactory $format_factory,
		?CronStatusManager $cron_status_manager = null,
		?PathsFinder $paths_finder = null
	) {
		$this->plugin_data         = $plugin_data;
		$this->cron_status_manager = $cron_status_manager ?: new CronStatusManager();
		$this->paths_finder        = $paths_finder ?: new PathsFinder( $plugin_data, $token_repository, $format_factory );
	}

	public function refresh_paths_to_conversion( bool $force_init = false ): bool {
		$saved_request_id = $this->cron_status_manager->get_conversion_request_id();
		if ( $this->cron_status_manager->is_conversion_locked()
			|| ( ! $force_init && ( $saved_request_id !== null ) ) ) {
			return false;
		}

		$plugin_settings = $this->plugin_data->get_plugin_settings();
		$cron_enabled    = in_array( ExtraFeaturesOption::OPTION_VALUE_CRON_ENABLED, $plugin_settings[ ExtraFeaturesOption::OPTION_NAME ] );

		$this->cron_status_manager->set_conversion_status_locked( true, true );

		$paths = $this->paths_finder->get_paths( true );
		$this->cron_status_manager->set_paths_to_conversion( $paths, $cron_enabled );
		$this->cron_status_manager->set_paths_skipped( ( $cron_enabled ) ? $paths : [] );

		$this->cron_status_manager->set_conversion_status_locked( false );

		return (bool) $paths;
	}

	/**
	 * @param string[] $new_paths              .
	 * @param bool     $force_convert_modified .
	 *
	 * @return void
	 */
	public function add_paths_to_conversion( array $new_paths, bool $force_convert_modified = false ) {
		$paths           = $this->cron_status_manager->get_paths_to_conversion();
		$valid_new_paths = $this->paths_finder->skip_converted_paths( $new_paths, null, $force_convert_modified );

		$this->cron_status_manager->set_paths_to_conversion( array_merge( $valid_new_paths, $paths ) );
	}

	/**
	 * @param string|null $request_id .
	 *
	 * @return void
	 */
	public function init_conversion( ?string $request_id = null ) {
		$saved_request_id = $this->cron_status_manager->get_conversion_request_id();
		if ( $this->cron_status_manager->is_conversion_locked()
			|| ( ( $saved_request_id !== null ) && ( $request_id !== $saved_request_id ) ) ) {
			return;
		}

		$paths = $this->cron_status_manager->get_paths_to_conversion();
		if ( ! $paths ) {
			$this->try_restart_conversion();
			return;
		}

		$this->cron_status_manager->set_paths_to_conversion( array_slice( $paths, 1 ) );
		do_action( 'webpc_convert_paths', array_slice( $paths, 0, 1 ) );

		$this->init_async_conversion();
	}

	/**
	 * @param bool $upload_request .
	 *
	 * @return void
	 */
	public function init_async_conversion( bool $upload_request = false ) {
		$plugin_settings = $this->plugin_data->get_plugin_settings();
		$service_mode    = ( $plugin_settings[ ServiceModeOption::OPTION_NAME ] === 'yes' );

		$headers = [
			CronConversionEndpoint::ROUTE_NONCE_HEADER => CronConversionEndpoint::get_route_nonce(),
		];
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( $_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		$args = [
			'timeout'   => 0.01,
			'blocking'  => false,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			'headers'   => $headers,
		];
		if ( $service_mode && $upload_request ) {
			unset( $args['timeout'] );
			unset( $args['blocking'] );
		}

		$response = wp_remote_post( CronConversionEndpoint::get_route_url(), $args );
		if ( $service_mode && $upload_request ) {
			$this->cron_status_manager->set_conversion_request_response( $response );
		}
	}

	/**
	 * @return void
	 */
	private function try_restart_conversion() {
		$plugin_settings = $this->plugin_data->get_plugin_settings();
		$cron_enabled    = in_array( ExtraFeaturesOption::OPTION_VALUE_CRON_ENABLED, $plugin_settings[ ExtraFeaturesOption::OPTION_NAME ] );

		$this->cron_status_manager->reset_conversion_request_id();
		if ( ! $cron_enabled || ! $this->cron_status_manager->get_paths_counter() ) {
			return;
		}

		$this->refresh_paths_to_conversion( true );
		$this->init_async_conversion();
	}
}
