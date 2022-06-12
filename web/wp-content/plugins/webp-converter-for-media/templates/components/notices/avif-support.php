<?php
/**
 * Notice displayed in admin panel.
 *
 * @var string $ajax_url     URL of admin-ajax.
 * @var string $close_action Action using in WP Ajax.
 * @package WebP Converter for Media
 */

use WebpConverter\WebpConverterConstants;

?>
<div class="notice notice-success is-dismissible"
	data-notice="webp-converter-for-media"
	data-notice-action="<?php echo esc_attr( $close_action ); ?>"
	data-notice-url="<?php echo esc_url( $ajax_url ); ?>"
>
	<div class="webpContent webpContent--notice webpContent--author">
		<h4>
			<?php echo esc_html( __( 'Hello, I am glad that you are with us and use our WebP Converter for Media plugin!', 'webp-converter-for-media' ) ); ?>
		</h4>
		<p>
			<?php
			echo wp_kses_post(
				implode(
					' ',
					[
						__( 'Especially for the users of our plugin, we have prepared a novelty - support for the AVIF format.', 'webp-converter-for-media' ),
						__( 'The AVIF format is a new extension - is the successor to WebP. It allows you to achieve even higher levels of image compression, and the quality of the converted images is better than in WebP.', 'webp-converter-for-media' ),

					]
				)
			);
			?>
		</p>
		<div class="webpContent__buttons">
			<a href="<?php echo esc_url( sprintf( WebpConverterConstants::UPGRADE_PRO_PREFIX_URL, 'admin-notice-avif-support' ) ); ?>"
				target="_blank"
				class="webpContent__button webpButton webpButton--blue webpButton--bg"
			>
				<?php echo esc_html( __( 'Find out more', 'webp-converter-for-media' ) ); ?>
			</a>
			<button type="button" data-permanently
				class="webpContent__button webpButton webpButton--gray webpButton--bg"
			>
				<?php echo esc_html( __( 'Hide, do not show again', 'webp-converter-for-media' ) ); ?>
			</button>
		</div>
	</div>
</div>
