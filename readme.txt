=== Elasticommerce Related Items ===
Contributors: hideokamoto,megumithemes,horike,kel-dc
Tags: WooCommerce,Elasticsearch,recommend
Requires at least: 4.3.1
Tested up to: 4.3.1
Stable tag: 0.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-2.0.html

You can get good Related Item List powered by Elasticsearch.

== Description ==

This plugin gives you Related Item Widget and function.

If you want to show Related Item List, you can do following actions:

- Add `Elasticommerce Related Widgets` in your widgets area.
- Add `escr_related_item();` function in your theme or plugins.
- If you only want to get the data, you can use `escr_get_related_item();` function.

*Notice*
This plugin requires *WooCommerce Plugin* , so please install it.
If *WooCommerce Plugin* is not installed or activated, this plugin will not work.

*Required*
This Plugin **cannot use** Elasticsearch version 2.x.
Please use Elasticsearch version 1.5 instead.

== Installation ==

This section describes how to install the plugin and get it working.


1. Upload `elasticommerce-relateditem` directory to `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Input setting data (eg.Elasticsearch Endpoint Address).
4. You can do the following actions:
	a: Place `<?php escr_related_item(); ?>` in your templates.
	b: Add `Elasticommerce Related Widgets` in your widgets area.

== Frequently Asked Questions ==

= Can I get Related Item Data only? =

If you only want to get the data, you can use `escr_get_related_item();` function.

= What about foo bar? =

Answer to foo bar dilemma.

== Changelog ==

= 0.1 =
* Initial Release.

== Upgrade Notice ==

= 0.1 =
* Initial Release.
