<?php

namespace WebpConverter\Settings;

use WebpConverter\Conversion\Directory\DirectoryFactory;
use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\Conversion\Method\MethodFactory;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\Service\OptionsAccessManager;
use WebpConverter\Settings\Option\OptionIntegrator;
use WebpConverter\Settings\Option\OptionsAggregator;

/**
 * Allows to integration with plugin settings by providing list of settings fields and saved values.
 */
class OptionsManager {

	/**
	 * @var OptionsAggregator
	 */
	private $options_aggregator;

	public function __construct(
		TokenRepository $token_repository,
		MethodFactory $method_factory,
		FormatFactory $format_factory,
		DirectoryFactory $directory_factory
	) {
		$this->options_aggregator = new OptionsAggregator( $token_repository, $method_factory, $format_factory, $directory_factory );
	}

	/**
	 * @param string|null $form_name .
	 *
	 * @return mixed[] Fields of plugin settings.
	 */
	public function get_fields( ?string $form_name = null ): array {
		$settings = OptionsAccessManager::get_option( SettingsManager::SETTINGS_OPTION, [] );

		$options = [];
		foreach ( $this->options_aggregator->get_options( $form_name ) as $option_object ) {
			$options[] = ( new OptionIntegrator( $option_object ) )->get_option_data( $settings );
		}
		return $options;
	}

	/**
	 * @param bool $is_debug .
	 *
	 * @return mixed[] Associative array of setting names and their values.
	 */
	public function get_values( bool $is_debug = false ): array {
		$settings = OptionsAccessManager::get_option( SettingsManager::SETTINGS_OPTION, [] );

		$values = [];
		foreach ( $this->options_aggregator->get_options() as $option_object ) {
			$values[ $option_object->get_name() ] = $option_object->sanitize_value(
				( ! $is_debug )
					? ( $settings[ $option_object->get_name() ] ?? $option_object->get_default_value() )
					: $option_object->get_debug_value( $settings )
			);
		}
		return $values;
	}

	/**
	 * @return mixed[] Values of plugin settings.
	 */
	public function get_public_values(): array {
		$settings = $this->get_values();

		$values = [];
		foreach ( $this->options_aggregator->get_options() as $option_object ) {
			$values[ $option_object->get_name() ] = $option_object->get_public_value(
				$settings[ $option_object->get_name() ]
			);
		}
		return $values;
	}

	/**
	 * Retrieves and validates submitted plugin setting values from POST data.
	 *
	 * @return mixed[]|null Array of validated setting names and their values.
	 *                      Returns null if form submission verification fails.
	 */
	public function get_validated_posted_values(): ?array {
		$nonce_value = sanitize_text_field( wp_unslash( $_POST[ SettingsManager::NONCE_PARAM_KEY ] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce_value, SettingsManager::NONCE_PARAM_VALUE ) ) {
			return null;
		}

		$form_type = ( isset( $_POST[ SettingsManager::FORM_TYPE_PARAM_KEY ] ) )
			? sanitize_text_field( wp_unslash( $_POST[ SettingsManager::FORM_TYPE_PARAM_KEY ] ?? '' ) )
			: '';
		if ( $form_type === '' ) {
			return [];
		}

		$settings = OptionsAccessManager::get_option( SettingsManager::SETTINGS_OPTION, [] );

		$values = [];
		foreach ( $this->options_aggregator->get_options( $form_type ) as $option_object ) {
			$values[ $option_object->get_name() ] = $option_object->validate_value(
				( isset( $_POST[ $option_object->get_name() ] ) ) ? wp_unslash( $_POST[ $option_object->get_name() ] ) : null, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$option_object->get_available_values( $settings ),
				$option_object->get_disabled_values( $settings )
			);
		}
		return $values;
	}

	/**
	 * Validates provided plugin setting values.
	 *
	 * @param mixed[] $form_data Plugin settings data to validate.
	 *
	 * @return mixed[] Array of validated setting names and their values.
	 */
	public function get_validated_form_values( array $form_data ): array {
		$values = [];
		foreach ( $this->options_aggregator->get_options() as $option_object ) {
			$values[ $option_object->get_name() ] = $option_object->validate_value(
				$form_data[ $option_object->get_name() ] ?? $option_object->get_default_value(),
				$option_object->get_available_values( $form_data ),
				$option_object->get_disabled_values( $form_data )
			);
		}
		return $values;
	}
}
