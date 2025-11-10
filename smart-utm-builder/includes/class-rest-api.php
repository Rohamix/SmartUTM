<?php
/**
 * REST API
 * 
 * Handles all REST API endpoints for the plugin
 * Provides endpoints for UTM links, templates, presets, and bulk operations
 * Used by the admin JavaScript to interact with the backend
 * 
 * @package Smart_UTM_Builder
 * @author Roham Parsa
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API endpoints handler
 * 
 * Registers all the API routes the plugin needs
 * All endpoints require proper authentication and permissions
 */
class Smart_UTM_REST_API {

	/**
	 * Singleton instance
	 *
	 * @var Smart_UTM_REST_API
	 */
	private static $instance = null;

	/**
	 * API namespace
	 * 
	 * All our endpoints live under /wp-json/smart-utm/v1/
	 *
	 * @var string
	 */
	private $namespace = 'smart-utm/v1';

	/**
	 * Get instance.
	 *
	 * @return Smart_UTM_REST_API
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// UTM Links endpoints.
		register_rest_route(
			$this->namespace,
			'/links',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_links' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'page'     => array(
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'per_page' => array(
							'default'           => 20,
							'sanitize_callback' => 'absint',
						),
						'search'   => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_link' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/links/(?P<id>\d+)/(?P<preset>[a-zA-Z0-9_-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_link' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'utm_url' => array(
							'required'          => true,
							'sanitize_callback' => 'esc_url_raw',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_link' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Bulk operations endpoint.
		register_rest_route(
			$this->namespace,
			'/bulk',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'bulk_operation' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Templates endpoints.
		register_rest_route(
			$this->namespace,
			'/templates',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_template' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_template' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Presets endpoints.
		register_rest_route(
			$this->namespace,
			'/presets',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_presets' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_preset' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/presets/(?P<id>[a-zA-Z0-9_-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_preset' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_preset' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_preset' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);
	}

	/**
	 * Check if current user has permission to use the API
	 * 
	 * Requires either manage_options (admin) or edit_posts (editor/author)
	 *
	 * @return bool True if user has permission
	 */
	public function check_permission(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
	}

