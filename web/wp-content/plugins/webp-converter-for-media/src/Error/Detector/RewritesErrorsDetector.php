<?php

namespace WebpConverter\Error\Detector;

use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\Conversion\OutputPathGenerator;
use WebpConverter\Error\Notice\BypassingApacheNotice;
use WebpConverter\Error\Notice\NoticeInterface;
use WebpConverter\Error\Notice\PassthruNotWorkingNotice;
use WebpConverter\Error\Notice\RewritesCachedNotice;
use WebpConverter\Error\Notice\RewritesNotExecutedNotice;
use WebpConverter\Error\Notice\RewritesNotWorkingNotice;
use WebpConverter\Error\Notice\RewritesUploadsBlockedNotice;
use WebpConverter\Loader\HtaccessBypassingLoader;
use WebpConverter\Loader\HtaccessLoader;
use WebpConverter\Loader\LoaderAbstract;
use WebpConverter\Loader\PassthruLoader;
use WebpConverter\PluginData;
use WebpConverter\PluginInfo;
use WebpConverter\Service\FileLoader;
use WebpConverter\Settings\Option\LoaderTypeOption;

/**
 * Checks for configuration errors about non-working HTTP rewrites.
 */
class RewritesErrorsDetector implements DetectorInterface {

	const PATH_SOURCE_FILE_PNG     = '/assets/img/icon-test.png';
	const PATH_SOURCE_FILE_WEBP    = '/assets/img/icon-test.webp';
	const PATH_SOURCE_FILE_AVIF    = '/assets/img/icon-test.avif';
	const PATH_OUTPUT_FILE_PNG     = '/webp-converter-for-media-test.png';
	const PATH_OUTPUT_FILE_PNG2    = '/webp-converter-for-media-test.png2';
	const PATH_OUTPUT_FILE_PLUGINS = '/webp-converter-for-media/assets/img/icon-test.png';
	const URL_DEBUG_HTACCESS_FILE  = 'assets/img/debug-htaccess/icon-test.png2';

	/**
	 * @var PluginInfo
	 */
	private $plugin_info;

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * @var FileLoader
	 */
	private $file_loader;

	/**
	 * @var OutputPathGenerator
	 */
	private $output_path;

	/**
	 * @var string
	 */
	private $test_version;

