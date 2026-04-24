<?php
/**
 * Flow Writer Engine class.
 *
 * @package FlowWriter
 */

namespace FlowWriter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI Content generation engine.
 */
class Engine {

	/**
	 * Generates content using the configured AI connector.
	 *
	 * @param string $category_prompt The prompt context specific to the category.
	 * @return string|\WP_Error|false The generated content on success, WP_Error on failure, or false if connector not configured.
	 */
	public static function generate_content( $category_prompt ) {
		$global_context = Settings_Utils::get_setting( 'global_context', '' );
		$connector_id   = Settings_Utils::get_setting( 'connector_id', '' );

		if ( empty( $connector_id ) ) {
			return false;
		}

		$post_length        = Settings_Utils::get_setting( 'post_length', 'regular' );
		$length_instruction = '';

		switch ( $post_length ) {
			case 'short':
				$length_instruction = 'The post should be short, around 500–2,000 characters (roughly 80–350 words).';
				break;
			case 'long':
				$length_instruction = 'The post should be long and in-depth, around 7,000–20,000 characters (roughly 1,200–3,500 words).';
				break;
			case 'regular':
			default:
				$length_instruction = 'The post should be a standard length, around 2,000–7,000 characters (roughly 350–1,200 words).';
				break;
		}

		$composed_prompt = $category_prompt . "\n\n" . $length_instruction . "\n\nOutput strictly in WordPress Gutenberg block markup (<!-- wp:paragraph --> etc). Do not wrap in markdown code blocks.";

		try {
			if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
				return false;
			}

			// Connectors API - WP 7.0+.
			$client = \wp_ai_client_prompt( $composed_prompt )
				->using_provider( $connector_id )
				->using_system_instruction( $global_context );

			$result = $client->generate_text();

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			// Basic cleanup if the model still surrounds with markdown block.
			$result = preg_replace( '/^```(?:html|gutenberg)?\s*/i', '', $result );
			$result = preg_replace( '/```\s*$/i', '', $result );

			return trim( $result );
		} catch ( \Exception $e ) {
			return false;
		}
	}
}
