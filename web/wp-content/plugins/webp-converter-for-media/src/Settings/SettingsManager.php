<?php

namespace WebpConverter\Settings;

use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\Conversion\Method\RemoteMethod;
use WebpConverter\PluginData;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\Service\OptionsAccessManager;
use WebpConverter\Service\TokenValidator;
use WebpConverter\Settings\Option\AccessTokenOption;
use WebpConverter\Settings\Option\ConversionMethodOption;
use WebpConverter\Settings\Option\OutputFormatsOption;

/**
 * Supports saving plugin settings on plugin settings page.
 */
class SettingsManager {

	const SETTINGS_OPTION     = 'webpc_settings';
	const FORM_TYPE_PARAM_KEY = 'webpc_form_type';
	const NONCE_PARAM_KEY     = 'webpc_nonce';
	const NONCE_PARAM_VALUE   = 'webpc-save';

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * @var TokenValidator
	 */
	private $token_validator;

	public function __construct(
		PluginData $plugin_data,
		TokenRepository $token_repository,
		TokenValidator $token_validator = null
	) {
		$this->plugin_data     = $plugin_data;
		$this->token_validator = $token_validator ?: new TokenValidator( $token_repository );
	}

	/**
	 * @param mixed[]|null $post_data .
	 *
	 * @return void
	 */
	public function save_settings( array $post_data = null ) {
		$previous_settings = $this->plugin_data->get_plugin_settings();
		$posted_settings   = ( $post_data !== null )
			? $this->plugin_data->validate_plugin_settings( $post_data, $post_data[ self::FORM_TYPE_PARAM_KEY ] ?? null )
			: [];
		$plugin_settings   = array_merge( $previous_settings, $posted_settings );

		$token = $this->token_validator->validate_token( $plugin_settings[ AccessTokenOption::OPTION_NAME ] );
		if ( $token->get_valid_status() ) {
			$plugin_settings[ ConversionMethodOption::OPTION_NAME ] = RemoteMethod::METHOD_NAME;

			if ( isset( $posted_settings[ AccessTokenOption::OPTION_NAME ] ) || ! $plugin_settings[ OutputFormatsOption::OPTION_NAME ] ) {
				$plugin_settings[ OutputFormatsOption::OPTION_NAME ] = [
					AvifFormat::FORMAT_EXTENSION,
					WebpFormat::FORMAT_EXTENSION,
				];
			}
		} elseif ( ( $plugin_settings[ ConversionMethodOption::OPTION_NAME ] === RemoteMethod::METHOD_NAME )
			&& ! $plugin_settings[ AccessTokenOption::OPTION_NAME ] ) {
			$plugin_settings[ ConversionMethodOption::OPTION_NAME ] = '';
		}

		$plugin_settings = $this->plugin_data->validate_plugin_settings( $plugin_settings );

		OptionsAccessManager::update_option( self::SETTINGS_OPTION, $plugin_settings );
		$this->plugin_data->invalidate_plugin_settings();

		do_action( 'webpc_settings_updated', $plugin_settings, $previous_settings );
	}
}
