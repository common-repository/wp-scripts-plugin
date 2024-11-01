=== WP-Scripts ===
Contributors: 082net
Tags: wp-scripts, javascript, jquery, prototype, mootools, lightbox, slimbox, smoothbox, thickbox, sweetTitles
Requires at least: 2.6
Tested up to: 2.7.1
Stable tag: 2.0.1

Helps you add popular javascript library or plugin on blog pages with wordpress's script-loader.

== Description ==

Wordpress has awsome core system for loading javascript called 'script-loader', and this plugin helps you loading any registered javascripts on your blog pages.

See "FAQ" and "Screenshots" for more details.

== Installation ==

1. Upload `wp-scripts` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Can I register my own javascript? =

There's two way to register your custom javascript (alpha)

*   by using wordpress core function `wp_register_script()` (recommended)
*   by editing `/wp-scripts/js/custom/_custom.php` file. There's some simple examples and descriptions.

== Screenshots ==

1. General independent scripts
2. Dependencies
3. Checked dependent script
4. Unchecked independent script
