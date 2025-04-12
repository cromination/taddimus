<?php

namespace WebpConverter;

use WebpConverter\Conversion\Directory\DirectoryFactory;
use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\Conversion\Method\MethodFactory;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\Settings\OptionsManager;

/**
 * Manages plugin values.
 */
class PluginData {

	/**
	 * Handler of class with plugin settings.
	 *
	 * @var OptionsManager
	 */
	private $settings_object;

	/**
	 * Cached settings of plugin.
	 *
	 * @var mixed[]|null
	 */
	private $plugin_settings = null;

	/**
	 * Cached settings of plugin without sensitive data.
	 *
	 * @var mixed[]|null
	 */
	private $plugin_public_settings = null;

	/**
	 * Cached settings of plugin for debug.
	 *
	 * @var mixed[]|null
	 */
	private $debug_settings = null;

	public function __construct(
		TokenRepository $token_repository,
		MethodFactory $method_factory,
		FormatFactory $format_factory,
		DirectoryFactory $directory_factory
	) {
		$this->settings_object = new OptionsManager( $token_repository, $method_factory, $format_factory, $directory_factory );
	}

	/**
	 * @return mixed[]
	 */
	public function get_plugin_settings(): array {
		if ( $this->plugin_settings === null ) {
			$this->plugin_settings = $this->settings_object->get_values();
		}
		return $this->plugin_settings;
	}

	/**
	 * Returns settings of plugin without sensitive data.
	 *
	 * @return mixed[]
	 */
	public function get_plugin_settings_public(): array {
		if ( $this->plugin_public_settings === null ) {
			$this->plugin_public_settings = $this->settings_object->get_public_values();
		}
		return $this->plugin_public_settings;
	}

	/**
	 * Returns settings of plugin for debug.
	 *
	 * @return mixed[]
	 */
	public function get_plugin_settings_debug(): array {
		if ( $this->debug_settings === null ) {
			$this->debug_settings = $this->settings_object->get_values( true );
		}
		return $this->debug_settings;
	}

	/**
	 * @param string|null $form_name .
	 *
	 * @return mixed[]
	 */
	public function get_settings_fields( ?string $form_name = null ): array {
		return $this->settings_object->get_fields( $form_name );
	}

	/**
	 * Clears cache for settings of plugin.
	 *
	 * @return void
	 */
	public function invalidate_plugin_settings() {
		$this->plugin_settings        = null;
		$this->plugin_public_settings = null;
		$this->debug_settings         = null;
	}

	/**
	 * Retrieves and validates plugin settings submitted via POST.
	 *
	 * @return mixed[]|null Validated plugin settings values. Returns null if form submission has not been verified.
	 */
	public function get_validated_posted_data(): ?array {
		return $this->settings_object->get_validated_posted_values();
	}

	/**
	 * Validates provided plugin settings data.
	 *
	 * @param mixed[] $form_data Plugin settings data to validate.
	 *
	 * @return mixed[] Validated plugin settings values.
	 */
	public function get_validated_form_data( array $form_data ): array {
		return $this->settings_object->get_validated_form_values( $form_data );
	}
}
