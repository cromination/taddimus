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
<!-- wp:group {"metadata":{"name":"Hero"},"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="wp-block-heading has-text-align-center">Transform Ideas into Reality</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Bold designs, innovative solutions, and flawless execution</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","textColor":"white","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-accent-background-color has-text-color has-background has-link-color wp-element-button"><strong>Get Started!</strong></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:cover {"url":"' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-1.jpeg","id":322,"dimRatio":0,"overlayColor":"accent","isUserOverlayColor":true,"isDark":false,"align":"wide","style":{"border":{"radius":"8px"}}} -->
<div class="wp-block-cover alignwide is-light" style="border-radius:8px"><span aria-hidden="true" class="wp-block-cover__background has-accent-background-color has-background-dim-0 has-background-dim"></span><img class="wp-block-cover__image-background wp-image-322" alt="" src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-1.jpeg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->',
);
