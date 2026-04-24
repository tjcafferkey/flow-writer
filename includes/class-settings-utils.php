<?php
/**
 * Settings Utilities class.
 *
 * @package FlowWriter
 */

namespace FlowWriter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides utility methods for managing plugin settings stored in a single option.
 */
class Settings_Utils {

	/**
	 * The name of the WordPress option where settings are stored.
	 */
	const OPTION_NAME = 'flow_writer_settings';

	/**
	 * Retrieves all settings.
	 *
	 * @return array The settings array.
	 */
	public static function get_settings() {
		$settings = get_option( self::OPTION_NAME, array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Retrieves a specific setting by key.
	 *
	 * @param string $key     The setting key.
	 * @param mixed  $default_value The default value if the setting is not found.
	 * @return mixed The setting value or default.
	 */
	public static function get_setting( $key, $default_value = null ) {
		$settings = self::get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default_value;
	}

	/**
	 * Adds a new setting. Fails if the setting already exists.
	 *
	 * @param string $key   The setting key.
	 * @param mixed  $value The setting value.
	 * @return bool True on success, false if the setting exists or update failed.
	 */
	public static function add_setting( $key, $value ) {
		$settings = self::get_settings();
		if ( isset( $settings[ $key ] ) ) {
			return false;
		}
		$settings[ $key ] = $value;
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Updates an existing setting. Fails if the setting does not exist.
	 *
	 * @param string $key   The setting key.
	 * @param mixed  $value The setting value.
	 * @return bool True on success, false if the setting does not exist or update failed.
	 */
	public static function update_setting( $key, $value ) {
		$settings = self::get_settings();
		if ( ! isset( $settings[ $key ] ) ) {
			return false;
		}
		$settings[ $key ] = $value;
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Upserts a setting (adds if not exists, updates if it does).
	 *
	 * @param string $key   The setting key.
	 * @param mixed  $value The setting value.
	 * @return bool True on success, false on failure.
	 */
	public static function upsert_setting( $key, $value ) {
		$settings         = self::get_settings();
		$settings[ $key ] = $value;
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Deletes a setting.
	 *
	 * @param string $key The setting key.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_setting( $key ) {
		$settings = self::get_settings();
		if ( ! isset( $settings[ $key ] ) ) {
			return true; // Already gone.
		}
		unset( $settings[ $key ] );
		return update_option( self::OPTION_NAME, $settings );
	}
}
