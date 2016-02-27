<?php
use Elastica\Client;
use Elastica\Query;
use Elastica\Query\QueryString;
use Elastica\Type\Mapping;
use Elastica\Bulk;

class ESCR_Importer extends ESCR_Base {
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

	public function import_single_product( $post ) {
		if ( 'product' != $post->post_type ) {
			return;
		}

		$data = $this->get_product_data( $post );
		if ( ! $data ) {
			return;
		}
		$ID = $post->ID;

		$json = $this->convert_json( $data );
		//Dev now...
		$item_id_list = ['2182','2180','2179','2166','2181'];
		$this->overwrite_woo_related( $ID, $item_id_list );

		//debug
		$transient_name = 'wc_related_' . $ID;
		$related_posts  = get_transient( $transient_name );
		var_dump($related_posts);
	}

	private function overwrite_woo_related( $ID, $item_id_list ) {
		$transient_name = 'wc_related_' . $ID;
		set_transient( $transient_name , $item_id_list , DAY_IN_SECONDS);
	}

	private function is_search_target( $Product ) {
		if ( $Product->is_visible() ) {
			return true;
		}
		return false;
	}

	private function get_term_name_list( $terms ) {
		foreach ( $terms as $key => $value ) {
			$term_name_list[] = $value->name;
		}
		return $term_name_list;
	}

	private function get_product_data( $post ) {
		$ID = $post->ID;
		$Product = wc_get_product( $ID );
		$data = '';
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
			//@TODO バリエーション名に対応する
			//$data['attr'] = $Product->get_attributes();
		}
		return apply_filters( 'escr_create_data', $data );
	}

	private function convert_json( $data ) {
		$json = json_encode( $data , JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		return $json;
	}
}
