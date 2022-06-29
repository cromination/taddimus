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
					'Cloudflare'
				)
			);
			?>
		</p>
		<ul>
			<li>
				1.
				<?php
				echo wp_kses_post(
					__( 'Log in to your Cloudflare dashboard.', 'webp-converter-for-media' )
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
						'<strong>"Caching > Configuration"</strong>'
					)
				);
				?>
			</li>
			<li>3.
				<?php
				echo wp_kses_post(
					sprintf(
					/* translators: %1$s: section label, %2$s: button label */
						__( 'Under %1$s, click %2$s. A warning window appears.', 'webp-converter-for-media' ),
						'<strong>"Purge Cache"</strong>',
						'<strong>"Purge Everything"</strong>'
					)
				);
				?>
			</li>
			<li>4.
				<?php
				echo wp_kses_post(
					sprintf(
					/* translators: %1$s: button label */
						__( 'If you agree, click %1$s.', 'webp-converter-for-media' ),
						'<strong>"Purge Everything"</strong>'
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
