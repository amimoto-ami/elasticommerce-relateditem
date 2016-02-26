<?php
use Elastica\Client;
use Elastica\Query;
use Elastica\Query\QueryString;
use Elastica\Type\Mapping;
use Elastica\Bulk;

class ESCR_Importer extends ESCR_Base {
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
}
