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

	}
	public function endpoint_render() {
		$options = get_option( 'escr_settings' );
		echo "<input type='text' name='escr_settings[endpoint]' value='". $options['endpoint']. "'>";
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
