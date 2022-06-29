<?php
/**
 * Notice displayed in admin panel.
 *
 * @var string $ajax_url     URL of admin-ajax.
 * @var string $close_action Action using in WP Ajax.
 * @package WebP Converter for Media
 */

?>
<div class="notice notice-success is-dismissible"
	data-notice="webp-converter-for-media"
	data-notice-action="<?php echo esc_attr( $close_action ); ?>"
	data-notice-url="<?php echo esc_url( $ajax_url ); ?>"
>
	<div class="webpContent webpContent--notice">
		<p>
			<?php
			echo esc_html(
				sprintf(
				/* translators: %1$s: service name */
					__( 'You are using %1$s, right? Please follow the steps below for the plugin to function properly:', 'webp-converter-for-media' ),
					'LiteSpeed Cache'
				)
			);
			?>
		</p>
		<ul>
			<li>
				1.
				<?php
				echo wp_kses_post(
					sprintf(
					/* translators: %1$s: button label */
						__( 'Look for the %1$s icon in the admin bar.', 'webp-converter-for-media' ),
						'<strong>"LiteSpeed Cache Purge All - LSCache"</strong>'
					)
				);
				?>
			</li>
			<li>
				2.
				<?php
				echo wp_kses_post(
					sprintf(
					/* translators: %1$s: button label */
						__( 'Click %1$s.', 'webp-converter-for-media' ),
						'<strong>"Purge All"</strong>'
					)
				);
				?>
			</li>
		</ul>
		<div class="webpContent__buttons">
			<button type="button" data-permanently
				class="webpContent__button webpButton webpButton--blue webpButton--bg"
			>
				<?php echo esc_html( __( 'Done', 'webp-converter-for-media' ) ); ?>
			</button>
		</div>
	</div>
</div>
