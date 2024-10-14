<?php
/**
 * Pricing plan columns block pattern
 *
 * @package Hestia
 */
return array(
	'title'      => __( 'Pricing plan columns', 'hestia' ),
	'categories' => array( 'hestia', 'columns' ),
	'content'    => '
<!-- wp:group {"metadata":{"name":"pricing"},"align":"wide","style":{"spacing":{"padding":{"top":"24px","bottom":"24px","left":"22px","right":"22px"}},"border":{"radius":"8px"},"color":{"background":"#f9f8f7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:24px;padding-right:22px;padding-bottom:24px;padding-left:22px"><!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading -->
<h2 class="wp-block-heading">Upgrade to PRO version</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Adaptation is key, and the future of design lies in flexibility. From responsive interfaces to immersive environments</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:group {"metadata":{"name":"pricing table"},"style":{"spacing":{"blockGap":"var:preset|spacing|50","padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"},"margin":{"top":"-50px"}},"border":{"radius":"8px"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"backgroundColor":"accent","textColor":"white","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-white-color has-accent-background-color has-text-color has-background has-link-color" style="border-radius:8px;margin-top:-50px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-text-align-center has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">One-time Payment</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"textColor":"white","fontSize":"x-large"} -->
<p class="has-text-align-center has-white-color has-text-color has-link-color has-x-large-font-size"><strong>$299</strong></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"white","textColor":"accent","width":100,"style":{"elements":{"link":{"color":{"text":"var:preset|color|accent"}}}}} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link has-accent-color has-white-background-color has-text-color has-background has-link-color wp-element-button"><strong>Get Started</strong></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"top":"16px"}}},"fontSize":"small"} -->
<p class="has-text-align-center has-small-font-size" style="margin-top:16px">14-Day Refund Period</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
);
