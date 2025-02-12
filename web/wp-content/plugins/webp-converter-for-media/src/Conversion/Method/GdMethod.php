<?php

namespace WebpConverter\Conversion\Method;

use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\Exception\ConversionErrorException;
use WebpConverter\Exception\ExtensionUnsupportedException;
use WebpConverter\Exception\FunctionUnavailableException;
use WebpConverter\Exception\ImageAnimatedException;
use WebpConverter\Exception\ImageInvalidException;
use WebpConverter\Exception\ResolutionOversizeException;
use WebpConverter\Settings\Option\ImagesQualityOption;
use WebpConverter\Settings\Option\SupportedExtensionsOption;

/**
 * Supports image conversion method using GD library.
 */
class GdMethod extends LibraryMethodAbstract {

	const METHOD_NAME        = 'gd';
	const MAX_METHOD_QUALITY = 99.9;

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return self::METHOD_NAME;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return 'GD';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function is_method_installed(): bool {
		return ( extension_loaded( 'gd' ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public static function is_method_active( string $format ): bool {
		if ( ! self::is_method_installed() || ! ( $function = self::get_format_function( $format ) ) ) {
			return false;
		}
		return function_exists( $function );
	}

	/**
	 * Returns name of function to convert source image to output image.
	 *
	 * @param string $format Extension of output format.
	 *
	 * @return string|null Function name using for conversion.
	 */
	private static function get_format_function( string $format ): ?string {
		switch ( $format ) {
			case WebpFormat::FORMAT_EXTENSION:
				return 'imagewebp';
			default:
				return null;
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return resource Image object.
	 * @throws ExtensionUnsupportedException
	 * @throws FunctionUnavailableException
	 * @throws ImageInvalidException
	 * @throws ImageAnimatedException
	 */
	public function create_image_by_path( string $source_path, array $plugin_settings ) {
		$extension = strtolower( pathinfo( $source_path, PATHINFO_EXTENSION ) );
		$methods   = apply_filters(
			'webpc_gd_create_methods',
			[
				'imagecreatefromjpeg' => [ 'jpg', 'jpeg' ],
				'imagecreatefrompng'  => [ 'png' ],
				'imagecreatefromgif'  => [ 'gif' ],
			]
		);

		if ( ( $extension === 'gif' ) && $this->is_animated( $source_path ) ) {
			throw new ImageAnimatedException( $source_path );
		}

		foreach ( $methods as $method => $extensions ) {
			if ( ! in_array( $extension, $plugin_settings[ SupportedExtensionsOption::OPTION_NAME ] )
				|| ! in_array( $extension, $extensions ) ) {
				continue;
			} elseif ( ! function_exists( $method ) ) {
				throw new FunctionUnavailableException( $method );
			} elseif ( ! $image = @$method( $source_path ) ) { // phpcs:ignore
				throw new ImageInvalidException( $source_path );
			}
		}

		if ( ! isset( $image ) ) {
			throw new ExtensionUnsupportedException( [ $extension, $source_path ] );
		}

		$exif = ( function_exists( 'exif_read_data' ) )
			? ( @exif_read_data( $source_path ) ?: [] ) // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			: [];

		switch ( $exif['Orientation'] ?? '' ) {
			case 2:
				imageflip( $image, IMG_FLIP_HORIZONTAL );
				break;
			case 3:
				$image = imagerotate( $image, 180, 0 );
				break;
			case 4:
				imageflip( $image, IMG_FLIP_HORIZONTAL );
				$image = imagerotate( $image, 180, 0 );
				break;
			case 5:
				imageflip( $image, IMG_FLIP_VERTICAL );
				$image = imagerotate( $image, -90, 0 );
				break;
			case 6:
				$image = imagerotate( $image, -90, 0 );
				break;
			case 7:
				imageflip( $image, IMG_FLIP_VERTICAL );
				$image = imagerotate( $image, 90, 0 );
				break;
			case 8:
				$image = imagerotate( $image, 90, 0 );
				break;
		}

		return $this->update_image_resource( $image, $extension );
	}

	/**
	 * Updates image object before converting to output format.
	 *
	 * @param resource $image     Image object.
	 * @param string   $extension Extension of output format.
	 *
	 * @return resource Image object.
	 * @throws FunctionUnavailableException
	 */
	private function update_image_resource( $image, string $extension ) {
		if ( ! function_exists( 'imageistruecolor' ) ) {
			throw new FunctionUnavailableException( 'imageistruecolor' );
		}

		if ( ! imageistruecolor( $image ) ) {
			if ( ! function_exists( 'imagepalettetotruecolor' ) ) {
				throw new FunctionUnavailableException( 'imagepalettetotruecolor' );
			}
			imagepalettetotruecolor( $image );
		}

		switch ( $extension ) {
			case 'png':
				if ( ! function_exists( 'imagealphablending' ) ) {
					throw new FunctionUnavailableException( 'imagealphablending' );
				}
				imagealphablending( $image, false );

				if ( ! function_exists( 'imagesavealpha' ) ) {
					throw new FunctionUnavailableException( 'imagesavealpha' );
				}
				imagesavealpha( $image, true );
				break;
		}

		return $image;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws ConversionErrorException
	 * @throws FunctionUnavailableException
	 * @throws ResolutionOversizeException
	 */
	public function convert_image_to_output( $image, string $source_path, string $output_path, string $format, array $plugin_settings ) {
		$function = self::get_format_function( $format );
		if ( $function === null ) {
			return;
		}

		$image          = apply_filters( 'webpc_gd_before_saving', $image, $source_path );
		$output_quality = min( $plugin_settings[ ImagesQualityOption::OPTION_NAME ], self::MAX_METHOD_QUALITY );

		if ( ! function_exists( $function ) ) {
			throw new FunctionUnavailableException( $function );
		} elseif ( ( imagesx( $image ) > 8192 ) || ( imagesy( $image ) > 8192 ) ) {
			throw new ResolutionOversizeException( $source_path );
		} elseif ( is_callable( $function ) && ! $function( $image, $output_path, $output_quality ) ) {
			throw new ConversionErrorException( $source_path );
		}

		if ( filesize( $output_path ) % 2 === 1 ) {
			file_put_contents( $output_path, "\0", FILE_APPEND );
		}
	}

	/**
	 * @param string $source_path .
	 *
	 * @link https://www.php.net/manual/en/function.imagecreatefromgif.php#104473
	 */
	private function is_animated( string $source_path ): bool {
		if ( ! ( $fh = @fopen( $source_path, 'rb' ) ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return false;
		}

		$count = 0;
		while ( ! feof( $fh ) && ( $count < 2 ) ) {
			$chunk = fread( $fh, 1024 * 100 );
			$count = $count + preg_match_all( '#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk ?: '', $matches );
		}

		fclose( $fh );
		return ( $count > 1 );
	}
}
