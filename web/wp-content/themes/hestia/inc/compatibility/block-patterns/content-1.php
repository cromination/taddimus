<?php
/**
 * Content with alternating image and text block pattern
 *
 * @package Hestia
 */
return array(
	'title'      => __( 'Content with alternating image and text', 'hestia' ),
	'categories' => array( 'hestia', 'text' ),
	'content'    => '
<!-- wp:columns {"metadata":{"name":"Content"},"align":"wide","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":{"left":"32px"}}}} -->
<div class="wp-block-columns alignwide" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:column {"verticalAlignment":"center"} -->
	<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph {"align":"left","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|accent"}}}},"textColor":"accent","fontSize":"small"} -->
		<p class="has-text-align-left has-accent-color has-text-color has-link-color has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">Subtitle</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading -->
		<h2 class="wp-block-heading">Dynamic Concepts, Fluid Execution</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p>Elevate form, transcend function—design emerges where creativity meets innovation. Lines blur, spaces evolve, and every element speaks the language of harmony. Bold ideas shape the contours of tomorrow, aligning vision with tactile expression.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","textColor":"white","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}}} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-accent-background-color has-text-color has-background has-link-color wp-element-button"><strong>Get started</strong></a></div>
			<!-- /wp:button --></div>
		<!-- /wp:buttons --></div>
	<!-- /wp:column -->

	<!-- wp:column {"width":"40%"} -->
	<div class="wp-block-column" style="flex-basis:40%"><!-- wp:image {"id":321,"aspectRatio":"1","scale":"cover","sizeSlug":"full","linkDestination":"none","className":"is-style-default","style":{"border":{"radius":"8px"}}} -->
		<figure class="wp-block-image size-full has-custom-border is-style-default"><img src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-2.jpeg" alt="" class="wp-image-321" style="border-radius:8px;aspect-ratio:1;object-fit:cover"/></figure>
		<!-- /wp:image --></div>
	<!-- /wp:column --></div>
<!-- /wp:columns -->',
);
