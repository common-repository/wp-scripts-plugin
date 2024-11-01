<?php
/* Add your custom javascripts */
/*
 * You can add your custom scripts with wp_regiset_script() - wordpress core function (recommended) -
 * 
 * or
 * register your file with 'wp_scripts_add_custom($handle, $src, $deps, $ver)' function
 * $handle : unique name of javascript
 * $src : relative path from current directory(wp-scripts/js/custom) or external url with http scheme
 * $deps : array value of depenencies.
 * $ver : version number of your file. if false, we would apply plugin version number.
 * See the examples below
 * 
 */

wp_scripts_add_custom('jquery-site-custom', '/jquery/jquery.site.custom.js', array('jquery', 'jquery-spoiler', 'humanmsg'), false);
wp_scripts_add_custom('jquery-sweetTitles', '/jquery/jquery.sweetTitles.js', array('jquery'), false);
wp_scripts_add_custom_style('jquery-sweetTitles', '/css/sweetTitles.css', false, false);

wp_scripts_add_custom('addEvent', '/addEvent.js', false, false);
wp_scripts_add_custom('sweetTitles', '/sweetTitles.js', array('addEvent'), false);
wp_scripts_add_custom_style('sweetTitles', '/css/sweetTitles.css', false, false);

//wp_scripts_add_custom('site-custom', '/site.custom.js', false, false);
//wp_scripts_add_custom('moo-fx', '/mootools/moo.fx.all.js', array('prototype'), '2.0');

?>