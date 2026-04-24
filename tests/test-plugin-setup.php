<?php
/**
 * Class PluginSetupTest
 *
 * @package Wp_flow_writer
 */

/**
 * Sample test case for checking if the plugin is loaded and options are working.
 */
class PluginSetupTest extends WP_UnitTestCase {

	/**
	 * Test if the plugin is loaded.
	 */
	public function test_plugin_is_loaded() {
		$this->assertTrue( function_exists( 'flow_writer_init' ) );
		$this->assertTrue( class_exists( '\FlowWriter\Settings' ) );
	}

	/**
	 * Test if we can save and retrieve options in a real DB.
	 */
	public function test_options_persistence() {
		\FlowWriter\Settings_Utils::upsert_setting( 'connector_id', 'test-id' );
		$this->assertEquals( 'test-id', \FlowWriter\Settings_Utils::get_setting( 'connector_id' ) );
	}

	/**
	 * Test if the engine can be initialized.
	 */
	public function test_engine_initialization() {
		$engine = new \FlowWriter\Engine();
		$this->assertInstanceOf( \FlowWriter\Engine::class, $engine );
	}
}
