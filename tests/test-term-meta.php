<?php
/**
 * Class TermMetaTest
 *
 * @package Wp_flow_writer
 */

use FlowWriter\Term_Meta;

/**
 * Integration tests for Term Meta logic.
 */
class TermMetaTest extends WP_UnitTestCase {

	/**
	 * Test saving category prompt.
	 */
	public function test_save_category_prompt() {
		// Set current user as admin for capability check
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// Create a term
		$term_id = $this->factory->term->create( array(
			'taxonomy' => 'category',
			'name'     => 'Meta Test Category',
		) );

		// Simulate POST request with valid nonce
		$_POST['flow_writer_category_prompt'] = 'This is a custom prompt for the category.';
		$_POST['_wpnonce']                         = wp_create_nonce( 'update-tag_' . $term_id );

		// Trigger save
		Term_Meta::save_category_fields( $term_id );

		// Verify meta was saved
		$saved_prompt = get_term_meta( $term_id, '_flow_writer_category_prompt', true );
		$this->assertEquals( 'This is a custom prompt for the category.', $saved_prompt );
	}
}
