=== Elasticommerce Related Items ===
Contributors: hideokamoto,megumithemes,horike
Tags: WooCommerce,Elasticsearch,recommend
Requires at least: 4.3.1
Tested up to: 4.3.1
Stable tag: 0.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 2.5
WC tested up to: 2.5

You Can get Good Related Item List powered by Elasticsearch.

== Description ==

This plugin give you Related Item Widget and function.

If you want to show Related Item List.You can do following action.

- Add `Elasticommerce Related Widgets` in your widgets area.
- add `escr_related_item();` function in your theme or plguins.
- if you want to get just data, you can use `escr_get_related_item();` function.

*Notice*
This plugin must need *WooCommerce Plugin* please install it.
If *WooCommerce Plugin* is not installed or deactivated, this plugin doesn't work.

*Required*
This Plugin **can not use** Elasticsearch version 2.x.
Please use Elasticsearch version 1.5

== Installation ==

This section describes how to install the plugin and get it working.


1. Upload `elasticommerce-relateditem` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Input setting data (eg.Elasticsearch Endpoint Address).
4. You can do following action.
	a:Place `<?php escr_related_item(); ?>` in your templates.
	b:Add `Elasticommerce Related Widgets` in your widgets area.

== Frequently Asked Questions ==

= Can i get only Related Item Data ? =

If you want to get just data, you can use `escr_get_related_item();` function.

= What about foo bar? =

Answer to foo bar dilemma.

== Changelog ==

= 0.1 =
* Initial Release.

== Upgrade Notice ==

= 0.1 =
* Initial Release.
