<?php
/**
 * Plugin Name: Elasticommerce Related Item
 * Version: 0.1-alpha
 * Description: You Can get Good Related Item List powered by Elasticsearch.
 * Author: hideokamoto
 * Author URI: YOUR SITE HERE
 * Plugin URI: PLUGIN SITE HERE
 * Text Domain: elasticommerce-relateditem
 * Domain Path: /languages
 * @package Elasticommerce-relateditem
 */
if ( ! escr_is_activate_woocommerce() ) {
	$ESCR_Err = new ESCR_Error();
	$msg = array(
		__( 'Elasticommerce Need "WooCommerce" Plugin.' , 'elasticommerce-relateditem' ),
		__( 'Please Activate it.' , 'elasticommerce-relateditem' ),
	);
	$e = new WP_Error( 'Elasticommerce Activation Error', $msg );
	$ESCR_Err->show_error_message( $e );
	add_action( 'admin_notices', array( $ESCR_Err, 'admin_notices' ) );
	return;
}
define( 'ESCR_ROOT', __FILE__ );
require_once 'vendor/autoload.php';

function escr_get_related_item_data() {
	$data = '';
	if ( is_singular() ) {
		$ESCR_Searcher = ESCR_Searcher::get_instance();
		$data = $ESCR_Searcher->get_related_item_data();
	}
	return $data;
}

function escr_get_related_item( $class = '' ) {
	$dataList = escr_get_related_item_data();
	if ( ! $dataList ) {
		return '';
	}
	$ID = get_the_ID();
	$html = "<ul class='escr{$class}_row'>";
	foreach ( $dataList as $key => $data ) {
		$options = get_option( 'escr_settings' );
		if ( ! isset( $options['score'] ) ) {
			$options['score'] = 0.8;
		}
		if ( $options['score'] > $data->_score || $ID == $data->_id ) {
			continue;
		}
		$Product = wc_get_product( $data->_id );
		$url = $Product->get_permalink();
		$title = $Product->post->post_title;
		$price = $Product->get_price_html();
		$html .= "<li class='escr{$class}_item'>";
		$html .= "<p><a href='{$url}'>{$title}</a><br/>{$price}</p>";
		//@TODO:表示項目を選択可能にする
		//@TODO:Tag/Categoryの処理
		//$html .= "<p>$source->excerpt</p>";
		//$html .= $source->content;
		$html .= '</li>';
	}
	$html .= '</ul>';
	return $html;
}
function escr_related_item( $class = '' ) {
	$html = escr_get_related_item( $class );
	echo $html;
}

class ESCR_Error {
	public function admin_notices() {
		if ( $messageList = get_transient( 'escr-admin-errors' ) ) {
			$this->show_notice_html( $messageList );
		}
	}

	public function show_error_message( $msg ) {
		if ( ! is_wp_error( $msg ) ) {
			$e = new WP_Error();
			$e->add( 'error' , $msg , 'escr-admin-errors' );
		} else {
			$e = $msg;
		}
		set_transient( 'escr-admin-errors' , $e->get_error_messages(), 10 );
	}

	public function show_notice_html( $messageList ) {
		foreach ( $messageList as $key => $messages ) {
			$html  = "<div class='error'><ul>";
			foreach ( (array)$messages as $key => $message ) {
				$html .= "<li>{$message}</li>";
			}
			$html .= '</ul></div>';
		}
		echo $html;
	}
}
function escr_is_activate_woocommerce() {
	$activePlugins = get_option('active_plugins');
	$plugin = 'woocommerce/woocommerce.php';
	if ( ! array_search( $plugin, $activePlugins ) && file_exists( WP_PLUGIN_DIR. '/'. $plugin ) ) {
		return false;
	} else {
		return true;
	}
}

if ( php_sapi_name() == 'cli' ) {
	if ( defined('WP_CLI') && WP_CLI ) {
		include __DIR__ . '/wp-esc.php';
	}
} else {
	$ESCR_Admin = ESCR_Admin::get_instance();
	$ESCR_Admin->init();
}
