<?php
/**
 * Searching Elasticsearch  Class
 *
 * @package Elasticommerce-relateditem
 * @author hideokamoto
 * @since 0.1.0
 **/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Search Class that using Elasticsearch API
 *
 * @class ESCR_Searcher
 * @since 0.1.0
 */
class ESCR_Searcher extends ESCR_Base {
	/**
	 * Instance Class
	 * @access private
	 */
	private static $instance;

	/**
	 * text domain
	 * @access private
	 */
	private static $text_domain;

	/**
	 * Constructer
	 * Set text domain on class
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		self::$text_domain = ESCR_Base::text_domain();
	}

	/**
	 * Get Instance Class
	 *
	 * @return ESCR_Searcher
	 * @since 0.1.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	/**
	 * send GET request to Elasticsearch API
	 *
	 * @param $endpoint string
	 * @param $search string
	 * @return object
	 * @since 0.1.0
	 * @throws WP_Error
	 */
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

	/**
	 * create Elasticsearch API Path & Query
	 *
	 * @param $post WC_Product
	 * @return string
	 * @since 0.1.0
	 * @throws WP_Error
	 */
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

	/**
	 * Get Related Item Object from Elasticsearch API
	 *
	 * @param $post WC_Product
	 * @return object
	 * @since 0.1.0
	 * @throws WP_Error
	 */
	public function get_related_item_list( $post ) {
		try {
			$search_types = $this->get_elasticsearch_target();
			$search_types = apply_filters( 'escr_search_types', $search_types );

			$options = $this->get_elasticsearch_endpoint();
			$url = parse_url( home_url() );
			if ( ! $url ) {
				throw new Exception( 'home_url() is disabled.' );
			}
			$es_endpoint = 'https://' . $options['endpoint'] . '/';
			$es_endpoint .= $url['host']. '/'. $this->get_index_type(). '/_search';

			$text = wp_strip_all_tags( $post->post_content, true );
			$mapping = array(
				'query' => array(
					'more_like_this' => array(
						'fields' => $search_types,
						'like_text' => $text,
						'min_term_freq' => 1,
						'min_doc_freq' => 1,
						'percent_terms_to_match' => apply_filters( 'escr_widget_score', 0.5 ),
						'analyzer' => 'kuromoji',
					),
				),
			);
			$arg = array(
				'body' => json_encode( $mapping, JSON_UNESCAPED_UNICODE ),
			);
			$result = wp_remote_get( $es_endpoint, $arg );
			if ( is_wp_error( $result ) ) {
				return $result;
			} elseif ( 'OK' != $result['response']['message'] ) {
				throw new Exception( $result['response']['code']. ':'. $result['response']['message'] );
			}
			$data = json_decode( $result['body'] );
			$hits = $data->hits->hits;
			return $hits;
		} catch ( Exception $e ) {
			$err = new WP_Error( 'Elasticsearch Search Error', $e->getMessage() );
			return $err;
		}
	}

	/**
	 * Get Related Item ID List
	 *
	 * @param $post WC_Product
	 * @return array
	 * @since 0.1.0
	 * @throws WP_Error
	 */
	public function get_related_item_id_list( $post ) {
		try {
			$es_endpoint = $this->_create_elasticsearch_endpoint( $post );
			if ( is_wp_error( $es_endpoint ) ) {
				return $es_endpoint;
			}
			$search_types = $this->get_elasticsearch_target();
			$search_types = apply_filters( 'escr_search_types', $search_types );
			$ids = [];
			foreach( $search_types as $key => $search_type ) {
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

	/**
	 * Parse Elasticsearch Return Parameter
	 *
	 * @param $result array
	 * @return array
	 * @since 0.1.0
	 * @throws WP_Error
	 */
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

	/**
	 * Get Related Item Data
	 *
	 * @return object
	 * @since 0.1.0
	 */
	public function get_related_item_data() {
		$ID = get_the_ID();
		if ( ! $ID ) {
			return false;
		}
		$post = get_post( $ID );
		$data = $this->get_related_item_list( $post );
		if ( is_wp_error( $data ) ) {
			$msg = $data->get_error_messages();
			if ( is_array( $msg ) ) {
				foreach( $msg as $value ) {
					error_log( $value );
				}
			} else {
				error_log( $msg );
			}
			return null;
		}
		return $data;
	}
}
