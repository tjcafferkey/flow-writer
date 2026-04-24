<?php
/**
 * Class EngineIntegrationTest
 *
 * @package Wp_flow_writer
 */

use FlowWriter\Engine;
use FlowWriter\Settings_Utils;
use FlowWriter\Tests\Mock_AI_Client;

/**
 * Integration tests for Engine logic.
 */
class EngineIntegrationTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		Mock_AI_Client::$mock_response = "TITLE: Mock Engine Title\n\nMock Content";
		Settings_Utils::upsert_setting( 'connector_id', 'mock-connector' );
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test that the engine generates content correctly.
	 */
	public function test_engine_prompt_composition() {
		// Set length to short
		Settings_Utils::upsert_setting( 'post_length', 'short' );
		Settings_Utils::upsert_setting( 'global_context', 'You are a test.' );

		$result = Engine::generate_content( 'This is a test category prompt.' );

		$this->assertEquals( "TITLE: Mock Engine Title\n\nMock Content", $result );
	}
}
