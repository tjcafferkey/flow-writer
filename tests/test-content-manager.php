<?php
/**
 * Class ContentManagerTest
 *
 * @package Wp_flow_writer
 */

use FlowWriter\Tests\Mock_AI_Client;
use FlowWriter\Content_Manager;
use FlowWriter\Settings_Utils;

/**
 * Integration tests for Content Manager.
 */
class ContentManagerTest extends WP_Ajax_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		Mock_AI_Client::$mock_response = "TITLE: Mock Title\n\nMock Content";
		Settings_Utils::upsert_setting( 'connector_id', 'mock-connector' );
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test generating post for a given term.
	 */
	public function test_generate_for_term_success() {
		// Create a test term
		$term_id = $this->factory->term->create( array(
			'taxonomy' => 'category',
			'name'     => 'Test Category',
		) );

		// Configure plugin options
		Settings_Utils::upsert_setting( 'status', 'draft' );
		$author_id = $this->factory->user->create( array( 'role' => 'author' ) );
		Settings_Utils::upsert_setting( 'author', $author_id );

		// Act
		$post_id = Content_Manager::generate_for_term( $term_id );

		// Assert
		$this->assertIsInt( $post_id );
		$this->assertGreaterThan( 0, $post_id );

		$post = get_post( $post_id );
		$this->assertNotNull( $post );
		$this->assertEquals( 'Mock Title', $post->post_title );
		$this->assertEquals( 'Mock Content', $post->post_content );
		$this->assertEquals( 'draft', $post->post_status );
		$this->assertEquals( $author_id, $post->post_author );

		// Check term
		$categories = wp_get_post_categories( $post_id );
		$this->assertContains( $term_id, $categories );
	}

	/**
	 * Test ajax generation endpoint success.
	 */
	public function test_ajax_generate_post_success() {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$term_id = $this->factory->term->create( array(
			'taxonomy' => 'category',
			'name'     => 'Ajax Category',
		) );

		$_POST['term_id'] = $term_id;
		$_POST['nonce']   = wp_create_nonce( 'flow_writer_admin' );
		$_POST['action']  = 'flow_writer_create_post';

		try {
			$this->_handleAjax( 'flow_writer_create_post' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Catch die.
		}

		$response = json_decode( $this->_last_response );
		$this->assertTrue( $response->success );
		$this->assertIsObject( $response->data );
		$this->assertTrue( property_exists( $response->data, 'post_id' ) );
		$this->assertTrue( property_exists( $response->data, 'edit_link' ) );
	}

	/**
	 * Test ajax generation endpoint without proper permissions.
	 */
	public function test_ajax_generate_post_unauthorized() {
		$subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$_POST['term_id'] = 1;
		$_POST['nonce']   = wp_create_nonce( 'flow_writer_admin' );
		$_POST['action']  = 'flow_writer_create_post';

		try {
			$this->_handleAjax( 'flow_writer_create_post' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Catch die.
		}

		$response = json_decode( $this->_last_response );
		$this->assertFalse( $response->success );
		$this->assertEquals( 'Unauthorized', $response->data );
	}
}
