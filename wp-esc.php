<?php
/**
 * Control Elasticommerce.
 */
require_once  __DIR__ . '/module/base.php';
require_once  __DIR__ . '/module/es-importer.php';
require_once  __DIR__ . '/module/es-searcher.php';
require_once  __DIR__ . '/module/es-admin.php';
class Elascticommerce_Related_Command extends WP_CLI_Command {

    /**
     * Import All Product to Elasticsearch
     *
     * ## EXAMPLES
     *
     *     wp esc import_all          : Import All Products to Elasticsearch
	 *     wp esc import_all --update : Import and Update Related Product List.
     *
     */
    function import_all( $args, $assoc_args ) {
		if ( array_search( 'update', $assoc_args ) ) {
			echo 'Import and Update Related Product List...';
			$ESCR_Admin = ESCR_Admin::get_instance();
			$result = $ESCR_Admin->all_update_related_product();
		} else {
			echo 'Import All Products to Elasticsearch...';
			$ESCR_Importer = ESCR_Importer::get_instance();
			$result = $ESCR_Importer->import_all_product();
		}
		if ( ! is_wp_error( $result ) ) {
			WP_CLI::success( "Import Success" );
		}
    }
}

WP_CLI::add_command( 'esc', 'Elascticommerce_Related_Command' );
