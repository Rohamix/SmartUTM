<?php
/**
 * UTM Generator
 * 
 * The heart of the plugin - generates UTM links for posts and pages
 * Handles automatic generation on save, manual generation, and URL building
 * 
 * @package Smart_UTM_Builder
 * @author Roham Parsa
 */

// Security first - like a bouncer at a club, but for code
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates UTM links for WordPress posts and pages
 * 
 * This class does the heavy lifting - takes presets, applies them to posts,
 * replaces placeholders, and stores the results in post meta
 * 
 * Think of it as a URL factory that never sleeps (well, until you disable it)
 */
class Smart_UTM_Generator {

	/**
	 * Singleton instance - because we only need one of these running around
	 *
	 * @var Smart_UTM_Generator
	 */
	private static $instance = null;

	/**
	 * Post meta key where we store UTM links
	 * 
	 * Using underscore prefix makes it "hidden" from custom fields UI
	 * It's like hiding your snacks so roommates don't eat them
	 *
	 * @var string
	 */
	private $meta_key = '_utm_link';

	/**
	 * Get the singleton instance
	 * 
	 * If we don't have one yet, make it. Otherwise, return the existing one.
	 * Classic singleton pattern - keeps things tidy.
	 *
	 * @return Smart_UTM_Generator
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - hooks into WordPress like a good plugin should
	 */
	private function __construct() {
		// Generate UTM links automatically when posts are saved
		add_action( 'save_post', array( $this, 'generate_on_save' ), 10, 2 );
		// Add meta box to post edit screens (the fun part!)
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		// Load scripts for the meta box (because JavaScript makes everything better)
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_meta_box_scripts' ) );
	}

	/**
	 * Called when a post is saved
	 * 
	 * Checks settings and generates UTM links if everything is configured correctly
	 * Has more exit conditions than a shopping mall
	 *
	 * @param int     $post_id Post ID
	 * @param WP_Post $post Post object
	 */
	public function generate_on_save( int $post_id, WP_Post $post ) {
		// WordPress autosaves are annoying - skip them like a bad Tinder date
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Don't generate for revisions either - we're not time travelers
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Respect user settings - if auto-generation is disabled, bail out gracefully
		if ( ! get_option( 'smart_utm_enable_auto_generate', true ) ) {
			return;
		}

		// Check if we should generate on publish (vs. on every save)
		// Some people like to save 47 times before publishing, we respect that
		if ( ! get_option( 'smart_utm_generate_on_publish', true ) ) {
			return;
		}

		// Make sure this post type is enabled
		$post_type = get_post_type( $post_id );
		if ( 'post' === $post_type && ! get_option( 'smart_utm_include_posts', true ) ) {
			return;
		}
		if ( 'page' === $post_type && ! get_option( 'smart_utm_include_pages', true ) ) {
			return;
		}

		// All checks passed - generate the links! *cue dramatic music*
		$this->generate_for_post( $post_id );
	}

	/**
	 * Generate UTM links for a specific post
	 * 
	 * Creates links for all active presets and stores them in post meta
	 * Like a factory assembly line, but for URLs
	 *
	 * @param int $post_id Post ID to generate links for
	 * @return array Generated UTM links indexed by preset ID
	 */
	public function generate_for_post( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post ) {
			// Post doesn't exist? That's awkward...
			return array();
		}

		// Get all presets and generate a link for each one
		// It's like making multiple versions of the same recipe with different spices
		$preset_manager = Smart_UTM_Preset_Manager::get_instance();
		$presets        = $preset_manager->get_presets();
		$generated      = array();

		foreach ( $presets as $preset_id => $preset ) {
			$utm_url = $this->build_utm_url( $post, $preset );
			if ( $utm_url ) {
				$generated[ $preset_id ] = array(
					'url'      => $utm_url,
					'preset'   => $preset_id,
					'created'  => current_time( 'mysql' ),
					'post_id'  => $post_id,
				);
			}
		}

		// Save to post meta if we generated anything
		// No point saving an empty array - that's like ordering food and getting an empty plate
		if ( ! empty( $generated ) ) {
			update_post_meta( $post_id, $this->meta_key, $generated );
		}

