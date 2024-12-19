<?php

namespace WebpConverter\Conversion\Endpoint;

use WebpConverter\Conversion\Cron\CronInitiator;
use WebpConverter\Conversion\Cron\CronStatusManager;
use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\PluginData;
use WebpConverter\Repository\TokenRepository;

/**
 * .
 */
class CronConversionEndpoint extends EndpointAbstract {

	const ROUTE_NONCE_HEADER = 'Webpc-Nonce';

	/**
	 * @var CronInitiator
	 */
	private $cron_initiator;

	/**
	 * @var CronStatusManager
	 */
	private $cron_status_manager;

	public function __construct(
		PluginData $plugin_data,
		TokenRepository $token_repository,
		FormatFactory $format_factory,
		?CronInitiator $cron_initiator = null,
		?CronStatusManager $cron_status_manager = null
	) {
		$this->cron_initiator      = $cron_initiator ?: new CronInitiator( $plugin_data, $token_repository, $format_factory );
		$this->cron_status_manager = $cron_status_manager ?: new CronStatusManager();
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_route_name(): string {
		return 'cron-conversion';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_http_methods(): string {
		return \WP_REST_Server::CREATABLE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_valid_request( string $request_nonce ): bool {
		$nonce_value = $this->cron_status_manager->get_conversion_request_id();
		if ( $nonce_value === null ) {
			return false;
		}

		return ( $request_nonce === $nonce_value );
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_route_url(): string {
		return get_rest_url(
			null,
			sprintf(
				'%1$s/%2$s',
				EndpointIntegrator::ROUTE_NAMESPACE,
				self::get_route_name()
			)
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_route_nonce(): string {
		return ( new CronStatusManager() )->refresh_conversion_request_id();
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_route_nonce_header(): string {
		return self::ROUTE_NONCE_HEADER;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_route_response( \WP_REST_Request $request ) {
		$request_id = $request->get_header( $this->get_route_nonce_header() ) ?: '';
		$this->cron_initiator->init_conversion( $request_id );

		return new \WP_REST_Response( null, 200 );
	}
}
