<?php
/**
 * Call to action block pattern
 *
 * @package Hestia
 */
return array(
	'title'      => __( 'Call to action', 'hestia' ),
	'categories' => array( 'hestia', 'call-to-action' ),
	'content'    => '
<!-- wp:group {"metadata":{"name":"CTA"},"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|70","right":"var:preset|spacing|70"}},"border":{"radius":"8px"},"color":{"background":"#f9f8f7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--70)"><!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group"><!-- wp:heading -->
			<h2 class="wp-block-heading">Ready to Elevate Your Project?</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>Let\'s collaborate and create something extraordinary.</p>
			<!-- /wp:paragraph --></div>
		<!-- /wp:group -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","textColor":"white","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}}} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-accent-background-color has-text-color has-background has-link-color wp-element-button"><strong>Get Started!</strong></a></div>
			<!-- /wp:button --></div>
		<!-- /wp:buttons --></div>
	<!-- /wp:group --></div>
<!-- /wp:group -->',
);
