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
<div class="wp-block-group alignwide"><!-- wp:heading {"textAlign":"left"} -->
<h2 class="wp-block-heading has-text-align-left">Our Features</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left"} -->
<p class="has-text-align-left">Aligning user experience with functional beauty.</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"blockGap":"0","padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"border":{"radius":"8px"},"color":{"background":"#f9f8f7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Functional</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Design is where function meets inspiration, delivering not only usability but also emotional depth.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","textColor":"white","style":{"spacing":{"padding":{"left":"var:preset|spacing|60","right":"var:preset|spacing|60","top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}},"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"fontSize":"small"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase"><a class="wp-block-button__link has-white-color has-accent-background-color has-text-color has-background has-link-color wp-element-button" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--60)">Learn more</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"blockGap":"0","padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"border":{"radius":"8px"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"backgroundColor":"accent","textColor":"white","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-white-color has-accent-background-color has-text-color has-background has-link-color" style="border-radius:8px;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Customizable</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Design is where function meets inspiration, delivering not only usability but also emotional depth.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"white","style":{"spacing":{"padding":{"left":"var:preset|spacing|60","right":"var:preset|spacing|60","top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}},"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"border":{"width":"2px"},"color":{"background":"#ffffff00"}},"fontSize":"small","borderColor":"white"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase"><a class="wp-block-button__link has-white-color has-text-color has-background has-link-color has-border-color has-white-border-color wp-element-button" style="border-width:2px;background-color:#ffffff00;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--60)">Learn more</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"blockGap":"0","padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"border":{"radius":"8px"},"color":{"background":"#f9f8f7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Future-Driven</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Every line, every color, every form works in harmony to redefine how users interact with the world.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","textColor":"white","style":{"spacing":{"padding":{"left":"var:preset|spacing|60","right":"var:preset|spacing|60","top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}},"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"fontSize":"small"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase"><a class="wp-block-button__link has-white-color has-accent-background-color has-text-color has-background has-link-color wp-element-button" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--60)">Learn more</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"blockGap":"0","padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"border":{"radius":"8px"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"backgroundColor":"accent","textColor":"white","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-white-color has-accent-background-color has-text-color has-background has-link-color" style="border-radius:8px;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Redefining</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>The journey continues, as design shapes tomorrow\'s digital and physical landscapes.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"white","style":{"spacing":{"padding":{"left":"var:preset|spacing|60","right":"var:preset|spacing|60","top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}},"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"border":{"width":"2px"},"color":{"background":"#ffffff00"}},"fontSize":"small","borderColor":"white"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase"><a class="wp-block-button__link has-white-color has-text-color has-background has-link-color has-border-color has-white-border-color wp-element-button" style="border-width:2px;background-color:#ffffff00;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--60)">Learn more</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"blockGap":"0","padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"border":{"radius":"8px"},"color":{"background":"#f9f8f7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Real-Time</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>The journey continues, as design shapes tomorrow\'s digital and physical landscapes.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","textColor":"white","style":{"spacing":{"padding":{"left":"var:preset|spacing|60","right":"var:preset|spacing|60","top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}},"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"fontSize":"small"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase"><a class="wp-block-button__link has-white-color has-accent-background-color has-text-color has-background has-link-color wp-element-button" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--60)">Learn more</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"blockGap":"0","padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"border":{"radius":"8px"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"backgroundColor":"accent","textColor":"white","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-white-color has-accent-background-color has-text-color has-background has-link-color" style="border-radius:8px;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Advanced</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>The journey continues, as design shapes tomorrow\'s digital and physical landscapes.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"white","style":{"spacing":{"padding":{"left":"var:preset|spacing|60","right":"var:preset|spacing|60","top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}},"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"border":{"width":"2px"},"color":{"background":"#ffffff00"}},"fontSize":"small","borderColor":"white"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase"><a class="wp-block-button__link has-white-color has-text-color has-background has-link-color has-border-color has-white-border-color wp-element-button" style="border-width:2px;background-color:#ffffff00;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--60)">Learn more</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
);
