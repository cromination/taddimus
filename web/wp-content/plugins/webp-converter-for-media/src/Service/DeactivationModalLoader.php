<?php

namespace WebpConverter\Service;

use WebpConverter\Error\Notice\LibsNotInstalledNotice;
use WebpConverter\Error\Notice\LibsWithoutWebpSupportNotice;
use WebpConverter\HookableInterface;
use WebpConverter\PluginData;
use WebpConverter\PluginInfo;
use WebpConverter\Settings\Page\AdvancedSettingsPage;
use WebpConverter\Settings\Page\PageIntegrator;
use WebpConverterVendor\MattPlugins\DeactivationModal;

/**
 * Initiates the popup displayed when the plugin is deactivated.
 */
class DeactivationModalLoader implements HookableInterface {

	const API_URL = 'https://data.mattplugins.com/deactivations/%s';

	/**
	 * @var PluginInfo
	 */
	private $plugin_info;

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * @var StatsManager
	 */
	private $stats_manager;

	public function __construct(
		PluginInfo $plugin_info,
		PluginData $plugin_data,
		StatsManager $stats_manager = null
	) {
		$this->plugin_info   = $plugin_info;
		$this->plugin_data   = $plugin_data;
		$this->stats_manager = $stats_manager ?: new StatsManager();
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		add_action( 'load-plugins.php', [ $this, 'load_modal' ] );
	}