		return $generated;
	}

	/**
	 * Build a UTM URL from a post and preset
	 * 
	 * Takes the post permalink, applies preset parameters, replaces placeholders,
	 * and returns a complete UTM-tagged URL
	 * 
	 * This is where the magic happens - turning a regular URL into a tracking masterpiece
	 *
	 * @param WP_Post $post Post object
	 * @param array   $preset Preset configuration
	 * @return string|null Complete UTM URL or null if something went wrong
	 */
	public function build_utm_url( WP_Post $post, array $preset ) {
		$base_url = get_permalink( $post->ID );
		if ( ! $base_url ) {
			// Can't build a URL without a base URL - that's like trying to make coffee without water
			return null;
		}

		// Process each UTM parameter from the preset
		$utm_params = array();
		foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term' ) as $key ) {
			if ( isset( $preset[ $key ] ) && ! empty( $preset[ $key ] ) ) {
				// Replace placeholders like {post_slug}, {category}, etc.
				// It's like Mad Libs, but for URLs
				$value = $this->replace_placeholders( $preset[ $key ], $post );
				if ( $value ) {
					$utm_params[ $key ] = $value;
				}
			}
		}

		// Need at least some parameters to make a valid UTM link
		// Empty UTM links are like empty promises - technically possible but not useful
		if ( empty( $utm_params ) ) {
			return null;
		}

		// Build the final URL - handle cases where permalink already has query params
		// Because some URLs are like Russian nesting dolls - URLs within URLs
		$separator = strpos( $base_url, '?' ) !== false ? '&' : '?';
		$query_string = http_build_query( $utm_params );
		return $base_url . $separator . $query_string;
	}

	/**
	 * Replace placeholders in template strings
	 * 
	 * Takes something like "{category}_{year}" and replaces it with actual values
	 * Available placeholders: {source}, {medium}, {category}, {year}, {post_slug}, 
	 * {post_title}, {post_id}, {author}
	 * 
	 * Think of it as a find-and-replace on steroids
	 *
	 * @param string  $template Template string with placeholders
	 * @param WP_Post $post Post object for context
	 * @return string Template with placeholders replaced
	 */
	private function replace_placeholders( string $template, WP_Post $post ): string {
		// Map placeholders to their actual values
		// This is where we turn {magic_words} into real data
		$replacements = array(
			'{source}'     => 'website',
			'{medium}'     => 'organic',
			'{category}'   => $this->get_post_category( $post ),
			'{year}'       => date( 'Y' ), // Current year, because time travel isn't a thing yet
			'{post_slug}'  => $post->post_name,
			'{post_title}' => sanitize_title( $post->post_title ),
			'{post_id}'    => $post->ID,
			'{author}'     => get_the_author_meta( 'user_nicename', $post->post_author ),
		);

		$result = $template;
		foreach ( $replacements as $placeholder => $replacement ) {
			$result = str_replace( $placeholder, $replacement, $result );
		}

		// Clean up the result before returning - because nobody likes dirty URLs
		return sanitize_text_field( $result );
	}

	/**
	 * Get the category slug for a post
	 * 
	 * Returns the first category's slug, or 'uncategorized' if none found
	 * Because every post needs a category, even if it's the default "I don't know where this goes" category
	 *
	 * @param WP_Post $post Post object
	 * @return string Category slug
	 */
	private function get_post_category( WP_Post $post ): string {
		$categories = get_the_category( $post->ID );
		if ( ! empty( $categories ) ) {
			// Just grab the first one - we're not picky
			return $categories[0]->slug;
		}
		// The catch-all category for posts that don't fit anywhere
		return 'uncategorized';
	}

	/**
	 * Get all UTM links for a post
	 * 
	 * Returns empty array if nothing found - because null is scary
	 *
	 * @param int $post_id Post ID
	 * @return array UTM links indexed by preset ID
	 */
	public function get_utm_links( int $post_id ): array {
		return get_post_meta( $post_id, $this->meta_key, true ) ?: array();
	}

	/**
	 * Update a specific UTM link for a post
	 * 
	 * Useful for manual edits or corrections
	 * Because sometimes even robots make mistakes
	 *
	 * @param int    $post_id Post ID
	 * @param string $preset_id Preset ID
	 * @param string $utm_url New UTM URL
	 * @return bool True if updated successfully
	 */
	public function update_utm_link( int $post_id, string $preset_id, string $utm_url ): bool {
		$links = $this->get_utm_links( $post_id );
		$links[ $preset_id ] = array(
			'url'      => esc_url_raw( $utm_url ),
			'preset'   => $preset_id,
			'created'  => current_time( 'mysql' ),
			'post_id'  => $post_id,
		);
		return update_post_meta( $post_id, $this->meta_key, $links );
	}

	/**
	 * Delete a UTM link for a post
	 * 
	 * Removes a specific preset's link from a post
	 * Like removing a bad photo from your social media profile
	 *
	 * @param int    $post_id Post ID
	 * @param string $preset_id Preset ID to remove
	 * @return bool True if deleted successfully
	 */
	public function delete_utm_link( int $post_id, string $preset_id ): bool {
		$links = $this->get_utm_links( $post_id );
		if ( isset( $links[ $preset_id ] ) ) {
			unset( $links[ $preset_id ] );
			// If no links left, remove the meta entirely
			// Clean house, as they say
			if ( empty( $links ) ) {
				return delete_post_meta( $post_id, $this->meta_key );
			}
			return update_post_meta( $post_id, $this->meta_key, $links );
		}
		return false;
	}

	/**
	 * Add meta box to post/page edit screens
	 * 
	 * Shows generated UTM links right in the post editor
	 * Because context is everything, and nobody wants to switch tabs
	 */
	public function add_meta_box() {
		$post_types = array();
		if ( get_option( 'smart_utm_include_posts', true ) ) {
			$post_types[] = 'post';
		}
		if ( get_option( 'smart_utm_include_pages', true ) ) {
			$post_types[] = 'page';
		}

		// Add meta box to each enabled post type
		// Like putting a helpful note on each door
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'smart_utm_links',
				__( 'UTM Links', 'smart-utm-builder' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render the meta box content
	 * 
	 * Shows all generated UTM links with copy buttons
	 * Makes it easy to grab links without hunting through menus
	 *
	 * @param WP_Post $post Post object
	 */
	public function render_meta_box( WP_Post $post ) {
		wp_nonce_field( 'smart_utm_meta_box', 'smart_utm_meta_box_nonce' );

		$utm_links = $this->get_utm_links( $post->ID );
		$preset_manager = Smart_UTM_Preset_Manager::get_instance();
		$presets = $preset_manager->get_presets();

		// If no links exist yet, show a generate button
		// Because empty boxes are sad boxes
		if ( empty( $utm_links ) ) {
			echo '<p>' . esc_html__( 'No UTM links generated yet.', 'smart-utm-builder' ) . '</p>';
			echo '<p><button type="button" class="button button-small smart-utm-generate-now" data-post-id="' . esc_attr( $post->ID ) . '">' . esc_html__( 'Generate Now', 'smart-utm-builder' ) . '</button></p>';
			return;
		}

		// Display each UTM link with copy functionality
		// Because copying URLs manually is so 2010
		echo '<div class="smart-utm-meta-box-links">';
		foreach ( $utm_links as $preset_id => $link_data ) {
			$preset_name = isset( $presets[ $preset_id ] ) ? $presets[ $preset_id ]['name'] : $preset_id;
			$utm_url = $link_data['url'] ?? '';
			?>
			<div class="smart-utm-link-item" style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #ddd;">
				<strong><?php echo esc_html( $preset_name ); ?></strong>
				<div style="margin-top: 5px;">
					<input type="text" readonly value="<?php echo esc_attr( $utm_url ); ?>" class="smart-utm-url-input" style="width: 100%; font-size: 11px; font-family: monospace;" onclick="this.select();">
					<div style="margin-top: 5px;">
						<button type="button" class="button button-small smart-utm-copy-meta" data-url="<?php echo esc_attr( $utm_url ); ?>" style="margin-right: 5px;">
							<?php esc_html_e( 'Copy', 'smart-utm-builder' ); ?>
						</button>
						<a href="<?php echo esc_url( $utm_url ); ?>" target="_blank" class="button button-small">
							<?php esc_html_e( 'Open', 'smart-utm-builder' ); ?>
						</a>
					</div>
				</div>
			</div>
			<?php
		}
		echo '</div>';
		echo '<p><button type="button" class="button button-small smart-utm-regenerate" data-post-id="' . esc_attr( $post->ID ) . '">' . esc_html__( 'Regenerate All', 'smart-utm-builder' ) . '</button></p>';
	}

	/**
	 * Load JavaScript for the meta box
	 * 
	 * Only loads on post edit screens to keep things lightweight
	 * Because nobody likes slow admin pages (except maybe sloths)
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_meta_box_scripts( string $hook ) {
		// Only load on post edit screens
		// No point loading scripts where they're not needed
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_script(
			'smart-utm-meta-box',
			SMART_UTM_PLUGIN_URL . 'admin/js/meta-box.js',
			array( 'jquery' ),
			SMART_UTM_VERSION,
			true
		);

		// Pass API URL and nonce to JavaScript
		// Because JavaScript can't read PHP minds (yet)
		wp_localize_script(
			'smart-utm-meta-box',
			'smartUtmMeta',
			array(
				'apiUrl' => rest_url( 'smart-utm/v1/' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}
