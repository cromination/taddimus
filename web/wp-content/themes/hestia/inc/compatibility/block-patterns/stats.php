<?php
/**
 * Stats block pattern
 *
 * @package Hestia
 */
return array(
	'title'      => __( 'Stats', 'hestia' ),
	'categories' => array( 'hestia', 'columns' ),
	'content'    => '
<!-- wp:group {"metadata":{"name":"stats"},"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Metrics</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Reimagine the interplay of color, texture, and space.</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"32px"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"blockGap":"0","padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"border":{"radius":"8px"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"dimensions":{"minHeight":"300px"}},"backgroundColor":"accent","textColor":"white","layout":{"type":"flex","orientation":"vertical","verticalAlignment":"bottom"}} -->
<div class="wp-block-group has-white-color has-accent-background-color has-text-color has-background has-link-color" style="border-radius:8px;min-height:300px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"700","fontSize":"72px"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"white"} -->
<p class="has-white-color has-text-color has-link-color" style="margin-top:0;margin-bottom:0;font-size:72px;font-style:normal;font-weight:700">23%</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Dynamic Fluidity</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"blockGap":"0","padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"border":{"radius":"8px"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"dimensions":{"minHeight":"300px"}},"backgroundColor":"accent","textColor":"white","layout":{"type":"flex","orientation":"vertical","verticalAlignment":"bottom"}} -->
<div class="wp-block-group has-white-color has-accent-background-color has-text-color has-background has-link-color" style="border-radius:8px;min-height:300px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"700","fontSize":"72px"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"white"} -->
<p class="has-white-color has-text-color has-link-color" style="margin-top:0;margin-bottom:0;font-size:72px;font-style:normal;font-weight:700">5K+</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Multi-Sensory Cohesion</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"blockGap":"0","padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"border":{"radius":"8px"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"dimensions":{"minHeight":"300px"}},"backgroundColor":"accent","textColor":"white","layout":{"type":"flex","orientation":"vertical","verticalAlignment":"bottom"}} -->
<div class="wp-block-group has-white-color has-accent-background-color has-text-color has-background has-link-color" style="border-radius:8px;min-height:300px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"700","fontSize":"72px"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"white"} -->
<p class="has-white-color has-text-color has-link-color" style="margin-top:0;margin-bottom:0;font-size:72px;font-style:normal;font-weight:700">34</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Emotional Connectivity</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
);
