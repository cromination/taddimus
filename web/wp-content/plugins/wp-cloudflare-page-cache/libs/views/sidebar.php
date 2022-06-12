<?php
add_thickbox(); ?>

<div id="swcfpc_sidebar">

    <div class="swcfpc_sidebar_widget">

        <h3><?php _e( 'Our Products', 'wp-cloudflare-page-cache' ); ?></h3>

        <p><?php _e( 'Extend your website perfomance with more free products from our portofolio:', 'wp-cloudflare-page-cache' ); ?></p>
        <h4 style="font-size:20px; ">
            Optimole <?php _e( 'plugin', 'wp-cloudflare-page-cache' ); ?>
        </h4>
        <ul style="list-style: circle;margin-left: 30px;">
            <li><?php _e( 'Cloud based image optimization service', 'wp-cloudflare-page-cache' ); ?></li>
            <li><?php _e( 'Smart Lazyload ( background images included )', 'wp-cloudflare-page-cache' ); ?></li>
            <li><?php _e( 'Auto Scaled Images', 'wp-cloudflare-page-cache' ); ?></li>
            <li><?php _e( 'Machine Learning(ML) compression.', 'wp-cloudflare-page-cache' ); ?></li>
            <li><?php _e( 'Automatically convert to best format(WebP, AVIF)', 'wp-cloudflare-page-cache' ); ?></li>
            <li><?php echo sprintf( __( 'Deliver via CDN - AWS CloudFront ( %s+ locations )', 'wp-cloudflare-page-cache' ), '300'); ?></li>
            <li><?php _e( 'Unmetered Bandwidth', 'wp-cloudflare-page-cache' ); ?></li>
            <li><?php _e( 'Dozens of compatibilities with 3rd party plugins', 'wp-cloudflare-page-cache' ); ?></li>
        </ul>
        <a href="<?php
		$url = add_query_arg(
			array(
				'tab'       => 'plugin-information',
				'plugin'    => 'optimole-wp',
				'TB_iframe' => 'true',
				'width'     => '600',
				'height'    => '500'
			),
			network_admin_url( 'plugin-install.php' )
		);
		echo esc_url( $url );
		?>" style="text-align: center;   padding: 0 10px;" class="button button-primary thickbox "
           target="_blank"><?php _e( 'Install', 'wp-cloudflare-page-cache' ); ?> </a>
        <h4 style="font-size:20px;">
            Neve <?php _e( 'theme', 'wp-cloudflare-page-cache' ); ?>
        </h4>
        <ul style="list-style: circle;margin-left: 30px;">
            <li><?php _e( 'Lightweight, 25kB in page-weight', 'wp-cloudflare-page-cache' ); ?></li>
            <li><?php _e( 'Perfomance built-in', 'wp-cloudflare-page-cache' ); ?></li>
            <li><?php _e( 'Ready to use Starter Sites available', 'wp-cloudflare-page-cache' ); ?></li>
            <li><?php _e( 'AMP/Mobile ready.', 'wp-cloudflare-page-cache' ); ?></li>
            <li><?php _e( 'Dozens of ecommerce extra features', 'wp-cloudflare-page-cache' ); ?></li>
            <li><?php _e( 'Lots of customizations options', 'wp-cloudflare-page-cache' ); ?></li>
        </ul>
        <a style="text-align: center;   padding: 0 10px;" href="https://themeisle.com/themes/neve/" target="_blank"
           class="button button-primary"><?php _e( 'View more', 'wp-cloudflare-page-cache' ); ?></a>
    </div>


</div>