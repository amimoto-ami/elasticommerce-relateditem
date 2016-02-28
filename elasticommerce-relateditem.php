<?php
/**
 * Plugin Name: Elasticommerce-relateditem
 * Version: 0.1-alpha
 * Description: PLUGIN DESCRIPTION HERE
 * Author: YOUR NAME HERE
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

$ESCR_Base = ESCR_Base::get_instance();
$ESCR_Admin = ESCR_Admin::get_instance();
$ESCR_Admin->init();

function escr_related_item() {
	$data = escr_get_related_item();
	echo $html;
}

function escr_get_related_item() {
	$data = escr_get_related_item_data();
	$html = "<p>{$data}</p>";
	return $html;
}

function escr_get_related_item_data() {
	$data = 'hoge';
	return $data;
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
		var_dump('err');
		return false;
	} else {
		return true;
	}
}
