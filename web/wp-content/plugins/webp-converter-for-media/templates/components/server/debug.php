<?php
/**
 * Information about debugging displayed in server configuration widget.
 *
 * @var string[] $errors_codes          List of server configuration errors.
 * @var string   $size_png_path         Size of file.
 * @var string   $size_png2_path        Size of file.
 * @var string   $size_png_url          Size of file.
 * @var string   $size_png2_url         Size of file.
 * @var string   $size_png_as_webp_url  Size of file.
 * @var string   $size_png2_as_webp_url Size of file.
 * @var mixed[]  $plugin_settings       Option keys with values.
 *
 * @package Converter for Media
 */

use WebpConverter\Error\Notice\AccessTokenInvalidNotice;
use WebpConverter\Service\OptionsAccessManager;
use WebpConverter\Service\TokenValidator;

?>
<h4>Errors debug</h4>
<table>
	<tbody>
	<tr>
		<td class="e">Size of PNG <em>(by server path)</em></td>
		<td class="v">
			<?php echo esc_html( $size_png_path ); ?>
		</td>
	</tr>
	<tr>
		<td class="e">Size of PNG2 <em>(by server path)</em></td>
		<td class="v">
			<?php echo esc_html( $size_png2_path ); ?>
		</td>
	</tr>
	<tr>
		<td class="e">Size of PNG as WEBP <em>(by URL)</em></td>
		<td class="v">
			<?php echo esc_html( $size_png_as_webp_url ); ?>
		</td>
	</tr>
	<tr>
		<td class="e">Size of PNG as PNG <em>(by URL)</em></td>
		<td class="v">
			<?php echo esc_html( $size_png_url ); ?>
		</td>
	</tr>
	<tr>
		<td class="e">Size of PNG2 as WEBP <em>(by URL)</em></td>
		<td class="v">
			<?php echo esc_html( $size_png2_as_webp_url ); ?>
		</td>
	</tr>
	<tr>
		<td class="e">Size of PNG2 as PNG2 <em>(by URL)</em></td>
		<td class="v">
			<?php echo esc_html( $size_png2_url ); ?>
		</td>
	</tr>
	<tr>
		<td class="e">Plugin settings</td>
		<td class="v">
			<?php echo esc_html( json_encode( $plugin_settings ) ?: '-' ); ?>
		</td>
	</tr>
	<tr>
		<td class="e">Error codes</td>
		<td class="v">
			<?php echo esc_html( json_encode( $errors_codes ) ?: '-' ); ?>
		</td>
	</tr>
	<?php if ( in_array( AccessTokenInvalidNotice::ERROR_KEY, $errors_codes ) ) : ?>
		<tr>
			<td class="e">Token validation request</td>
			<td class="v">
				<?php echo esc_html( json_encode( OptionsAccessManager::get_option( TokenValidator::REQUEST_INFO_OPTION ) ) ?: '-' ); ?>
			</td>
		</tr>
	<?php endif; ?>
	</tbody>
</table>
