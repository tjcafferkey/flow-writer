<?php
/**
 * Class SettingsUtilsTest
 *
 * @package FlowWriter
 */

use FlowWriter\Settings_Utils;

/**
 * Test the Settings_Utils class.
 */
class SettingsUtilsTest extends WP_UnitTestCase {

	/**
	 * Setup the test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		// Ensure the class is loaded.
		if ( ! class_exists( 'FlowWriter\Settings_Utils' ) ) {
			require_once dirname( __DIR__ ) . '/includes/class-settings-utils.php';
		}
		delete_option( Settings_Utils::OPTION_NAME );
	}

	/**
	 * Test get_settings returns empty array when no option exists.
	 */
	public function test_get_settings_empty() {
		$this->assertEquals( array(), Settings_Utils::get_settings() );
	}

	/**
	 * Test adding a setting.
	 */
	public function test_add_setting() {
		$this->assertTrue( Settings_Utils::add_setting( 'test_key', 'test_value' ) );
		$this->assertEquals( 'test_value', Settings_Utils::get_setting( 'test_key' ) );

		// Should fail if already exists.
		$this->assertFalse( Settings_Utils::add_setting( 'test_key', 'new_value' ) );
		$this->assertEquals( 'test_value', Settings_Utils::get_setting( 'test_key' ) );
	}

	/**
	 * Test updating a setting.
	 */
	public function test_update_setting() {
		// Should fail if doesn't exist.
		$this->assertFalse( Settings_Utils::update_setting( 'non_existent', 'value' ) );

		Settings_Utils::add_setting( 'test_key', 'old_value' );
		$this->assertTrue( Settings_Utils::update_setting( 'test_key', 'new_value' ) );
		$this->assertEquals( 'new_value', Settings_Utils::get_setting( 'test_key' ) );
	}

	/**
	 * Test upserting a setting.
	 */
	public function test_upsert_setting() {
		// Add.
		$this->assertTrue( Settings_Utils::upsert_setting( 'test_key', 'value1' ) );
		$this->assertEquals( 'value1', Settings_Utils::get_setting( 'test_key' ) );

		// Update.
		$this->assertTrue( Settings_Utils::upsert_setting( 'test_key', 'value2' ) );
		$this->assertEquals( 'value2', Settings_Utils::get_setting( 'test_key' ) );
	}

	/**
	 * Test get_setting with default value.
	 */
	public function test_get_setting_default() {
		$this->assertEquals( 'default', Settings_Utils::get_setting( 'non_existent', 'default' ) );
	}

	/**
	 * Test deleting a setting.
	 */
	public function test_delete_setting() {
		Settings_Utils::upsert_setting( 'test_key', 'value' );
		$this->assertEquals( 'value', Settings_Utils::get_setting( 'test_key' ) );

		$this->assertTrue( Settings_Utils::delete_setting( 'test_key' ) );
		$this->assertNull( Settings_Utils::get_setting( 'test_key' ) );

		// Deleting non-existent should return true.
		$this->assertTrue( Settings_Utils::delete_setting( 'non_existent' ) );
	}
	/**
	 * Test settings migration.
	 */
	public function test_migrate_settings() {
		update_option( 'flow_writer_connector_id', 'migrated-id' );
		update_option( 'flow_writer_frequency', 'daily' );

		Settings_Utils::migrate_settings();

		$this->assertEquals( 'migrated-id', Settings_Utils::get_setting( 'connector_id' ) );
		$this->assertEquals( 'daily', Settings_Utils::get_setting( 'frequency' ) );

		// Check that old options are deleted.
		$this->assertFalse( get_option( 'flow_writer_connector_id' ) );
		$this->assertFalse( get_option( 'flow_writer_frequency' ) );
	}
}
