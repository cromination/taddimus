<?php

namespace SPC\Utils;

use SPC\Constants;
use SPC\Services\Settings_Store;

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

class Logger {

	public const VERBOSITY_STANDARD = 1;
	public const VERBOSITY_HIGH     = 2;

	/**
	 * @var Logger|null
	 */
	private static $_instance = null;

	/**
	 * @var bool
	 */
	protected $is_logging_enabled = false;

	/**
	 * @var string
	 */
	protected $log_file_path = '';

	/**
	 * @var int
	 */
	protected $verbosity = self::VERBOSITY_STANDARD;

	protected function __construct() {
		$settings_store = Settings_Store::get_instance();

		$this->log_file_path      = Helpers::get_plugin_content_dir() . '/debug.log';
		$this->is_logging_enabled = (bool) $settings_store->get( Constants::SETTING_LOG_ENABLED );
		$max_file_size            = (int) $settings_store->get( Constants::SETTING_LOG_MAX_FILESIZE );

		// Reset log if it exceeded the max file size.
		if ( $max_file_size > 0 && file_exists( $this->log_file_path ) && ( filesize( $this->log_file_path ) / 1024 / 1024 ) >= $max_file_size ) {
			$this->do_reset();
		}

		$this->apply_verbosity( (int) $settings_store->get( Constants::SETTING_LOG_VERBOSITY ) );
	}

	/**
	 * Get the lazily-resolved singleton instance.
	 *
	 * @return Logger
	 */
	public static function get_instance(): self {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Swap the internal singleton. Intended as a test seam.
	 *
	 * @param Logger|null $instance The replacement instance, or null to clear.
	 */
	public static function set_instance( ?self $instance ): void {
		self::$_instance = $instance;
	}

	/**
	 * Append a log line.
	 *
	 * @param string $identifier   Short identifier tag.
	 * @param string $message      Log message.
	 * @param bool   $only_verbose Only write when verbosity is HIGH.
	 */
	public static function log( string $identifier, string $message, bool $only_verbose = false ): void {
		self::get_instance()->do_log( $identifier, $message, $only_verbose );
	}

	/**
	 * Truncate the log file.
	 */
	public static function reset(): void {
		self::get_instance()->do_reset();
	}

	/**
	 * Read the log file.
	 *
	 * @param int $last_lines Tail line count; 0 for full file.
	 */
	public static function read( int $last_lines = 0 ): string {
		return self::get_instance()->do_read( $last_lines );
	}

	/**
	 * Get the absolute path to the log file.
	 */
	public static function file_path(): string {
		return self::get_instance()->log_file_path;
	}

	/**
	 * Enable logging for the remainder of the request.
	 */
	public static function enable(): void {
		self::get_instance()->is_logging_enabled = true;
	}

	/**
	 * Disable logging for the remainder of the request.
	 */
	public static function disable(): void {
		self::get_instance()->is_logging_enabled = false;
	}

	/**
	 * Set the verbosity for the remainder of the request.
	 */
	public static function set_verbosity( int $verbosity ): void {
		self::get_instance()->apply_verbosity( $verbosity );
	}

	/**
	 * `init` handler: serve or download the log file for admins.
	 */
	public static function download_handler(): void {
		self::get_instance()->do_download();
	}

	protected function apply_verbosity( int $verbosity ): void {
		if ( ! in_array( $verbosity, [ self::VERBOSITY_STANDARD, self::VERBOSITY_HIGH ], true ) ) {
			$verbosity = self::VERBOSITY_STANDARD;
		}

		$this->verbosity = $verbosity;
	}

	protected function do_log( string $identifier, string $message, bool $only_verbose ): void {
		if (
			! $this->is_logging_enabled ||
			! $this->log_file_path ||
			( $only_verbose && $this->verbosity !== self::VERBOSITY_HIGH )
		) {
			return;
		}

		$line = sprintf( '[%s] [%s] %s', gmdate( 'Y-m-d H:i:s' ), $identifier, $message ) . PHP_EOL;

		error_log( $line, 3, $this->log_file_path );
	}

	protected function do_read( int $last_lines ): string {
		if ( ! $this->log_file_path ) {
			return '';
		}

		$log = file_get_contents( $this->log_file_path );

		if ( ! is_string( $log ) ) {
			return '';
		}

		if ( $last_lines > 0 ) {
			$log = explode( PHP_EOL, $log );
			$log = array_slice( $log, -$last_lines );
			$log = implode( PHP_EOL, $log );
		}

		return $log;
	}

	protected function do_reset(): void {
		if ( ! $this->log_file_path ) {
			return;
		}

		file_put_contents( $this->log_file_path, '' );
	}

	protected function do_download(): void {
		if ( ! isset( $_GET['swcfpc_download_log'] ) || ! file_exists( $this->log_file_path ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( $_GET['swcfpc_download_log'] === 'view' ) {
			echo '<pre>' . esc_html( file_get_contents( $this->log_file_path ) ) . '</pre>';

			exit;
		}

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=debug.log' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Connection: Keep-Alive' );
		header( 'Expires: 0' );
		header( 'Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0, s-maxage=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $this->log_file_path ) );
		readfile( $this->log_file_path );

		exit;
	}
}
