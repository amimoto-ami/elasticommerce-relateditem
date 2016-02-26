<?php
class ESC_RelatedItems extends WP_Widget {

	function ESC_RelatedItems() {
		// Instantiate the parent object
		parent::__construct( false, 'My New Widget Title' );
	}

	function widget( $args, $instance ) {
		// Widget output
	}

	function update( $new_instance, $old_instance ) {
		// Save widget options
	}

	function form( $instance ) {
		// Output admin widget options form
	}
}
