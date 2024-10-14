<?php
/**
 * Team cards block pattern
 *
 * @package Hestia
 */
return array(
	'title'      => __( 'Team cards', 'hestia' ),
	'categories' => array( 'hestia', 'team' ),
	'content'    => '
<!-- wp:group {"metadata":{"name":"Team 2"},"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"textAlign":"left"} -->
<h2 class="wp-block-heading has-text-align-left">Our Team</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left"} -->
<p class="has-text-align-left">Aligning user experience with functional beauty.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"width":"33.34%"} -->
<div class="wp-block-column" style="flex-basis:33.34%"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40","padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"border":{"radius":"8px"},"color":{"background":"#f9f8f7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:group {"style":{"spacing":{"margin":{"top":"-60px","bottom":"20px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:-60px;margin-bottom:20px"><!-- wp:image {"id":317,"width":"128px","sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"8px"}}} -->
<figure class="wp-block-image size-full is-resized has-custom-border"><img src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-6.jpeg" alt="" class="wp-image-317" style="border-radius:8px;width:128px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Samantha Ramsey</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|accent"}}}},"textColor":"accent","fontSize":"small"} -->
<p class="has-text-align-left has-accent-color has-text-color has-link-color has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">Lead UX Designer</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Specializes in crafting intuitive user experiences with a focus on seamless interaction. Samantha\'s expertise in wireframing and prototyping ensures designs are both beautiful and functional.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.34%"} -->
<div class="wp-block-column" style="flex-basis:33.34%"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40","padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"border":{"radius":"8px"},"color":{"background":"#f9f8f7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:group {"style":{"spacing":{"margin":{"top":"-60px","bottom":"20px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:-60px;margin-bottom:20px"><!-- wp:image {"id":319,"width":"128px","sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"8px"}}} -->
<figure class="wp-block-image size-full is-resized has-custom-border"><img src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-4.jpeg" alt="" class="wp-image-319" style="border-radius:8px;width:128px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">T<strong>essa Morgan</strong></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|accent"}}}},"textColor":"accent","fontSize":"small"} -->
<p class="has-text-align-left has-accent-color has-text-color has-link-color has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">Front-End Developer</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Expert in coding responsive, user-friendly interfaces. Tessa brings designs to life with clean, efficient code, optimizing for both speed and performance across all platforms.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40","padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"border":{"radius":"8px"},"color":{"background":"#f9f8f7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:group {"style":{"spacing":{"margin":{"top":"-60px","bottom":"20px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:-60px;margin-bottom:20px"><!-- wp:image {"id":318,"width":"128px","sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"8px"}}} -->
<figure class="wp-block-image size-full is-resized has-custom-border"><img src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-5.jpeg" alt="" class="wp-image-318" style="border-radius:8px;width:128px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Harper Diaz</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|accent"}}}},"textColor":"accent","fontSize":"small"} -->
<p class="has-text-align-left has-accent-color has-text-color has-link-color has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">Creative Director</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Oversees the creative vision, blending strategic design thinking with cutting-edge trends. Harper ensures every project aligns with the brand\'s identity while pushing the boundaries of innovation.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
);
