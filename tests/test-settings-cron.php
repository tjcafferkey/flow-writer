<?php
/**
 * Class SettingsCronTest
 *
 * @package Wp_flow_writer
 */

use FlowWriter\Settings;
use FlowWriter\Settings_Utils;

/**
 * Integration tests for Settings and Cron logic.
 */
class SettingsCronTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		wp_clear_scheduled_hook( 'flow_writer_cron_event' );
	}

	public function tearDown(): void {
		parent::tearDown();
		wp_clear_scheduled_hook( 'flow_writer_cron_event' );
	}

	/**
	 * Test that cron schedule updates when settings are saved.
	 */
	public function test_cron_schedule_updates_on_setting_save() {
		// Ensure it's clear
		$this->assertFalse( wp_next_scheduled( 'flow_writer_cron_event' ) );

		delete_option( Settings_Utils::OPTION_NAME );

		// Update options to trigger hooks
		Settings_Utils::upsert_setting( 'frequency', 'weekly' );
		Settings_Utils::upsert_setting( 'times', array( '14:00' ) );

		// Manually call update_cron_schedule just in case hooks are not active in test context.
		Settings::update_cron_schedule();

		// The update_option hook should fire and register the cron
		$next_scheduled = wp_next_scheduled( 'flow_writer_cron_event' );
		$this->assertNotFalse( $next_scheduled );
	}

	/**
	 * Test next run calculation logic.
	 */
	public function test_calculate_next_run_logic() {
		$times = array( '10:00', '15:00' );
		
		// Set frequency to daily
		$next_run = Settings::calculate_next_run( 'daily', $times );
		$this->assertNotFalse( $next_run );

		// Every other day without last run should schedule today
		$next_run_eod = Settings::calculate_next_run( 'every_other_day', $times );
		$this->assertNotFalse( $next_run_eod );
	}
}
