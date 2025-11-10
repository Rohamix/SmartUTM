<?php
/**
 * Preset Manager
 * 
 * Handles marketing channel presets - predefined UTM configurations for different platforms
 * Each preset represents a marketing channel like Facebook, Instagram, Email, etc.
 * 
 * Think of presets as shortcuts - why configure the same thing 47 times?
 * 
 * @package Smart_UTM_Builder
 * @author Roham Parsa
 */

// Don't allow direct access - we're not that kind of plugin
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages channel presets
 * 
 * Presets make it easy to generate consistent UTM links for specific marketing channels
 * Users can create custom presets or use the defaults we provide
 * 
 * It's like having favorite recipes - you know they work, so why reinvent the wheel?
 */
class Smart_UTM_Preset_Manager {

	/**
	 * Singleton instance
	 *
	 * @var Smart_UTM_Preset_Manager
	 */
	private static $instance = null;

	/**
	 * Database option name where presets are stored
	 * 
	 * Stored in wp_options because that's where WordPress keeps all the "stuff"
	 *
	 * @var string
	 */
	private $option_name = 'smart_utm_presets';

	/**
	 * Get the singleton instance
	 *
	 * @return Smart_UTM_Preset_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - creates default presets if none exist
	 */
	private function __construct() {
		// Set up default presets on first run
		// Because empty presets are sad presets
		if ( false === get_option( $this->option_name ) ) {
			$this->init_default_presets();
		}
	}

	/**
	 * Create default presets for common marketing channels
	 * 
	 * These are sensible defaults that work for most use cases
	 * Users can modify or delete them as needed
	 * 
	 * We include Facebook, Instagram, Email, and Telegram because they're popular
	 * If your marketing channel isn't here, you can add it - we're not judging
	 */
	public function init_default_presets() {
		$default_presets = array(
			'facebook'  => array(
				'name'         => 'Facebook',
				'utm_source'   => 'facebook',
				'utm_medium'   => 'social',
				'utm_campaign' => '{post_slug}',
				'utm_content'  => '{author}',
			),
			'instagram' => array(
				'name'         => 'Instagram',
				'utm_source'   => 'instagram',
				'utm_medium'   => 'social',
				'utm_campaign' => '{category}',
				'utm_content'  => '{post_title}',
			),
			'email'     => array(
				'name'         => 'Email',
				'utm_source'   => 'newsletter',
				'utm_medium'   => 'email',
				'utm_campaign' => 'weekly_update',
				'utm_content'  => '{post_id}',
			),
			'telegram'  => array(
				'name'         => 'Telegram',
				'utm_source'   => 'telegram',
				'utm_medium'   => 'social',
				'utm_campaign' => '{category}',
				'utm_content'  => '{post_title}',
			),
		);

		update_option( $this->option_name, $default_presets );
	}

	/**
	 * Get all presets
	 * 
	 * Returns empty array if nothing found - because null is the enemy
	 *
	 * @return array All presets indexed by preset ID
	 */
	public function get_presets(): array {
		return get_option( $this->option_name, array() );
	}

	/**
	 * Get a specific preset by its ID
	 * 
	 * Returns null if not found - because sometimes things just don't exist
	 *
	 * @param string $preset_id The preset identifier (e.g., 'facebook', 'instagram')
	 * @return array|null Preset data or null if not found
	 */
	public function get_preset( string $preset_id ) {
		// Sanitize preset ID before lookup
		$preset_id = sanitize_key( $preset_id );
		$presets = $this->get_presets();
		return isset( $presets[ $preset_id ] ) ? $presets[ $preset_id ] : null;
	}

	/**
	 * Save or update a preset
	 * 
	 * If the preset ID already exists, it gets updated. Otherwise, a new one is created.
	 * It's like updating a contact vs. adding a new one - same function, different outcome
	 *
	 * @param string $preset_id Unique identifier for the preset
	 * @param array  $preset Preset configuration data
	 * @return bool True if saved successfully
	 */
	public function update_preset( string $preset_id, array $preset ): bool {
		// Sanitize preset ID to prevent injection
		$preset_id = sanitize_key( $preset_id );
		if ( empty( $preset_id ) ) {
			return false;
		}

		$presets = $this->get_presets();
		$presets[ $preset_id ] = $this->sanitize_preset( $preset );
		return update_option( $this->option_name, $presets );
	}

	/**
	 * Delete a preset
	 * 
	 * Note: This doesn't delete UTM links that were already generated with this preset
	 * Those links are like ghosts - they'll keep haunting your analytics forever
	 *
	 * @param string $preset_id Preset ID to delete
	 * @return bool True if deleted successfully, false if preset didn't exist
	 */
	public function delete_preset( string $preset_id ): bool {
		// Sanitize preset ID
		$preset_id = sanitize_key( $preset_id );
		if ( empty( $preset_id ) ) {
			return false;
		}

		$presets = $this->get_presets();
		if ( isset( $presets[ $preset_id ] ) ) {
			unset( $presets[ $preset_id ] );
			return update_option( $this->option_name, $presets );
		}
		// Preset doesn't exist? That's fine - can't delete what isn't there
		return false;
	}

	/**
	 * Clean preset data before saving
	 * 
	 * Removes any unwanted data and sanitizes everything
	 * Because we don't want bad data sneaking into our database
	 *
	 * @param array $preset Raw preset data from user input
	 * @return array Cleaned and sanitized preset data
	 */
	private function sanitize_preset( array $preset ): array {
		$sanitized = array();

		// Preset name is optional but nice to have
		// Like a name tag at a party - not required, but helpful
		if ( isset( $preset['name'] ) ) {
			$sanitized['name'] = sanitize_text_field( $preset['name'] );
		}

		// Only allow standard UTM parameter names
		// We're strict about this - no custom parameters allowed
		$allowed_keys = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term' );

		foreach ( $allowed_keys as $key ) {
			if ( isset( $preset[ $key ] ) ) {
				$sanitized[ $key ] = sanitize_text_field( $preset[ $key ] );
			}
		}

		return $sanitized;
	}
}
