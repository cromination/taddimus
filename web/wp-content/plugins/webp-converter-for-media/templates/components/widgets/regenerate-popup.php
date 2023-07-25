<?php
/**
 * Pop-up displayed on plugin settings page.
 *
 * @var string $author_image_url    Avatar of plugin author.
 * @var bool   $token_active_status Status of PRO version.
 *
 * @package Converter for Media
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<?php if ( ! $token_active_status ) : ?>
	<div class="webpcPopup" hidden data-popup="regeneration">
		<div class="webpcPopup__wrapper">
			<div class="webpcPopup__inner" data-popup-content>
				<button type="button" class="webpcPopup__close dashicons dashicons-no"
					data-popup-close></button>
				<div class="webpcPopup__sidebar">
					<div class="webpcPopup__sidebarInner">
						<img src="<?php echo esc_attr( $author_image_url ); ?>" alt="" class="webpcPopup__sidebarImage">
						<div class="webpcPopup__sidebarCover" style="background-image: url(https://mattplugins.com/images/matt-plugins-author-logo.png);"></div>
					</div>
				</div>
				<div class="webpcPopup__content">
					<div class="webpcPopup__contentText">
						<?php
						echo esc_html(
							sprintf(
							/* translators: %1$s: author name, %2$s: format name, %3$s: percent value, %4$s: format name */
								__( 'Hi - I am %1$s, the author of this plugin. Did you know that by converting your images to the %2$s format as well, you can reduce the weight of your images by an additional about %3$s compared to using only the %4$s format?', 'webp-converter-for-media' ),
								'Mateusz',
								'AVIF',
								'50%',
								'WebP'
							)
						);
						?>
					</div>
					<div class="webpcPopup__contentButton">
						<a href="https://url.mattplugins.com/converter-regeneration-popup-avif" target="_blank"
							class="webpcButton webpcButton--blue webpcButton--bg">
							<?php echo esc_html( __( 'Explore the opportunities for yourself', 'webp-converter-for-media' ) ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>
