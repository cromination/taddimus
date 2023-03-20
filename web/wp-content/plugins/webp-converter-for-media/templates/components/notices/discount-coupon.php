<?php
/**
 * Notice displayed in admin panel.
 *
 * @var string $ajax_url     URL of admin-ajax.
 * @var string $close_action Action using in WP Ajax.
 * @var string $coupon_code .
 * @var string $discount_value .
 * @var string $button_url .
 * @var string $promotion_date .
 *
 * @package Converter for Media
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-success is-dismissible"
	data-notice="webp-converter-for-media"
	data-notice-action="<?php echo esc_attr( $close_action ); ?>"
	data-notice-url="<?php echo esc_url( $ajax_url ); ?>"
>
	<div class="webpcContent webpcContent--notice">
		<h4>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %1$s: discount value, %2$s: plugin name */
					__( '%1$s discount on all PRO version plans of the %2$s plugin!', 'webp-converter-for-media' ),
					$discount_value,
					'Converter for Media'
				)
			);
			?>
		</h4>
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %1$s: icon heart, %2$s: coupon code, %3$s: discount value, %4$s: date */
					__( 'We have prepared a special offer for users of our %1$s plugin. Use the coupon code: %2$s when placing your order and get %3$s discount! This offer is valid until %4$s.', 'webp-converter-for-media' ),
					'<span class="dashicons dashicons-heart"></span>',
					'<code>' . $coupon_code . '</code>',
					$discount_value,
					wp_date( get_option( 'date_format' ), strtotime( $promotion_date ) ?: 0 )
				)
			);
			?>
		</p>
		<div class="webpcContent__buttons">
			<a href="<?php echo esc_attr( $button_url ); ?>"
				target="_blank"
				class="webpcContent__button webpcButton webpcButton--blue webpcButton--bg"
			>
				<?php echo esc_html( __( 'Get it now', 'webp-converter-for-media' ) ); ?>
			</a>
		</div>
	</div>
</div>
