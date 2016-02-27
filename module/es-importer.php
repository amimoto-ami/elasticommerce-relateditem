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

	public function import_all_product() {
		$query = apply_filters( 'escr-default-query', array(
			'post_type' => 'product',
			'posts_per_page' => -1,
		) );
		$the_query = new WP_Query( $query );
		while ( $the_query->have_posts() ) : $the_query->the_post();
			$ID = get_the_ID();
			$data[ $ID ] = $this->get_product_data( $ID );
		endwhile;
		$this->import_all_product_to_es( $data );

	}

	public function import_single_product( $post ) {
		if ( 'product' != $post->post_type ) {
			return;
		}

		$ID = $post->ID;
		$data[ $ID ] = $this->get_product_data( $ID );
		if ( ! $data ) {
			return;
		}

		$json = $this->convert_json( $data );
		$result = $this->import_all_product_to_es( $data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$item_id_list = $this->get_related_item_list( $json );
		//Dev now...
		$this->overwrite_woo_related( $ID, $item_id_list );

		//debug
		$transient_name = 'wc_related_' . $ID;
		$related_posts  = get_transient( $transient_name );
		var_dump($related_posts);
	}

	private function get_related_item_list( $json ) {
		$item_id_list = ['2182','2180','2179','2166','2181'];
		var_dump($json);
		return $item_id_list;
	}

	private function import_all_product_to_es( $dataList ) {
		try {
			$options = get_option( 'escr_settings' );
			$options['endpoint'] = 'search-woo-recommend-3rcvzwri7rddinzmsty7b3gdsy.ap-northeast-1.es.amazonaws.com';
			$client = $this->_create_client( $options );
			if ( ! $client ) {
				throw new Exception( 'Couldn\'t make Elasticsearch Client. Parameter is not enough.' );
			}

			$url = parse_url(home_url());
			if ( ! $url ) {
				throw new Exception( 'home_url() is disabled.' );
			}
			$index = $client->getIndex( $url['host'] );
			$index->create( array(), true );
			$type = $index->getType( 'product' );

			foreach ( $dataList as $ID => $data ) {
				$docs[] = $type->createDocument( (int) $ID , $data );
			}
			$bulk = new Bulk( $client );
			$bulk->setType( $type );
			$bulk->addDocuments( $docs );
			$res = $bulk->send();
			if ( ! $res->isOk()) {
				throw new Exception( $res->getError() );
			}
			return true;
		} catch ( Exception $e ) {
			$err = new WP_Error( 'Elasticsearch Import Error', $e->getMessage() );
			return $err;
		}
	}

	private function _create_client( $options ) {
		if ( empty( $options['endpoint'] ) ) {
			return false;
		}
		$client = new \Elastica\Client( array(
			'host' => $options['endpoint'],
			'port' => 80,
		));
		return $client;
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
		if ( ! $terms ) {
			return;
		}
		foreach ( $terms as $key => $value ) {
			$term_name_list[] = $value->name;
		}
		return $term_name_list;
	}

	private function get_product_data( $ID ) {
		$Product = wc_get_product( $ID );
		$data = '';
		if ( $this->is_search_target( $Product ) ) {
			$data['title'] = $Product->post->post_title;
			$data['content'] = wp_strip_all_tags( $Product->post->post_content, true );
			$data['excerpt'] = wp_strip_all_tags( $Product->post->post_excerpt, true );
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
