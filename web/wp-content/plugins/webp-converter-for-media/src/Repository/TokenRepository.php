<?php

namespace WebpConverter\Repository;

use WebpConverter\Model\Token;
use WebpConverter\Service\OptionsAccessManager;
use WebpConverter\Settings\Option\AccessTokenOption;
use WebpConverter\Settings\SettingsManager;

/**
 * Manages the token for the PRO version.
 */
class TokenRepository {

	const TOKEN_OPTION             = 'webpc_token_data';
	const TOKEN_VALUE_ACCESS_VALUE = 'token_value';
	const TOKEN_VALUE_VALID_STATUS = 'valid_status';
	const TOKEN_VALUE_IMAGES_USAGE = 'images_usage';
	const TOKEN_VALUE_IMAGES_LIMIT = 'images_limit';

	/**
	 * @var Token|null
	 */
	private $token = null;

	public function get_token( string $token_value = null ): Token {
		if ( $this->token ) {
			return $this->token;
		}

		$values   = OptionsAccessManager::get_option( self::TOKEN_OPTION, null );
		$settings = OptionsAccessManager::get_option( SettingsManager::SETTINGS_OPTION, [] );
		if ( ( $values === null ) || ( ! $token_value && ! ( $settings[ AccessTokenOption::OPTION_NAME ] ?? null ) ) ) {
			$this->token = new Token();
		} else {
			$this->token = new Token(
				$values[ self::TOKEN_VALUE_ACCESS_VALUE ] ?? null,
				$values[ self::TOKEN_VALUE_VALID_STATUS ] ?? false,
				$values[ self::TOKEN_VALUE_IMAGES_USAGE ] ?? 0,
				$values[ self::TOKEN_VALUE_IMAGES_LIMIT ] ?? 0
			);
		}

		return $this->token;
	}

	/**
	 * @param Token $token .
	 *
	 * @return void
	 */
	public function update_token( Token $token ) {
		OptionsAccessManager::update_option(
			self::TOKEN_OPTION,
			[
				self::TOKEN_VALUE_ACCESS_VALUE => $token->get_token_value(),
				self::TOKEN_VALUE_VALID_STATUS => $token->get_valid_status(),
				self::TOKEN_VALUE_IMAGES_USAGE => $token->get_images_usage(),
				self::TOKEN_VALUE_IMAGES_LIMIT => $token->get_images_limit(),
			]
		);
	}

	/**
	 * @return void
	 */
	public function reset_token() {
		$this->update_token( new Token() );
		$this->token = null;
	}
}
