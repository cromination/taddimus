<?php
/**
 * Field displayed in plugin settings form.
 *
 * @var mixed[] $option Data of field.
 * @var string  $index  Index of field.
 *
 * @package Converter for Media
 */

use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\WebpFormat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_webp_checked  = in_array( WebpFormat::FORMAT_EXTENSION, $option['value'] );
$is_avif_checked  = in_array( AvifFormat::FORMAT_EXTENSION, $option['value'] );
$is_avif_disabled = in_array( AvifFormat::FORMAT_EXTENSION, $option['disabled'] );

?>

<?php if ( $option['info'] ) : ?>
	<p><?php echo wp_kses_post( $option['info'] ); ?></p>
<?php endif; ?>

<?php foreach ( $option['values'] as $value => $label ) : ?>
	<input type="checkbox"
		name="<?php echo esc_attr( $option['name'] ); ?>[]"
		value="<?php echo esc_attr( $value ); ?>"
		id="<?php echo esc_attr( $option['name'] . '-' . $value ); ?>"
		<?php echo ( in_array( $value, $option['disabled'] ) ) ? 'disabled' : ''; ?>
		<?php echo ( in_array( $value, $option['value'] ) ) ? 'checked' : ''; ?>
		hidden
	>
<?php endforeach; ?>

