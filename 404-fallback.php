<?php

/**
 * Plugin Name:     404 Fallback
 * Plugin URI:      https://vandragt.com/projects/404-fallback
 * Description:     Redirect missing pages to a different site.
 * Author:          Sander van Dragt
 * Author URI:      https://vandragt.com
 * Text Domain:     404-fallback
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         404_Fallback
 */

namespace fb404;

require_once plugin_dir_path( __FILE__ ) . '/lib/menus.php';
require_once plugin_dir_path( __FILE__ ) . '/lib/menu-site.php';

function redirect_404() {
	global $wp;

	$request = wp_unslash( $_SERVER['REQUEST_URI'] );
	$url = stripslashes( get_option( 'fb404_setting_fallback_url' ));
	if ( is_404() && !empty($url) ) {
		$location      =  $url . $request;
		$status        = 302;
		$x_redirect_by = '404 Fallback';
		wp_redirect( $location, $status, $x_redirect_by );
		die();
	}
}

add_action( 'template_redirect', __NAMESPACE__ . '\\redirect_404' );

/**
 * Add a site menu called Cluster
 */
function add_settings_menu() {
	wpc_menu_register_page( [
		'id'         => 'fb404',
		'parent_slug' => 'plugins.php',
		'menu_slug'  => __NAMESPACE__ . '\\page',
		'page_title' => '404 Fallback Settings',
		'menu_title' => '404 Fallback',
		'action'     => __NAMESPACE__ . '\\page_update_handler',
	] );

	wpc_menu_register_settings( 'fb404', [
		'general' => [
			__NAMESPACE__ . '\\setting_fallback_url' => 'Fallback URL',
		],
	] );

	// Allow for anyone else to add settings.
	do_action( __NAMESPACE__ . '-' . __FUNCTION__ );
}

add_action( 'init', __NAMESPACE__ . '\\add_settings_menu' );

/**
 * Templating for our menu page. The function must match the 'menu_slug' in the menu config.
 */
function page() {
	// Use the default page rendering instead of templating our page here.
	wpc_menu_page_render( 'fb404' );
}

/**
 * Handling the form submission. The function must match the 'action' key in the menu config.
 */
function page_update_handler() {
	// TODO: use debugger to see if this function is actually called.
	// Use the default page update handler instead of processing our options in a custom way.
	wpc_menu_page_update_handler( 'fb404' );
}

function setting_fallback_url_render() {
	$option_name = __NAMESPACE__ . '\\setting_fallback_url';
	$config      = stripslashes( get_option( $option_name ) );
	printf( '<input name="%1$s" value="%2$s"
	size="48"/>', $option_name, $config );
}
