<?php
/**
 * Bulk Processor
 * 
 * Handles bulk operations on UTM links - generate, refresh, or delete for all posts
 * Processes in batches to avoid timeouts on large sites
 * 
 * @package Smart_UTM_Builder
 * @author Roham Parsa
 */

// Don't allow direct access - we're not running a public API here
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processes bulk UTM operations
 * 
 * Handles generating, refreshing, or deleting UTM links for all posts/pages at once
 * Uses batching to keep things manageable on large sites
 * 
 * Think of it as a factory worker that never complains about overtime
 */
class Smart_UTM_Bulk_Processor {

	/**
	 * Singleton instance
	 *
	 * @var Smart_UTM_Bulk_Processor
	 */
	private static $instance = null;

	/**
	 * How many posts to process at a time
	 * 
	 * 50 seems like a good balance - not too slow, not too memory-intensive
	 * Picked after extensive testing (and one server crash - oops!)
	 *
	 * @var int
	 */
	private $batch_size = 50;

	/**
	 * Get the singleton instance
	 *
	 * @return Smart_UTM_Bulk_Processor
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - set up AJAX handlers
	 */
	private function __construct() {
		add_action( 'wp_ajax_smart_utm_bulk_process', array( $this, 'ajax_process' ) );
		add_action( 'smart_utm_bulk_process', array( $this, 'process_batch' ) );
	}

	/**
	 * Process a bulk action across all posts
	 * 
	 * Supports three actions:
	 * - generate_all: Create UTM links for all posts that don't have them
	 * - refresh_all: Regenerate all existing UTM links (like hitting refresh, but for URLs)
	 * - delete_all: Remove all UTM links (the nuclear option - use with caution!)
	 *
	 * @param string $action Action to perform
	 * @param array  $args Additional arguments (not used currently, but here for future expansion)
	 * @return array Result with success status and message
	 */
	public function process( string $action, array $args = array() ): array {
		// Figure out which post types to process
		// Because we're polite and respect user preferences
		$post_types = array();
		if ( get_option( 'smart_utm_include_posts', true ) ) {
			$post_types[] = 'post';
		}
		if ( get_option( 'smart_utm_include_pages', true ) ) {
			$post_types[] = 'page';
		}

		// Can't do anything if no post types are enabled
		// That's like trying to bake a cake with no ingredients
		if ( empty( $post_types ) ) {
			return array(
				'success' => false,
				'message' => __( 'No post types enabled.', 'smart-utm-builder' ),
			);
		}

		// Get all published posts/pages
		// Using 'fields' => 'ids' to save memory - we're eco-friendly like that
		$query_args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1, // Get everything - we're ambitious
			'fields'         => 'ids', // Just get IDs to save memory
		);

		$query = new WP_Query( $query_args );
		$post_ids = $query->posts;
		$total = count( $post_ids );

		if ( 0 === $total ) {
			// No posts? That's... actually fine. Some sites are just getting started
			return array(
				'success' => false,
				'message' => __( 'No posts found.', 'smart-utm-builder' ),
			);
		}

		// Process in batches to avoid timeouts
		// Because nobody likes a script that runs forever (except maybe that one cron job)
		$batches = array_chunk( $post_ids, $this->batch_size );
		$processed = 0;
		$generator = Smart_UTM_Generator::get_instance();

		foreach ( $batches as $batch ) {
			foreach ( $batch as $post_id ) {
				switch ( $action ) {
					case 'generate_all':
					case 'refresh_all':
						// Generate or regenerate UTM links
						// Like giving your URLs a makeover
						$generator->generate_for_post( $post_id );
						break;
					case 'delete_all':
						// Remove all UTM links
						// The "I've made a huge mistake" button
						delete_post_meta( $post_id, '_utm_link' );
						break;
				}
				$processed++;
			}
		}

		return array(
			'success'  => true,
			'message'  => sprintf(
				// translators: %1$d: processed count, %2$d: total count.
				__( 'Processed %1$d of %2$d posts.', 'smart-utm-builder' ),
				$processed,
				$total
			),
			'processed' => $processed,
			'total'     => $total,
		);
	}

	/**
	 * AJAX handler for bulk processing
	 * 
	 * Called from the admin dashboard when user clicks bulk action buttons
	 * Includes all the security checks because we're paranoid (and that's good!)
	 */
	public function ajax_process() {
		// Security check - verify the nonce
		// Because we don't trust anyone, not even ourselves
		check_ajax_referer( 'smart_utm_bulk', 'nonce' );

		// Permission check - only admins can do bulk operations
		// Because with great power comes great responsibility (and potential for chaos)
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'smart-utm-builder' ) ) );
		}

		$action = sanitize_text_field( $_POST['action_type'] ?? '' );
		if ( ! in_array( $action, array( 'generate_all', 'refresh_all', 'delete_all' ), true ) ) {
			// Invalid action? That's suspicious...
			wp_send_json_error( array( 'message' => __( 'Invalid action.', 'smart-utm-builder' ) ) );
		}

		$result = $this->process( $action );
		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Process a batch (for cron jobs)
	 * 
	 * This could be used for async processing via WP Cron if needed
	 * Currently not fully implemented but structure is here for future use
	 * 
	 * Left here for when we want to get fancy with background processing
	 *
	 * @param string $action Action to perform
	 */
	public function process_batch( string $action ) {
		// Placeholder for async batch processing
		// One day this will be amazing, today it just calls the main process function
		$this->process( $action );
	}
}
