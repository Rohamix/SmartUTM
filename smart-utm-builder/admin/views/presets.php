<?php
/**
 * Presets View
 *
 * @package Smart_UTM_Builder
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap smart-utm-presets">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="smart-utm-presets-list" id="smart-utm-presets-list">
		<!-- Presets will be loaded here via JavaScript -->
	</div>

	<div class="smart-utm-preset-editor" id="smart-utm-preset-editor" style="display: none;">
		<h2 id="smart-utm-preset-editor-title"><?php esc_html_e( 'Add New Preset', 'smart-utm-builder' ); ?></h2>
		<form id="smart-utm-preset-form">
			<input type="hidden" id="preset_id" name="preset_id">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="preset_name"><?php esc_html_e( 'Preset Name', 'smart-utm-builder' ); ?></label>
					</th>
					<td>
						<input type="text" id="preset_name" name="preset_name" class="regular-text" required>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="preset_utm_source"><?php esc_html_e( 'UTM Source', 'smart-utm-builder' ); ?></label>
					</th>
					<td>
						<input type="text" id="preset_utm_source" name="utm_source" class="regular-text" required>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="preset_utm_medium"><?php esc_html_e( 'UTM Medium', 'smart-utm-builder' ); ?></label>
					</th>
					<td>
						<input type="text" id="preset_utm_medium" name="utm_medium" class="regular-text" required>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="preset_utm_campaign"><?php esc_html_e( 'UTM Campaign', 'smart-utm-builder' ); ?></label>
					</th>
					<td>
						<input type="text" id="preset_utm_campaign" name="utm_campaign" class="regular-text" required>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="preset_utm_content"><?php esc_html_e( 'UTM Content', 'smart-utm-builder' ); ?></label>
					</th>
					<td>
						<input type="text" id="preset_utm_content" name="utm_content" class="regular-text">
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Save Preset', 'smart-utm-builder' ); ?>
				</button>
				<button type="button" class="button" id="smart-utm-cancel-preset">
					<?php esc_html_e( 'Cancel', 'smart-utm-builder' ); ?>
				</button>
			</p>
		</form>
	</div>

	<p>
		<button type="button" class="button button-primary" id="smart-utm-add-preset">
			<?php esc_html_e( 'Add New Preset', 'smart-utm-builder' ); ?>
		</button>
	</p>
</div>

