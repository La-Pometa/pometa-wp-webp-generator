<?php

/*
	Plugin Name: Pometa Imatges
	Plugin URI: http://www.lapometa.com/plugins/pometaimages
	Description: Gestió Imatges Pometa
	Version: 1.2
	Requires at least: 5.6
	Author: La Pometa
	Author URI: https://github.com/La-Pometa
    GitHub Plugin URI: La-Pometa/pometa-wp-webp-generator
    Primary Branch: main
	License: GPL2
	Text Domain: pometaimagesltd
*/


	/* DEFINES */

	define("POMETAIMAGES_LTD","pometaimagesltd");
  	define('POMETAIMAGES_PLUGIN_SERVER_PATH', plugin_dir_path( __FILE__ ));
  	define('POMETAIMAGES_PLUGIN_SERVER_URL', plugin_dir_url( __FILE__ ));


	/* ENQUEUE FILES CSS & JS */
	add_action( 'init', 'pometaimages_load_textdomain' );
	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 */
	function pometaimages_load_textdomain() {
		load_plugin_textdomain( 'pometaimagesltd', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
	}


	add_action("wp_head","pometaimages_wp_header_css_js");
	add_action( 'admin_enqueue_scripts', 'pometaimages_admin_header_css_js' );

	function pometaimages_wp_header_css_js() {
	}
	function pometaimages_admin_header_css_js() {
		wp_register_style("adminpometaimagescss",plugins_url('assets/css/pometaimages.admin.css', __FILE__));

		wp_enqueue_style("adminpometaimagescss");
	}


	/* INCLUDES */

	require_once("includes/common.php");
	require_once("includes/convert.php");
	//require_once("includes/rest-api-filter.php");
	require_once("includes/settings.php");
	require_once("vendors/init.php");

	require_once("general.php");
	require_once("rest.php");


    
    

