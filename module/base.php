<?php
use Elastica\Client;
class ESCR_Base {
	private static $instance;
	private static $text_domain;

	private function __construct() {
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	public static function version() {
		static $version;

		if ( ! $version ) {
			$data = get_file_data( ESCR_ROOT , array( 'version' => 'Version' ) );
			$version = $data['version'];
		}
		return $version;
	}

	public static function text_domain() {
		static $text_domain;

		if ( ! $text_domain ) {
			$data = get_file_data( ESCR_ROOT , array( 'text_domain' => 'Text Domain' ) );
			$text_domain = $data['text_domain'];
		}
		return $text_domain;
	}

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

	public function get_index_type() {
		return apply_filters( 'escr_index_type', 'product' );
	}

	public function get_elasticsearch_endpoint() {
		$options = get_option( 'escr_settings' );
		$options['endpoint'] = 'search-woo-recommend-3rcvzwri7rddinzmsty7b3gdsy.ap-northeast-1.es.amazonaws.com';

		$wpels_settings = get_option( 'wpels_settings' );
		if ( $wpels_settings ) {
			$options['endpoint'] = $wpels_settings['endpoint'];
		}

		return $options;
	}
}
