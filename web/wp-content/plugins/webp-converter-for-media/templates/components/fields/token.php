<?php
/**
 * Field displayed in plugin settings form.
 *
 * @var bool    $token_valid_status Status of PRO version.
 * @var string  $api_calculate_url  URL of REST API endpoint.
 * @var mixed[] $option             Data of field.
 * @var string  $index              Index of field.
 *
 * @package Converter for Media
 */

?>
<?php if ( $option['info'] ) : ?>
	<p><?php echo wp_kses_post( $option['info'] ); ?></p>
<?php endif; ?>
<div class="webpcInput">
	<input type="text"
		name="<?php echo esc_attr( ( $token_valid_status ) ? '' : $option['name'] ); ?>"
		value="<?php echo esc_attr( $option['value_public'] ); ?>"
		id="<?php echo esc_attr( $option['name'] ); ?>"
		class="webpcInput__field"
		<?php echo ( $token_valid_status ) ? 'readonly' : ''; ?>
	>
</div>
<p data-calculate-widget data-calculate-widget-api="<?php echo esc_url( $api_calculate_url ); ?>">
	<?php
	echo esc_html( __( 'How many images to convert are remaining on my website?', 'webp-converter-for-media' ) );
	echo ' ';
	echo wp_kses_post(
		sprintf(
		/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
			__( '%1$sCalculate%2$s', 'webp-converter-for-media' ),
			'<a href="#" data-calculate-widget-button>',
			'</a>'
		)
	);
	?>
	<strong data-calculate-widget-loading hidden>
		<?php echo esc_html( __( 'Please wait...', 'webp-converter-for-media' ) ); ?>
	</strong>
	<strong style="display: block;" data-calculate-widget-output hidden></strong>
</p>
