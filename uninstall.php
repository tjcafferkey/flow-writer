<?php
/**
 * Flow Writer Uninstall
 *
 * This file is called when the plugin is uninstalled.
 * It deletes all plugin settings and metadata.
 *
 * @package FlowWriter
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete the main settings option and clear cron.
 */
delete_option( 'flow_writer_settings' );
wp_clear_scheduled_hook( 'flow_writer_cron_event' );

/**
 * Delete all term meta associated with the plugin.
 */
delete_metadata( 'term', 0, '_flow_writer_category_prompt', '', true );
