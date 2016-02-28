<?php
class ESC_RelatedItems extends WP_Widget {
	private static $text_domain;

	function __construct() {
		self::$text_domain = ESCR_Base::text_domain();
		// Instantiate the parent object
		parent::__construct( false, __( 'My New Widget Title' ), self::$text_domain );
	}

	function widget( $args, $instance ) {
		// Widget output
		escr_related_item();
	}

	function update( $new_instance, $old_instance ) {
		// Save widget options
	}

	function form( $instance ) {
		// Output admin widget options form
	}
}
