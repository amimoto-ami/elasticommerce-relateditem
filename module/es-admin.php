<?php
class ESCR_Admin extends ESCR_Base {
	private static $instance;
	private static $text_domain;

	const INDEX_ALL = 'escr_all_index';

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
		add_action( 'transition_post_status' , array( $this, 'update_related_product' ) , 10 , 3 );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );
	}

	public function escr_register_widgets() {
		register_widget( 'ESC_RelatedItems' );
	}

	public function add_admin_menu() {
		add_options_page( 'Elasticommerce Related Items', 'Elasticommerce Related Items', 'manage_options', 'escr_related', array( $this, 'escr_related_options' ) );
	}

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

	private function _register_admin_panels() {
		register_setting( 'ElasticommerceRelated', 'escr_settings' );
		add_settings_section(
			'escr_RelatedItem_settings',
			__( '', 'escr-relateditem' ),
			array( $this, 'escr_settings_section_callback' ),
			'ElasticommerceRelated'
		);
		if( ! get_option( 'wpels_settings' ) ) {
			add_settings_field(
				'endpoint',
				__( 'Endpoint', 'escr-relateditem' ),
				array( $this, 'endpoint_render' ),
				'ElasticommerceRelated',
				'escr_RelatedItem_settings'
			);
		}
		add_settings_field(
			'score',
			__( 'Search Score', 'escr-relateditem' ),
			array( $this, 'score_render' ),
			'ElasticommerceRelated',
			'escr_RelatedItem_settings'
		);
	}

	public function endpoint_render() {
		$options = get_option( 'escr_settings' );
		echo "<input type='text' name='escr_settings[endpoint]' value='". $options['endpoint']. "'>";
	}

	public function score_render() {
		$options = get_option( 'escr_settings' );
		if ( ! isset( $options['score'] ) ) {
			$options['score'] = 0.8;
		}
		echo "<input type='text' name='escr_settings[score]' value='". $options['score']. "'>";
	}
	public function escr_settings_section_callback() {
		echo __( '', 'escr-relateditem' );
	}


	public function escr_related_options() {
		echo "<form action='options.php' method='post'>";
		echo '<h2>Elasticommerce Related Items</h2>';
		settings_fields( 'ElasticommerceRelated' );
		do_settings_sections( 'ElasticommerceRelated' );
		submit_button();
		echo '</form>';
		echo "<form action='' method='post'>";
		submit_button( __( 'Import All Products', self::$text_domain ) );
		wp_nonce_field( self::INDEX_ALL , self::INDEX_ALL , true );
		echo '</form>';
	}

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
			$item_id_list = $Searcher->get_related_item_list( $Product->post );
			$this->overwrite_woo_related( $ID, $item_id_list );
		endwhile;
		return true;
	}

	public function update_related_product( $new_status, $old_status, $post ) {
		if ( ! $this->is_import( $new_status , $old_status ) ) {
			return;
		}
		$Importer = ESCR_Importer::get_instance();
		$result = $Importer->import_all_product();
		//@TODO:全商品のインデックス登録は専用ボタンをつけたい
		//@TODO:記事更新時のインデックスはその記事だけにしたい
		//$result = $Importer->import_single_product( $post );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$Searcher = ESCR_Searcher::get_instance();
		$item_id_list = $Searcher->get_related_item_list( $post );
		if ( is_wp_error( $item_id_list ) ) {
			return $item_id_list;
		}
		$this->overwrite_woo_related( $post->ID, $item_id_list );
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

	private function overwrite_woo_related( $ID, $item_id_list ) {
		$transient_name = 'wc_related_' . $ID;
		set_transient( $transient_name , $item_id_list , DAY_IN_SECONDS);
	}
}
