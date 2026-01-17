<?php

namespace WebpConverter\Conversion\Format;

use WebpConverter\Conversion\Method\RemoteMethod;
use WebpConverter\Repository\TokenRepository;

/**
 * Supports AVIF as output format for images.
 */
class AvifFormat extends FormatAbstract {

	const FORMAT_EXTENSION = 'avif';

	/**
	 * @var TokenRepository
	 */
	private $token_repository;

	public function __construct( TokenRepository $token_repository ) {
		$this->token_repository = $token_repository;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_extension(): string {
		return self::FORMAT_EXTENSION;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_mime_type(): string {
		return 'image/avif';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return 'AVIF';
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_available( string $conversion_method ): bool {
		return ( $this->token_repository->get_token()->get_valid_status() && ( $conversion_method === RemoteMethod::METHOD_NAME ) );
	}
}
