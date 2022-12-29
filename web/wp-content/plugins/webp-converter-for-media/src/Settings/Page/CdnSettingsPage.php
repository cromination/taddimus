<?php


namespace WebpConverter\Settings\Page;

use WebpConverter\Loader\LoaderAbstract;
use WebpConverter\PluginData;
use WebpConverter\PluginInfo;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\Service\CloudflareConfigurator;
use WebpConverter\Settings\Option\OptionAbstract;
use WebpConverter\Settings\PluginOptions;

/**
 * {@inheritdoc}
 */
class CdnSettingsPage extends GeneralSettingsPage {

	const PAGE_SLUG = 'cdn';
	/**
	 * @var CloudflareConfigurator
	 */
	private $cloudflare_configurator;

	public function __construct(
		PluginInfo $plugin_info,
		PluginData $plugin_data,
		TokenRepository $token_repository,
		CloudflareConfigurator $cloudflare_configurator = null
	) {
		parent::__construct( $plugin_info, $plugin_data, $token_repository );
		$this->cloudflare_configurator = $cloudflare_configurator ?: new CloudflareConfigurator( $plugin_data );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_slug(): string {
		return self::PAGE_SLUG;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'CDN Settings', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_template_vars(): array {
		$parent_vars = parent::get_template_vars();

		$this->cloudflare_configurator->set_cache_config();
		$this->cloudflare_configurator->purge_cache();

		do_action( LoaderAbstract::ACTION_NAME, true );

		return array_merge(
			$parent_vars,
			[
				'form_options'         => ( new PluginOptions() )->get_options( OptionAbstract::FORM_TYPE_CDN ),
				'form_input_value'     => OptionAbstract::FORM_TYPE_CDN,
				'api_paths_url'        => null,
				'api_paths_nonce'      => null,
				'api_regenerate_url'   => null,
				'api_regenerate_nonce' => null,
			]
		);
	}
}