	/**
	 * @return void
	 * @throws DeactivationModal\Exception\DuplicatedFormOptionKeyException
	 * @throws DeactivationModal\Exception\DuplicatedFormValueKeyException
	 * @internal
	 */
	public function load_modal() {
		new DeactivationModal\Modal(
			$this->plugin_info->get_plugin_slug(),
			new DeactivationModal\Model\FormTemplate(
				sprintf( self::API_URL, $this->plugin_info->get_plugin_slug() ),
				sprintf(
				/* translators: %s: plugin name */
					__( 'We are sorry that you are leaving our %s plugin', 'webp-converter-for-media' ),
					'Converter for Media'
				),
				__( 'Can you, please, take a moment to tell us why you are deactivating this plugin (anonymous answer)?', 'webp-converter-for-media' ),
				__( 'Submit and Deactivate', 'webp-converter-for-media' ),
				__( 'Skip and Deactivate', 'webp-converter-for-media' ),
				'https://mattplugins.com/images/matt-plugins-gray.png',
				$this->load_notice_message()
			),
			( new DeactivationModal\Model\FormOptions() )
				->set_option(
					new DeactivationModal\Model\FormOption(
						'server_config',
						10,
						sprintf(
						/* translators: %s: notice title */
							__( 'I have the %s notice in the plugin settings', 'webp-converter-for-media' ),
							__( 'Server configuration error', 'webp-converter-for-media' )
						),
						function () {
							$errors = apply_filters( 'webpc_server_errors', [] );
							if ( ! in_array(
								$errors,
								[ [ LibsWithoutWebpSupportNotice::ERROR_KEY ], [ LibsNotInstalledNotice::ERROR_KEY ] ]
							) ) {
								return null;
							}

							return sprintf(
							/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
								__( 'If your server does not meet the technical requirements, you can use "Remote server" as "Conversion method", in %1$sthe plugin settings%2$s.', 'webp-converter-for-media' ),
								'<a href="' . esc_url( PageIntegrator::get_settings_page_url() ) . '">',
								'</a>'
							);
						},
						__( 'What is your error? Have you been looking for a solution to this issue?', 'webp-converter-for-media' )
					)
				)
				->set_option(
					new DeactivationModal\Model\FormOption(
						'misunderstanding',
						20,
						__( 'Images are not displayed in the WebP format', 'webp-converter-for-media' ),
						function () {
							return sprintf(
							/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
								__( 'Check out %1$sour instructions%2$s and see how to check if the plugin is working properly.', 'webp-converter-for-media' ),
								'<a href="https://url.mattplugins.com/converter-deactivation-misunderstanding-instruction" target="_blank">',
								'</a>'
							);
						},
						__( 'Did you check the operation of the plugin in accordance with the instructions?', 'webp-converter-for-media' )
					)
				)
				->set_option(
					new DeactivationModal\Model\FormOption(
						'website_broken',
						30,
						__( 'This plugin broke my website', 'webp-converter-for-media' ),
						function () {
							return sprintf(
							/* translators: %1$s: option label, %2$s: open anchor tag, %3$s: close anchor tag */
								__( 'Check the %1$s option in %2$sthe plugin settings%3$s - this should solve the problem.', 'webp-converter-for-media' ),
								__( 'Disable rewrite inheritance in .htaccess files', 'webp-converter-for-media' ),
								'<a href="' . esc_url( PageIntegrator::get_settings_page_url( AdvancedSettingsPage::PAGE_SLUG ) ) . '">',
								'</a>'
							);
						},
						__( 'What exactly happened?', 'webp-converter-for-media' )
					)
				)
				->set_option(
					new DeactivationModal\Model\FormOption(
						'better_plugin',
						40,
						__( 'I found a better plugin', 'webp-converter-for-media' ),
						null,
						__( 'What is the name of this plugin? Why is it better?', 'webp-converter-for-media' )
					)
				)
				->set_option(
					new DeactivationModal\Model\FormOption(
						'temporary_deactivation',
						50,
						__( 'This is a temporary deactivation', 'webp-converter-for-media' ),
						null,
						null
					)
				)
				->set_option(
					new DeactivationModal\Model\FormOption(
						'other',
						60,
						__( 'Other reason', 'webp-converter-for-media' ),
						null,
						__( 'What is the reason? What can we improve for you?', 'webp-converter-for-media' )
					)
				),
			( new DeactivationModal\Model\FormValues() )
				->set_value(
					new DeactivationModal\Model\FormValue(
						'request_error_codes',
						function () {
							return implode( ',', apply_filters( 'webpc_server_errors', [] ) );
						}
					)
				)
				->set_value(
					new DeactivationModal\Model\FormValue(
						'request_plugin_settings',
						function () {
							$settings_json = json_encode( $this->plugin_data->get_public_settings() );
							return base64_encode( $settings_json ?: '' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
						}
					)
				)
				->set_value(
					new DeactivationModal\Model\FormValue(
						'request_plugin_stats',
						function () {
							$stats_data = [
								'usage_time'          => $this->stats_manager->get_plugin_usage_time(),
								'first_version'       => $this->stats_manager->get_plugin_first_version(),
								'regeneration_images' => $this->stats_manager->get_regeneration_images(),
								'webp_all'            => $this->stats_manager->get_images_webp_all(),
								'webp_unconverted'    => $this->stats_manager->get_images_webp_unconverted(),
								'avif_all'            => $this->stats_manager->get_images_avif_all(),
								'avif_unconverted'    => $this->stats_manager->get_images_avif_unconverted(),
								'rewrite_root'        => PathsGenerator::get_rewrite_root(),
								'rewrite_path'        => PathsGenerator::get_rewrite_path(),
							];

							$stats_json = json_encode( $stats_data );
							return base64_encode( $stats_json ?: '' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
						}
					)
				)
				->set_value(
					new DeactivationModal\Model\FormValue(
						'request_plugin_version',
						function () {
							return $this->plugin_info->get_plugin_version();
						}
					)
				)
		);
	}

	/**
	 * @return string|null
	 */
	private function load_notice_message() {
		if ( ( apply_filters( 'webpc_server_errors', [] ) !== [] ) || is_multisite() ) {
			return null;
		}

		$images_all  = $this->stats_manager->get_images_webp_all() ?: 0;
		$images_left = $this->stats_manager->get_images_webp_unconverted() ?: 0;
		if ( ( $images_all === 0 ) || ( $images_left === 0 ) ) {
			return null;
		}

		return sprintf(
		/* translators: %1$s: button label, %2$s: open anchor tag, %3$s: close anchor tag */
			__( 'You have unconverted images on your website - click the %1$s button in %2$sthe plugin settings%3$s. This is all you need to do after installing the plugin.', 'webp-converter-for-media' ),
			'"' . __( 'Start Bulk Optimization', 'webp-converter-for-media' ) . '"',
			'<a href="' . esc_url( admin_url( 'upload.php?page=' . PageIntegrator::UPLOAD_MENU_PAGE ) ) . '">',
			'</a>'
		);
	}
}
