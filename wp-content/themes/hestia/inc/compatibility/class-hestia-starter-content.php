<?php
/**
 * Starter Content Compatibility.
 *
 * @package Hestia
 */

/**
 * Class Hestia_Starter_Content
 */
class Hestia_Starter_Content {

	const HOME_SLUG    = 'home';
	const BLOG_SLUG    = 'blog';
	const CONTACT_SLUG = 'contact';

	/**
	 * Navigation items
	 *
	 * @return array
	 */
	private function get_nav_menu_items() {
		return array(
			'page_home'           => array(
				'type'      => 'post_type',
				'object'    => 'page',
				'object_id' => '{{' . self::HOME_SLUG . '}}',
			),
			'page_blog'           => array(
				'type'      => 'post_type',
				'object'    => 'page',
				'object_id' => '{{' . self::BLOG_SLUG . '}}',
			),
			'page_contact'        => array(
				'type'      => 'post_type',
				'object'    => 'page',
				'object_id' => '{{' . self::CONTACT_SLUG . '}}',
			),
			'link_menu_button'    => array(
				'url'     => '#',
				'title'   => _x( 'More', 'Theme starter content', 'hestia' ),
				'classes' => array( 'btn', 'btn-round', 'btn-primary', 'hestia-mega-menu' ),
			),
			'link_mm_col_1'       => array(
				'url'              => '#',
				'title'            => _x( 'col-1', 'Theme starter content', 'hestia' ),
				'classes'          => array( 'hestia-mm-col' ),
				'menu_item_parent' => -4,
			),
			'link_mm_col_1_title' => array(
				'url'              => '#',
				'title'            => _x( 'SETUP', 'Theme starter content', 'hestia' ),
				'classes'          => array( 'hestia-mm-heading' ),
				'menu_item_parent' => -5,
			),
			'link_mm_col_1_1'     => array(
				'url'              => '#',
				'title'            => _x( '1 minute setup', 'Theme starter content', 'hestia' ),
				'menu_item_parent' => -5,
			),
			'link_mm_col_1_2'     => array(
				'url'              => '#',
				'title'            => _x( 'Live Customizer', 'Theme starter content', 'hestia' ),
				'menu_item_parent' => -5,
			),
			'link_mm_col_1_3'     => array(
				'url'              => '#',
				'title'            => _x( 'Video Tutorials', 'Theme starter content', 'hestia' ),
				'menu_item_parent' => -5,
			),
			'link_mm_col_2'       => array(
				'url'              => '#',
				'title'            => _x( 'col-2', 'Theme starter content', 'hestia' ),
				'classes'          => array( 'hestia-mm-col' ),
				'menu_item_parent' => -4,
			),
			'link_mm_col_2_title' => array(
				'url'              => '#',
				'title'            => _x( 'CONTENT', 'Theme starter content', 'hestia' ),
				'classes'          => array( 'hestia-mm-heading' ),
				'menu_item_parent' => -10,
			),
			'link_mm_col_2_1'     => array(
				'url'              => '#',
				'title'            => _x( 'Custom Backgrounds', 'Theme starter content', 'hestia' ),
				'menu_item_parent' => -10,
			),
			'link_mm_col_2_2'     => array(
				'url'              => '#',
				'title'            => _x( 'SEO Optimized', 'Theme starter content', 'hestia' ),
				'menu_item_parent' => -10,
			),
			'link_mm_col_2_3'     => array(
				'url'              => '#',
				'title'            => _x( 'Translation & RTL Ready', 'Theme starter content', 'hestia' ),
				'menu_item_parent' => -10,
			),
			'link_mm_col_3'       => array(
				'url'              => '#',
				'title'            => _x( 'col-3', 'Theme starter content', 'hestia' ),
				'classes'          => array( 'hestia-mm-col' ),
				'menu_item_parent' => -4,
			),
			'link_mm_col_3_title' => array(
				'url'              => '#',
				'title'            => _x( 'DESIGN', 'Theme starter content', 'hestia' ),
				'classes'          => array( 'hestia-mm-heading' ),
				'menu_item_parent' => -15,
			),
			'link_mm_col_3_1'     => array(
				'url'              => '#',
				'title'            => _x( 'Responsive Design', 'Theme starter content', 'hestia' ),
				'menu_item_parent' => -15,
			),
			'link_mm_col_3_2'     => array(
				'url'              => '#',
				'title'            => _x( 'Optimized for Speed', 'Theme starter content', 'hestia' ),
				'menu_item_parent' => -15,
			),
			'link_mm_col_3_3'     => array(
				'url'              => '#',
				'title'            => _x( 'Fast Updates & Support', 'Theme starter content', 'hestia' ),
				'menu_item_parent' => -15,
			),
			'link_mm_col_4'       => array(
				'url'              => '#',
				'title'            => _x( 'col-4', 'Theme starter content', 'hestia' ),
				'classes'          => array( 'hestia-mm-col' ),
				'menu_item_parent' => -4,
			),
			'link_mm_col_4_title' => array(
				'url'              => '#',
				'title'            => _x( 'INTEGRATIONS', 'Theme starter content', 'hestia' ),
				'classes'          => array( 'hestia-mm-heading' ),
				'menu_item_parent' => -20,
			),
			'link_mm_col_4_1'     => array(
				'url'              => '#',
				'title'            => _x( 'WooCommerce Ready', 'Theme starter content', 'hestia' ),
				'menu_item_parent' => -20,
			),
			'link_mm_col_4_2'     => array(
				'url'              => '#',
				'title'            => _x( 'Drag-and-Drop Builder', 'Theme starter content', 'hestia' ),
				'menu_item_parent' => -20,
			),
		);
	}

