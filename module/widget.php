<?php
/**
 * Define Widgets
 *
 * @package Elasticommerce-relateditem
 * @author hideokamoto
 * @since 0.1.0
 **/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Related Item Widgets Class
 *
 * @class ESC_RelatedItems
 * @since 0.1.0
 */
class ESC_RelatedItems extends WP_Widget {
	/**
	 * text domain
	 * @access private
	 */
	private static $text_domain;

	/**
	 * Constructer
	 * Define Elasticommerce Related Widgets
	 *
	 * @since 0.1.0
	 */
	function __construct() {
		self::$text_domain = ESCR_Base::text_domain();
		// Instantiate the parent object
		parent::__construct( false, __( 'Elasticommerce Related Widgets' ), self::$text_domain );
	}

	/**
	 * define Elasticommerce Related Widget Content
	 *
	 * @param array $args
	 * @param array $instance
	 * @since 0.1.0
	 */
	function widget( $args, $instance ) {
		escr_related_item( '_widget' );
	}

}
