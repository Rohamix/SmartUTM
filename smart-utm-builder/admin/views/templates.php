<?php
/**
 * Templates View
 *
 * @package Smart_UTM_Builder
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap smart-utm-templates">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div id="smart-utm-template-editor">
		<form id="smart-utm-template-form">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="utm_source"><?php esc_html_e( 'UTM Source', 'smart-utm-builder' ); ?></label>
					</th>
					<td>
						<input type="text" id="utm_source" name="utm_source" class="regular-text" placeholder="{source}">
						<p class="description">
							<?php esc_html_e( 'Available placeholders: {source}, {medium}, {category}, {year}, {post_slug}, {post_title}, {post_id}, {author}', 'smart-utm-builder' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="utm_medium"><?php esc_html_e( 'UTM Medium', 'smart-utm-builder' ); ?></label>
					</th>
					<td>
						<input type="text" id="utm_medium" name="utm_medium" class="regular-text" placeholder="{medium}">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="utm_campaign"><?php esc_html_e( 'UTM Campaign', 'smart-utm-builder' ); ?></label>
					</th>
					<td>
						<input type="text" id="utm_campaign" name="utm_campaign" class="regular-text" placeholder="{category}_{year}">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="utm_content"><?php esc_html_e( 'UTM Content', 'smart-utm-builder' ); ?></label>
					</th>
					<td>
						<input type="text" id="utm_content" name="utm_content" class="regular-text" placeholder="{post_slug}">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="utm_term"><?php esc_html_e( 'UTM Term (Optional)', 'smart-utm-builder' ); ?></label>
					</th>
					<td>
						<input type="text" id="utm_term" name="utm_term" class="regular-text">
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Save Template', 'smart-utm-builder' ); ?>
				</button>
			</p>
		</form>
	</div>
</div>

