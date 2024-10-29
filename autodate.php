<?php
/**
 * Plugin Name: Autodate
 * Description: This plugin allows you to add dates to your site that will update automatically according to the interval you specify
 * Plugin URI: http://sergei-sahapov.online/page/wp-autodate
 * Author URI: http://sergei-sahapov.online
 * Author: Sergei Sahapov
 * Version: 1.0
 * 
 * Text Domain: autodate
 * Domain Path: /languages/
 
 * License: GPLv2 or later
*/

if ( !function_exists( 'add_action' ) ) {
	echo "It's just a plugin";
	exit;
}
$dir=plugin_dir_path( __FILE__ )."php_inc/";
include_once("{$dir}autodate.php");

$main_obj=new Autodate();

add_action( 'plugins_loaded', 'register_autodate_textdomain' );

function register_autodate_textdomain(){
	load_plugin_textdomain( 'autodate', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

register_activation_hook( __FILE__, array($main_obj,Autodate::ACTIV_DEACTIV_DATA["activation"][1]) );
register_deactivation_hook( __FILE__, array($main_obj, Autodate::ACTIV_DEACTIV_DATA["deactivation"][1]) );

foreach (Autodate::ACT_AND_FUNC_DATA as $group_name=>$data) {
	if($group_name=="js_add"){
		add_action( $data[0], array($main_obj,$data[1]), 99 );
	}
	else {
		add_action( $data[0], array($main_obj,$data[1]) );
	}
}

add_shortcode(Autodate::SHORTCODE_DATA[0],"Autodate::".Autodate::SHORTCODE_DATA[1]);
