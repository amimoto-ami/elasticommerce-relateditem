<?php
class ESCR_Admin extends ESCR_Base {
	private static $instance;
	private static $text_domain;


	private function __construct() {
		self::$text_domain = ESCR_Base::text_domain();
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	public function init() {
		add_action( 'widgets_init', array( $this, 'escr_register_widgets' ) );
	}

	public function escr_register_widgets() {
		register_widget( 'ESC_RelatedItems' );
	}
}
