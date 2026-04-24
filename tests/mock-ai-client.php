<?php
namespace FlowWriter\Tests {

	/**
	 * Mock AI Client for testing.
	 */
	class Mock_AI_Client {
		public $prompt;
		public $provider;
		public $system_instruction;
		
		/**
		 * @var string|\WP_Error Response to return when generate_text is called.
		 */
		public static $mock_response = "TITLE: Mock Title\n\nMock Content";

		public function __construct( $prompt ) {
			$this->prompt = $prompt;
		}

		public function using_provider( $provider ) {
			$this->provider = $provider;
			return $this;
		}

		public function using_system_instruction( $instruction ) {
			$this->system_instruction = $instruction;
			return $this;
		}

		public function generate_text() {
			return self::$mock_response;
		}
	}

}

namespace {
	// If the function doesn't exist (e.g. wp-env doesn't have the AI plugin), define it for tests.
	if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
		function wp_ai_client_prompt( $prompt ) {
			return new \FlowWriter\Tests\Mock_AI_Client( $prompt );
		}
	}

	if ( ! function_exists( 'wp_get_connectors' ) ) {
		function wp_get_connectors() {
			return array(
				'mock-connector' => array(
					'name' => 'Mock Connector',
				),
			);
		}
	}
}
