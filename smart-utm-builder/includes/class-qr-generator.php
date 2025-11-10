<?php
/**
 * QR Code Generator
 * 
 * Generates QR codes for UTM links
 * Useful for print campaigns and offline marketing
 * Uses Google Charts API (no external library needed)
 * 
 * @package Smart_UTM_Builder
 * @author Roham Parsa
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates QR codes for UTM links
 * 
 * Currently uses Google Charts API for simplicity
 * Could be upgraded to use a PHP library for more control
 */
class Smart_UTM_QR_Generator {

	/**
	 * Singleton instance
	 *
	 * @var Smart_UTM_QR_Generator
	 */
	private static $instance = null;

	/**
	 * QR code size in pixels
	 * 
	 * 256x256 is a good default - readable but not huge
	 *
	 * @var int
	 */
	private $size = 256;

	/**
	 * Get the singleton instance
	 *
	 * @return Smart_UTM_QR_Generator
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - set up AJAX handler
	 */
	private function __construct() {
		add_action( 'wp_ajax_smart_utm_generate_qr', array( $this, 'ajax_generate_qr' ) );
	}

	/**
	 * Generate a QR code URL for a given URL
	 * 
	 * Uses Google Charts API - simple and works well
	 * For production, you might want to use a PHP library for more control
	 *
	 * @param string $url URL to encode in QR code
	 * @param string $label Optional label (not currently used)
	 * @return string|false QR code image URL or false on failure
	 */
	public function generate( string $url, string $label = '' ) {
		// Google Charts API is free and doesn't require authentication
		$size = $this->size;
		$encoded_url = urlencode( $url );
		$qr_url = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encoded_url}";

		// Label support could be added here if needed
		// Would require a more advanced QR library

		return $qr_url;
	}

	/**
	 * Generate QR code and save it to uploads directory
	 * 
	 * Downloads the QR code image and saves it locally
	 * Useful if you want to serve it from your own server
	 *
	 * @param string $url URL to encode
	 * @param int    $post_id Post ID for filename
	 * @param string $preset_id Preset ID for filename
	 * @return string|false File URL or false on failure
	 */
	public function generate_and_save( string $url, int $post_id, string $preset_id ) {
		$qr_url = $this->generate( $url );

		if ( ! $qr_url ) {
			return false;
		}

		// Download the QR code image
		$response = wp_remote_get( $qr_url );
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$image_data = wp_remote_retrieve_body( $response );
		if ( ! $image_data ) {
			return false;
		}

		// Save to uploads directory
		$upload_dir = wp_upload_dir();
		$qr_dir     = $upload_dir['basedir'] . '/smart-utm-qr';
		if ( ! file_exists( $qr_dir ) ) {
			wp_mkdir_p( $qr_dir );
		}

		$filename = "qr-{$post_id}-{$preset_id}.png";
		$filepath = $qr_dir . '/' . $filename;

		if ( file_put_contents( $filepath, $image_data ) ) {
			return $upload_dir['baseurl'] . '/smart-utm-qr/' . $filename;
		}

		return false;
	}

	/**
	 * AJAX handler for QR code generation
	 * 
	 * Called from admin when user wants to generate a QR code
	 */
	public function ajax_generate_qr() {
		check_ajax_referer( 'smart_utm_qr', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'smart-utm-builder' ) ) );
		}

		$url      = esc_url_raw( $_POST['url'] ?? '' );
		$post_id  = absint( $_POST['post_id'] ?? 0 );
		$preset_id = sanitize_text_field( $_POST['preset_id'] ?? '' );

		if ( ! $url ) {
			wp_send_json_error( array( 'message' => __( 'Invalid URL.', 'smart-utm-builder' ) ) );
		}

		$qr_url = $this->generate( $url );
		if ( $qr_url ) {
			wp_send_json_success( array( 'qr_url' => $qr_url ) );
		}

		wp_send_json_error( array( 'message' => __( 'Failed to generate QR code.', 'smart-utm-builder' ) ) );
	}
}
