<?php
/**
 * Pop-up displayed on plugin settings page.
 *
 * @var string $author_image_url         Avatar of plugin author.
 * @var string $form_input_name          Name of hidden field with form ID.
 * @var string $form_sidebar_input_value ID of settings form.
 * @var string $nonce_input_name         Name of hidden field with WordPress Nonce value.
 * @var string $nonce_input_value        WordPress Nonce value.
 * @var bool   $token_active_status      Status of PRO version.
 *
 * @package Converter for Media
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<?php if ( ! $token_active_status ) : ?>
	<div class="webpcPopup" data-popup="regeneration" hidden>
		<div class="webpcPopup__wrapper">
			<div class="webpcPopup__inner" data-popup-page="1">
				<button type="button" class="webpcPopup__close dashicons dashicons-no" data-popup-close></button>
				<div class="webpcPopup__sidebar">
					<div class="webpcPopup__sidebarInner">
						<img src="<?php echo esc_attr( $author_image_url ); ?>" alt="" class="webpcPopup__sidebarImage">
						<div class="webpcPopup__sidebarCover" style="background-image: url(https://mattplugins.com/images/matt-plugins-author-logo.png);"></div>
					</div>
				</div>
				<div class="webpcPopup__content">
					<div class="webpcPopup__contentText">
						<p>
							<?php
							echo esc_html(
								sprintf(
								/* translators: %s: author name */
									__( 'Hi - I am %s, the author of this plugin.', 'webp-converter-for-media' ),
									'Mateusz'
								)
							);
							?>
							<strong>
								<?php
								echo esc_html(
									sprintf(
									/* translators: %s: format name */
										__( 'Please tell me, would you like to know more about the %s format?', 'webp-converter-for-media' ),
										'AVIF'
									)
								);
								?>
							</strong>
						</p>
						<p>
							<?php echo esc_html__( 'The AVIF format is the successor to the WebP format. Images converted to the AVIF format weigh about 50% less than images converted only to the WebP format, while maintaining better image quality.', 'webp-converter-for-media' ); ?>
						</p>
					</div>
					<div class="webpcPopup__contentButtons">
						<div class="webpcPopup__contentButton">
							<a href="https://url.mattplugins.com/converter-regeneration-popup-step-avif" target="_blank"
								class="webpcButton webpcButton--blue webpcButton--bg"
								data-popup-button-page="2">
								<?php echo esc_html__( 'Find out more', 'webp-converter-for-media' ); ?>
							</a>
						</div>
						<div class="webpcPopup__contentButton">
							<button type="button"
								class="webpcButton webpcButton--gray webpcButton--bg"
								data-popup-button-page="3">
								<?php echo esc_html__( 'Skip for now', 'webp-converter-for-media' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
			<div class="webpcPopup__inner" data-popup-page="2" hidden>
				<button type="button" class="webpcPopup__close dashicons dashicons-no" data-popup-close></button>
				<form method="post" action="" class="webpcPopup__content">
					<input type="hidden" name="<?php echo esc_attr( $form_input_name ); ?>"
						value="<?php echo esc_attr( $form_sidebar_input_value ); ?>">
					<input type="hidden" name="<?php echo esc_attr( $nonce_input_name ); ?>"
						value="<?php echo esc_attr( $nonce_input_value ); ?>">
					<div class="webpcPopup__contentText">
						<p>
							<strong>
								<?php
								echo esc_html(
									sprintf(
									/* translators: %s: format name */
										__( 'Provide a valid access token to continue the process of optimizing images to the %s format.', 'webp-converter-for-media' ),
										'AVIF'
									)
								);
								?>
							</strong>
						</p>
						<p>
							<?php echo esc_html__( 'Converting images to WebP and AVIF simultaneously guarantees the lowest weight of your images and compatibility with all browsers. By using the AVIF format you will reduce the weight of your images even more compared to WebP.', 'webp-converter-for-media' ); ?>
						</p>
						<p data-plans>
							<?php
							echo wp_kses_post(
								sprintf(
								/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
									__( 'You can get your access token %1$shere%2$s.', 'webp-converter-for-media' ),
									'<a href="https://url.mattplugins.com/converter-regeneration-popup-step-token-yes?webp=0&avif=0" target="_blank" data-plans-button>',
									'</a>'
								)
							);
							?>
							<?php
							echo wp_kses_post(
								sprintf(
								/* translators: %1$s: percent value, %2$s: coupon code */
									__( 'Get %1$s off by entering the code %2$s at checkout!', 'webp-converter-for-media' ),
									'<strong>20%</strong>',
									'"<strong>20VZUMCF</strong>"'
								)
							);
							?>
						</p>
					</div>
					<div class="webpcPopup__input webpcInput">
						<input type="text" name="access_token" id="access_token" class="webpcInput__field" required>
					</div>
					<div class="webpcPopup__contentButtons">
						<div class="webpcPopup__contentButton">
							<button type="submit" class="webpcButton webpcButton--blue webpcButton--bg">
								<?php echo esc_html__( 'Activate Token', 'webp-converter-for-media' ); ?>
							</button>
						</div>
						<div class="webpcPopup__contentButton">
							<button type="button"
								class="webpcButton webpcButton--gray webpcButton--bg"
								data-popup-close>
								<?php echo esc_html__( 'Not now', 'webp-converter-for-media' ); ?>
							</button>
						</div>
					</div>
				</form>
			</div>
			<div class="webpcPopup__inner" data-popup-page="3" hidden>
				<button type="button" class="webpcPopup__close dashicons dashicons-no" data-popup-close></button>
				<form method="post" action="" class="webpcPopup__content">
					<input type="hidden" name="<?php echo esc_attr( $form_input_name ); ?>"
						value="<?php echo esc_attr( $form_sidebar_input_value ); ?>">
					<input type="hidden" name="<?php echo esc_attr( $nonce_input_name ); ?>"
						value="<?php echo esc_attr( $nonce_input_value ); ?>">
					<div class="webpcPopup__contentText">
						<p>
							<strong>
								<?php
								echo esc_html(
									sprintf(
									/* translators: %s: format name */
										__( 'Provide a valid access token to continue the process of optimizing images to the %s format.', 'webp-converter-for-media' ),
										'AVIF'
									)
								);
								?>
							</strong>
						</p>
						<p>
							<?php echo esc_html__( 'Converting images to WebP and AVIF simultaneously guarantees the lowest weight of your images and compatibility with all browsers. By using the AVIF format you will reduce the weight of your images even more compared to WebP.', 'webp-converter-for-media' ); ?>
						</p>
						<p data-plans>
							<?php
							echo wp_kses_post(
								sprintf(
								/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
									__( 'You can get your access token %1$shere%2$s.', 'webp-converter-for-media' ),
									'<a href="https://url.mattplugins.com/converter-regeneration-popup-step-token-no?webp=0&avif=0" target="_blank" data-plans-button>',
									'</a>'
								)
							);
							?>
							<?php
							echo wp_kses_post(
								sprintf(
								/* translators: %1$s: percent value, %2$s: coupon code */
									__( 'Get %1$s off by entering the code %2$s at checkout!', 'webp-converter-for-media' ),
									'<strong>20%</strong>',
									'"<strong>20YKFPKV</strong>"'
								)
							);
							?>
						</p>
					</div>
					<div class="webpcPopup__input webpcInput">
						<input type="text" name="access_token" id="access_token" class="webpcInput__field" required>
					</div>
					<div class="webpcPopup__contentButtons">
						<div class="webpcPopup__contentButton">
							<button type="submit" class="webpcButton webpcButton--blue webpcButton--bg">
								<?php echo esc_html__( 'Activate Token', 'webp-converter-for-media' ); ?>
							</button>
						</div>
						<div class="webpcPopup__contentButton">
							<button type="button"
								class="webpcButton webpcButton--gray webpcButton--bg"
								data-popup-close>
								<?php echo esc_html__( 'Not now', 'webp-converter-for-media' ); ?>
							</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
<?php endif; ?>
