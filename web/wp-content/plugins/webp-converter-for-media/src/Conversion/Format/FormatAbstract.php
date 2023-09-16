<?php

namespace WebpConverter\Conversion\Format;

use WebpConverter\Conversion\Method\GdMethod;
use WebpConverter\Conversion\Method\ImagickMethod;
use WebpConverter\Conversion\Method\RemoteMethod;

/**
 * Abstract class for class that supports output format for images.
 */
abstract class FormatAbstract implements FormatInterface {

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return sprintf( '.%s', $this->get_extension() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_available( string $conversion_method ): bool {
		switch ( $conversion_method ) {
			case ImagickMethod::METHOD_NAME:
				return ImagickMethod::is_method_active( $this->get_extension() );
			case GdMethod::METHOD_NAME:
				return GdMethod::is_method_active( $this->get_extension() );
			case RemoteMethod::METHOD_NAME:
				return RemoteMethod::is_method_active( $this->get_extension() );
		}

		return false;
	}
}
