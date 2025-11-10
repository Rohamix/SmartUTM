<?php
/**
 * Dashboard Manager
 * 
 * Handles the admin interface - menus, pages, and script loading
 * Creates the main UTM Builder menu and all sub-pages
 * 
 * @package Smart_UTM_Builder
 * @author Roham Parsa
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages admin menu and dashboard pages
 * 
 * Sets up the WordPress admin interface for the plugin
 */
class Smart_UTM_Dashboard_Manager {

	/**
	 * Singleton instance
	 *
	 * @var Smart_UTM_Dashboard_Manager
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance
	 *
	 * @return Smart_UTM_Dashboard_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - hook into WordPress admin
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add the main menu and sub-pages to WordPress admin
	 * 
	 * Creates the "UTM Builder" menu item with Dashboard, Templates, Presets, and Settings sub-pages
	 */
	public function add_admin_menu() {
		$capability = 'manage_options';

		// Main menu item
		add_menu_page(
			__( 'UTM Builder', 'smart-utm-builder' ),
			__( 'UTM Builder', 'smart-utm-builder' ),
			$capability,
			'smart-utm-builder',
			array( $this, 'render_dashboard' ),
			'dashicons-admin-links',
			30
		);

		// Dashboard sub-page (same as main menu)
		add_submenu_page(
			'smart-utm-builder',
			__( 'Dashboard', 'smart-utm-builder' ),
			__( 'Dashboard', 'smart-utm-builder' ),
			$capability,
			'smart-utm-builder',
			array( $this, 'render_dashboard' )
		);

		// Templates sub-page
		add_submenu_page(
			'smart-utm-builder',
			__( 'Templates', 'smart-utm-builder' ),
			__( 'Templates', 'smart-utm-builder' ),
			$capability,
			'smart-utm-templates',
			array( $this, 'render_templates' )
		);

		// Presets sub-page
		add_submenu_page(
			'smart-utm-builder',
			__( 'Presets', 'smart-utm-builder' ),
			__( 'Presets', 'smart-utm-builder' ),
			$capability,
			'smart-utm-presets',
			array( $this, 'render_presets' )
		);

		// Settings sub-page
		add_submenu_page(
			'smart-utm-builder',
			__( 'Settings', 'smart-utm-builder' ),
			__( 'Settings', 'smart-utm-builder' ),
			$capability,
			'smart-utm-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Load CSS and JavaScript for admin pages
	 * 
	 * Only loads on our plugin pages to keep things fast
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_scripts( string $hook ) {
		// Only load on our plugin pages
		if ( strpos( $hook, 'smart-utm' ) === false ) {
			return;
		}

		// Load admin CSS
		wp_enqueue_style(
			'smart-utm-admin',
			SMART_UTM_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			SMART_UTM_VERSION
		);

		// Load admin JavaScript
		wp_enqueue_script(
			'smart-utm-admin',
			SMART_UTM_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery', 'wp-api-fetch' ),
			SMART_UTM_VERSION,
			true
		);

		// Pass data to JavaScript
		wp_localize_script(
			'smart-utm-admin',
			'smartUtm',
			array(
				'apiUrl'   => rest_url( 'smart-utm/v1/' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'bulkNonce' => wp_create_nonce( 'smart_utm_bulk' ),
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Render the dashboard page
	 */
	public function render_dashboard() {
		require_once SMART_UTM_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render the templates page
	 */
	public function render_templates() {
		require_once SMART_UTM_PLUGIN_DIR . 'admin/views/templates.php';
	}

	/**
	 * Render the presets page
	 */
	public function render_presets() {
		require_once SMART_UTM_PLUGIN_DIR . 'admin/views/presets.php';
	}

	/**
	 * Render the settings page
	 */
	public function render_settings() {
		require_once SMART_UTM_PLUGIN_DIR . 'admin/views/settings.php';
	}
}
