<?php

namespace WebpConverter\Service;

use WebpConverter\Model\Token;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\WebpConverterConstants;

/**
 * Checks the token status for the PRO version.
 */
class TokenValidator {

	const API_TOKEN_SUCCESS_CODE = 200;
	const REQUEST_INFO_OPTION    = 'webpc_token_request_info';

	/**
	 * @var TokenRepository
	 */
	private $token_repository;

	/**
	 * @var Token
	 */
	private $token;

	public function __construct( TokenRepository $token_repository = null ) {
		$this->token_repository = $token_repository ?: new TokenRepository();
	}

	public function validate_token( string $token_value = null ): Token {
		$this->token = $this->token_repository->get_token( $token_value );
		$status      = ( $token_value && $this->check_access_token( $token_value ) );

		if ( $status ) {
			$this->token_repository->update_token(
				$this->token
					->set_token_value( $token_value )
					->set_valid_status( true )
			);
		} else {
			$this->token_repository->reset_token();
		}

		return $this->token_repository->get_token( $token_value );
	}

	private function check_access_token( string $token_value ): bool {
		$connect = curl_init( sprintf( WebpConverterConstants::API_TOKEN_VALIDATION_URL, $token_value ) );
		if ( ! $connect ) {
			return false;
		}

		curl_setopt( $connect, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $connect, CURLOPT_SSL_VERIFYHOST, 0 );
		curl_setopt( $connect, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $connect, CURLOPT_TIMEOUT, 10 );
		curl_setopt( $connect, CURLOPT_POST, true );
		curl_setopt(
			$connect,
			CURLOPT_POSTFIELDS,
			[
				'domain_host' => parse_url( get_site_url(), PHP_URL_HOST ),
			]
		);

		$response     = curl_exec( $connect );
		$request_info = curl_getinfo( $connect );
		curl_close( $connect );

		if ( $request_info['http_code'] !== self::API_TOKEN_SUCCESS_CODE ) {
			OptionsAccessManager::update_option( self::REQUEST_INFO_OPTION, $request_info );
			return false;
		}

		$response_json = ( $response && is_string( $response ) ) ? json_decode( $response, true ) : null;
		if ( ! $response_json ) {
			return false;
		}

		$this->token->set_images_usage( $response_json[ WebpConverterConstants::API_RESPONSE_VALUE_LIMIT_USAGE ] );
		$this->token->set_images_limit( $response_json[ WebpConverterConstants::API_RESPONSE_VALUE_LIMIT_MAX ] );

		return true;
	}
}
