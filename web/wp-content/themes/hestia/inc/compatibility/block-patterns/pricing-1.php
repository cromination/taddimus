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
<!-- wp:group {"metadata":{"name":"pricing"},"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading -->
<h2 class="wp-block-heading">Choose your plan</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Adaptation is key, and the future of design lies in flexibility. From responsive interfaces to immersive environments</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"metadata":{"name":"pricing table"},"style":{"spacing":{"blockGap":"var:preset|spacing|30","padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"8px"},"color":{"background":"#f9f8f7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-text-align-center has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">Basic</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|accent"}}}},"textColor":"accent","fontSize":"x-large"} -->
<p class="has-text-align-center has-accent-color has-text-color has-link-color has-x-large-font-size"><strong>$199</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"right":"0","left":"0"}}}} -->
<p class="has-text-align-center" style="margin-right:0;margin-left:0"><strong>Up to 3</strong> custom designs</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><strong>Basic</strong> prototyping and revisions</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><strong>Mobile-responsive</strong> layout</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><strong>1-week</strong> turnaround</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","textColor":"white","width":100,"style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}}} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link has-white-color has-accent-background-color has-text-color has-background has-link-color wp-element-button"><strong>Get Started</strong></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"metadata":{"name":"pricing table"},"style":{"spacing":{"blockGap":"var:preset|spacing|30","padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"8px"},"color":{"background":"#f9f8f7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-text-align-center has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">Basic</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|accent"}}}},"textColor":"accent","fontSize":"x-large"} -->
<p class="has-text-align-center has-accent-color has-text-color has-link-color has-x-large-font-size"><strong>$299</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"right":"0","left":"0"}}}} -->
<p class="has-text-align-center" style="margin-right:0;margin-left:0"><strong>Up to 5</strong> custom designs</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><strong>Advanced</strong> prototyping and revisions</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><strong>Mobile-responsive</strong> layout</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><strong>4-days</strong> turnaround</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","textColor":"white","width":100,"style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}}} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link has-white-color has-accent-background-color has-text-color has-background has-link-color wp-element-button"><strong>Get Started</strong></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
);
