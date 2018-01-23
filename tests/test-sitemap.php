<?php
/**
 * Class Test_Sitemap
 *
 * @package 
 */

/**
 * Sitemap test case.
 */

require_once dirname( __FILE__ ) . '/aioseop-test-base.php';

class Test_Sitemap extends AIOSEOP_Unit_Test_Base {

	public function setUp(){
		parent::init();
		parent::setUp();
	}

	public function tearDown(){
		parent::init();
		parent::tearDown();
	}

	public function test_only_pages() {
		$posts = $this->setup_posts( 2 );
		$pages = $this->setup_posts( 2, 0, 'page' );

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'page' );

		$this->_setup_options( 'sitemap', $custom_options );

		$this->validate_sitemap(
			array(
					$pages['without'][0] => true,
					$pages['without'][1] => true,
					$posts['without'][0] => false,
					$posts['without'][1] => false,					
			)
		);
	}

	public function test_featured_image() {
		$posts = $this->setup_posts( 2, 2 );

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		$this->_setup_options( 'sitemap', $custom_options );

		$with = $posts['with'];
		$without = $posts['without'];
		$this->validate_sitemap(
			array(
					$with[0] => array(
						'image'	=> true,
					),
					$with[1] => array(
						'image'	=> true,
					),
					$without[0] => array(
						'image'	=> false,
					),
					$without[1] => array(
						'image'	=> false,
					),
			)
		);
	}

	public function test_exclude_images() {
		$posts = $this->setup_posts( 2, 2 );

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = 'on';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		$this->_setup_options( 'sitemap', $custom_options );

		$with = $posts['with'];
		$without = $posts['without'];
		$this->validate_sitemap(
			array(
					$with[0] => array(
						'image'	=> false,
					),
					$with[1] => array(
						'image'	=> false,
					),
					$without[0] => array(
						'image'	=> false,
					),
					$without[1] => array(
						'image'	=> false,
					),
			)
		);
	}

	public function test_exclude_taxonomy() {
		$posts = $this->factory->post->create_many( 5 );

		$term_vs_tax = array(
			// term => taxonomy
			'cat1' => 'custom_category0',
			'cat2' => 'custom_category0',
			'custcat1' => 'custom_category1',
			'custcat2' => 'custom_category1',
			'custcat3' => 'custom_category2',
		);

		register_taxonomy( 'custom_category0', 'post' );
		register_taxonomy( 'custom_category1', 'post' );
		register_taxonomy( 'custom_category2', 'post' );

		$terms = array();
		$index = 0;
		foreach ( $term_vs_tax as $term => $taxonomy ) {
			$id = $this->factory->term->create( array( 'taxonomy' => $taxonomy, 'name' => $term ) );
			$terms[ $term ] = $id;
			$this->factory->term->add_post_terms( $posts[ $index ], $term, $taxonomy, false );
			$index++;
		}

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = 'on';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		// exclude all posts from custom_category0 and only custcat1 from custom_category1 and none from custom_category2.
		$custom_options['aiosp_sitemap_excl_taxonomies'] = array( 'custom_category0', 'custom_category1' );
		$custom_options['aiosp_sitemap_excl_categories'] = array( $terms['custcat1'] );

		$this->_setup_options( 'sitemap', $custom_options );

		$urls = array();
		foreach( $posts as $id ) {
			$urls[] = get_permalink( $id );
		}
		$this->validate_sitemap(
			array(
					$urls[0] => false,
					$urls[1] => false,
					$urls[2] => false,
					$urls[3] => true,
					$urls[4] => true,
			)
		);
	}
}


