<?php
/**
 * Widget allows regeneration images on plugin settings page.
 *
 * @var bool   $token_valid_status .
 * @var string $api_paths_url      URL of REST API endpoint.
 * @var string $api_regenerate_url URL of REST API endpoint.
 * @package WebP Converter for Media
 */

use WebpConverter\WebpConverterConstants;

?>
<div class="webpPage__widget">
	<h3 class="webpPage__widgetTitle">
		<?php echo esc_html( __( 'Regenerate images', 'webp-converter-for-media' ) ); ?>
	</h3>
	<div class="webpLoader webpContent"
		data-api-paths="<?php echo esc_url( $api_paths_url ); ?>"
		data-api-regenerate="<?php echo esc_url( $api_regenerate_url ); ?>"
		data-api-error-message="<?php echo esc_html( __( 'An error occurred while connecting to REST API. Please try again.', 'webp-converter-for-media' ) ); ?>"
	>
		<div class="webpPage__widgetRow">
			<p>
				<?php echo wp_kses_post( __( 'Convert all existing images with just one click! This tool uses the WordPress REST API by downloading addresses of all images and converting all files gradually. This is a process that can take anywhere from several minutes to many hours depending on the number of files. During this process, please do not close your browser window.', 'webp-converter-for-media' ) ); ?>
			</p>
			<p>
				<?php echo wp_kses_post( __( 'This operation should be performed only once after installing the plugin. New images from the Media Library will be converted automatically. For other images, e.g. from the /themes or /uploads directory that are not from the Media Library, you must start manual conversion after adding new images.', 'webp-converter-for-media' ) ); ?>
			</p>
			<div class="webpLoader__status" hidden>
				<div class="webpLoader__bar">
					<div class="webpLoader__barProgress" data-percent="0">
						<div class="webpLoader__barCount"></div>
					</div>
					<div class="webpLoader__progress">
						<?php
						echo sprintf(
						/* translators: %s progress value */
							wp_kses_post( __( 'Saving the weight of your images: %s', 'webp-converter-for-media' ) ),
							'<span class="webpLoader__progressValue webpLoader__progressValue--size">0 kB</span>'
						);
						?>
						<br>
						<?php
						echo sprintf(
						/* translators: %s images count */
							wp_kses_post( __( 'Successfully converted files: %s', 'webp-converter-for-media' ) ),
							'<span class="webpLoader__progressValue webpLoader__progressValue--successful">0</span>'
						);
						?>
						<br>
						<?php
						echo sprintf(
						/* translators: %s images count */
							wp_kses_post( __( 'Failed or skipped file conversion attempts: %s', 'webp-converter-for-media' ) ),
							'<span class="webpLoader__progressValue webpLoader__progressValue--failed">0 </span>'
						);
						?>
					</div>
				</div>
				<div class="webpLoader__success" hidden>
					<div class="webpLoader__successContent">
						<?php echo wp_kses_post( __( 'The process was completed successfully. Your images have been converted!', 'webp-converter-for-media' ) ); ?>
						<?php echo wp_kses_post( __( 'Please flush cache if you use caching plugin or caching via hosting.', 'webp-converter-for-media' ) ); ?>
						<br>
						<?php
						echo wp_kses_post(
							sprintf(
							/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
								__( 'Do you want to know how a plugin works and how to check if it is working properly? Read %1$sour manual%2$s.', 'webp-converter-for-media' ),
								'<a href="https://wordpress.org/support/topic/how-can-i-check-if-the-plugin-is-working-properly/" target="_blank">',
								'</a>'
							)
						);
						?>
					</div>
				</div>
				<?php if ( ! $token_valid_status ) : ?>
					<div class="webpLoader__popup webpPopup" hidden>
						<div class="webpPopup__inner">
							<div class="webpPopup__image"></div>
							<div class="webpPopup__content">
								<p>
									<?php
									echo wp_kses_post(
										sprintf(
										/* translators: %s break line tag */
											__( 'Hello, my name is Mateusz! %sI am glad you managed to reduce the weight of your website. Did you know that you can achieve even better image optimization results? Check how much you can reduce the weight of images on your website by also using the AVIF format.', 'webp-converter-for-media' ),
											'<br>'
										)
									);
									?>
								</p>
								<p>
									<a href="<?php echo esc_url( sprintf( WebpConverterConstants::OPTIMIZATION_TEST_PREFIX_URL, 'regeneration-notice-upgrade' ) ); ?>"
										target="_blank"
										class="webpButton webpButton--blue dashicons-external"
									>
										<?php echo wp_kses_post( __( 'Test image optimization', 'webp-converter-for-media' ) ); ?>
									</a>
								</p>
							</div>
						</div>
					</div>
				<?php endif; ?>
				<div class="webpLoader__errors" hidden>
					<div class="webpLoader__errorsTitle">
						<?php echo esc_html( __( 'Additional informations about process:', 'webp-converter-for-media' ) ); ?>
					</div>
					<div class="webpLoader__errorsContent"></div>
				</div>
			</div>
		</div>
		<div class="webpPage__widgetRow">
			<div class="webpField">
				<input type="checkbox"
					name="regenerate_force"
					value="1"
					id="webpc-regenerate-force"
					class="webpField__input webpField__input--toggle">
				<label for="webpc-regenerate-force"></label>
				<span class="webpField__label">
					<?php echo esc_html( __( 'Force convert all images again', 'webp-converter-for-media' ) ); ?>
				</span>
			</div>
			<button type="button"
				class="webpLoader__button webpButton webpButton--green"
				<?php echo ( apply_filters( 'webpc_server_errors', [], true ) ) ? 'disabled' : ''; ?>>
				<?php echo esc_html( __( 'Regenerate All', 'webp-converter-for-media' ) ); ?>
			</button>
		</div>
	</div>
</div>
