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

define( 'ESCR_ROOT', __FILE__ );
require_once 'vendor/autoload.php';

$ESCR_Base = ESCR_Base::get_instance();
$ESCR_Base->init();

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
