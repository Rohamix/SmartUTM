<?php
/**
 * Plugin Name: SmartUTM Builder
 * Plugin URI: https://rohamhub.info
 * Description: A professional WordPress plugin that automatically generates, manages, and tracks UTM links for all site pages, posts, and campaigns. It ensures consistent and accurate campaign tagging across all marketing channels — built for efficiency, privacy, and performance.
 * Version: 1.2.0
 * Author: Roham Parsa
 * Author URI: https://rohamhub.info
 * License: Private - Internal Use Only
 * Text Domain: smart-utm-builder
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Network: true
 */

// Don't let anyone access this file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version and paths - keeping these handy for later use
define( 'SMART_UTM_VERSION', '1.2.0' );
define( 'SMART_UTM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SMART_UTM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SMART_UTM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class - handles initialization and core setup
 * 
 * Using singleton pattern to avoid multiple instances floating around
 */
final class Smart_UTM_Builder {

	/**
	 * The one and only instance of this plugin
	 *
	 * @var Smart_UTM_Builder
	 */
	private static $instance = null;

	/**
	 * Get the plugin instance (singleton pattern)
	 *
	 * @return Smart_UTM_Builder
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - private so we can control instantiation
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load all the class files we need
	 * 
	 * Keeping these separate makes the code easier to maintain
	 */
	private function load_dependencies() {
		require_once SMART_UTM_PLUGIN_DIR . 'includes/class-template-manager.php';
		require_once SMART_UTM_PLUGIN_DIR . 'includes/class-preset-manager.php';
		require_once SMART_UTM_PLUGIN_DIR . 'includes/class-utm-generator.php';
		require_once SMART_UTM_PLUGIN_DIR . 'includes/class-bulk-processor.php';
		require_once SMART_UTM_PLUGIN_DIR . 'includes/class-dashboard-manager.php';
		require_once SMART_UTM_PLUGIN_DIR . 'includes/class-rest-api.php';
		require_once SMART_UTM_PLUGIN_DIR . 'includes/class-url-shortener.php';
		require_once SMART_UTM_PLUGIN_DIR . 'includes/class-qr-generator.php';
		require_once SMART_UTM_PLUGIN_DIR . 'includes/class-analytics.php';
	}

	/**
	 * Set up WordPress hooks and actions
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Load translation files if they exist
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'smart-utm-builder',
			false,
			dirname( SMART_UTM_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Initialize all the plugin components
	 * 
	 * This runs on WordPress init, so everything should be ready by now
	 */
	public function init() {
		// Fire up all the main classes
		Smart_UTM_Template_Manager::get_instance();
		Smart_UTM_Preset_Manager::get_instance();
		Smart_UTM_Generator::get_instance();
		Smart_UTM_Bulk_Processor::get_instance();
		Smart_UTM_Dashboard_Manager::get_instance();
		Smart_UTM_REST_API::get_instance();
		Smart_UTM_URL_Shortener::get_instance();
		Smart_UTM_QR_Generator::get_instance();
		Smart_UTM_Analytics::get_instance();
	}

	/**
	 * What happens when the plugin is activated
	 * 
	 * Sets up default options and initializes default templates/presets
	 */
	public function activate() {
		// Set sensible defaults for first-time users
		$default_options = array(
			'enable_auto_generate' => true,
			'include_pages'         => true,
			'include_posts'         => true,
			'generate_on_publish'   => true,
		);

		// Only set defaults if they don't already exist (respects existing settings)
		foreach ( $default_options as $key => $value ) {
			if ( false === get_option( 'smart_utm_' . $key ) ) {
				add_option( 'smart_utm_' . $key, $value );
			}
		}

		// Set up default template and presets so users have something to start with
		Smart_UTM_Template_Manager::get_instance()->init_default_template();
		Smart_UTM_Preset_Manager::get_instance()->init_default_presets();

		// Make sure REST API routes are ready
		flush_rewrite_rules();
	}

	/**
	 * Cleanup when plugin is deactivated
	 * 
	 * Removes scheduled tasks and flushes rewrite rules
	 */
	public function deactivate() {
		// Clean up any scheduled bulk processing tasks
		wp_clear_scheduled_hook( 'smart_utm_bulk_process' );
		flush_rewrite_rules();
	}
}

/**
 * Initialize the plugin
 * 
 * This function is the entry point - call it to get the plugin instance
 */
function smart_utm_builder() {
	return Smart_UTM_Builder::get_instance();
}

// Fire it up!
smart_utm_builder();

