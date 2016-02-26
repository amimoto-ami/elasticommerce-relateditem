<?php
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

	public function init() {
		add_action( 'widgets_init', array( $this, 'escr_register_widgets' ) );
	}

	public function escr_register_widgets() {
		register_widget( 'ESC_RelatedItems' );
	}
}
