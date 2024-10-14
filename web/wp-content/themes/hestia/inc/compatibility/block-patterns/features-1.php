<?php
/**
 * Features block pattern
 *
 * @package Hestia
 */
return array(
	'title'      => __( 'Features', 'hestia' ),
	'categories' => array( 'hestia', 'columns' ),
	'content'    => '
<!-- wp:group {"metadata":{"name":"Features"},"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|60","left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:cover {"url":"' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-2.jpeg","id":321,"dimRatio":90,"overlayColor":"accent","isUserOverlayColor":true,"minHeight":100,"minHeightUnit":"%","style":{"spacing":{"padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"border":{"radius":"8px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover" style="border-radius:8px;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px;min-height:100%"><span aria-hidden="true" class="wp-block-cover__background has-accent-background-color has-background-dim-90 has-background-dim"></span><img class="wp-block-cover__image-background wp-image-321" alt="" src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-2.jpeg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Key Features that Elevate Your Product</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Unlock the full potential of your digital experience with features designed to enhance usability, streamline interaction, and deliver visually stunning results. </p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"white","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"border":{"width":"2px"},"color":{"background":"#ffffff00"}},"borderColor":"white"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-text-color has-background has-link-color has-border-color has-white-border-color wp-element-button" style="border-width:2px;background-color:#ffffff00"><strong>Learn more</strong></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"var:preset|spacing|70"}}} -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"align":"left","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|accent"}}}},"textColor":"accent","fontSize":"small"} -->
<p class="has-text-align-left has-accent-color has-text-color has-link-color has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">Feature 1</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Real-Time Collaboration</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","style":{"spacing":{"margin":{"right":"0","left":"0"}}}} -->
<p class="has-text-align-left" style="margin-right:0;margin-left:0">Work with your team in sync, making updates and edits instantly from anywhere, ensuring faster workflows and better results.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"align":"left","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|accent"}}}},"textColor":"accent","fontSize":"small"} -->
<p class="has-text-align-left has-accent-color has-text-color has-link-color has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">Feature 2</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Customizable Templates</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","style":{"spacing":{"margin":{"right":"0","left":"0"}}}} -->
<p class="has-text-align-left" style="margin-right:0;margin-left:0">Choose from a range of design templates that can be tailored to fit your brand, providing flexibility without compromising quality.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"align":"left","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|accent"}}}},"textColor":"accent","fontSize":"small"} -->
<p class="has-text-align-left has-accent-color has-text-color has-link-color has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">Feature 3</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Advanced Analytics</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","style":{"spacing":{"margin":{"right":"0","left":"0"}}}} -->
<p class="has-text-align-left" style="margin-right:0;margin-left:0">Gain deep insights into user behavior with real-time data tracking, helping you optimize performance and enhance user engagement.<br></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
);
