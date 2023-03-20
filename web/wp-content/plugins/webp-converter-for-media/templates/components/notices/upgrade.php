<?php
/**
 * Notice displayed in admin panel.
 *
 * @var string $ajax_url     URL of admin-ajax.
 * @var string $close_action Action using in WP Ajax.
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
				/* translators: %s: plugin name */
					__( 'New opportunities in our %s plugin!', 'webp-converter-for-media' ),
					'Converter for Media'
				)
			);
			?>
		</h4>
		<p>
			<?php
			echo wp_kses_post(
				__( 'Did you know that by using the PRO version of our plugin you can speed up your website even more? Find out now what you can gain.', 'webp-converter-for-media' )
			);
			?>
		</p>
		<div class="webpcContent__buttons">
			<a href="https://url.mattplugins.com/converter-notice-upgrade-button-read"
				target="_blank"
				class="webpcContent__button webpcButton webpcButton--blue webpcButton--bg"
			>
				<?php echo esc_html( __( 'Explore the opportunities for yourself', 'webp-converter-for-media' ) ); ?>
			</a>
			<button type="button" data-permanently
				class="webpcContent__button webpcButton webpcButton--gray webpcButton--bg"
			>
				<?php echo esc_html( __( 'Hide and do not show again', 'webp-converter-for-media' ) ); ?>
			</button>
		</div>
	</div>
</div>
