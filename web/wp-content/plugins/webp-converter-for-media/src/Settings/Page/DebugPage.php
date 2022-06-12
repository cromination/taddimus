<?php


namespace WebpConverter\Settings\Page;

use WebpConverter\Error\Detector\RewritesErrorsDetector;
use WebpConverter\Loader\LoaderAbstract;
use WebpConverter\PluginData;
use WebpConverter\PluginInfo;
use WebpConverter\Service\FileLoader;
use WebpConverter\Service\OptionsAccessManager;
use WebpConverter\Service\ViewLoader;
use WebpConverter\Settings\Option\AccessTokenOption;
use WebpConverter\Settings\SettingsSave;

/**
 * Supports debug tab in plugin settings page.
 */
class DebugPage extends PageAbstract {

	const PAGE_VIEW_PATH = 'views/settings-debug.php';

	/**
	 * @var PluginInfo
	 */
	private $plugin_info;

	/**
	 * @var FileLoader
	 */
	private $file_loader;

	public function __construct(
		PluginInfo $plugin_info, PluginData $plugin_data,
		FileLoader $file_loader = null
	) {
		$this->plugin_info = $plugin_info;
		$this->file_loader = $file_loader ?: new FileLoader( $plugin_info, $plugin_data );
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_page_active(): bool {
		return ( isset( $_GET['action'] ) && ( $_GET['action'] === 'server' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * {@inheritdoc}
	 */
	public function show_page_view() {
		$uploads_url  = apply_filters( 'webpc_dir_url', '', 'uploads' );
		$uploads_path = apply_filters( 'webpc_dir_path', '', 'uploads' );
		$ver_param    = time();

		do_action( LoaderAbstract::ACTION_NAME, true, true );

		( new ViewLoader( $this->plugin_info ) )->load_view(
			self::PAGE_VIEW_PATH,
			[
				'settings_url'          => PageIntegration::get_settings_page_url(),
				'settings_debug_url'    => PageIntegration::get_settings_page_url( 'server' ),
				'size_png_path'         => $this->file_loader->get_file_size_by_path(
					$uploads_path . RewritesErrorsDetector::PATH_OUTPUT_FILE_PNG
				),
				'size_png2_path'        => $this->file_loader->get_file_size_by_path(
					$uploads_path . RewritesErrorsDetector::PATH_OUTPUT_FILE_PNG2
				),
				'size_png_url'          => $this->file_loader->get_file_size_by_url(
					$uploads_url . RewritesErrorsDetector::PATH_OUTPUT_FILE_PNG,
					false,
					$ver_param
				),
				'size_png2_url'         => $this->file_loader->get_file_size_by_url(
					$uploads_url . RewritesErrorsDetector::PATH_OUTPUT_FILE_PNG2,
					false,
					$ver_param
				),
				'size_png_as_webp_url'  => $this->file_loader->get_file_size_by_url(
					$uploads_url . RewritesErrorsDetector::PATH_OUTPUT_FILE_PNG,
					true,
					$ver_param
				),
				'size_png2_as_webp_url' => $this->file_loader->get_file_size_by_url(
					$uploads_url . RewritesErrorsDetector::PATH_OUTPUT_FILE_PNG2,
					true,
					$ver_param
				),
				'plugin_settings'       => $this->get_plugin_settings(),
			]
		);

		do_action( LoaderAbstract::ACTION_NAME, true );
	}

	/**
	 * @return mixed[]
	 */
	private function get_plugin_settings(): array {
		$plugin_settings = OptionsAccessManager::get_option( SettingsSave::SETTINGS_OPTION, [] );
		$access_token    = $plugin_settings[ AccessTokenOption::OPTION_NAME ] ?? '';
		if ( $access_token ) {
			$plugin_settings[ AccessTokenOption::OPTION_NAME ] = substr( $access_token, 0, 32 ) . str_repeat( '*', 32 );
		}

		return $plugin_settings;
	}
}
