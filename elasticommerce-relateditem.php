<?php
/**
* Plugin Name: Elasticommerce Related Item
* Plugin URI: http://woothemes.com/products/woocommerce-extension/
* Description: You Can get Good Related Item List powered by Elasticsearch.
* Version: 0.2.0
* Author: WooThemes
* Author URI: http://woothemes.com/
* Developer: hideokamoto
* Developer URI: https://profiles.wordpress.org/hideokamoto
* Text Domain: elasticommerce-relateditem
* Domain Path: /languages
* @package Elasticommerce-relateditem
* @author hideokamoto
*
* Copyright: © 2009-2015 WooThemes.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Check WooCommerce plugin status
 * @version 0.2.0
 * @return bool
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
	return false;
}
define( 'ESCR_ROOT', __FILE__ );
require_once 'vendor/autoload.php';

/**
 * Get Related Item Object From Elasticsearch API
 * @since 0.1.0
 * @return object
 */
function escr_get_related_item_data() {
	$data = '';
	if ( is_singular() ) {
		$ESCR_Searcher = ESCR_Searcher::get_instance();
		$data = $ESCR_Searcher->get_related_item_data();
	}
	return $data;
}

/**
 * Get Related Item HTML
 *
 * @param string $class: add class for ul tag
 * @since 0.1.0
 * @return html
 */
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
		$html .= '</li>';
	}
	$html .= '</ul>';
	return $html;
}

/**
 * echo Related Item HTML
 *
 * @param string $class: add class for ul tag
 * @since 0.1.0
 */
function escr_related_item( $class = '' ) {
	$html = escr_get_related_item( $class );
	echo $html;
}

/**
 * Elasticommerce Related Item Error Handle class
 *
 * @class ESCR_Err
 * @since 0.1.0
 */
class ESCR_Error {

	/**
	 * Show notice for wp-admin if have error messages
	 *
	 * @since 0.1.0
	 **/
	public function admin_notices() {
		if ( $messageList = get_transient( 'escr-admin-errors' ) ) {
			$this->show_notice_html( $messageList );
		}
	}

	/**
	 * Set error message
	 *
	 * @param WP_Error
	 * @since 0.1.0
	 */
	public function show_error_message( $msg ) {
		if ( ! is_wp_error( $msg ) ) {
			$e = new WP_Error();
			$e->add( 'error' , $msg , 'escr-admin-errors' );
		} else {
			$e = $msg;
		}
		set_transient( 'escr-admin-errors' , $e->get_error_messages(), 10 );
	}

	/**
	 * echo error message html
	 *
	 * @param array
	 * @since 0.1.0
	 */
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

/**
 * Check WooCommerce Plugin status
 *
 * @since 0.1.0
 * @return bool
 */
function escr_is_activate_woocommerce() {
	$activePlugins = get_option('active_plugins');
	$plugin = 'woocommerce/woocommerce.php';
	if ( ! array_search( $plugin, $activePlugins ) && file_exists( WP_PLUGIN_DIR. '/'. $plugin ) ) {
		return false;
	} else {
		return true;
	}
}

/**
 * call WP-CLi Command Script
 *
 **/
if ( php_sapi_name() == 'cli' ) {
	if ( defined('WP_CLI') && WP_CLI ) {
		include __DIR__ . '/wp-esc.php';
	}
} else {
	$ESCR_Admin = ESCR_Admin::get_instance();
	$ESCR_Admin->init();
}
