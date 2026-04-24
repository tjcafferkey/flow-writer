<?php
/**
 * Plugin Settings class.
 *
 * @package FlowWriter
 */

namespace FlowWriter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin configuration and administration menu.
 */
class Settings {

	/**
	 * Initializes the Settings class by hooking into WordPress admin actions.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		// Hook into options update to handle cron.
		add_action( 'update_option_' . Settings_Utils::OPTION_NAME, array( __CLASS__, 'update_cron_schedule' ), 10, 3 );
		add_action( 'add_option_' . Settings_Utils::OPTION_NAME, array( __CLASS__, 'update_cron_schedule' ), 10, 3 );
	}

	/**
	 * Adds the plugin settings page to the WordPress admin menu.
	 */
	public static function add_settings_page() {
		add_options_page(
			'Flow Writer',
			'Flow Writer',
			'manage_options',
			'flow-writer',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Registers the plugin's settings with WordPress.
	 */
	public static function register_settings() {
		register_setting(
			'flow_writer_options',
			Settings_Utils::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Renders the HTML for the plugin settings page.
	 */
	public static function render_settings_page() {
		?>
		<div class="wrap">
			<h1>Flow Writer Settings</h1>
			<?php settings_errors(); ?>
			<form action="options.php" method="post">
				<?php settings_fields( 'flow_writer_options' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Connector ID</th>
						<td>
							<?php
							$saved_connector_id = Settings_Utils::get_setting( 'connector_id', '' );
							$connectors         = function_exists( 'wp_get_connectors' ) ? wp_get_connectors() : array();

							if ( class_exists( '\WordPress\AiClient\AiClient' ) ) {
								$registry = \WordPress\AiClient\AiClient::defaultRegistry();
								foreach ( $connectors as $id => $connector ) {
									try {
										if ( ! $registry->hasProvider( $id ) || ! $registry->isProviderConfigured( $id ) ) {
											unset( $connectors[ $id ] );
										}
									} catch ( \Exception $e ) {
										unset( $connectors[ $id ] );
									}
								}
							}

							if ( ! empty( $connectors ) ) :
								?>
								<select name="<?php echo esc_attr( Settings_Utils::OPTION_NAME ); ?>[connector_id]">
									<option value="">-- Select Connector --</option>
									<?php
									foreach ( $connectors as $id => $connector ) :
										$label = isset( $connector['name'] ) ? $connector['name'] : $id;
										?>
									<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $saved_connector_id, $id ); ?>>
										<?php echo esc_html( $label ); ?> (<?php echo esc_html( $id ); ?>)
									</option>
									<?php endforeach; ?>
								</select>
							<?php else : ?>
								<input type="text" name="<?php echo esc_attr( Settings_Utils::OPTION_NAME ); ?>[connector_id]" value="<?php echo esc_attr( $saved_connector_id ); ?>" class="regular-text" placeholder="e.g. openai, anthropic, google" />
								<p class="description">No connectors were auto-detected. Enter the connector ID manually (e.g. <code>openai</code>). Configure connectors at <a href="<?php echo esc_url( admin_url( 'options-connectors.php' ) ); ?>">Settings &rarr; Connectors</a>.</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Frequency</th>
						<td>
							<select name="<?php echo esc_attr( Settings_Utils::OPTION_NAME ); ?>[frequency]" id="flow_writer_frequency">
								<option value="" <?php selected( Settings_Utils::get_setting( 'frequency' ), '' ); ?>>Disabled</option>
								<option value="daily" <?php selected( Settings_Utils::get_setting( 'frequency' ), 'daily' ); ?>>Daily</option>
								<option value="every_other_day" <?php selected( Settings_Utils::get_setting( 'frequency' ), 'every_other_day' ); ?>>Every Other Day</option>
								<option value="weekly" <?php selected( Settings_Utils::get_setting( 'frequency' ), 'weekly' ); ?>>Weekly</option>
							</select>
						</td>
					</tr>
					<tr valign="top" id="cc-times-row" style="<?php echo empty( Settings_Utils::get_setting( 'frequency' ) ) ? 'display: none;' : ''; ?>">
						<th scope="row">Times of Day (Current time: <strong><?php echo esc_html( current_time( 'H:i' ) ); ?></strong>)</th>
						<td>
							<div id="cc-times-container">
								<?php
								$times = Settings_Utils::get_setting( 'times', array() );
								if ( ! is_array( $times ) ) {
									$times = array_filter( explode( ',', $times ) );
								}
								if ( empty( $times ) ) {
									$times = array( '' );
								}
								foreach ( $times as $time ) :
									?>
								<div class="cc-time-row" style="margin-bottom: 5px;">
									<input type="time" name="<?php echo esc_attr( Settings_Utils::OPTION_NAME ); ?>[times][]" value="<?php echo esc_attr( $time ); ?>" />
									<button type="button" class="button cc-remove-time">&times;</button>
								</div>
								<?php endforeach; ?>
							</div>
							<button type="button" class="button" id="cc-add-time">Add Time</button>
							<p class="description">Select the times of day you want posts to go live. Ensure your server cron is configured correctly.</p>
							<script>
								document.addEventListener('DOMContentLoaded', function() {
									const frequencySelect = document.getElementById('flow_writer_frequency');
									const timesRow = document.getElementById('cc-times-row');
									const container = document.getElementById('cc-times-container');

									if (frequencySelect) {
										frequencySelect.addEventListener('change', function() {
											if (this.value === '') {
												timesRow.style.display = 'none';
											} else {
												timesRow.style.display = '';
											}
										});
									}

									document.getElementById('cc-add-time').addEventListener('click', function() {
										const div = document.createElement('div');
										div.className = 'cc-time-row';
										div.style.marginBottom = '5px';
										div.innerHTML = '<input type="time" name="<?php echo esc_attr( Settings_Utils::OPTION_NAME ); ?>[times][]" value="" /> <button type="button" class="button cc-remove-time">&times;</button>';
										container.appendChild(div);
									});

									container.addEventListener('click', function(e) {
										if (e.target.classList.contains('cc-remove-time')) {
											if (container.querySelectorAll('.cc-time-row').length > 1) {
												e.target.closest('.cc-time-row').remove();
											} else {
												e.target.closest('.cc-time-row').querySelector('input').value = '';
											}
										}
									});
								});
							</script>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Post Status</th>
						<td>
							<select name="<?php echo esc_attr( Settings_Utils::OPTION_NAME ); ?>[status]">
								<option value="draft" <?php selected( Settings_Utils::get_setting( 'status' ), 'draft' ); ?>>Draft</option>
								<option value="publish" <?php selected( Settings_Utils::get_setting( 'status' ), 'publish' ); ?>>Publish</option>
								<option value="future" <?php selected( Settings_Utils::get_setting( 'status' ), 'future' ); ?>>Future</option>
								<option value="private" <?php selected( Settings_Utils::get_setting( 'status' ), 'private' ); ?>>Private</option>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Author</th>
						<td>
							<?php
							wp_dropdown_users(
								array(
									'name'     => Settings_Utils::OPTION_NAME . '[author]',
									'selected' => Settings_Utils::get_setting( 'author', get_current_user_id() ),
								)
							);
							?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Post Length</th>
						<td>
							<select name="<?php echo esc_attr( Settings_Utils::OPTION_NAME ); ?>[post_length]">
								<option value="short" <?php selected( Settings_Utils::get_setting( 'post_length' ), 'short' ); ?>>Short (500–2,000 chars)</option>
								<option value="regular" <?php selected( Settings_Utils::get_setting( 'post_length', 'regular' ), 'regular' ); ?>>Regular (2,000–7,000 chars)</option>
								<option value="long" <?php selected( Settings_Utils::get_setting( 'post_length' ), 'long' ); ?>>Long (7,000–20,000 chars)</option>
							</select>
							<p class="description">Select the target length for generated blog posts.</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Global Context</th>
						<td>
							<textarea name="<?php echo esc_attr( Settings_Utils::OPTION_NAME ); ?>[global_context]" rows="5" cols="50" class="large-text"><?php echo esc_textarea( Settings_Utils::get_setting( 'global_context', '' ) ); ?></textarea>
							<p class="description">Define the site-wide AI personality. E.g., "You are an expert copywriter for a tech blog."</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Updates the WordPress cron schedule for content generation based on settings.
	 */
	public static function update_cron_schedule() {
		wp_clear_scheduled_hook( 'flow_writer_cron_event' );

		$frequency = Settings_Utils::get_setting( 'frequency' );
		$times     = Settings_Utils::get_setting( 'times', array() );

		if ( empty( $frequency ) || empty( $times ) ) {
			return;
		}

		$next_run = self::calculate_next_run( $frequency, $times );
		if ( $next_run ) {
			wp_schedule_single_event( $next_run, 'flow_writer_cron_event' );
		}
	}

	/**
	 * Calculates the next run time for the content generation cron event.
	 *
	 * @param string       $frequency The frequency setting (daily, every_other_day, weekly).
	 * @param array|string $times     The scheduled times of day.
	 * @return int|false The next run timestamp in UTC, or false if no times scheduled.
	 */
	public static function calculate_next_run( $frequency, $times ) {
		if ( ! is_array( $times ) ) {
			$times = array_filter( explode( ',', $times ) );
		}

		if ( empty( $times ) ) {
			return false;
		}

		$local_now = time() + ( (int) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		$last_run  = (int) Settings_Utils::get_setting( 'last_run', 0 ); // This is also local time.

		$candidates    = array();
		$days_to_check = 14;

		for ( $i = 0; $i < $days_to_check; $i++ ) {
			$date_str = gmdate( 'Y-m-d', strtotime( "+$i days", $local_now ) );

			if ( ! self::is_valid_day( $date_str, $frequency, $last_run ) ) {
				continue;
			}

			foreach ( $times as $time ) {
				if ( empty( $time ) ) {
					continue;
				}
				$candidate = strtotime( $date_str . ' ' . $time );
				if ( $candidate > $local_now ) {
					$candidates[] = $candidate;
				}
			}

			if ( ! empty( $candidates ) ) {
				break;
			}
		}

		if ( empty( $candidates ) ) {
			return false;
		}

		sort( $candidates );
		$local_next_run = $candidates[0];

		// Convert local timestamp to UTC for wp_schedule_single_event..
		return $local_next_run - ( (int) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
	}

	/**
	 * Checks if a given date is valid according to the frequency and last run time.
	 *
	 * @param string $date_str  The date string to check (Y-m-d).
	 * @param string $frequency The frequency setting.
	 * @param int    $last_run  The timestamp of the last successful run.
	 * @return bool True if the day is valid for content generation, false otherwise.
	 */
	private static function is_valid_day( $date_str, $frequency, $last_run ) {
		if ( 'daily' === $frequency ) {
			return true;
		}

		if ( ! $last_run ) {
			return true;
		}

		$target_date   = strtotime( $date_str );
		$last_run_date = strtotime( gmdate( 'Y-m-d', $last_run ) );
		$days_diff     = ( $target_date - $last_run_date ) / DAY_IN_SECONDS;

		if ( 'every_other_day' === $frequency ) {
			return $days_diff >= 2;
		}

		if ( 'weekly' === $frequency ) {
			return $days_diff >= 7;
		}

		return false;
	}

	/**
	 * Sanitizes the settings array input from the settings page.
	 *
	 * @param array $input The raw settings array input.
	 * @return array The sanitized settings array.
	 */
	public static function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $input as $key => $value ) {
			if ( 'times' === $key ) {
				$sanitized[ $key ] = array_filter( array_map( 'sanitize_text_field', (array) $value ) );
			} else {
				$sanitized[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		return $sanitized;
	}
}
