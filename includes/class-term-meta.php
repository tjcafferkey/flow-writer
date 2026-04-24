<?php
/**
 * Term Meta management class.
 *
 * @package FlowWriter
 */

namespace FlowWriter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles custom meta fields for terms (categories).
 */
class Term_Meta {

	/**
	 * Initializes the Term_Meta class by hooking into WordPress term actions.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_hooks' ) );
	}

	/**
	 * Registers hooks for all public taxonomies.
	 */
	public static function register_hooks() {
		// Get all taxonomies to ensure we don't miss any relevant ones.
		$taxonomies = get_taxonomies( array(), 'names' );

		foreach ( $taxonomies as $taxonomy ) {
			add_action( "{$taxonomy}_add_form_fields", array( __CLASS__, 'add_category_fields' ) );
			add_action( "{$taxonomy}_edit_form_fields", array( __CLASS__, 'edit_category_fields' ) );

			// Render the "Create post" button row inside the edit form's table.
			// Using priority 10 to ensure it appears before other 3rd party fields if possible, or 99 to be at the end.
			// Let's stick with 99 but ensure the callback is robust.
			add_action( "{$taxonomy}_edit_form_fields", array( __CLASS__, 'render_create_post_button' ), 99 );

			add_action( "created_{$taxonomy}", array( __CLASS__, 'save_category_fields' ) );
			add_action( "edited_{$taxonomy}", array( __CLASS__, 'save_category_fields' ) );

			// Add row action for quick generation from the list table.
			add_filter( "{$taxonomy}_row_actions", array( __CLASS__, 'add_row_actions' ), 10, 2 );
		}
	}

	/**
	 * Adds a "Create Post" action to the term row actions.
	 *
	 * @param array    $actions Existing row actions.
	 * @param \WP_Term $term    The term object.
	 * @return array Modified row actions.
	 */
	public static function add_row_actions( $actions, $term ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $actions;
		}

		$nonce = wp_create_nonce( 'flow_writer_admin' );
		$url   = '#';

		$actions['flow-writer-generate'] = sprintf(
			'<a href="%1$s" class="flow-writer-quick-generate" data-term-id="%2$d" data-nonce="%3$s">%4$s</a>',
			esc_url( $url ),
			intval( $term->term_id ),
			esc_attr( $nonce ),
			esc_html__( 'Create AI Post', 'flow-writer' )
		);

		// Ensure our JS is loaded on the list page.
		add_action( 'admin_footer', array( __CLASS__, 'render_js' ) );

