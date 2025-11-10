<?php
/**
 * Dashboard View
 *
 * @package Smart_UTM_Builder
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap smart-utm-dashboard">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="smart-utm-bulk-actions">
		<button type="button" class="button button-primary" id="smart-utm-generate-all">
			<?php esc_html_e( 'Generate All', 'smart-utm-builder' ); ?>
		</button>
		<button type="button" class="button" id="smart-utm-refresh-all">
			<?php esc_html_e( 'Refresh All', 'smart-utm-builder' ); ?>
		</button>
		<button type="button" class="button button-link-delete" id="smart-utm-delete-all">
			<?php esc_html_e( 'Delete All', 'smart-utm-builder' ); ?>
		</button>
	</div>

	<div class="smart-utm-filters">
		<input type="search" id="smart-utm-search" placeholder="<?php esc_attr_e( 'Search links...', 'smart-utm-builder' ); ?>" class="regular-text">
		<select id="smart-utm-filter-preset">
			<option value=""><?php esc_html_e( 'All Channels', 'smart-utm-builder' ); ?></option>
		</select>
	</div>

	<div id="smart-utm-table-container">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th class="sortable" data-sort="post_title">
						<?php esc_html_e( 'Post Title', 'smart-utm-builder' ); ?>
					</th>
					<th><?php esc_html_e( 'Original URL', 'smart-utm-builder' ); ?></th>
					<th><?php esc_html_e( 'UTM URL', 'smart-utm-builder' ); ?></th>
					<th class="sortable" data-sort="preset">
						<?php esc_html_e( 'Channel', 'smart-utm-builder' ); ?>
					</th>
					<th class="sortable" data-sort="created">
						<?php esc_html_e( 'Created Date', 'smart-utm-builder' ); ?>
					</th>
					<th><?php esc_html_e( 'Actions', 'smart-utm-builder' ); ?></th>
				</tr>
			</thead>
			<tbody id="smart-utm-links-tbody">
				<tr>
					<td colspan="6" class="loading">
						<?php esc_html_e( 'Loading...', 'smart-utm-builder' ); ?>
					</td>
				</tr>
			</tbody>
		</table>

		<div class="smart-utm-pagination"></div>
	</div>
</div>

