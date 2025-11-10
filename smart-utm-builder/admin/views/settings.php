<?php
/**
 * Settings View
 *
 * @package Smart_UTM_Builder
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle form submission.
if ( isset( $_POST['smart_utm_settings'] ) && check_admin_referer( 'smart_utm_settings' ) ) {
	update_option( 'smart_utm_enable_auto_generate', isset( $_POST['enable_auto_generate'] ) ? 1 : 0 );
	update_option( 'smart_utm_include_pages', isset( $_POST['include_pages'] ) ? 1 : 0 );
	update_option( 'smart_utm_include_posts', isset( $_POST['include_posts'] ) ? 1 : 0 );
	update_option( 'smart_utm_generate_on_publish', isset( $_POST['generate_on_publish'] ) ? 1 : 0 );

	// URL Shortener settings.
	update_option( 'smart_utm_shortener_service', sanitize_text_field( $_POST['shortener_service'] ?? '' ) );
	update_option( 'smart_utm_bitly_api_key', sanitize_text_field( $_POST['bitly_api_key'] ?? '' ) );
	update_option( 'smart_utm_rebrandly_api_key', sanitize_text_field( $_POST['rebrandly_api_key'] ?? '' ) );
	update_option( 'smart_utm_custom_shortener_endpoint', esc_url_raw( $_POST['custom_shortener_endpoint'] ?? '' ) );
	update_option( 'smart_utm_custom_shortener_api_key', sanitize_text_field( $_POST['custom_shortener_api_key'] ?? '' ) );

	// Analytics settings.
	update_option( 'smart_utm_ga4_property_id', sanitize_text_field( $_POST['ga4_property_id'] ?? '' ) );
	update_option( 'smart_utm_ga4_credentials', sanitize_textarea_field( $_POST['ga4_credentials'] ?? '' ) );

	echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'smart-utm-builder' ) . '</p></div>';
}
?>
<div class="wrap smart-utm-settings">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'smart_utm_settings' ); ?>

		<h2><?php esc_html_e( 'Auto-Generation Settings', 'smart-utm-builder' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Auto-Generation', 'smart-utm-builder' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_auto_generate" value="1" <?php checked( get_option( 'smart_utm_enable_auto_generate', true ) ); ?>>
						<?php esc_html_e( 'Automatically generate UTM links for posts and pages', 'smart-utm-builder' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Include Pages', 'smart-utm-builder' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="include_pages" value="1" <?php checked( get_option( 'smart_utm_include_pages', true ) ); ?>>
						<?php esc_html_e( 'Generate UTM links for pages', 'smart-utm-builder' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Include Posts', 'smart-utm-builder' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="include_posts" value="1" <?php checked( get_option( 'smart_utm_include_posts', true ) ); ?>>
						<?php esc_html_e( 'Generate UTM links for posts', 'smart-utm-builder' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Generate on Publish', 'smart-utm-builder' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="generate_on_publish" value="1" <?php checked( get_option( 'smart_utm_generate_on_publish', true ) ); ?>>
						<?php esc_html_e( 'Generate UTM links when posts/pages are published', 'smart-utm-builder' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'URL Shortener Settings', 'smart-utm-builder' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="shortener_service"><?php esc_html_e( 'Shortener Service', 'smart-utm-builder' ); ?></label>
				</th>
				<td>
					<select name="shortener_service" id="shortener_service">
						<option value=""><?php esc_html_e( 'None', 'smart-utm-builder' ); ?></option>
						<option value="bitly" <?php selected( get_option( 'smart_utm_shortener_service' ), 'bitly' ); ?>>
							<?php esc_html_e( 'Bitly', 'smart-utm-builder' ); ?>
						</option>
						<option value="rebrandly" <?php selected( get_option( 'smart_utm_shortener_service' ), 'rebrandly' ); ?>>
							<?php esc_html_e( 'Rebrandly', 'smart-utm-builder' ); ?>
						</option>
						<option value="custom" <?php selected( get_option( 'smart_utm_shortener_service' ), 'custom' ); ?>>
							<?php esc_html_e( 'Custom API', 'smart-utm-builder' ); ?>
						</option>
					</select>
				</td>
			</tr>
			<tr id="bitly-settings">
				<th scope="row">
					<label for="bitly_api_key"><?php esc_html_e( 'Bitly API Key', 'smart-utm-builder' ); ?></label>
				</th>
				<td>
					<input type="text" name="bitly_api_key" id="bitly_api_key" class="regular-text" value="<?php echo esc_attr( get_option( 'smart_utm_bitly_api_key', '' ) ); ?>">
				</td>
			</tr>
			<tr id="rebrandly-settings">
				<th scope="row">
					<label for="rebrandly_api_key"><?php esc_html_e( 'Rebrandly API Key', 'smart-utm-builder' ); ?></label>
				</th>
				<td>
					<input type="text" name="rebrandly_api_key" id="rebrandly_api_key" class="regular-text" value="<?php echo esc_attr( get_option( 'smart_utm_rebrandly_api_key', '' ) ); ?>">
				</td>
			</tr>
			<tr id="custom-shortener-settings">
				<th scope="row">
					<label for="custom_shortener_endpoint"><?php esc_html_e( 'Custom API Endpoint', 'smart-utm-builder' ); ?></label>
				</th>
				<td>
					<input type="url" name="custom_shortener_endpoint" id="custom_shortener_endpoint" class="regular-text" value="<?php echo esc_url( get_option( 'smart_utm_custom_shortener_endpoint', '' ) ); ?>">
				</td>
			</tr>
			<tr id="custom-shortener-api-key">
				<th scope="row">
					<label for="custom_shortener_api_key"><?php esc_html_e( 'Custom API Key', 'smart-utm-builder' ); ?></label>
				</th>
				<td>
					<input type="text" name="custom_shortener_api_key" id="custom_shortener_api_key" class="regular-text" value="<?php echo esc_attr( get_option( 'smart_utm_custom_shortener_api_key', '' ) ); ?>">
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Analytics Settings', 'smart-utm-builder' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="ga4_property_id"><?php esc_html_e( 'GA4 Property ID', 'smart-utm-builder' ); ?></label>
				</th>
				<td>
					<input type="text" name="ga4_property_id" id="ga4_property_id" class="regular-text" value="<?php echo esc_attr( get_option( 'smart_utm_ga4_property_id', '' ) ); ?>">
					<p class="description"><?php esc_html_e( 'Enter your Google Analytics 4 Property ID', 'smart-utm-builder' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="ga4_credentials"><?php esc_html_e( 'GA4 Credentials (JSON)', 'smart-utm-builder' ); ?></label>
				</th>
				<td>
					<textarea name="ga4_credentials" id="ga4_credentials" rows="5" class="large-text"><?php echo esc_textarea( get_option( 'smart_utm_ga4_credentials', '' ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Paste your Google Analytics service account credentials JSON', 'smart-utm-builder' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
		<input type="hidden" name="smart_utm_settings" value="1">
	</form>
</div>

<script>
jQuery(document).ready(function($) {
	function toggleShortenerSettings() {
		var service = $('#shortener_service').val();
		$('#bitly-settings, #rebrandly-settings, #custom-shortener-settings, #custom-shortener-api-key').hide();
		if (service === 'bitly') {
			$('#bitly-settings').show();
		} else if (service === 'rebrandly') {
			$('#rebrandly-settings').show();
		} else if (service === 'custom') {
			$('#custom-shortener-settings, #custom-shortener-api-key').show();
		}
	}
	$('#shortener_service').on('change', toggleShortenerSettings);
	toggleShortenerSettings();
});
</script>

