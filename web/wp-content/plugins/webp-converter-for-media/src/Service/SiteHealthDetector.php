<?php

namespace WebpConverter\Service;

use WebpConverter\HookableInterface;
use WebpConverter\PluginData;
use WebpConverter\Settings\Option\AccessTokenOption;
use WebpConverter\Settings\Page\PageIntegrator;

/**
 * Diagnoses the website and displays recommendations on the Site Health screen.
 */
class SiteHealthDetector implements HookableInterface {

	const SITE_HEALTH_TEST_AVIF_FORMAT = 'webpc_avif_format';

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	public function __construct( PluginData $plugin_data ) {
		$this->plugin_data = $plugin_data;
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		add_filter( 'site_status_tests', [ $this, 'add_test_to_check_avif_format' ] );
	}

	/**
	 * @param mixed[][] $tests .
	 *
	 * @return mixed[][]
	 * @internal
	 */
	public function add_test_to_check_avif_format( array $tests ): array {
		$settings = $this->plugin_data->get_plugin_settings();
		if ( isset( $settings[ AccessTokenOption::OPTION_NAME ] ) && $settings[ AccessTokenOption::OPTION_NAME ] ) {
			return $tests;
		}

		$tests['direct'] = array_merge(
			[
				self::SITE_HEALTH_TEST_AVIF_FORMAT => [
					'label' => __( 'Serve images in the AVIF format', 'webp-converter-for-media' ),
					'test'  => [ $this, 'perform_avif_format_test' ],
				],
			],
			$tests['direct']
		);
		return $tests;
	}

	/**
	 * @return mixed[]
	 * @internal
	 */
	public function perform_avif_format_test(): array {
		return [
			'label'       => __( 'Serve images in the AVIF format', 'webp-converter-for-media' ),
			'status'      => 'recommended',
			'badge'       => [
				'label' => __( 'Performance' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				'color' => 'blue',
			],
			'description' => __( 'The AVIF format is the successor to the WebP format. Images converted to the AVIF format weigh about 50% less than images converted only to the WebP format, while maintaining better image quality.', 'webp-converter-for-media' ),
			'actions'     => sprintf(
				'<p>%1$s</p><h4>%2$s</h4><p>%3$s</p><p>%4$s</p>',
				sprintf(
				/* translators: %1$s: plugin name, %2$s: format name */
					__( 'The %1$s plugin you are using allows you to convert your images to the %2$s format.', 'webp-converter-for-media' ),
					'<a href="' . PageIntegrator::get_settings_page_url() . '">Converter for Media</a>',
					'AVIF'
				),
				__( 'How does the plugin work?', 'webp-converter-for-media' ),
				__( 'When a browser tries to load an image file, the plugin checks if it supports the AVIF format (if enabled in the plugin settings). If so, the browser will receive the equivalent of the original image in the AVIF format. If it does not support AVIF, but supports the WebP format, the browser will receive the equivalent of the original image in WebP format. In case the browser does not support either WebP or AVIF, the original image is loaded. This means full support for all browsers.', 'webp-converter-for-media' ),
				sprintf(
				/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
					__( '%1$sUpgrade to PRO%2$s', 'webp-converter-for-media' ),
					'<a href="https://url.mattplugins.com/converter-site-health-avif-format-upgrade" target="_blank">',
					' </a>'
				)
			),
			'test'        => self::SITE_HEALTH_TEST_AVIF_FORMAT,
		];
	}
}