<div class="webpcFormats">
	<div class="webpcFormats__table">
		<div class="webpcFormats__tableRow">
			<div class="webpcFormats__tableCell webpcFormats__tableCell--header"></div>
			<div class="webpcFormats__tableCell">
				<div class="webpcFormats__button <?php echo ( $is_avif_disabled ) ? 'webpcFormats__button--overlay' : ''; ?>">
					<input type="radio"
						name="<?php echo esc_attr( $option['name'] ); ?>__radio"
						value="<?php echo esc_attr( WebpFormat::FORMAT_EXTENSION ); ?>"
						id="<?php echo esc_attr( $option['name'] . '__radio1' ); ?>"
						class="webpcFormats__buttonInput"
						<?php echo ( $is_webp_checked && ! $is_avif_checked ) ? 'checked' : ''; ?>
						data-radio-selector="<?php echo esc_attr( $option['name'] ); ?>"
					>
					<label class="webpcFormats__buttonWrapper" for="<?php echo esc_attr( $option['name'] . '__radio1' ); ?>">
						<span class="webpcFormats__buttonCircle"></span>
						<span class="webpcFormats__buttonLabel">WebP</span>
					</label>
				</div>
			</div>
			<div class="webpcFormats__tableCell">
				<div class="webpcFormats__button <?php echo ( $is_avif_disabled ) ? 'webpcFormats__button--overlay' : ''; ?>"">
					<input type="radio"
						name="<?php echo esc_attr( $option['name'] ); ?>__radio"
						value="<?php echo esc_attr( AvifFormat::FORMAT_EXTENSION ); ?>"
						id="<?php echo esc_attr( $option['name'] . '__radio2' ); ?>"
						class="webpcFormats__buttonInput"
						<?php echo ( ! $is_webp_checked && $is_avif_checked ) ? 'checked' : ''; ?>
						data-radio-selector="<?php echo esc_attr( $option['name'] ); ?>"
						<?php echo ( $is_avif_disabled ) ? 'disabled' : ''; ?>
					>
					<?php if ( $is_avif_disabled ) : ?>
						<a href="https://url.mattplugins.com/converter-field-output-formats-column-avif-header"
							target="_blank"
							class="webpcFormats__buttonWrapper"
						>
							<span class="webpcFormats__buttonOverlay">
								<span class="webpcFormats__buttonOverlayInfo">
									<?php echo esc_html__( 'Unlock PRO', 'webp-converter-for-media' ); ?>
								</span>
							</span>
							<span class="webpcFormats__buttonCircle"></span>
							<span class="webpcFormats__buttonLabel">AVIF</span>
						</a>
					<?php else : ?>
						<label class="webpcFormats__buttonWrapper"
							for="<?php echo esc_attr( $option['name'] . '__radio2' ); ?>"
						>
							<span class="webpcFormats__buttonCircle"></span>
							<span class="webpcFormats__buttonLabel">AVIF</span>
						</label>
					<?php endif; ?>
				</div>
			</div>
			<div class="webpcFormats__tableCell">
				<div class="webpcFormats__button <?php echo ( $is_avif_disabled ) ? 'webpcFormats__button--overlay' : ''; ?>"">
					<input type="radio"
						name="<?php echo esc_attr( $option['name'] ); ?>__radio"
						value="<?php echo esc_attr( WebpFormat::FORMAT_EXTENSION . ',' . AvifFormat::FORMAT_EXTENSION ); ?>"
						id="<?php echo esc_attr( $option['name'] . '__radio3' ); ?>"
						class="webpcFormats__buttonInput"
						<?php echo ( $is_webp_checked && $is_avif_checked ) ? 'checked' : ''; ?>
						data-radio-selector="<?php echo esc_attr( $option['name'] ); ?>"
						<?php echo ( $is_avif_disabled ) ? 'disabled' : ''; ?>
					>
					<?php if ( $is_avif_disabled ) : ?>
						<a href="https://url.mattplugins.com/converter-field-output-formats-column-avif-webp-header"
							target="_blank"
							class="webpcFormats__buttonWrapper"
						>
							<span class="webpcFormats__buttonOverlay">
								<span class="webpcFormats__buttonOverlayInfo">
									<?php echo esc_html__( 'Unlock PRO', 'webp-converter-for-media' ); ?>
								</span>
							</span>
							<span class="webpcFormats__buttonCircle"></span>
							<span class="webpcFormats__buttonLabel">AVIF + WebP</span>
						</a>
					<?php else : ?>
						<label class="webpcFormats__buttonWrapper"
							for="<?php echo esc_attr( $option['name'] . '__radio3' ); ?>"
						>
							<span class="webpcFormats__buttonCircle"></span>
							<span class="webpcFormats__buttonLabel">AVIF + WebP</span>
						</label>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="webpcFormats__tableRow webpcFormats__tableRow--bg">
			<div class="webpcFormats__tableCell webpcFormats__tableCell--header">
				<?php echo esc_html__( 'File size', 'webp-converter-for-media' ); ?>
			</div>
			<div class="webpcFormats__tableCell">
				<div class="webpcFormats__tableCellInner">
					<div class="webpcFormats__tableCellText">
						<?php
						echo sprintf(
						/* translators: %s: image format name */
							esc_html__( 'Smaller than %s', 'webp-converter-for-media' ),
							'JPEG, PNG'
						);
						?>
					</div>
				</div>
			</div>
			<div class="webpcFormats__tableCell webpcFormats__tableCell--col2">
				<div class="webpcFormats__tableCellInner">
					<div class="webpcFormats__tableCellText">
						<?php
						echo sprintf(
						/* translators: %1$s: percent value, %2$s: image format name, %3$s: open strong tag, %4$s: close strong tag */
							esc_html__( 'Additional %1$s smaller than %2$s %3$s(maximum size reduction)%4$s', 'webp-converter-for-media' ),
							'≈50%',
							'WebP',
							'<small>',
							'</small>'
						);
						?>
					</div>
				</div>
			</div>
		</div>
		<div class="webpcFormats__tableRow">
			<div class="webpcFormats__tableCell webpcFormats__tableCell--header">
				<?php echo esc_html__( 'Image quality', 'webp-converter-for-media' ); ?>
			</div>
			<div class="webpcFormats__tableCell">
				<div class="webpcFormats__tableCellInner">
					<div class="webpcFormats__tableCellText">
						<?php
						echo sprintf(
						/* translators: %1$s: open strong tag, %2$s: close strong tag */
							esc_html__( 'Standard quality %1$s(possible quality loss)%2$s', 'webp-converter-for-media' ),
							'<small>',
							'</small>'
						);
						?>
					</div>
				</div>
			</div>
			<div class="webpcFormats__tableCell webpcFormats__tableCell--col2">
				<div class="webpcFormats__tableCellInner">
					<div class="webpcFormats__tableCellText">
						<?php echo esc_html__( 'Highest quality', 'webp-converter-for-media' ); ?>
					</div>
				</div>
			</div>
		</div>
		<div class="webpcFormats__tableRow webpcFormats__tableRow--bg">
			<div class="webpcFormats__tableCell webpcFormats__tableCell--header">
				<?php echo esc_html__( 'Hosting load', 'webp-converter-for-media' ); ?>
			</div>
			<div class="webpcFormats__tableCell">
				<div class="webpcFormats__tableCellInner">
					<div class="webpcFormats__tableCellText">
						<?php echo esc_html__( 'Local conversion', 'webp-converter-for-media' ); ?>
					</div>
				</div>
			</div>
			<div class="webpcFormats__tableCell webpcFormats__tableCell--col2">
				<div class="webpcFormats__tableCellInner">
					<div class="webpcFormats__tableCellText">
						<?php
						echo sprintf(
						/* translators: %1$s: open strong tag, %2$s: close strong tag */
							esc_html__( 'Conversion via remote server %1$s(saves your hosting resources)%2$s', 'webp-converter-for-media' ),
							'<small>',
							'</small>'
						);
						?>
					</div>
				</div>
			</div>
		</div>
		<div class="webpcFormats__tableRow">
			<div class="webpcFormats__tableCell webpcFormats__tableCell--row2 webpcFormats__tableCell--alignTop webpcFormats__tableCell--header">
				<?php echo esc_html__( 'Browser support', 'webp-converter-for-media' ); ?>
			</div>
			<div class="webpcFormats__tableCell webpcFormats__tableCell--col3">
				<div class="webpcFormats__tableCellInner">
					<div class="webpcFormats__tableCellText">
						<?php echo esc_html__( 'The plugin automatically serves the best format for each visitor:', 'webp-converter-for-media' ); ?>
					</div>
				</div>
			</div>
		</div>
		<div class="webpcFormats__tableRow">
			<div class="webpcFormats__tableCell webpcFormats__tableCell--alignTop">
				<div class="webpcFormats__tableCellInner">
					<div class="webpcFormats__steps">
						<div class="webpcFormats__step">
							<div class="webpcFormats__stepLabel webpcFormats__stepLabel--second">WebP</div>
						</div>
						<div class="webpcFormats__step">
							<div class="webpcFormats__stepLabel webpcFormats__stepLabel--third">JPEG / PNG</div>
							<small>
								<?php
								echo sprintf(
								/* translators: %s: image format name */
									esc_html__( '(if %s not supported)', 'webp-converter-for-media' ),
									'WebP'
								);
								?>
							</small>
						</div>
					</div>
				</div>
			</div>
			<div class="webpcFormats__tableCell webpcFormats__tableCell--alignTop">
				<div class="webpcFormats__tableCellInner">
					<div class="webpcFormats__steps">
						<div class="webpcFormats__step">
							<div class="webpcFormats__stepLabel webpcFormats__stepLabel--first">AVIF</div>
						</div>
						<div class="webpcFormats__step">
							<div class="webpcFormats__stepLabel webpcFormats__stepLabel--third">JPEG / PNG</div>
							<small>
								<?php
								echo sprintf(
								/* translators: %s: image format name */
									esc_html__( '(if %s not supported)', 'webp-converter-for-media' ),
									'AVIF'
								);
								?>
							</small>
						</div>
					</div>
				</div>
			</div>
			<div class="webpcFormats__tableCell webpcFormats__tableCell--alignTop">
				<div class="webpcFormats__tableCellInner">
					<div class="webpcFormats__steps">
						<div class="webpcFormats__step">
							<div class="webpcFormats__stepLabel webpcFormats__stepLabel--first">AVIF</div>
						</div>
						<div class="webpcFormats__step">
							<div class="webpcFormats__stepLabel webpcFormats__stepLabel--second">WebP</div>
							<small>
								<?php
								echo sprintf(
								/* translators: %s: image format name */
									esc_html__( '(if %s not supported)', 'webp-converter-for-media' ),
									'AVIF'
								);
								?>
							</small>
						</div>
						<div class="webpcFormats__step">
							<div class="webpcFormats__stepLabel webpcFormats__stepLabel--third">JPEG / PNG</div>
							<small>
								<?php
								echo sprintf(
								/* translators: %s: image format name */
									esc_html__( '(if %s not supported)', 'webp-converter-for-media' ),
									'WebP'
								);
								?>
							</small>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php if ( $is_avif_disabled ) : ?>
			<div class="webpcFormats__tableRow">
				<div class="webpcFormats__tableCell webpcFormats__tableCell--header"></div>
				<div class="webpcFormats__tableCell"></div>
				<div class="webpcFormats__tableCell webpcFormats__tableCell--col2 webpcFormats__tableCell--bgBlue">
					<div class="webpcFormats__tableCellInner">
						<div class="webpcFormats__tableCellText">
							<?php
							echo sprintf(
							/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
								esc_html__( 'Get the best image optimization with %1$sthe PRO version%2$s', 'webp-converter-for-media' ),
								'<a href="https://url.mattplugins.com/converter-field-output-formats-column-double-info" target="_blank">',
								'</a>'
							);
							?>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
