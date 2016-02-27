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

	private function get_term_name_list( $terms ) {
		foreach ( $terms as $key => $value ) {
			$term_name_list[] = $value->name;
		}
		return $term_name_list;
	}

	public function escr_import( $new_status, $old_status, $post ) {
		if ( 'product' == $post->post_type ) {
			$ID = $post->ID;
			$Product = wc_get_product( $ID );
			if ( $this->is_search_target( $Product ) ) {
				$data['title'] = $Product->post->post_title;
				$data['content'] = $Product->post->post_content;
				$data['excerpt'] = $Product->post->post_excerpt;
	 			$data['sale_price'] = $Product->get_sale_price( );
				$data['regular_price'] = $Product->get_regular_price( );
				$data['price'] = $Product->get_price( );
				$data['display_price'] = $Product->get_display_price();
				$data['rate'] = $Product->get_average_rating();
				$data['tag'] = $this->get_term_name_list( get_the_terms($ID, 'product_tag') );
				$data['cat'] = $this->get_term_name_list( get_the_terms($ID, 'product_cat') );
				$data['attr'] = $Product->get_attributes();
			}

		}
		if ( ! $this->is_import( $new_status , $old_status ) ) {
			return;
		}

		//Dev now...
		$item_id_list = ['2182','2180','2179','2166','2181'];
		$this->overwrite_woo_related( $ID, $item_id_list );

		//debug
		$transient_name = 'wc_related_' . $ID;
		$related_posts  = get_transient( $transient_name );
		var_dump($related_posts);
	}

	public function overwrite_woo_related( $ID, $item_id_list ) {
		$transient_name = 'wc_related_' . $ID;
		set_transient( $transient_name , $item_id_list , DAY_IN_SECONDS);
	}

	private function is_search_target( $Product ) {
		if ( $Product->is_visible() ) {
			return true;
		}
		return false;
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
