<?php
/**
 * Widget displayed information about plugin operation on plugin settings page.
 *
 * @package WebP Converter for Media
 */

?>
<div class="webpPage__widget">
	<h3 class="webpPage__widgetTitle webpPage__widgetTitle--second">
		<?php echo esc_html( __( 'How does this work?', 'webp-converter-for-media' ) ); ?>
	</h3>
	<div class="webpContent">
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
				/* translators: %1$s: button label */
					__( 'By adding images to your Media Library, they are automatically converted and saved in the separate directory. Images are converted using a selected conversion method. After installing the plugin, you need to convert all images once using the "%1$s" button.', 'webp-converter-for-media' ),
					__( 'Regenerate All', 'webp-converter-for-media' )
				)
			);
			?>
		</p>
		<p>
			<?php
			echo wp_kses_post(
				__( 'When the browser tries to download an image file, the server checks if it supports the AVIF format (if enabled in the plugin settings). If so, the browser will receive an equivalent of the original image in AVIF format. If it does not support AVIF, but supports the WebP format, the browser will receive an equivalent of the original image in WebP format. If the browser does not support either WebP or AVIF, the original image is loaded. This means full support for all browsers.', 'webp-converter-for-media' )
			);
			?>
		</p>
		<p>
			<?php
			echo wp_kses_post(
				__( 'The plugin in default loading mode (via .htaccess) does not change file URLs, so there are no problems with saving the page to the cache and the page generation time does not increase.', 'webp-converter-for-media' )
			);
			?>
		</p>
		<p>
			<?php
			echo wp_kses_post(
				__( 'Image URLs are modified using the module mod_rewrite on the server, i.e. the same, thanks to which we can use friendly links in WordPress. Additionally, the MIME type of the sent file is modified.', 'webp-converter-for-media' )
			);
			?>
		</p>
		<p class="center">
			<a href="https://wordpress.org/support/topic/how-can-i-check-if-the-plugin-is-working-properly/"
				target="_blank"
				class="webpButton webpButton--blue"
			>
				<?php echo esc_html( __( 'Find out more', 'webp-converter-for-media' ) ); ?>
			</a>
		</p>
	</div>
</div>
