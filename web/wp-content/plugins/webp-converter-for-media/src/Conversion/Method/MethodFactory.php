<?php

namespace WebpConverter\Conversion\Method;

use WebpConverter\Conversion\CrashedFilesOperator;
use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\Conversion\LargerFilesOperator;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\Service\ServerConfigurator;

/**
 * Adds support for all conversion methods and returns information about them.
 */
class MethodFactory {

	/**
	 * @var TokenRepository
	 */
	private $token_repository;

	/**
	 * @var FormatFactory
	 */
	private $format_factory;

	/**
	 * @var CrashedFilesOperator
	 */
	private $skip_crashed;

	/**
	 * @var LargerFilesOperator
	 */
	private $skip_larger;

	/**
	 * @var ServerConfigurator
	 */
	private $server_configurator;

	/**
	 * Objects of supported conversion methods.
	 *
	 * @var MethodInterface[]
	 */
	private $methods = [];

	public function __construct(
		TokenRepository $token_repository,
		FormatFactory $format_factory,
		CrashedFilesOperator $skip_crashed = null,
		LargerFilesOperator $skip_larger = null,
		ServerConfigurator $server_configurator = null
	) {
		$this->token_repository    = $token_repository;
		$this->format_factory      = $format_factory;
		$this->skip_crashed        = $skip_crashed ?: new CrashedFilesOperator();
		$this->skip_larger         = $skip_larger ?: new LargerFilesOperator();
		$this->server_configurator = $server_configurator ?: new ServerConfigurator();

		$this->set_integration( new ImagickMethod( $format_factory, $this->skip_crashed, $this->skip_larger, $this->server_configurator ) );
		$this->set_integration( new GdMethod( $format_factory, $this->skip_crashed, $this->skip_larger, $this->server_configurator ) );
		$this->set_integration( new RemoteMethod( $this->token_repository, $format_factory, $this->skip_crashed, $this->skip_larger, $this->server_configurator ) );
	}

	/**
	 * Sets integration for method.
	 *
	 * @param MethodInterface $method .
	 *
	 * @return void
	 */
	private function set_integration( MethodInterface $method ) {
		$this->methods[ $method->get_name() ] = $method;
	}

	/**
	 * Returns objects of conversion methods.
	 *
	 * @return MethodInterface[] .
	 */
	public function get_methods_objects(): array {
		$values = [];
		foreach ( $this->methods as $method ) {
			$values[ $method->get_name() ] = $method;
		}
		return $values;
	}

	/**
	 * Returns list of conversion methods.
	 *
	 * @return string[] Names of conversion methods with labels.
	 */
	public function get_methods(): array {
		$values = [];
		foreach ( $this->get_methods_objects() as $method_name => $method ) {
			$values[ $method_name ] = $method->get_label();
		}
		return $values;
	}

	/**
	 * Returns list of installed conversion methods.
	 *
	 * @return string[] Names of conversion methods with labels.
	 */
	public function get_available_methods(): array {
		$token_status = $this->token_repository->get_token()->get_valid_status();
		$values       = [];
		foreach ( $this->get_methods_objects() as $method_name => $method ) {
			if ( ! $method::is_method_installed()
				|| ( ! $this->format_factory->get_available_formats( $method_name ) ) ) {
				continue;
			}

			if ( ( $token_status && $method::is_pro_feature() ) || ( ! $token_status && ! $method::is_pro_feature() ) ) {
				$values[ $method_name ] = $method->get_label();
			}
		}
		return $values;
	}
}
