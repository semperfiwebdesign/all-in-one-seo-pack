<?php
/**
 * Class Test_Sitemap
 *
 * @package
 */

/**
 * Sitemap test case.
 */

require_once AIOSEOP_UNIT_TESTING_DIR . '/base/class-sitemap-test-base.php';

class Test_Sitemap extends Sitemap_Test_Base {

	/**
	 * @var array $_urls Stores the external pages that need to be added to the sitemap.
	 */
	private $_urls;

	public function setUp() {
		parent::init();
		parent::setUp();
	}

	public function tearDown() {
		parent::init();
		parent::tearDown();
	}

	/**
	 * Creates posts and pages and tests whether only pages are being shown in the sitemap.
	 */
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

	/**
	 * @requires PHPUnit 5.7
	 * Creates posts with and without featured images and tests whether the sitemap
	 * 1) contains the image tag in the posts that have images attached.
	 * 2) does not contain the image tag in the posts that do not have images attached.
	 */
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
					'image' => true,
				),
				$with[1] => array(
					'image' => true,
				),
				$without[0] => array(
					'image' => false,
				),
				$without[1] => array(
					'image' => false,
				),
			)
		);
	}

	/**
	 * @requires PHPUnit 5.7
	 * Creates posts with and without featured images and switches OFF the images from the sitemap. Tests that the sitemap does not contain the image tag for any post.
	 */
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
					'image' => false,
				),
				$with[1] => array(
					'image' => false,
				),
				$without[0] => array(
					'image' => false,
				),
				$without[1] => array(
					'image' => false,
				),
			)
		);
	}

	/**
	 * Testing post type archive pages.
	 *
	 * @ticket 155 XML Sitemap - Add support for post type archive pages and support to exclude them as well.
	 *
	 * @access public
	 * @dataProvider post_type_archive_pages_provider
	 */
	public function test_post_type_archive_pages( $post_types, $has_archive, $exclude ) {
		$tests = array();

		foreach( $post_types as $post_type ) {
			$ids		= array();
			if ( ! in_array( $post_type, array( 'post', 'page' ) ) ) {
				register_post_type( $post_type, array( 'has_archive' => $has_archive ) );
			}

			$ids	= $this->factory->post->create_many( 2, array( 'post_type' => $post_type ) );
			foreach ( $ids as $id ) {
				$tests[ get_permalink( $id ) ] = true;
			}
			$url = get_post_type_archive_link( $post_type );
			$tests[ $url ] = $has_archive && ! $exclude;
		}

		if ( $exclude ) {
			add_filter( 'aiosp_sitemap_include_post_types_archives', array( $this, 'filter_aiosp_sitemap_include_post_types_archives' ) );
		}

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = 'on';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_archive'] = 'on';
		$custom_options['aiosp_sitemap_posttypes'] = $post_types;

		$this->_setup_options( 'sitemap', $custom_options );

		$this->validate_sitemap( $tests );
	}

	/**
	 * Implements the filter 'aiosp_sitemap_include_post_types_archives'.
	 */
	public function filter_aiosp_sitemap_include_post_types_archives( $types ) {
		return array();
	}

	/**
	 * Provide the post types for testing test_post_type_archive_pages.
	 * 
	 * This will enable us to test these cases:
	 * 1) When a CPT post type is selected that DOES NOT support archives => only CPT in the sitemap.
	 * 2) When a CPT post type is selected that DOES support archives => CPT and CPT archives in the sitemap.
	 * 3) When a CPT post type is selected that DOES support archives and we exclude this => only CPT in the sitemap.
	 *
	 * @access public
	 */
	public function post_type_archive_pages_provider() {
		return array(
			array( array( 'xxxx' ), false, false ),
			array( array( 'xxxx' ), true, false ),
			array( array( 'xxxx' ), true, true ),
		);
	}
  
	/**
	 * Add WooCommerce product gallery images to XML sitemap.
	 *
	 * @ticket 366 Add WooCommerce product gallery images to XML sitemap
	 */
	public function test_woocommerce_gallery() {
		$woo = 'woocommerce/woocommerce.php';
		$file = dirname( dirname( AIOSEOP_UNIT_TESTING_DIR ) ) . '/';
		
		if ( ! file_exists( $file . $woo ) ) {
			$this->markTestSkipped( 'WooCommerce not installed. Skipping.' );
		}

		$this->plugin_to_load = $file . $woo;
		tests_add_filter( 'muplugins_loaded', array( $this, 'filter_muplugins_loaded' ) ) ;

		activate_plugin( $woo );

		if ( ! is_plugin_active( $woo ) ) {
			$this->markTestSkipped( 'WooCommerce not activated. Skipping.' );
		}

		// create 4 attachments.
		$attachments = array();
		for ( $x = 0; $x < 4; $x++ ) {
			$attachments[] = $this->upload_image_and_maybe_attach( str_replace( '\\', '/', AIOSEOP_UNIT_TESTING_DIR . '/resources/images/footer-logo.png' ) );
		}

		$id = $this->factory->post->create( array( 'post_type' => 'product' ) );
		update_post_meta( $id, '_product_image_gallery', implode( ',', $attachments ) );
		$url = get_permalink( $id );

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'product' );
 		$this->_setup_options( 'sitemap', $custom_options );
 		$this->validate_sitemap(
			array(
					$url => array(
						'image'	=> true,
					),
			)
		);
	}

	/**
	 * Adds posts to taxonomies, enables only taxonomies in the sitemap.
	 */
	public function test_only_taxonomies() {
		// create 3 categories.
		$test1 = wp_create_category( 'test1' );
		$test2 = wp_create_category( 'test2' );
		$test3 = wp_create_category( 'test3' );
		$ids = $this->factory->post->create_many( 10 );
		// first 3 to test1, next 3 to test2 and let others remain uncategorized.
		for ( $x = 0; $x < 3; $x++ ) {
			wp_set_post_categories( $ids[ $x ], $test1 );
		}
		for ( $x = 3; $x < 6; $x++ ) {
			wp_set_post_categories( $ids[ $x ], $test2 );
		}
		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_taxonomies'] = array( 'category' );
		$custom_options['aiosp_sitemap_posttypes'] = array();
		$this->_setup_options( 'sitemap', $custom_options );
		// in the sitemap, test3 should not appear as no posts have been assigned to it.
		$this->validate_sitemap(
			array(
					get_category_link( $test1 ) => true,
					get_category_link( $test2 ) => true,
					get_category_link( $test3 ) => false,
					get_category_link( 1 ) => true,
			)
		);
	}

	/**
	 * Loads the specified plugin.
	 */
	public function filter_muplugins_loaded() {
		require $this->plugin_to_load;
	}

	/**
	 * @requires PHPUnit 5.7
	 * Creates posts with schemeless images in the content and checks if they are being correctly included in the sitemap.
	 */
	public function test_schemeless_images() {
		$id1 = $this->factory->post->create( array( 'post_type' => 'post', 'post_content' => 'content <img src="http://example.org/image1.jpg">', 'post_title' => 'title with image' ) );
		$id2 = $this->factory->post->create( array( 'post_type' => 'post', 'post_content' => 'content <img src="//example.org/image2.jpg">', 'post_title' => 'title with image' ) );
		$id3 = $this->factory->post->create( array( 'post_type' => 'post', 'post_content' => 'content <img src="/image3.jpg">', 'post_title' => 'title with image' ) );
		$urls = array( get_permalink( $id1 ), get_permalink( $id2 ), get_permalink( $id3 ) );

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		$this->_setup_options( 'sitemap', $custom_options );

		$this->validate_sitemap(
			array(
					$urls[0] => array(
						'image'	=> true,
					),
					$urls[1] => array(
						'image'	=> true,
					),
					$urls[2] => array(
						'image'	=> true,
					),
			)
		);
	}
  
	/**
	 * Creates different types of posts, enables indexes and pagination and checks if the posts are being paginated correctly without additional/blank sitemaps.
	 * @requires PHPUnit 5.7
	 * @dataProvider enabledPostTypes
	 */
	public function test_sitemap_index_pagination( $enabled_post_type, $enabled_post_types_count, $cpt ) {
		// choose numbers which are not multiples of each other.
		$num_posts = 22;
		$per_xml = 7;

		if ( in_array( 'post', $enabled_post_type ) ) {
			$this->factory->post->create_many( $num_posts );
		}

		if ( in_array( 'page', $enabled_post_type ) ) {
			$this->factory->post->create_many( $num_posts, array( 'post_type' => 'page' ) );
		}

		if ( in_array( 'attachment', $enabled_post_type ) ) {
			$this->create_attachments( $num_posts );
		}

		if ( ! is_null( $cpt ) ) {
			register_post_type( $cpt );
			$this->factory->post->create_many( $num_posts, array( 'post_type' => $cpt ) );
		}

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = 'on';
		$custom_options['aiosp_sitemap_max_posts'] = $per_xml;
		$custom_options['aiosp_sitemap_images'] = 'on';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = $enabled_post_type;
		$custom_options['aiosp_sitemap_taxonomies'] = array();

			$this->_setup_options( 'sitemap', $custom_options );

		// calculate the number of sitemaps expected in the index. The +1 is for the sitemap_addl.xml that includes the home page.
		$expected = intval( $enabled_post_types_count * ceil( $num_posts / $per_xml ) + 1 );
		$got = $this->count_sitemap_elements( array( '<sitemap>' ) );

		$this->assertEquals( $expected, $got['<sitemap>'] );
	}

	/**
	 * Add external URLs to the sitemap using the filter 'aiosp_sitemap_addl_pages_only'.
	 *
	 * @dataProvider externalPagesProvider
	 */
	public function test_add_external_urls( $url1, $url2 ) {
		$this->_urls = array( $url1, $url2 );

		$posts = $this->setup_posts( 2 );

		add_filter( 'aiosp_sitemap_addl_pages_only', array( $this, 'filter_aiosp_sitemap_addl_pages_only' ) );

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		$this->_setup_options( 'sitemap', $custom_options );

		$without = $posts['without'];
		$this->validate_sitemap(
			array(
				$without[0] => true,
				$without[1] => true,
				$url1['loc'] => true,
				$url2['loc'] => true,
			)
		);
	}

	/**
	 * @requires PHPUnit 5.7
	 * Enables indexes and tests that the index and individual sitemaps are all valid according to the schema.
	 *
	 * @ticket 1371 Correct tags order according to Sitemap protocol
	 */
	public function test_index() {
		$posts = $this->setup_posts( 2, 2 );

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = 'on';
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		$this->_setup_options( 'sitemap', $custom_options );

		$this->validate_sitemap_index( array( 'post' ) );
	}

	/**
	 * Returns the urls to be added to the sitemap.
	 */
	public function filter_aiosp_sitemap_addl_pages_only() {
		return $this->_urls;
	}

	/**
	 * Provides the external pages that need to be added to the sitemap.
	 */
	public function externalPagesProvider() {
		return array(
			array(
				array(
					'loc'        => 'http://www.one.com',
					'lastmod'    => '2018-01-18T21:46:44Z',
					'changefreq' => 'daily',
					'priority'   => '1.0',
				),
				array(
					'loc'        => 'http://www.two.com',
					'lastmod'    => '2018-01-18T21:46:44Z',
					'changefreq' => 'daily',
					'priority'   => '1.0',
				),
			),
		);
	}
  
	/**
	 * Creates posts with external images and uses the filter 'aioseop_images_allowed_from_hosts' to allow only a particular host's images to be included in the sitemap.
	 */
	public function test_external_images() {
		$posts = $this->setup_posts( 2 );

		$id1 = $this->factory->post->create( array( 'post_type' => 'post', 'post_content' => 'content <img src="http://www.x.com/image.jpg">', 'post_title' => 'title with image' ) );
		$id2 = $this->factory->post->create( array( 'post_type' => 'post', 'post_content' => 'content <img src="http://www.y.com/image.jpg">', 'post_title' => 'title with image' ) );
		$posts['with'] = array( get_permalink( $id1 ), get_permalink( $id2 ) );

		// allow only www.x.com.
		add_filter( 'aioseop_images_allowed_from_hosts', array( $this, 'filter_aioseop_images_allowed_from_hosts' ) );

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

	/**
	 * Implements the filter 'aioseop_images_allowed_from_hosts' to allow speficic hosts.
	 */
	public function filter_aioseop_images_allowed_from_hosts( $hosts ) {
		$hosts[] = 'www.x.com';
		return $hosts;
	}

	/**
	 * Provides posts types to test test_sitemap_index_pagination against.
	 */
	public function enabledPostTypes() {
		return array(
			array( array( 'post' ), 1, null ),
			array( array( 'post', 'page' ), 2, null ),
			array( array( 'product' ), 1, 'product' ),
			array( array( 'attachment', 'product' ), 2, 'product' ),
			array( array( 'all', 'post', 'page' ), 2, null ),
			array( array( 'all', 'post', 'page', 'attachment', 'product' ), 4, 'product' ),
		);
	}
}