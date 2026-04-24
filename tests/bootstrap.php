<?php
/**
 * PHPUnit bootstrap file for Integration Tests
 */

// Load Composer autoloader
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * The WordPress testing framework requires a path to the WordPress tests directory.
 * Inside wp-env, this is typically /usr/src/wordpress-tests-lib.
 */
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php\n";
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Load mock AI client before plugin initializes
	require_once __DIR__ . '/mock-ai-client.php';
	require dirname( __DIR__ ) . '/flow-writer.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
