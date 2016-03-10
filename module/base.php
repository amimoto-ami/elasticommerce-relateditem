<?php
/**
 * Plugin Base Class
 *
 * @package Elasticommerce-relateditem
 * @author hideokamoto
 * @since 0.1.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
use Elastica\Client;

/**
 * Base Plugin class for Elasticommerce Related Item Plugin
 *
 * @class ESCR_Base
 * @since 0.1.0
 */
class ESCR_Base {
	/**
	 * Instance Class
	 * @access private
	 */
	private static $instance;

	/**
	 * text domain 
	 * @access private
	 */
	private static $text_domain;


	/**
	 * Get Instance Class
	 *
	 * @return ESCR_Base
	 * @since 0.1.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	/**
	 * Get Plugin version
	 *
	 * @return string
	 * @since 0.1.0
	 */
	public static function version() {
		static $version;

		if ( ! $version ) {
			$data = get_file_data( ESCR_ROOT , array( 'version' => 'Version' ) );
			$version = $data['version'];
		}
		return $version;
	}

	/**
	 * Get Plugin text_domain
	 *
	 * @return string
	 * @since 0.1.0
	 */
	public static function text_domain() {
		static $text_domain;

		if ( ! $text_domain ) {
			$data = get_file_data( ESCR_ROOT , array( 'text_domain' => 'Text Domain' ) );
			$text_domain = $data['text_domain'];
		}
		return $text_domain;
	}

	/**
	 * Get Elastica Client
	 *
	 * @param $options: array
	 * @return Elastica\Client
	 * @since 0.1.0
	 */
	public function create_client( $options ) {
		if ( empty( $options['endpoint'] ) ) {
			return false;
		}
		$client = new \Elastica\Client( array(
			'host' => $options['endpoint'],
			'port' => 80,
		));
		return $client;
	}

	/**
	 * Get Elasticsearch Index type
	 *
	 * @return string
	 * @since 0.1.0
	 */
	public function get_index_type() {
		return apply_filters( 'escr_index_type', 'product' );
	}

	/**
	 * Get Elasticsearch Settings
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_elasticsearch_endpoint() {
		$options = get_option( 'escr_settings' );
		if ( $this->is_activate_esc_search_form() ) {
			$wpels_settings = get_option( 'wpels_settings' );
			if ( $wpels_settings ) {
				$options['endpoint'] = $wpels_settings['endpoint'];
			}
		}

		return $options;
	}

	/**
	 * Get Elasticsearch Search target
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_elasticsearch_target() {
		$options = get_option( 'escr_settings' );
		if ( ! isset( $options['target'] ) ) {
			$target = array( 'product_excerpt', 'product_content', 'product_display_price', 'product_cat', 'product_tag', 'product_title' );
		} elseif ( ! is_array( $options['target'] ) ) {
			$target[0] = $options['target'];
		} else {
			$target = $options['target'];
		}
		return $target;
	}

	/**
	 * Check Elasticommerce Search Form Plugin status
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function is_activate_esc_search_form() {
		$activePlugins = get_option('active_plugins');
		$plugin = 'elasticommerce-search-form/elasticommerce-search-form.php';
		if ( array_search( $plugin, $activePlugins ) && file_exists( WP_PLUGIN_DIR. '/'. $plugin ) ) {
			return true;
		} else {
			return false;
		}
	}
}
