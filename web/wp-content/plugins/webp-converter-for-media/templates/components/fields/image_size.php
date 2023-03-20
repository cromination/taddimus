<?php
/**
 * Field displayed in plugin settings form.
 *
 * @var mixed[] $option Data of field.
 * @var string  $index  Index of field.
 *
 * @package Converter for Media
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="webpcField">
	<input type="checkbox"
		name="<?php echo esc_attr( $option['name'] ); ?>[0]"
		value="yes"
		id="<?php echo esc_attr( $option['name'] ); ?>[0]"
		class="webpcField__input webpcField__input--checkbox"
		data-toggle-trigger="<?php echo esc_attr( $option['name'] ); ?>[0]"
		<?php echo ( ( $option['value'][0] ?? '' ) === 'yes' ) ? 'checked' : ''; ?>
		<?php echo ( in_array( 'yes', $option['disabled'] ) ) ? 'disabled' : ''; ?>
	>
	<label for="<?php echo esc_attr( $option['name'] ); ?>[0]"></label>
	<span class="webpcField__label"><?php echo wp_kses_post( $option['info'] ); ?></span>
</div>

<div class="webpcField webpcField--center">
	<span class="webpcField__label">
		<?php echo esc_html( __( 'Max. width:', 'webp-converter-for-media' ) ); ?>
	</span>
	<div class="webpcInput">
		<input type="number" min="0" step="1"
			name="<?php echo esc_attr( $option['name'] ); ?>[1]"
			value="<?php echo esc_attr( $option['value'][1] ?? '' ); ?>"
			id="<?php echo esc_attr( $option['name'] ); ?>[1]"
			class="webpcInput__field webpcInput__field--small"
			data-toggle-output="<?php echo esc_attr( $option['name'] ); ?>[0]"
			data-toggle-output-values="yes"
			data-toggle-output-attr="readonly"
			<?php echo ( in_array( 'yes', $option['disabled'] ) || ( $option['value'][0] ?? '' ) !== 'yes' ) ? 'readonly' : ''; ?>
		>
	</div>
	<span class="webpcField__label">
		<?php echo esc_html( __( 'Max. height:', 'webp-converter-for-media' ) ); ?>
	</span>
	<div class="webpcInput">
		<input type="number" min="0" step="1"
			name="<?php echo esc_attr( $option['name'] ); ?>[2]"
			value="<?php echo esc_attr( $option['value'][2] ?? '' ); ?>"
			id="<?php echo esc_attr( $option['name'] ); ?>[2]"
			class="webpcInput__field webpcInput__field--small"
			data-toggle-output="<?php echo esc_attr( $option['name'] ); ?>[0]"
			data-toggle-output-values="yes"
			data-toggle-output-attr="readonly"
			<?php echo ( in_array( 'yes', $option['disabled'] ) || ( $option['value'][0] ?? '' ) !== 'yes' ) ? 'readonly' : ''; ?>
		>
	</div>
</div>
