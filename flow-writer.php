<?php
/**
 * Plugin Name: Flow Writer
 * Description: Automated content generation for WordPress 7.0+ using the native Connectors API.
 * Version: 1.0.0
 * Author: Tom Cafferkey
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flow-writer
 *
 * @package FlowWriter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Version Guard.
add_action( 'plugins_loaded', 'flow_writer_init' );

/**
 * Initializes the Flow Writer plugin.
 *
 * Checks for required capabilities and loads core files and classes.
 */
function flow_writer_init() {
	// Check for minimum viable setup for native API capability.
	if ( ! function_exists( 'wp_get_connectors' ) ) {
		add_action( 'admin_notices', 'flow_writer_version_notice' );
		return;
	}

	// Load core files.
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ai-content-base.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-settings-utils.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-settings.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-term-meta.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-engine.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-content-manager.php';

	// Initialize classes.
	\FlowWriter\Settings::init();
	\FlowWriter\Term_Meta::init();
	\FlowWriter\Content_Manager::init();

	// Increase default timeout for AI requests (WP 7.0+).
	add_filter(
		'wp_ai_client_default_request_timeout',
		function () {
			return 120; // 2 minutes
		}
	);
}

/**
 * Displays an admin notice if WordPress requirements are not met.
 */
function flow_writer_version_notice() {
	echo '<div class="error"><p>';
	esc_html_e( 'Flow Writer requires WordPress 7.0 or greater and the Connectors API to function properly.', 'flow-writer' );
	echo '</p></div>';
}

// Plugin deactivation hook.
register_deactivation_hook( __FILE__, 'flow_writer_deactivate' );
/**
 * Handles plugin deactivation.
 *
 * Clears any scheduled cron events for the plugin.
 */
function flow_writer_deactivate() {
	wp_clear_scheduled_hook( 'flow_writer_cron_event' );
}
