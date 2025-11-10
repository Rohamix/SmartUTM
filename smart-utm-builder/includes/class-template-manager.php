<?php
/**
 * Template Manager
 * 
 * Handles UTM templates - the patterns used to generate UTM parameters
 * Templates are stored in wp_options so they persist across sessions
 * 
 * Think of templates as cookie cutters - same shape, different cookies
 * 
 * @package Smart_UTM_Builder
 * @author Roham Parsa
 */

// Security check - don't let anyone call this directly
// We're not running a public API here, folks
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages UTM templates
 * 
 * Templates define the structure of UTM parameters using placeholders
 * like {source}, {medium}, {category}, etc.
 * 
 * It's like a form letter, but for URLs instead of spam
 */
class Smart_UTM_Template_Manager {

	/**
	 * Singleton instance - because one is enough
	 *
	 * @var Smart_UTM_Template_Manager
	 */
	private static $instance = null;

	/**
	 * Where we store templates in the database
	 * 
	 * Using wp_options because it's WordPress's designated "stuff storage" area
	 *
	 * @var string
	 */
	private $option_name = 'smart_utm_templates';

	/**
	 * Get the singleton instance
	 * 
	 * Creates one if it doesn't exist, returns existing one if it does
	 * Classic singleton pattern - keeps things tidy
	 *
	 * @return Smart_UTM_Template_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - sets up default template if needed
	 */
	private function __construct() {
		// If no template exists yet, create a default one
		// Because starting from scratch is hard, and we're nice like that
		if ( false === get_option( $this->option_name ) ) {
			$this->init_default_template();
		}
	}

	/**
	 * Create a default template that works out of the box
	 * 
	 * Users can customize this later, but at least they have something to start with
	 * It's like giving someone a starter kit instead of an empty box
	 */
	public function init_default_template() {
		$default_template = array(
			'utm_source'   => '{source}',
			'utm_medium'   => '{medium}',
			'utm_campaign' => '{category}_{year}',
			'utm_content'  => '{post_slug}',
		);

		update_option( $this->option_name, $default_template );
	}

	/**
	 * Get the current template
	 * 
	 * Returns empty array if nothing found - because null is scary
	 *
	 * @return array Template array with UTM parameter patterns
	 */
	public function get_template() {
		return get_option( $this->option_name, array() );
	}

	/**
	 * Save a new template
	 * 
	 * Sanitizes everything before saving to prevent any nastiness
	 * Because we don't want bad data sneaking into our database
	 *
	 * @param array $template Template array with UTM parameter patterns
	 * @return bool True if saved successfully
	 */
	public function update_template( array $template ): bool {
		$sanitized = $this->sanitize_template( $template );
		return update_option( $this->option_name, $sanitized );
	}

	/**
	 * Clean up template data before saving
	 * 
	 * Only allows specific UTM parameter keys and sanitizes all values
	 * It's like a bouncer at a club - only the right stuff gets in
	 *
	 * @param array $template Raw template data
	 * @return array Cleaned template data
	 */
	private function sanitize_template( array $template ): array {
		$sanitized = array();

		// Only allow these UTM parameter names - nothing else gets through
		// We're strict, but fair
		$allowed_keys = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term' );

		foreach ( $allowed_keys as $key ) {
			if ( isset( $template[ $key ] ) ) {
				$sanitized[ $key ] = sanitize_text_field( $template[ $key ] );
			}
		}

		return $sanitized;
	}

	/**
	 * Check if a template is valid
	 * 
	 * At minimum, we need source, medium, and campaign
	 * Without these, it's not really a UTM link - it's just a regular link with delusions of grandeur
	 *
	 * @param array $template Template to validate
	 * @return bool True if valid, false if missing required fields
	 */
	public function validate_template( array $template ): bool {
		// These three are required for UTM links to be useful
		// Like the three basic food groups: source, medium, and campaign
		$required_keys = array( 'utm_source', 'utm_medium', 'utm_campaign' );

		foreach ( $required_keys as $key ) {
			if ( empty( $template[ $key ] ) ) {
				return false;
			}
		}

		return true;
	}
}
