<?php
/**
 * URL Shortener
 * 
 * Optional integration with URL shortening services
 * Supports Bitly, Rebrandly, and custom API endpoints
 * Results are cached to avoid hitting API limits
 * 
 * Because sometimes URLs are just too long, and we're here to help
 * 
 * @package Smart_UTM_Builder
 * @author Roham Parsa
 */

// Security check - because we're paranoid (and that's good!)
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles URL shortening via various services
 * 
 * This is optional - users can enable it if they want shorter URLs
 * Results are cached for an hour to reduce API calls
 * 
 * Think of it as a URL diet plan - makes your links skinnier
 */
class Smart_UTM_URL_Shortener {

	/**
	 * Singleton instance
	 *
	 * @var Smart_UTM_URL_Shortener
	 */
	private static $instance = null;

	/**
	 * How long to cache shortened URLs (in seconds)
	 * 
	 * 1 hour seems reasonable - balances freshness with API limits
	 * Picked after extensive testing (and one API rate limit hit - oops!)
	 *
	 * @var int
	 */
	private $cache_ttl = 3600;

	/**
	 * Get the singleton instance
	 *
	 * @return Smart_UTM_URL_Shortener
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - nothing to do here since this is optional
	 * 
	 * This class only works if configured, so no default hooks
	 * It's like a tool in a toolbox - only useful if you know how to use it
	 */
	private function __construct() {
		// This class only works if configured, so no default hooks
	}

	/**
	 * Shorten a URL using the configured service
	 * 
	 * Checks cache first, then calls the appropriate service API
	 * Because hitting APIs repeatedly is expensive (and annoying)
	 *
	 * @param string $url URL to shorten
	 * @return string|false Shortened URL or false if something went wrong
	 */
	public function shorten( string $url ) {
		$service = get_option( 'smart_utm_shortener_service', '' );
		if ( ! $service ) {
			// No service configured? Can't shorten what we don't have
			return false;
		}

		// Check cache first - no need to hit the API if we already have it
		// Because why ask for something you already have?
		$cache_key = 'smart_utm_short_' . md5( $url );
		$cached    = get_transient( $cache_key );
		if ( $cached ) {
			return $cached;
		}

		$shortened = false;

		// Route to the right service
		// Like a switchboard operator, but for URLs
		switch ( $service ) {
			case 'bitly':
				$shortened = $this->shorten_bitly( $url );
				break;
			case 'rebrandly':
				$shortened = $this->shorten_rebrandly( $url );
				break;
			case 'custom':
				$shortened = $this->shorten_custom( $url );
				break;
		}

		// Cache the result if we got one
		// Because success should be remembered
		if ( $shortened ) {
			set_transient( $cache_key, $shortened, $this->cache_ttl );
		}

		return $shortened;
	}

	/**
	 * Shorten URL using Bitly API
	 * 
	 * Bitly uses Bearer token authentication
	 * They're fancy like that
	 *
	 * @param string $url URL to shorten
	 * @return string|false Shortened URL or false on failure
	 */
	private function shorten_bitly( string $url ) {
		$api_key = get_option( 'smart_utm_bitly_api_key', '' );
		if ( ! $api_key ) {
			// No API key? Can't make API calls without it
			return false;
		}

		$endpoint = 'https://api-ssl.bitly.com/v4/shorten';
		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( array( 'long_url' => $url ) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			// Something went wrong - log it and return false
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $body['link'] ?? false;
	}

	/**
	 * Shorten URL using Rebrandly API
	 * 
	 * Rebrandly uses API key in headers
	 * Different service, different approach - variety is the spice of life
	 *
	 * @param string $url URL to shorten
	 * @return string|false Shortened URL or false on failure
	 */
	private function shorten_rebrandly( string $url ) {
		$api_key = get_option( 'smart_utm_rebrandly_api_key', '' );
		if ( ! $api_key ) {
			return false;
		}

		$endpoint = 'https://api.rebrandly.com/v1/links';
		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'apikey'       => $api_key,
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( array( 'destination' => $url ) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		// Rebrandly returns shortUrl without https://, so we add it
		return isset( $body['shortUrl'] ) ? 'https://' . $body['shortUrl'] : false;
	}

	/**
	 * Shorten URL using a custom API endpoint
	 * 
	 * For users who have their own URL shortener service
	 * Because sometimes you want to do things your own way
	 *
	 * @param string $url URL to shorten
	 * @return string|false Shortened URL or false on failure
	 */
	private function shorten_custom( string $url ) {
		$endpoint = get_option( 'smart_utm_custom_shortener_endpoint', '' );
		$api_key  = get_option( 'smart_utm_custom_shortener_api_key', '' );

		if ( ! $endpoint ) {
			// No endpoint? Can't make requests to nowhere
			return false;
		}

		$headers = array( 'Content-Type' => 'application/json' );
		if ( $api_key ) {
			$headers['Authorization'] = 'Bearer ' . $api_key;
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( array( 'url' => $url ) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		// Try common response field names - because APIs are inconsistent
		return $body['short_url'] ?? $body['shortUrl'] ?? false;
	}
}
