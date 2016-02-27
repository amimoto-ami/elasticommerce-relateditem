<?php
class ESCR_Searcher extends ESCR_Base {
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

	private function _get_elasticsearch_result( $endpoint , $search_type ) {
		try {
			$endpoint .= "&mlt_fields={$search_type}";
			$response = wp_remote_get( $endpoint );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			if ( 'OK' != $response['response']['message'] ) {
				$msg  = 'Connection Failed to Elasticsearch API.HTTP Code is '. $response['response']['code'];
				$msg .= ' '. $response['response']['message'];
				throw new Exception( $msg );
			}

			$response = json_decode( $response['body']  );
			if ( ! isset( $response->error ) ) {
				$result = $response->hits->hits ;
			}
			return $result;
		} catch ( Exception $e ) {
			$err = new WP_Error( 'Elasticsearch Search Error', $e->getMessage() );
			return $err;
		}
	}

	private function _create_elasticsearch_endpoint( $post ) {
		try {
			$options = $this->get_elasticsearch_endpoint();
			$url = parse_url( home_url() );
			if ( ! $url ) {
				throw new Exception( 'home_url() is disabled.' );
			}
			$query = apply_filters( 'escr_mlt_base_query' , 'min_term_freq=1&min_doc_freq=1' );
			$es_endpoint = 'https://' . $options['endpoint'] . '/';
			$es_endpoint .= $url['host']. '/'. $this->get_index_type(). '/'. $post->ID . "/_mlt?{$query}";
			return $es_endpoint;
		} catch ( Exception $e ) {
			$err = new WP_Error( 'Elasticsearch Search Error', $e->getMessage() );
			return $err;
		}

	}

	public function get_related_item_list( $post ) {
		try {
			$es_endpoint = $this->_create_elasticsearch_endpoint( $post );
			if ( is_wp_error( $es_endpoint ) ) {
				return $es_endpoint;
			}
			$search_types = $this->get_elasticsearch_target();
			$search_types = apply_filters( 'escr_search_types', $search_types );
			$ids = [];
			foreach( $search_types as $key => $search_type ) {
				var_dump($search_type);
				$result = $this->_get_elasticsearch_result( $es_endpoint, $search_type );
				if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
					$ids = array_merge( $ids, $this->_parse_elasticsearch_result( $result ) );
				}
			}
			$ids = array_unique( $ids );
			$ids = array_merge( $ids );
			return $ids;
		} catch ( Exception $e ) {
			$err = new WP_Error( 'Elasticsearch Search Error', $e->getMessage() );
			return $err;
		}
	}

	private function _parse_elasticsearch_result( $result ) {
		$options = get_option( 'escr_settings' );
		if ( ! isset( $options['score'] ) ) {
			$options['score'] = 0.8;
		}
		$ids = [];

		foreach ( $result as $key => $value ) {
			if ( $options['score'] > $value->_score ) {
				continue;
			}
			$ids[] = $value->_id;
		}
		return $ids;
	}

}
