<?php
/**
 * Show Admin Panel Class
 *
 * @package Elasticommerce-relateditem
 * @author hideokamoto
 * @since 0.1.0
 **/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Admin Page Class
 *
 * @class ESCR_Admin
 * @since 0.1.0
 */
class ESCR_Admin extends ESCR_Base {
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

	const INDEX_ALL = 'escr_all_index';

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
	 * @return ESCR_Admin
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
	 * Init action filters
	 *
	 * @since 0.1.0
	 */
	public function init() {

		add_action( 'widgets_init', array( $this, 'escr_register_widgets' ) );
		add_action( 'transition_post_status' , array( $this, 'update_related_product' ) , 10 , 3 );

		if ( $this->is_activate_esc_search_form() ) {
			add_action( 'wpels_after_setting_form', array( $this, 'escr_related_options' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		}

		add_action( 'admin_init', array( $this, 'settings_init' ) );
	}

	/**
	 * Register Widget
	 *
	 * @since 0.1.0
	 */
	public function escr_register_widgets() {
		register_widget( 'ESC_RelatedItems' );
	}

	/**
	 * Register Admin Option Page
	 *
	 * @since 0.1.0
	 */
	public function add_admin_menu() {
		add_options_page( 'Elasticommerce Services', 'Elasticommerce Services', 'manage_options', 'escr_related', array( $this, 'escr_related_options' ) );
	}

	/**
	 * Routing function
	 *
	 * @since 0.1.0
	 */
	public function settings_init() {
		$this->_register_admin_panels();
		if ( empty( $_POST ) ) {
			return;
		}
		if ( isset( $_POST[ self::INDEX_ALL] ) && $_POST[ self::INDEX_ALL ] ) {
			if ( check_admin_referer( self::INDEX_ALL, self::INDEX_ALL ) ) {
				$this->all_update_related_product();
			}
		}
	}

	/**
	 * Register admin setting field
	 *
	 * @since 0.1.0
	 */
	private function _register_admin_panels() {
		register_setting( 'ElasticommerceRelated', 'escr_settings' );
		add_settings_section(
			'escr_RelatedItem_settings',
			__( '', self::$text_domain ),
			array( $this, 'escr_settings_section_callback' ),
			'ElasticommerceRelated'
		);
		if ( ! $this->is_activate_esc_search_form() ) {
			add_settings_field(
				'endpoint',
				__( 'Endpoint', self::$text_domain ),
				array( $this, 'endpoint_render' ),
				'ElasticommerceRelated',
				'escr_RelatedItem_settings'
			);
		}
		add_settings_section(
			'escr_RelatedScore_settings',
			__( 'Search Score Setting', self::$text_domain ),
			array( $this, 'escr_settings_score_section_callback' ),
			'ElasticommerceRelated'
		);
		add_settings_field(
			'score',
			__( 'Search Score <br/>( Min 0.0 ~ Max 1.0 )', self::$text_domain ),
			array( $this, 'score_render' ),
			'ElasticommerceRelated',
			'escr_RelatedScore_settings'
		);
		add_settings_section(
			'escr_RelatedTarget_settings',
			__( 'Search Target Setting', self::$text_domain ),
			array( $this, 'escr_settings_target_section_callback' ),
			'ElasticommerceRelated'
		);

		add_settings_field(
			'target',
			__( 'Select Search Target <br/>( Multiple )', self::$text_domain ),
			array( $this, 'search_target_render' ),
			'ElasticommerceRelated',
			'escr_RelatedTarget_settings'
		);
	}

	/**
	 * echo input field (elasticsearch endpoint)
	 *
	 * @since 0.1.0
	 */
	public function endpoint_render() {
		$options = get_option( 'escr_settings' );
		$endpoint = '';
		if ( isset( $options['endpoint'] ) ) {
			$endpoint = $option['endpoint'];
		}
		echo "<input type='text' name='escr_settings[endpoint]' value='{$endpoint}'>";
	}

	/**
	 * echo input field( Elasticsearch score)
	 *
	 * @since 0.1.0
	 */
	public function score_render() {
		$options = get_option( 'escr_settings' );
		if ( ! isset( $options['score'] ) ) {
			$options['score'] = 0.8;
		}
		echo "<input type='text' name='escr_settings[score]' value='". $options['score']. "'>";
	}

	/**
	 * echo selection field( Elasticsearch search target )
	 *
	 * @since 0.1.0
	 */
	public function search_target_render() {
		$target = $this->get_elasticsearch_target();
		$default_target_list = array(
			'product_excerpt' => __( 'Product Short Description', 'woocommerce' ),
			'product_content' => __( 'Product Description', 'woocommerce' ),
			'product_display_price' => __( 'Prices', 'woocommerce' ),
			'product_cat' => __( 'Product Category', 'woocommerce' ),
			'product_tag' => __( 'Product Tag', 'woocommerce' ),
			'product_title' => __( 'Product Name', 'woocommerce' )
		);
		$size = count( $default_target_list );
		echo "<select name='escr_settings[target][]' size='{$size}' multiple>";
		foreach( $default_target_list as $key => $default_target ) {
			$is_selected = '';
			if ( false !== array_search( $key, $target ) ) {
				$is_selected = 'selected';
			}
			echo "<option value='{$key}' {$is_selected}>{$default_target}</option>";
		}
		echo "</select>";
	}

	/**
	 * echo setting section description
	 *
	 * @since 0.1.0
	 */
	public function escr_settings_section_callback() {
		echo __( '', self::$text_domain );
	}

	/**
	 * echo Search Score Field Description
	 *
	 * @since 0.1.0
	 */
	public function escr_settings_score_section_callback() {
		echo __( 'You can exchange Search Score.', self::$text_domain );
	}

	/**
	 * echo Search Target Field Description
	 *
	 * @since 0.1.0
	 */
	public function escr_settings_target_section_callback() {
		echo __( 'You can select search target fields.', self::$text_domain );
	}

	/**
	 * echo form area
	 *
	 * @since 0.1.0
	 */
	public function escr_related_options() {
		if ( $this->is_activate_esc_search_form() ) {
			echo '<hr/>';
		} else {
			echo '<h2>Elasticommerce Services</h2>';
		}
		echo '<h3>Elasticommerce Related Items Settings</h3>';
		echo "<form action='options.php' method='post'>";
		settings_fields( 'ElasticommerceRelated' );
		do_settings_sections( 'ElasticommerceRelated' );
		submit_button();
		echo '</form>';
		echo "<form action='' method='post'>";
		submit_button( __( 'Import All Products', self::$text_domain ) );
		wp_nonce_field( self::INDEX_ALL , self::INDEX_ALL , true );
		echo '</form>';
	}

	/**
	 * Update related product list using Elasticsearch
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function all_update_related_product() {
		$Importer = ESCR_Importer::get_instance();
		$result = $Importer->import_all_product();
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$Searcher = ESCR_Searcher::get_instance();

		$type = $this->get_index_type();
		$query = apply_filters( 'escr-default-query', array(
			'post_type' => $type,
			'posts_per_page' => -1,
		) );
		$the_query = new WP_Query( $query );
		while ( $the_query->have_posts() ) : $the_query->the_post();
			$ID = get_the_ID();
			$Product = wc_get_product( $ID );
			$item_id_list = $Searcher->get_related_item_id_list( $Product->post );
			$this->overwrite_woo_related( $ID, $item_id_list );
		endwhile;
		return true;
	}

	/**
	 * Update Related Prduct if product update
	 *
	 * @param $new_status string
	 * @param $old_status string
	 * @param $post WC_Product
	 * @since 0.1.0
	 * @return array
	 */
	public function update_related_product( $new_status, $old_status, $post ) {
		if ( ! $this->is_import( $new_status , $old_status ) ) {
			return;
		}

		if ( ! $this->is_activate_esc_search_form() ) {
			$Importer = ESCR_Importer::get_instance();
			$result = $Importer->import_all_product();
			//@TODO Add single data index function
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$Searcher = ESCR_Searcher::get_instance();
		$item_id_list = $Searcher->get_related_item_id_list( $post );
		if ( is_wp_error( $item_id_list ) ) {
			return $item_id_list;
		}
		$this->overwrite_woo_related( $post->ID, $item_id_list );
	}

	/**
	 * Check Post status
	 *
	 * @param $new_status string
	 * @param $old_status string
	 * @since 0.1.0
	 * @return bool
	 */
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

	/**
	 * Overwrite transient wc_related_{product_id}
	 *
	 * @param $ID string
	 * @param $product_id srray
	 * @since 0.1.0
	 */
	private function overwrite_woo_related( $ID, $item_id_list ) {
		$transient_name = 'wc_related_' . $ID;
		set_transient( $transient_name , $item_id_list , DAY_IN_SECONDS);
	}
}