	/**
	 * Default contact page content.
	 *
	 * @return string
	 */
	private function get_default_contact_content() {
		return '<div class="hestia-info info info-horizontal">
		<div class="icon icon-primary"><i class="fas fa-map-marker-alt"></i></div>
		<div class="description">
		<h4 class="info-title">Find us at the office</h4>
		Strada Povernei, nr 20, Bucharest, Romania
		
		</div>
		</div>
		<div class="hestia-info info info-horizontal">
		<div class="icon icon-primary"><i class="fas fa-mobile-alt"></i></div>
		<div class="description">
		<h4 class="info-title">Give us a ring</h4>
		John Doe
		+40 712 345 678
		Mon – Fri, 8:00-22:00
		
		</div>
		</div>';
	}

	/**
	 * Return starter content definition.
	 *
	 * @return mixed|void
	 */
	public function get() {
		$nav_items                   = $this->get_nav_menu_items();
		$contact_default             = $this->get_default_contact_content();
		$default_home_featured_image = get_template_directory_uri() . '/assets/img/contact.jpg';

		return array(
			'theme_mods'  => array(
				'hestia_big_title_title'       => 'Welcome to Hestia!',
				'hestia_big_title_text'        => 'Lorem ipsum dolor sit amet',
				'hestia_big_title_button_text' => 'See more',
				'hestia_big_title_button_link' => '#about',
				'hestia_contact_title'         => 'Get in Touch',
				'hestia_contact_content_new'   => $contact_default,
				'hestia_blog_sidebar_layout'   => 'full-width',
			),
			'attachments' => array(
				'featured-image-home' => array(
					'post_title'   => __( 'Featured Image Homepage', 'hestia' ),
					'post_content' => __( 'Featured Image Homepage', 'hestia' ),
					'file'         => 'assets/img/contact.jpg',
				),
				'featured-slide1'     => array(
					'post_title' => 'First slide',
					'file'       => 'assets/img/slider1.jpg',
				),
				'post-1'              => array(
					'post_title' => 'Landscape',
					'file'       => 'assets/img/card-blog1.jpg',
				),
				'post-2'              => array(
					'post_title' => 'Drone',
					'file'       => 'assets/img/card-blog2.jpg',
				),
				'post-3'              => array(
					'post_title' => 'Convertible',
					'file'       => 'assets/img/card-blog3.jpg',
				),
				'post-4'              => array(
					'post_title' => 'Tourism',
					'file'       => 'assets/img/card-blog4.jpg',
				),
				'post-5'              => array(
					'post_title' => 'Castle',
					'file'       => 'assets/img/card-blog5.jpg',
				),
			),
			'posts'       => array(
				self::HOME_SLUG    => require __DIR__ . '/starter-content/home.php',
				self::CONTACT_SLUG => require __DIR__ . '/starter-content/contact.php',
				self::BLOG_SLUG    => array(
					'post_name'  => self::BLOG_SLUG,
					'post_type'  => 'page',
					'post_title' => _x( 'Blog', 'Theme starter content', 'hestia' ),
				),
				'custom_post_1'    => array(
					'post_type'    => 'post',
					'post_title'   => 'Appearance guide',
					'post_content' => '<!-- wp:paragraph -->
					<p>Yet bed any for travelling assistance indulgence unpleasing. Not thoughts all exercise blessing. Indulgence way everything joy alteration boisterous the attachment. Party we years to order allow asked of. We so opinion friends me message as delight. Whole front do of plate heard oh ought. His defective nor convinced residence own. Connection has put impossible own apartments boisterous. At jointure ladyship an insisted so humanity he. Friendly bachelor entrance to on by.</p>
					<!-- /wp:paragraph -->
					
					<!-- wp:paragraph -->
					<p>That last is no more than a foot high, and about seven paces across, a mere flat top of a grey rock which smokes like a hot cinder after a shower, and where no man would care to venture a naked sole before sunset. On the Little Isabel an old ragged palm, with a thick bulging trunk rough with spines, a very witch amongst palm trees, rustles a dismal bunch of dead leaves above the<a href="#"> coarse sand</a>.</p>
					<!-- /wp:paragraph -->',
					'thumbnail'    => '{{post-1}}',
				),
				'custom_post_2'    => array(
					'post_type'    => 'post',
					'post_title'   => 'Perfectly on furniture',
					'post_content' => '<!-- wp:heading {"level":3,"className":"title"} -->
					<h3 class="title">Feet evil to hold long he open knew an no.</h3>
					<!-- /wp:heading -->
					
					<!-- wp:paragraph -->
					<p>Apartments occasional boisterous as solicitude to introduced. Or fifteen covered we enjoyed demesne is in prepare. In stimulated my everything it literature. Greatly explain attempt perhaps in feeling he. House men taste bed not drawn joy. Through enquire however do equally herself at. Greatly way old may you present improve. Wishing the feeling village him musical.</p>
					<!-- /wp:paragraph -->
					
					<!-- wp:paragraph -->
					<p>Smile spoke total few great had never their too. Amongst moments do in arrived at my replied. Fat weddings servants but man believed prospect. Companions understood is as especially pianoforte connection introduced. Nay newspaper can sportsman are admitting gentleman belonging his.</p>
					<!-- /wp:paragraph -->',
					'thumbnail'    => '{{post-2}}',
				),
				'custom_post_3'    => array(
					'post_type'    => 'post',
					'post_title'   => 'Fat son how smiling natural',
					'post_content' => '<!-- wp:paragraph -->
					<p><em>To shewing another demands sentiments. Marianne property cheerful informed at striking at. Clothes parlors however by cottage on. In views it or meant drift to. Be concern parlors settled or do shyness address.&nbsp;</em></p>
					<!-- /wp:paragraph -->
					
					<!-- wp:heading -->
					<h2>He always do do former he highly.</h2>
					<!-- /wp:heading -->
					
					<!-- wp:paragraph -->
					<p>Continual so distrusts pronounce by unwilling listening</p>
					<!-- /wp:paragraph -->
					
					<!-- wp:paragraph -->
					<p>Expenses as material breeding insisted building to in. Continual so distrusts pronounce by unwilling listening. Thing do taste on we manor. Him had wound use found hoped. Of distrusts immediate enjoyment curiosity do. Marianne numerous saw thoughts the humoured.</p>
					<!-- /wp:paragraph -->',
					'thumbnail'    => '{{post-3}}',
				),
				'custom_post_4'    => array(
					'post_type'    => 'post',
					'post_title'   => 'Can curiosity may end shameless explained',
					'post_content' => '<!-- wp:heading -->
					<h2>Way nor furnished sir procuring therefore but.</h2>
					<!-- /wp:heading -->
					
					<!-- wp:paragraph -->
					<p>Warmth far manner myself active are cannot called. Set her half end girl rich met. Me allowance departure an curiosity ye. In no talking address excited it conduct. Husbands debating replying overcame<em>&nbsp;blessing</em>&nbsp;he it me to domestic.</p>
					<!-- /wp:paragraph -->
					
					<!-- wp:list -->
					<ul><li>As absolute is by amounted repeated entirely ye returned.</li><li>These ready timed enjoy might sir yet one since.</li><li>Years drift never if could forty being no.</li></ul>
					<!-- /wp:list -->',
					'thumbnail'    => '{{post-4}}',
				),
				'custom_post_5'    => array(
					'post_type'    => 'post',
					'post_title'   => 'Improve him believe opinion offered',
					'post_content' => '<!-- wp:paragraph -->
					<p>It acceptance thoroughly my advantages everything as. Are projecting inquietude affronting preference saw who. Marry of am do avoid ample as. Old disposal followed she ignorant desirous two has. Called played entire roused though for one too. He into walk roof made tall cold he. Feelings way likewise addition wandered contempt bed indulged.</p>
					<!-- /wp:paragraph -->
					
					<!-- wp:heading {"level":4} -->
					<h4><strong>Still court no small think death so an wrote.</strong></h4>
					<!-- /wp:heading -->
					
					<!-- wp:paragraph -->
					<p>Incommode necessary no it behaviour convinced distrusts an unfeeling he. Could death since do we hoped is in. Exquisite no my attention extensive. The determine conveying moonlight age. Avoid for see marry sorry child. Sitting so totally forbade hundred to.</p>
					<!-- /wp:paragraph -->',
					'thumbnail'    => '{{post-5}}',
				),
			),
			'nav_menus'   => array(
				'primary' => array(
					'name'  => esc_html__( 'Primary Menu', 'hestia' ),
					'items' => $nav_items,
				),
			),
			'options'     => array(
				'show_on_front'            => 'page',
				'page_on_front'            => '{{' . self::HOME_SLUG . '}}',
				'page_for_posts'           => '{{' . self::BLOG_SLUG . '}}',
				'hestia_feature_thumbnail' => $default_home_featured_image,
			),
		);
	}
}
