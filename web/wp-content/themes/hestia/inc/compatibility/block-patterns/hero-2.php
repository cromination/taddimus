<?php
/**
 * Block Pattern: Hero
 *
 * @package Hestia
 */
return array(
	'title'      => __( 'Header', 'hestia' ),
	'categories' => array( 'hestia', 'media' ),
	'content'    => '
<!-- wp:group {"metadata":{"name":"hero 2"},"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull"><!-- wp:cover {"url":"' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-1.jpeg","id":322,"dimRatio":90,"overlayColor":"accent","isUserOverlayColor":true,"minHeightUnit":"vh","metadata":{"name":"Call to Action"},"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"32px","right":"32px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull" style="padding-top:80px;padding-right:32px;padding-bottom:80px;padding-left:32px"><span aria-hidden="true" class="wp-block-cover__background has-accent-background-color has-background-dim-90 has-background-dim"></span><img class="wp-block-cover__image-background wp-image-322" alt="" src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-1.jpeg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"textColor":"white","fontSize":"small"} -->
<p class="has-text-align-center has-white-color has-text-color has-link-color has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">Call to Action</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="wp-block-heading has-text-align-center">Key Features that Elevate Your Product</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Unlock the full potential of your digital experience with awesome features</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"white","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"border":{"width":"2px"},"color":{"background":"#ffffff00"}},"borderColor":"white"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-text-color has-background has-link-color has-border-color has-white-border-color wp-element-button" style="border-width:2px;background-color:#ffffff00"><strong>Learn more</strong></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->',
);
