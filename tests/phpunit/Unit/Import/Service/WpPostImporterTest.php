<?php # -*- coding: utf-8 -*-

namespace W2M\Test\Unit\Import\Service;

use Brain;
use W2M\Import\Service;
use W2M\Test\Helper;

class WpPostImporterTest extends Helper\MonkeyTestCase {

	private $fs_helper;

	/**
	 * runs before each test
	 */
	public function setUp() {

		if ( ! $this->fs_helper ) {
			$this->fs_helper = new Helper\FileSystem;
		}

		parent::setUp();

		/**
		 * Just create some mocks of these types to avoid
		 * error messages like this
		 * https://github.com/sebastianbergmann/phpunit-mock-objects/issues/273
		 * when mocking objects that type hint WP core components
		 */
		$this->getMock( 'WP_Post' );
	}

	/**
	 * @group import_post
	 */
	public function test_import_post() {

		/**
		 * Create mocks for the dependency of the testee (WpPostImporter)
		 */
		$translation_connector_mock = $this->getMockBuilder( 'W2M\Import\Module\TranslationConnectorInterface' )
		                                   ->getMock();

		$id_mapper_mock = $this->mock_builder->data_multi_type_id_mapper();

		$testee = new Service\WpPostImporter( $translation_connector_mock, $id_mapper_mock );

		$post_mock = $this->getMockBuilder( 'W2M\Import\Type\ImportPostInterface' )
		                  ->getMock();

		$postmeta_mock_single = $this->mock_builder->type_wp_import_meta();
		$postmeta_mock_single->method( 'key' )->willReturn( 'mocky' );
		$postmeta_mock_single->method( 'value' )->willReturn( 'mocky' );
		$postmeta_mock_single->method( 'is_single' )->willReturn( TRUE );

		$postmeta_mock_array = $this->mock_builder->type_wp_import_meta();
		$postmeta_mock_array->method( 'key' )->willReturn( 'mocky' );
		$postmeta_mock_array->method( 'value' )->willReturn( array( 'mocky', 'mreed' ) );
		$postmeta_mock_array->method( 'is_single' )->willReturn( FALSE );

		$term_mock = $this->mock_builder->type_wp_term_reference();
		$term_mock->method( 'origin_id' )->willReturn( 113 );
		$term_mock->method( 'taxonomy' )->willReturn( 'category' );

		/**
		 * Now define the behaviour of the mock object. Each of the specified
		 * methods ( @see ImportPostInterface ) should return a proper value!
		 */
		$postdata = array(
			'title'                 => 'Mocky test fight',
			'origin_author_id'      => 42,
			'status'                => 'draft',
			'guid'                  => 'mocky',
			'date'                  => ( new \DateTime( 'NOW' ) )->format( 'Y-m-d H:i:s' ),
			'comment_status'        => 'open',
			'ping_status'           => 'open',
			'type'                  => 'post',
			'excerpt'               => 'Mocky the fighter',
			'content'               => 'Mock will go for a greate fight.',
			'name'                  => 'mocky',
			'origin_parent_post_id' => 42,
			'menu_order'            => 1,
			'password'              => 'mocky',
			'is_sticky'             => FALSE,
			'origin_link'           => 'http://wpml2mlp.test/mocky',
			'terms'                 => array( $term_mock ),
			'meta'                  => array( $postmeta_mock_single, $postmeta_mock_array )
		);

		$new_parent_id = 15;

		$post = array(
			'post_title'     => $postdata[ 'title' ],
			'post_author'    => $postdata[ 'origin_author_id' ],
			'post_status'    => $postdata[ 'status' ],
			'guid'           => $postdata[ 'guid' ],
			'post_date_gmt'  => $postdata[ 'date' ],
			'comment_status' => $postdata[ 'comment_status' ],
			'ping_status'    => $postdata[ 'ping_status' ],
			'post_type'      => $postdata[ 'type' ],
			'post_excerpt'   => $postdata[ 'excerpt' ],
			'post_content'   => $postdata[ 'content' ],
			'post_name'      => $postdata[ 'name' ],
			'post_parent'    => $new_parent_id,
			'menu_order'     => $postdata[ 'menu_order' ],
			'post_password'  => $postdata[ 'password' ]
		);

		$id_mapper_mock->expects( $this->atLeast( 1 ) )
		               ->method( 'local_id' )
		               ->with( 'post', $postdata[ 'origin_parent_post_id' ] )
		               ->willReturn( $new_parent_id );


		foreach ( $postdata as $method => $return_value ) {

			$post_mock->expects( $this->atLeast( 1 ) )
			          ->method( $method )
			          ->willReturn( $return_value );

		}

		$post_id = 3;

		Brain\Monkey\Functions::expect( 'wp_insert_post' )
		                      ->atLeast()
		                      ->once()
		                      ->with(
			                      $post,
			                      TRUE
		                      )
		                      ->andReturn( $post_id );

		Brain\Monkey\Functions::when( 'is_wp_error' )
		                      ->justReturn( FALSE );

		$post_return = array(
			'ID'                    => $post_id,
			'to_ping'               => FALSE,
			'pinged'                => FALSE,
			'post_content_filtered' => FALSE,
			'post_mime_type'        => FALSE,
			'comment_count'         => 0,
			'filter'                => 'raw',
			'ancestors'             => array( 42 ),
			'post_category'         => array( 1 ),
			'tags_input'            => array()
		);

		$post_return = array_merge( $post, $post_return );

		Brain\Monkey\Functions::expect( 'get_post' )
		                      ->atLeast()
		                      ->once()
		                      ->with( $post_id )
		                      ->andReturn( $post_return );



		Brain\Monkey\Functions::expect( 'wp_set_post_terms' )->once();

		Brain\Monkey\Functions::expect( 'update_post_meta' )->times( 2 );
		Brain\Monkey\Functions::expect( 'add_post_meta' )->twice();


		#/**
		# * Remove this line when the test is completely configured.
		# * Currently the missing mock of wp_insert_post() lets the test
		# * ends in a fatal error.
		# */
		#$this->markTestIncomplete( 'Under Construction' );
		$testee->import_post( $post_mock );

	}

}
