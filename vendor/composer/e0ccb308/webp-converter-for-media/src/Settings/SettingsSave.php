<?php

namespace WebpConverter\Settings;

use WebpConverter\Conversion\Cron\CronEventGenerator;
use WebpConverter\Conversion\Directory\DirectoryFactory;
use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\Conversion\Method\RemoteMethod;
use WebpConverter\Loader\LoaderAbstract;
use WebpConverter\PluginData;
use WebpConverter\Service\NonceManager;
use WebpConverter\Service\OptionsAccessManager;
use WebpConverter\Service\TokenValidator;
use WebpConverter\Settings\Option\AccessTokenOption;
use WebpConverter\Settings\Option\ConversionMethodOption;
use WebpConverter\Settings\Option\OutputFormatsOption;
use WebpConverter\Settings\Option\SupportedDirectoriesOption;

/**
 * Supports saving plugin settings on plugin settings page.
 */
class SettingsSave {

	const SETTINGS_OPTION         = 'webpc_settings';
	const SUBMIT_VALUE            = 'webpc_save';
	const SUBMIT_TOKEN_ACTIVATE   = 'webpc_token_activate';
	const SUBMIT_TOKEN_DEACTIVATE = 'webpc_token_deactivate';
	const NONCE_PARAM_KEY         = 'webpc_nonce';
	const NONCE_PARAM_VALUE       = 'webpc-save';

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * @var TokenValidator
	 */
	private $token_validator;

	public function __construct( PluginData $plugin_data, TokenValidator $token_validator = null ) {
		$this->plugin_data     = $plugin_data;
		$this->token_validator = $token_validator ?: new TokenValidator();
	}

	/**
	 * Saves plugin settings after submitting form on plugin settings page.
	 *
	 * @return void
	 */
	public function save_settings() {
		$post_data = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ( ! isset( $post_data[ self::SUBMIT_VALUE ] )
				&& ! isset( $post_data[ self::SUBMIT_TOKEN_ACTIVATE ] )
				&& ! isset( $post_data[ self::SUBMIT_TOKEN_DEACTIVATE ] ) )
			|| ! ( new NonceManager() )
				->verify_nonce( $post_data[ self::NONCE_PARAM_KEY ] ?? '', self::NONCE_PARAM_VALUE ) ) {
			return;
		}

		$saved_settings = $this->plugin_data->get_plugin_settings();
		if ( isset( $post_data[ self::SUBMIT_VALUE ] ) ) {
			$plugin_settings = ( new PluginOptions() )->get_values( false, $post_data );
		} else {
			$plugin_settings = $saved_settings;
		}

		if ( isset( $post_data[ self::SUBMIT_TOKEN_ACTIVATE ] ) ) {
			$plugin_settings[ AccessTokenOption::OPTION_NAME ] = sanitize_text_field( $post_data[ AccessTokenOption::OPTION_NAME ] ?: '' );
		} elseif ( isset( $post_data[ self::SUBMIT_TOKEN_DEACTIVATE ] ) ) {
			$plugin_settings[ AccessTokenOption::OPTION_NAME ] = '';
		} elseif ( substr( $plugin_settings[ AccessTokenOption::OPTION_NAME ], -32 ) === str_repeat( '*', 32 ) ) {
			$plugin_settings[ AccessTokenOption::OPTION_NAME ] = $saved_settings[ AccessTokenOption::OPTION_NAME ];
		}

		$token = $this->token_validator->validate_token( $plugin_settings[ AccessTokenOption::OPTION_NAME ] );
		if ( $token->get_valid_status() ) {
			$plugin_settings[ ConversionMethodOption::OPTION_NAME ] = RemoteMethod::METHOD_NAME;

			if ( isset( $post_data[ self::SUBMIT_TOKEN_ACTIVATE ] ) ) {
				$plugin_settings[ OutputFormatsOption::OPTION_NAME ] = [
					AvifFormat::FORMAT_EXTENSION,
					WebpFormat::FORMAT_EXTENSION,
				];
			} elseif ( ! $plugin_settings[ OutputFormatsOption::OPTION_NAME ] ) {
				$plugin_settings[ OutputFormatsOption::OPTION_NAME ] = [
					WebpFormat::FORMAT_EXTENSION,
				];
			}
		}

		OptionsAccessManager::update_option(
			self::SETTINGS_OPTION,
			( new PluginOptions() )->get_values( false, $plugin_settings )
		);
		$this->plugin_data->invalidate_plugin_settings();
		$this->init_actions_after_save();
	}

	/**
	 * Runs actions needed after saving plugin settings.
	 *
	 * @return void
	 */
	private function init_actions_after_save() {
		do_action( LoaderAbstract::ACTION_NAME, true );
		wp_clear_scheduled_hook( CronEventGenerator::CRON_PATHS_ACTION );

		$settings = $this->plugin_data->get_plugin_settings();
		( new DirectoryFactory() )->remove_unused_output_directories( $settings[ SupportedDirectoriesOption::OPTION_NAME ] );
	}
}
