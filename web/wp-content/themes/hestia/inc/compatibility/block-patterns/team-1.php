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
<!-- wp:group {"metadata":{"name":"Team"},"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|accent"}}}},"textColor":"accent","fontSize":"small"} -->
<p class="has-text-align-center has-accent-color has-text-color has-link-color has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">Subtitle</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Team members</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">optimizing user engagement through spatial and visual cohesion</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"32px"} -->
<div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"style":{"border":{"radius":"8px"},"color":{"background":"#f9f8f7"},"spacing":{"padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:group {"style":{"spacing":{"margin":{"top":"-64px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:-64px"><!-- wp:image {"id":319,"aspectRatio":"3/4","scale":"cover","sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"8px"}}} -->
<figure class="wp-block-image size-full has-custom-border"><img src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-4.jpeg" alt="" class="wp-image-319" style="border-radius:8px;aspect-ratio:3/4;object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"66.66%","style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:66.66%"><!-- wp:heading -->
<h2 class="wp-block-heading">Tessa Morgan</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">Creative Architect</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Tessa Morgan is a front-end development expert with a passion for turning complex designs into seamless, interactive web experiences. With a background in both design and coding, Tessa excels at bridging the gap between creative vision and functional execution. Her attention to detail ensures that every pixel aligns perfectly, while her deep understanding of user interface principles results in intuitive, user-friendly interfaces that engage and delight users.</p>
<!-- /wp:paragraph -->

<!-- wp:social-links {"iconColor":"white","iconColorValue":"#ffffff","iconBackgroundColorValue":"#e91e63","size":"has-normal-icon-size","className":"is-style-default","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|30"}}}} -->
<ul class="wp-block-social-links has-normal-icon-size has-icon-color has-icon-background-color is-style-default"><!-- wp:social-link {"url":"#","service":"x"} /-->

<!-- wp:social-link {"url":"#","service":"facebook"} /-->

<!-- wp:social-link {"url":"#","service":"linkedin"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"32px"} -->
<div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"style":{"border":{"radius":"8px"},"color":{"background":"#f9f8f7"},"spacing":{"padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f9f8f7;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"verticalAlignment":"center","width":"66.66%","style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:66.66%"><!-- wp:heading -->
<h2 class="wp-block-heading">Eliot Ramsey</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="font-style:normal;font-weight:600;text-transform:uppercase">Innovation Strategist</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Eliot Ramsey is the driving force behind creating intuitive and engaging user experiences. With over a decade of experience in UX design, Eliot has mastered the art of blending functionality with creativity, ensuring every project meets the highest standards of user-centered design. His passion for understanding user behavior has led to innovative design solutions that not only look great but also enhance usability and interaction.</p>
<!-- /wp:paragraph -->

<!-- wp:social-links {"iconColor":"white","iconColorValue":"#ffffff","iconBackgroundColorValue":"#e91e63","size":"has-normal-icon-size","className":"is-style-default","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|30"}}}} -->
<ul class="wp-block-social-links has-normal-icon-size has-icon-color has-icon-background-color is-style-default"><!-- wp:social-link {"url":"#","service":"x"} /-->

<!-- wp:social-link {"url":"#","service":"facebook"} /-->

<!-- wp:social-link {"url":"#","service":"linkedin"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:group {"style":{"spacing":{"margin":{"top":"-64px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:-64px"><!-- wp:image {"id":318,"aspectRatio":"3/4","scale":"cover","sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"8px"}}} -->
<figure class="wp-block-image size-full has-custom-border"><img src="' . esc_url( get_template_directory_uri() . '/inc/compatibility/block-patterns/img' ) . '/hst-demo-5.jpeg" alt="" class="wp-image-318" style="border-radius:8px;aspect-ratio:3/4;object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->',
);
