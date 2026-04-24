<?php
/**
 * Content Manager class.
 *
 * @package FlowWriter
 */

namespace FlowWriter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages content generation workflows and AJAX/Cron integration.
 */
class Content_Manager extends AI_Content_Base {

	/**
	 * Initializes the Content Manager by hooking into WordPress actions.
	 */
	public static function init() {
		add_action( 'flow_writer_cron_event', array( __CLASS__, 'run_generation' ) );
		add_action( 'wp_ajax_flow_writer_create_post', array( __CLASS__, 'ajax_generate_post' ) );
	}

	/**
	 * Runs the scheduled content generation process.
	 */
	public static function run_generation() {
		Settings_Utils::upsert_setting( 'last_run', time() );

		$manager = new self();
		$manager->generate();

		// Schedule next run.
		Settings::update_cron_schedule();
	}

	/**
	 * Handles the AJAX request for generating a single post.
	 */
	public static function ajax_generate_post() {
		check_ajax_referer( 'flow_writer_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$term_id = isset( $_POST['term_id'] ) ? intval( $_POST['term_id'] ) : 0;
		if ( ! $term_id ) {
			wp_send_json_error( 'Invalid term ID' );
		}

		$post_id = self::generate_for_term( $term_id );

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			wp_send_json_success(
				array(
					'post_id'   => $post_id,
					'edit_link' => get_edit_post_link( $post_id, 'url' ),
				)
			);
		} else {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				if ( is_wp_error( $post_id ) ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( 'Flow Writer: Failed to generate post for term_id=' . $term_id . ' — ' . $post_id->get_error_message() );
				} else {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
					error_log( 'Flow Writer: Failed to generate post for term_id=' . $term_id . ' (result: ' . print_r( $post_id, true ) . ')' );
				}
			}
			$error_message = 'Failed to generate content. Please check your AI connector settings.';
			if ( is_wp_error( $post_id ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$error_message = $post_id->get_error_message();
			}
			wp_send_json_error( $error_message );
		}
	}

	/**
	 * Generates content for a specific term ID.
	 *
	 * @param int $term_id The ID of the term to generate content for.
	 * @return int|\WP_Error|false The post ID on success, WP_Error on failure, or false if generation failed.
	 */
	public static function generate_for_term( $term_id ) {
		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return false;
		}

