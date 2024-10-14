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
<!-- wp:group {"metadata":{"name":"testimonials"},"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Hear from our Clients</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Hear from Those Who\'ve Transformed Their Vision into Reality</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"metadata":{"name":"testimonial"},"style":{"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"8px"},"layout":{"columnSpan":"","rowSpan":""},"color":{"background":"#f9f8f7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"#ffcd7d"}}},"color":{"text":"#ffcd7d"}},"fontSize":"medium"} -->
<p class="has-text-align-center has-text-color has-link-color has-medium-font-size" style="color:#ffcd7d">★★★★★</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">"...The creative approach brought our vision to life in ways we hadn\'t imagined. The user experience is intuitive and visually stunning..."</p>
<!-- /wp:paragraph -->

<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:image {"id":317,"width":"80px","sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"999px"}}} -->
<figure class="wp-block-image size-full is-resized has-custom-border"><img src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-6.jpeg" alt="" class="wp-image-317" style="border-radius:999px;width:80px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-text-align-center has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase"><strong>Samantha L.</strong>, Founder &amp; CEO</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"metadata":{"name":"testimonial"},"style":{"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"8px"},"layout":{"columnSpan":"","rowSpan":""},"color":{"background":"#f9f8f7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"#ffcd7d"}}},"color":{"text":"#ffcd7d"}},"fontSize":"medium"} -->
<p class="has-text-align-center has-text-color has-link-color has-medium-font-size" style="color:#ffcd7d">★★★★★</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">"...The creative approach brought our vision to life in ways we hadn\'t imagined. The user experience is intuitive and visually stunning..."</p>
<!-- /wp:paragraph -->

<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:image {"id":319,"width":"80px","sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"999px"}}} -->
<figure class="wp-block-image size-full is-resized has-custom-border"><img src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-4.jpeg" alt="" class="wp-image-319" style="border-radius:999px;width:80px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-text-align-center has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase"><strong>Samantha L.</strong>, Founder &amp; CEO</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"metadata":{"name":"testimonial"},"style":{"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"8px"},"layout":{"columnSpan":"","rowSpan":""},"color":{"background":"#f9f8f7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"#ffcd7d"}}},"color":{"text":"#ffcd7d"}},"fontSize":"medium"} -->
<p class="has-text-align-center has-text-color has-link-color has-medium-font-size" style="color:#ffcd7d">★★★★★</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">"...Working with them felt effortless. Their understanding of modern design trends and elements really set our project apart..."</p>
<!-- /wp:paragraph -->

<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:image {"id":318,"width":"80px","sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"999px"}}} -->
<figure class="wp-block-image size-full is-resized has-custom-border"><img src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-5.jpeg" alt="" class="wp-image-318" style="border-radius:999px;width:80px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-text-align-center has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">Jordan P., Marketing Director</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
);
