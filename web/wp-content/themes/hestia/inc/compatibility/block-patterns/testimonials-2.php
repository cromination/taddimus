<?php
/**
 * Testimonial columns block pattern
 *
 * @package Hestia
 */
return array(
	'title'      => __( 'Testimonial columns', 'hestia' ),
	'categories' => array( 'hestia', 'testimonials' ),
	'content'    => '
<!-- wp:group {"metadata":{"name":"testimonial"},"style":{"border":{"radius":"8px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-radius:8px"><!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"#ffcd7d"}}},"color":{"text":"#ffcd7d"}},"fontSize":"large"} -->
<p class="has-text-align-center has-text-color has-link-color has-large-font-size" style="color:#ffcd7d">★★★★★</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">"...The creative approach brought our vision to life in ways we hadn\'t imagined. The user experience is intuitive and visually stunning..."</p>
<!-- /wp:paragraph -->

<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:image {"id":317,"width":"80px","sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"999px"}}} -->
<figure class="wp-block-image size-full is-resized has-custom-border"><img src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-6.jpeg" alt="" class="wp-image-317" style="border-radius:999px;width:80px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"align":"left","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-text-align-left has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase"><strong>Samantha L.</strong>, Founder &amp; CEO</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->',
);