	/**
	 * Get UTM links.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_links( WP_REST_Request $request ): WP_REST_Response {
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );
		$search   = $request->get_param( 'search' );

		$post_types = array();
		if ( get_option( 'smart_utm_include_posts', true ) ) {
			$post_types[] = 'post';
		}
		if ( get_option( 'smart_utm_include_pages', true ) ) {
			$post_types[] = 'page';
		}

		$query_args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'meta_query'     => array(
				array(
					'key'     => '_utm_link',
					'compare' => 'EXISTS',
				),
			),
		);

		if ( $search ) {
			$query_args['s'] = $search;
		}

		$query = new WP_Query( $query_args );
		$links = array();

		foreach ( $query->posts as $post ) {
			$utm_links = get_post_meta( $post->ID, '_utm_link', true );
			if ( $utm_links && is_array( $utm_links ) ) {
				foreach ( $utm_links as $preset_id => $link_data ) {
					$links[] = array(
						'id'          => $post->ID,
						'post_title'  => $post->post_title,
						'original_url' => get_permalink( $post->ID ),
						'utm_url'     => $link_data['url'] ?? '',
						'preset'      => $preset_id,
						'created'     => $link_data['created'] ?? '',
					);
				}
			}
		}

		return new WP_REST_Response(
			array(
				'links' => $links,
				'total' => $query->found_posts,
			),
			200
		);
	}

	/**
	 * Create UTM link.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function create_link( WP_REST_Request $request ): WP_REST_Response {
		$post_id   = absint( $request->get_param( 'post_id' ) );
		$preset_id = sanitize_text_field( $request->get_param( 'preset_id' ) ?? '' );

		if ( ! $post_id ) {
			return new WP_REST_Response( array( 'message' => __( 'Invalid post ID.', 'smart-utm-builder' ) ), 400 );
		}

		$generator = Smart_UTM_Generator::get_instance();
		$result    = $generator->generate_for_post( $post_id );

		return new WP_REST_Response( array( 'links' => $result ), 200 );
	}

	/**
	 * Update UTM link.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function update_link( WP_REST_Request $request ): WP_REST_Response {
		$post_id   = absint( $request->get_param( 'id' ) );
		$preset_id = sanitize_text_field( $request->get_param( 'preset' ) );
		$utm_url   = $request->get_param( 'utm_url' );

		if ( ! $post_id || ! $preset_id || ! $utm_url ) {
			return new WP_REST_Response( array( 'message' => __( 'Invalid parameters.', 'smart-utm-builder' ) ), 400 );
		}

		$generator = Smart_UTM_Generator::get_instance();
		$result    = $generator->update_utm_link( $post_id, $preset_id, $utm_url );

		if ( $result ) {
			return new WP_REST_Response( array( 'success' => true ), 200 );
		}

		return new WP_REST_Response( array( 'message' => __( 'Update failed.', 'smart-utm-builder' ) ), 400 );
	}

	/**
	 * Delete UTM link.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function delete_link( WP_REST_Request $request ): WP_REST_Response {
		$post_id   = absint( $request->get_param( 'id' ) );
		$preset_id = sanitize_text_field( $request->get_param( 'preset' ) );

		if ( ! $post_id || ! $preset_id ) {
			return new WP_REST_Response( array( 'message' => __( 'Invalid parameters.', 'smart-utm-builder' ) ), 400 );
		}

		$generator = Smart_UTM_Generator::get_instance();
		$result    = $generator->delete_utm_link( $post_id, $preset_id );

		if ( $result ) {
			return new WP_REST_Response( array( 'success' => true ), 200 );
		}

		return new WP_REST_Response( array( 'message' => __( 'Delete failed.', 'smart-utm-builder' ) ), 400 );
	}

	/**
	 * Bulk operation.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function bulk_operation( WP_REST_Request $request ): WP_REST_Response {
		$action = sanitize_text_field( $request->get_param( 'action' ) );

		if ( ! in_array( $action, array( 'generate_all', 'refresh_all', 'delete_all' ), true ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Invalid action.', 'smart-utm-builder' ) ), 400 );
		}

		$processor = Smart_UTM_Bulk_Processor::get_instance();
		$result    = $processor->process( $action );

		return new WP_REST_Response( $result, $result['success'] ? 200 : 400 );
	}

	/**
	 * Get template.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_template( WP_REST_Request $request ): WP_REST_Response {
		$manager = Smart_UTM_Template_Manager::get_instance();
		return new WP_REST_Response( array( 'template' => $manager->get_template() ), 200 );
	}

	/**
	 * Update template.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function update_template( WP_REST_Request $request ): WP_REST_Response {
		$template = $request->get_json_params();

		$manager = Smart_UTM_Template_Manager::get_instance();
		if ( ! $manager->validate_template( $template ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Invalid template.', 'smart-utm-builder' ) ), 400 );
		}

		$result = $manager->update_template( $template );
		return new WP_REST_Response( array( 'success' => $result ), $result ? 200 : 400 );
	}

	/**
	 * Get presets.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_presets( WP_REST_Request $request ): WP_REST_Response {
		$manager = Smart_UTM_Preset_Manager::get_instance();
		return new WP_REST_Response( array( 'presets' => $manager->get_presets() ), 200 );
	}

	/**
	 * Get preset.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_preset( WP_REST_Request $request ): WP_REST_Response {
		$preset_id = sanitize_text_field( $request->get_param( 'id' ) );
		$manager   = Smart_UTM_Preset_Manager::get_instance();
		$preset    = $manager->get_preset( $preset_id );

		if ( ! $preset ) {
			return new WP_REST_Response( array( 'message' => __( 'Preset not found.', 'smart-utm-builder' ) ), 404 );
		}

		return new WP_REST_Response( array( 'preset' => $preset ), 200 );
	}

	/**
	 * Create preset.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function create_preset( WP_REST_Request $request ): WP_REST_Response {
		$data      = $request->get_json_params();
		$preset_id = sanitize_text_field( $data['id'] ?? '' );
		$preset    = $data['preset'] ?? array();

		if ( ! $preset_id || empty( $preset ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Invalid parameters.', 'smart-utm-builder' ) ), 400 );
		}

		$manager = Smart_UTM_Preset_Manager::get_instance();
		$result  = $manager->update_preset( $preset_id, $preset );

		return new WP_REST_Response( array( 'success' => $result ), $result ? 200 : 400 );
	}

	/**
	 * Update preset.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function update_preset( WP_REST_Request $request ): WP_REST_Response {
		$preset_id = sanitize_text_field( $request->get_param( 'id' ) );
		$preset    = $request->get_json_params();

		if ( ! $preset_id || empty( $preset ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Invalid parameters.', 'smart-utm-builder' ) ), 400 );
		}

		$manager = Smart_UTM_Preset_Manager::get_instance();
		$result  = $manager->update_preset( $preset_id, $preset );

		return new WP_REST_Response( array( 'success' => $result ), $result ? 200 : 400 );
	}

	/**
	 * Delete preset.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function delete_preset( WP_REST_Request $request ): WP_REST_Response {
		$preset_id = sanitize_text_field( $request->get_param( 'id' ) );

		if ( ! $preset_id ) {
			return new WP_REST_Response( array( 'message' => __( 'Invalid preset ID.', 'smart-utm-builder' ) ), 400 );
		}

		$manager = Smart_UTM_Preset_Manager::get_instance();
		$result  = $manager->delete_preset( $preset_id );

		return new WP_REST_Response( array( 'success' => $result ), $result ? 200 : 400 );
	}
}

