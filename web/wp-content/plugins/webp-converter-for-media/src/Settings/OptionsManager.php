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
	 * @param string|null  $form_name       .
	 * @param bool         $is_debug        Is debugging?
	 * @param mixed[]|null $posted_settings Settings submitted in form.
	 *
	 * @return mixed[] Options of plugin settings.
	 */
	public function get_options( string $form_name = null, bool $is_debug = false, array $posted_settings = null ): array {
		$is_save  = ( $posted_settings !== null );
		$settings = ( $is_save ) ? $posted_settings : OptionsAccessManager::get_option( SettingsManager::SETTINGS_OPTION, [] );

		$options = [];
		foreach ( $this->options_aggregator->get_options( $form_name ) as $option_object ) {
			$options[] = ( new OptionIntegrator( $option_object ) )->get_option_data( $settings, $is_debug, $is_save );
		}
		return $options;
	}

	/**
	 * @param bool $is_debug Is debugging?
	 *
	 * @return mixed[] Values of plugin settings.
	 */
	public function get_values( bool $is_debug = false ): array {
		$settings = OptionsAccessManager::get_option( SettingsManager::SETTINGS_OPTION, [] );

		$values = [];
		foreach ( $this->options_aggregator->get_options() as $option_object ) {
			$values[ $option_object->get_name() ] = $option_object->sanitize_value(
				( ! $is_debug )
					? ( $settings[ $option_object->get_name() ] ?? $option_object->get_default_value( $settings ) )
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
	 * @param mixed[]|null $posted_settings Settings submitted in form.
	 * @param string|null  $form_name       .
	 *
	 * @return mixed[] Values of plugin settings.
	 */
	public function get_validated_values( array $posted_settings = null, string $form_name = null ): array {
		$values = [];
		foreach ( $this->get_options( $form_name, false, $posted_settings ) as $option ) {
			$values[ $option['name'] ] = $option['value'];
		}
		return $values;
	}
}
