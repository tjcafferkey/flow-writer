<?php
/**
 * Base AI Content generation class.
 *
 * @package FlowWriter
 */

namespace FlowWriter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract class to establish our Generator pattern for extendability.
 */
abstract class AI_Content_Base {

	/**
	 * Run the content generation workflow.
	 *
	 * @return int|\WP_Error|false The post ID on success, WP_Error on failure, or false if generation failed.
	 */
	public function generate() {
		$target_term = $this->select_term();
		if ( ! $target_term ) {
			return false;
		}

		$prompt_context = $this->get_prompt_context( $target_term );
		$result         = Engine::generate_content( $prompt_context );

		if ( ! empty( $result ) ) {
			$title   = null;
			$content = $result;

			// Parse AI-generated title from the response.
			if ( preg_match( '/^TITLE:\s*(.+)$/m', $result, $matches ) ) {
				$title   = trim( $matches[1] );
				$content = trim( preg_replace( '/^TITLE:\s*.+$/m', '', $result ) );
			}

			return $this->insert_post( $content, $target_term, $title );
		}

		return false;
	}

	/**
	 * Abstract method to select a specific term (e.g., category).
	 *
	 * @return \WP_Term|false
	 */
	abstract protected function select_term();

	/**
	 * Abstract method to retrieve context for prompt.
	 *
	 * @param \WP_Term $term The selected term.
	 * @return string
	 */
	abstract protected function get_prompt_context( $term );

	/**
	 * Abstract method to insert the generated content.
	 *
	 * @param string      $content Generated content.
	 * @param \WP_Term    $term    The term context.
	 * @param string|null $title Optional AI-generated title.
	 * @return int|\WP_Error
	 */
	abstract protected function insert_post( $content, $term, $title = null );
}
