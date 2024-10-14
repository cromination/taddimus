<?php
/**
 * Features block pattern
 *
 * @package Hestia
 */
return array(
	'title'      => __( 'Features', 'hestia' ),
	'categories' => array( 'hestia', 'columns' ),
	'content'    => '<!-- wp:columns {"metadata":{"name":"Features"},"align":"wide","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":{"left":"32px"}}}} -->
<div class="wp-block-columns alignwide" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"blockGap":"var:preset|spacing|50"}}} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"id":321,"aspectRatio":"1","scale":"cover","sizeSlug":"full","linkDestination":"none","className":"is-style-default","style":{"border":{"radius":"8px"}}} -->
<figure class="wp-block-image size-full has-custom-border is-style-default"><img src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-2.jpeg" alt="" class="wp-image-321" style="border-radius:8px;aspect-ratio:1;object-fit:cover"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|accent"}}},"spacing":{"margin":{"top":"var:preset|spacing|60"}}},"textColor":"accent","fontSize":"small"} -->
<p class="has-text-align-center has-accent-color has-text-color has-link-color has-small-font-size" style="margin-top:var(--wp--preset--spacing--60);font-style:normal;font-weight:600;text-transform:uppercase">Subtitle</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Real-Time Collaboration</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Work with your team in sync, making updates and edits instantly from anywhere, ensuring faster workflows and better results.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","textColor":"white","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-accent-background-color has-text-color has-background has-link-color wp-element-button"><strong>Get started</strong></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"blockGap":"var:preset|spacing|50"}}} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"id":320,"aspectRatio":"1","scale":"cover","sizeSlug":"full","linkDestination":"none","className":"is-style-default","style":{"border":{"radius":"8px"}}} -->
<figure class="wp-block-image size-full has-custom-border is-style-default"><img src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-3.jpeg" alt="" class="wp-image-320" style="border-radius:8px;aspect-ratio:1;object-fit:cover"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|accent"}}},"spacing":{"margin":{"top":"var:preset|spacing|60"}}},"textColor":"accent","fontSize":"small"} -->
<p class="has-text-align-center has-accent-color has-text-color has-link-color has-small-font-size" style="margin-top:var(--wp--preset--spacing--60);font-style:normal;font-weight:600;text-transform:uppercase">Subtitle</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Customizable Templates</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Choose from a range of design templates that can be tailored to fit your brand, providing flexibility without compromising quality.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","textColor":"white","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-accent-background-color has-text-color has-background has-link-color wp-element-button"><strong>Get started</strong></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->',
);
