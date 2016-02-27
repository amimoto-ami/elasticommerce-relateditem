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
		add_action( 'transition_post_status' , array( $this, 'escr_import' ) , 10 , 3 );
	}

	public function escr_register_widgets() {
		register_widget( 'ESC_RelatedItems' );
	}

	public function escr_import( $new_status, $old_status, $post ) {
		if ( ! $this->is_import( $new_status , $old_status ) ) {
			return;
		}
		$Importer = ESCR_Importer::get_instance();
		//$Importer->import_all_product();
		$Importer->import_single_product( $post );
	}

	private function is_import( $new_status, $old_status ) {
		if ( 'publish' === $new_status ) {
			//if publish or update posts.
			$result = true;
		} elseif ( 'publish' === $old_status && $new_status !== $old_status ) {
			//if un-published post.
			$result = true;
		} else {
			$result = false;
		}
		$result = apply_filters( 'escr_is_import' , $result );
		return $result;
	}
}