	public function __construct(
		PluginInfo $plugin_info,
		PluginData $plugin_data,
		FormatFactory $format_factory,
		?FileLoader $file_loader = null,
		?OutputPathGenerator $output_path = null
	) {
		$this->plugin_info  = $plugin_info;
		$this->plugin_data  = $plugin_data;
		$this->file_loader  = $file_loader ?: new FileLoader();
		$this->output_path  = $output_path ?: new OutputPathGenerator( $format_factory );
		$this->test_version = uniqid();
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_error() {
		$this->convert_images_for_debug();

		do_action( LoaderAbstract::ACTION_NAME, true, true );
		$error = $this->detect_rewrites_error();
		do_action( LoaderAbstract::ACTION_NAME, true );

		return $error;
	}

	/**
	 * @return NoticeInterface|null
	 */
	private function detect_rewrites_error() {
		$settings    = $this->plugin_data->get_plugin_settings();
		$loader_type = $settings[ LoaderTypeOption::OPTION_NAME ] ?? '';

		switch ( $loader_type ) {
			case HtaccessLoader::LOADER_TYPE:
			case HtaccessBypassingLoader::LOADER_TYPE:
				if ( $this->if_redirects_are_works() === true ) {
					break;
				}

				if ( $this->if_htaccess_can_be_overwritten() !== true ) {
					return new RewritesNotExecutedNotice();
				} elseif ( $this->if_bypassing_apache_is_active() === true ) {
					return new BypassingApacheNotice();
				} elseif ( $this->if_redirects_for_plugins_are_works() === true ) {
					return new RewritesUploadsBlockedNotice();
				}

				return new RewritesNotWorkingNotice();
			case PassthruLoader::LOADER_TYPE:
				if ( $this->if_redirects_are_works() === true ) {
					break;
				}

				return new PassthruNotWorkingNotice();
		}

		$this->test_version = uniqid();
		if ( $this->if_redirects_are_cached() === true ) {
			return new RewritesCachedNotice();
		}

		return null;
	}

	/**
	 * Converts and saves files needed for testing.
	 *
	 * @return void
	 */
	private function convert_images_for_debug() {
		$uploads_dir = apply_filters( 'webpc_dir_path', '', 'uploads' );
		if ( ! is_writable( $uploads_dir ) ) {
			return;
		}

		$path_file_png     = $uploads_dir . self::PATH_OUTPUT_FILE_PNG;
		$path_file_png2    = $uploads_dir . self::PATH_OUTPUT_FILE_PNG2;
		$path_file_plugins = apply_filters( 'webpc_dir_path', '', 'plugins' ) . self::PATH_OUTPUT_FILE_PLUGINS;
		$file_statuses     = [];

		if ( ! file_exists( $path_file_png ) || ! file_exists( $path_file_png2 ) ) {
			$file_statuses[] = copy( $this->plugin_info->get_plugin_directory_path() . self::PATH_SOURCE_FILE_PNG, $path_file_png );
			$file_statuses[] = copy( $this->plugin_info->get_plugin_directory_path() . self::PATH_SOURCE_FILE_PNG, $path_file_png2 );
		} else {
			$file_statuses[] = true;
			$file_statuses[] = true;
		}

		if ( ( $output_path = $this->output_path->get_path( $path_file_png, true, WebpFormat::FORMAT_EXTENSION ) )
			&& ! file_exists( $output_path ) ) {
			$file_statuses[] = copy( $this->plugin_info->get_plugin_directory_path() . self::PATH_SOURCE_FILE_WEBP, $output_path );
		} else {
			$file_statuses[] = true;
		}
		if ( ( $output_path = $this->output_path->get_path( $path_file_png, true, AvifFormat::FORMAT_EXTENSION ) )
			&& ! file_exists( $output_path ) ) {
			$file_statuses[] = copy( $this->plugin_info->get_plugin_directory_path() . self::PATH_SOURCE_FILE_AVIF, $output_path );
		} else {
			$file_statuses[] = true;
		}
		if ( ( $output_path = $this->output_path->get_path( $path_file_png2, true, WebpFormat::FORMAT_EXTENSION ) )
			&& ! file_exists( $output_path ) ) {
			$file_statuses[] = copy( $this->plugin_info->get_plugin_directory_path() . self::PATH_SOURCE_FILE_WEBP, $output_path );
		} else {
			$file_statuses[] = true;
		}

		if ( ( $output_path = $this->output_path->get_path( $path_file_plugins, true, WebpFormat::FORMAT_EXTENSION ) )
			&& ! file_exists( $output_path ) ) {
			$file_statuses[] = copy( $this->plugin_info->get_plugin_directory_path() . self::PATH_SOURCE_FILE_WEBP, $output_path );
		} else {
			$file_statuses[] = true;
		}

		if ( in_array( false, $file_statuses, true ) ) {
			$GLOBALS[ FileLoader::GLOBAL_LOGS_VARIABLE ][] = [
				'context' => __FUNCTION__,
				'status'  => $file_statuses,
			];
		}
	}

	/**
	 * Checks if redirects to output images are works.
	 *
	 * @return bool
	 */
	private function if_redirects_are_works(): bool {
		$uploads_dir = apply_filters( 'webpc_dir_path', '', 'uploads' );
		$uploads_url = apply_filters( 'webpc_dir_url', '', 'uploads' );

		$file_size = $this->file_loader->get_file_size_by_path(
			$uploads_dir . self::PATH_OUTPUT_FILE_PNG
		);
		$file_webp = $this->file_loader->get_file_size_by_url(
			$uploads_url . self::PATH_OUTPUT_FILE_PNG,
			true,
			$this->test_version,
			__FUNCTION__
		);
		if ( $file_webp > 0 ) {
			return ( $file_webp < $file_size );
		}

		$file_png_status = $this->file_loader->get_file_status_by_url(
			$uploads_url . self::PATH_OUTPUT_FILE_PNG,
			false,
			$this->test_version,
			__FUNCTION__
		);
		if ( $file_png_status === 500 ) {
			return false;
		}

		$file_webp_status = $this->file_loader->get_file_status_by_url(
			$uploads_url . self::PATH_OUTPUT_FILE_PNG,
			true,
			$this->test_version,
			__FUNCTION__
		);
		if ( ( $file_png_status === 200 ) && ( $file_webp_status === 404 ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if server supports using .htaccess files from custom locations.
	 *
	 * @return bool
	 */
	private function if_htaccess_can_be_overwritten(): bool {
		$file_status = $this->file_loader->get_file_status_by_url(
			$this->plugin_info->get_plugin_directory_url() . self::URL_DEBUG_HTACCESS_FILE,
			true,
			$this->test_version,
			__FUNCTION__
		);

		return ( in_array( $file_status, [ 403, 404 ] ) );
	}

	/**
	 * Checks if bypassing of redirects to output images is exists.
	 *
	 * @return bool
	 */
	private function if_bypassing_apache_is_active(): bool {
		$uploads_url = apply_filters( 'webpc_dir_url', '', 'uploads' );

		$file_png  = $this->file_loader->get_file_size_by_url(
			$uploads_url . self::PATH_OUTPUT_FILE_PNG,
			true,
			$this->test_version,
			__FUNCTION__
		);
		$file_png2 = $this->file_loader->get_file_size_by_url(
			$uploads_url . self::PATH_OUTPUT_FILE_PNG2,
			true,
			$this->test_version,
			__FUNCTION__
		);

		return ( $file_png > $file_png2 );
	}

	/**
	 * Checks if redirects to output images from /plugins directory are works.
	 *
	 * @return bool
	 */
	private function if_redirects_for_plugins_are_works(): bool {
		$uploads_dir = apply_filters( 'webpc_dir_path', '', 'plugins' );
		$uploads_url = apply_filters( 'webpc_dir_url', '', 'plugins' );

		$file_size = $this->file_loader->get_file_size_by_path(
			$uploads_dir . self::PATH_OUTPUT_FILE_PLUGINS
		);
		$file_webp = $this->file_loader->get_file_size_by_url(
			$uploads_url . self::PATH_OUTPUT_FILE_PLUGINS,
			true,
			$this->test_version,
			__FUNCTION__
		);

		return ( ( $file_webp < $file_size ) && ( $file_webp !== 0 ) );
	}

	/**
	 * Checks if redirects to output images are cached.
	 *
	 * @return bool
	 */
	private function if_redirects_are_cached(): bool {
		$uploads_url = apply_filters( 'webpc_dir_url', '', 'uploads' );

		$file_webp     = $this->file_loader->get_file_size_by_url(
			$uploads_url . self::PATH_OUTPUT_FILE_PNG,
			true,
			$this->test_version,
			__FUNCTION__
		);
		$file_original = $this->file_loader->get_file_size_by_url(
			$uploads_url . self::PATH_OUTPUT_FILE_PNG,
			false,
			$this->test_version,
			__FUNCTION__
		);

		return ( ( $file_webp > 0 ) && ( $file_webp === $file_original ) );
	}
}