		$manager        = new self();
		$prompt_context = $manager->get_prompt_context( $term );
		$result         = Engine::generate_content( $prompt_context );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! empty( $result ) ) {
			$title   = null;
			$content = $result;

			// Parse AI-generated title from the response.
			if ( preg_match( '/^TITLE:\s*(.+)$/m', $result, $matches ) ) {
				$title   = trim( $matches[1] );
				$content = trim( preg_replace( '/^TITLE:\s*.+$/m', '', $result ) );
			}

			return $manager->insert_post( $content, $term, $title );
		}

		return false;
	}

	/**
	 * Selects a random category term for scheduled content generation.
	 *
	 * @return \WP_Term|false The selected term object or false on failure.
	 */
	protected function select_term() {
		// Pick random term from public taxonomies.
		$taxonomies = get_taxonomies( array( 'public' => true ) );
		$terms      = get_terms(
			array(
				'taxonomy'   => array_values( $taxonomies ),
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return false;
		}

		$random_key = array_rand( $terms );
		return $terms[ $random_key ];
	}

	/**
	 * Constructs the prompt context for the AI engine based on the term.
	 *
	 * Builds a structured prompt that includes category context, term description,
	 * recent post titles for deduplication, and formatting/structure guidance.
	 *
	 * @param \WP_Term $term The term object to provide context for.
	 * @return string The constructed prompt context.
	 */
	protected function get_prompt_context( $term ) {
		$category_context = get_term_meta( $term->term_id, '_flow_writer_category_prompt', true );
		$blog_name        = get_bloginfo( 'name' );

		if ( empty( $category_context ) ) {
			$category_context = $term->name;
		}

		$prompt  = 'Write an original, well-researched blog post for the "' . $term->name . '" category';
		$prompt .= ! empty( $blog_name ) ? ' on the blog "' . $blog_name . "\".\n\n" : ".\n\n";

		// Category context and optional description.
		$prompt .= "## Category Context\n";
		$prompt .= $category_context . "\n";

		if ( ! empty( $term->description ) ) {
			$prompt .= 'Category description: ' . $term->description . "\n";
		}
		$prompt .= "\n";

		// Fetch recent posts to avoid duplicate topics and angles.
		$recent_posts = get_posts(
			array(
				'tax_query'      => array(
					array(
						'taxonomy' => $term->taxonomy,
						'field'    => 'term_id',
						'terms'    => $term->term_id,
					),
				),
				'posts_per_page' => 10,
				'post_status'    => array( 'publish', 'draft', 'future', 'private' ),
			)
		);

		if ( ! empty( $recent_posts ) ) {
			$prompt .= "## Previously Published Posts (avoid these topics but reference if possible)\n";
			foreach ( $recent_posts as $recent_post ) {
				$permalink = get_permalink( $recent_post->ID );
				$prompt   .= '- ' . $recent_post->post_title . ' (URL: ' . $permalink . ")\n";
			}
			$prompt .= "\nDo NOT cover the same topics, angles, or arguments as the posts above. Choose a fresh, distinct angle that has not been explored.\n";
			$prompt .= "If possible, reference at least 1 or maximum 2 of these recent posts in your new post. When referencing, you MUST add an HTML anchor link linking to the referenced recent post's URL.\n\n";
		}

		// Structure and quality guidance.
		$prompt .= "## Writing Guidelines\n";
		$prompt .= "- Choose a specific, focused topic within this category — do NOT write a generic overview of the category itself.\n";
		$prompt .= "- Open with a compelling introduction that hooks the reader.\n";
		$prompt .= "- Use subheadings (H2/H3) to break the post into clear, scannable sections.\n";
		$prompt .= "- Include actionable insights, concrete examples, or data points where relevant.\n";
		$prompt .= "- End with a conclusion that summarises key takeaways or provides a call to action.\n";
		$prompt .= "- Write in a natural, conversational tone — avoid filler, clichés, and AI-sounding phrases.\n\n";

		// Output format — placed prominently to reduce parsing failures.
		$prompt .= "## Output Format (MANDATORY)\n";
		$prompt .= "Your response MUST follow this exact structure:\n\n";
		$prompt .= "1. The FIRST line must be: TITLE: Your Post Title Here\n";
		$prompt .= "2. Leave one blank line after the title.\n";
		$prompt .= "3. Then write the full post body.\n\n";
		$prompt .= 'Do NOT include any preamble, commentary, or explanation outside this structure.';

		return $prompt;
	}

	/**
	 * Inserts a generated post into the database.
	 *
	 * @param string      $content The content of the post.
	 * @param \WP_Term    $term    The term context for the post.
	 * @param string|null $title   Optional. The title of the post.
	 * @return int|\WP_Error The post ID on success, or WP_Error on failure.
	 */
	protected function insert_post( $content, $term, $title = null ) {
		$author = Settings_Utils::get_setting( 'author', 1 );
		$status = Settings_Utils::get_setting( 'status', 'draft' );

		if ( empty( $title ) ) {
			$title = $term->name . ' - ' . current_time( 'Y-m-d H:i:s' );
		}

		$post_id = wp_insert_post(
			array(
				'post_title'   => sanitize_text_field( $title ),
				'post_content' => $content,
				'post_status'  => $status,
				'post_author'  => $author,
			)
		);

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			wp_set_post_terms( $post_id, array( $term->term_id ), $term->taxonomy );
		}

		return $post_id;
	}
}
