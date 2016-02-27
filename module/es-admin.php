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
		add_options_page( 'WP Elasticsearch', 'WP Elasticsearch', 'manage_options', 'wp_elasticsearch', array( $this, 'wpels_options_page' ) );
	}

	public function settings_init() {
		register_setting( 'wpElasticsearch', 'wpels_settings' );

		add_settings_section(
			'wpels_wpElasticsearch_section',
			__( '', 'wp-elasticsearch' ),
			array( $this, 'escr_settings_section_callback' ),
			'wpElasticsearch'
		);

		add_settings_field(
			'endpoint',
			__( 'Endpoint', 'wp-elasticsearch' ),
			array( $this, 'endpoint_render' ),
			'wpElasticsearch',
			'wpels_wpElasticsearch_section'
		);

	}
	public function endpoint_render() {
		$options = get_option( 'wpels_settings' );
		echo "<input type='text' name='wpels_settings[endpoint]' value='". $options['endpoint']. "'>";
	}

	public function escr_settings_section_callback() {

		echo __( '', 'wp-elasticsearch' );

	}


	public function wpels_options_page() {

		echo "<form action='options.php' method='post'>";
		echo '<h2>WP Elasticsearch</h2>';
		settings_fields( 'wpElasticsearch' );
		do_settings_sections( 'wpElasticsearch' );
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