		return $actions;
	}

	/**
	 * Renders the minimal JS needed for quick generation from the row actions.
	 */
	public static function render_js() {
		static $printed = false;
		if ( $printed ) {
			return;
		}
		$printed = true;
		?>
		<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			document.querySelectorAll('.flow-writer-quick-generate').forEach(function(link) {
				link.addEventListener('click', function(e) {
					e.preventDefault();
					const termId = this.dataset.termId;
					const nonce  = this.dataset.nonce;
					const parent = this.closest('.row-actions');
					
					if (this.classList.contains('working')) return;
					this.classList.add('working');
					this.style.opacity = '0.5';
					this.innerText = 'Generating...';

					const params = new URLSearchParams();
					params.append('action', 'flow_writer_create_post');
					params.append('term_id', termId);
					params.append('nonce', nonce);

					fetch(ajaxurl, { method: 'POST', body: params })
						.then(r => r.json())
						.then(res => {
							this.classList.remove('working');
							this.style.opacity = '1';
							if (res.success) {
								this.innerHTML = '<span style="color:#46b450;">Done!</span>';
							} else {
								this.innerHTML = '<span style="color:#dc3232;">Error</span>';
								alert(res.data || 'Error generating post');
							}
						})
						.catch(() => {
							this.classList.remove('working');
							this.style.opacity = '1';
							this.innerText = 'Failed';
						});
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Adds custom fields to the category creation form.
	 */
	public static function add_category_fields() {
		?>
		<div class="form-field">
			<label for="flow_writer_category_prompt">Context</label>
			<textarea name="flow_writer_category_prompt" id="flow_writer_category_prompt" rows="5" cols="40"></textarea>
			<p class="description">Provide specific instructions for AI when generating content for this category.</p>
		</div>
		<?php
	}

	/**
	 * Adds custom fields to the category edit form.
	 *
	 * @param \WP_Term $term The term object being edited.
	 */
	public static function edit_category_fields( $term ) {
		$prompt = get_term_meta( $term->term_id, '_flow_writer_category_prompt', true );
		?>
		<tr class="form-field">
			<th scope="row"><label for="flow_writer_category_prompt">Context</label></th>
			<td>
				<textarea name="flow_writer_category_prompt" id="flow_writer_category_prompt" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $prompt ); ?></textarea>
				<p class="description">Provide specific instructions for AI when generating content for this category.</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Saves the custom category fields when a term is created or edited.
	 *
	 * @param int $term_id The ID of the term being saved.
	 */
	public static function save_category_fields( $term_id ) {
		// Verify nonce for term saving.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'update-tag_' . $term_id ) ) {
			// Also check for 'add-tag' nonce if it's a new term.
			if ( ! isset( $_POST['_wpnonce_add-tag'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce_add-tag'] ), 'add-tag' ) ) {
				return;
			}
		}

		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}

		$tax = get_taxonomy( $term->taxonomy );
		if ( ! current_user_can( $tax->cap->edit_terms ) ) {
			return;
		}

		if ( isset( $_POST['flow_writer_category_prompt'] ) ) {
			update_term_meta( $term_id, '_flow_writer_category_prompt', sanitize_textarea_field( wp_unslash( $_POST['flow_writer_category_prompt'] ) ) );
		}
	}

	/**
	 * Renders the "Create post" button on the category edit page.
	 *
	 * @param \WP_Term $term The term object being edited.
	 */
	public static function render_create_post_button( $term ) {
		// Ensure $term is a valid object.
		if ( ! is_object( $term ) || ! isset( $term->taxonomy ) ) {
			return;
		}

		// Verify capability. Admins should always see this.
		$tax = get_taxonomy( $term->taxonomy );
		if ( ! $tax || ( ! current_user_can( 'manage_options' ) && ! current_user_can( $tax->cap->edit_terms ) ) ) {
			return;
		}

		// Only show when a connector is configured.
		$connector_id = Settings_Utils::get_setting( 'connector_id', '' );
		$nonce        = wp_create_nonce( 'flow_writer_admin' );
		?>
		<tr class="form-field" id="flow-writer-row">
			<th scope="row"><?php esc_html_e( 'AI Content', 'flow-writer' ); ?></th>
			<td>
				<?php if ( empty( $connector_id ) ) : ?>
					<div class="notice notice-warning inline" style="margin: 0 0 10px 0;">
						<p>
							<?php
							/* translators: %s: URL to the plugin settings page. */
							echo wp_kses_post( sprintf( __( 'Please <a href="%s">configure an AI connector</a> to enable on-demand post generation.', 'flow-writer' ), admin_url( 'options-general.php?page=flow-writer' ) ) );
							?>
						</p>
					</div>
					<button type="button" class="button button-secondary" disabled>
						<?php esc_html_e( 'Create post', 'flow-writer' ); ?>
					</button>
				<?php else : ?>
					<button type="button" id="flow-writer-generate" class="button button-primary" style="display: inline-flex; align-items: center; gap: 6px;">
						<?php esc_html_e( 'Create post', 'flow-writer' ); ?>
					</button>
				<?php endif; ?>
				<span class="spinner" id="flow-writer-spinner" style="float: none; margin: 0 4px; vertical-align: middle;"></span>
				<span id="flow-writer-status" style="vertical-align: middle;"></span>
				<p class="description"><?php esc_html_e( 'Generate an AI post for this term using the configured connector.', 'flow-writer' ); ?></p>
			</td>
		</tr>
		<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			const btn     = document.getElementById('flow-writer-generate');
			const spinner = document.getElementById('flow-writer-spinner');
			const status  = document.getElementById('flow-writer-status');

			if (!btn) return;

			btn.addEventListener('click', function(e) {
				e.preventDefault();

				if (btn.disabled) return;

				btn.disabled = true;
				spinner.classList.add('is-active');
				status.innerHTML = '';

				const params = new URLSearchParams();
				params.append('action', 'flow_writer_create_post');
				params.append('term_id', '<?php echo intval( $term->term_id ); ?>');
				params.append('nonce', '<?php echo esc_js( $nonce ); ?>');

				fetch(ajaxurl, {
					method: 'POST',
					body: params
				})
				.then(function(response) {
					if (!response.ok) {
						throw new Error('Network response was not ok');
					}
					return response.json();
				})
				.then(function(response) {
					btn.disabled = false;
					spinner.classList.remove('is-active');

					if (response.success) {
						status.innerHTML = '<span style="color:#46b450;">&#10003; Post created! <a href="' + response.data.edit_link + '" target="_blank">Edit post &rarr;</a></span>';
					} else {
						status.innerHTML = '<span style="color:#dc3232;">&#10005; ' + (response.data || 'Error generating post') + '</span>';
					}
				})
				.catch(function() {
					btn.disabled = false;
					spinner.classList.remove('is-active');
					status.innerHTML = '<span style="color:#dc3232;">&#10005; Request failed. Please try again.</span>';
				});
			});
		});
		</script>
		<?php
	}
}
