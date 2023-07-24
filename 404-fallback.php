<?php

/**
 * Plugin Name:     404 Fallback
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     404-fallback
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         404_Fallback
 */

require_once plugin_dir_path( __FILE__ ) . '/lib/menus.php';
require_once plugin_dir_path( __FILE__ ) . '/lib/menu-site.php';

function fb404_redirect_404() {
	/**
	 * We only want to handle 404 requests.
	 */
	if ( ! is_404() ) {
		return;
	}

	$request = fb404_get_request();

	$url = fb404_validate_url( get_option( 'fb404_setting_fallback_url', '' ) );
	if ( empty( $url ) ) {
		return;
	}

	/**
	 * We make sure this URL has a trailing slash, as we removed it from the Request.
	 */
	$location  = trailingslashit( $url);
	$location .= $request;

	$status        = 302;
	$x_redirect_by = '404 Fallback';

	wp_redirect( $location, $status, $x_redirect_by );
	die;
}
add_action( 'template_redirect', 'fb404_redirect_404' );

/**
 * Add a site menu called Cluster
 */
function fb404_add_settings_menu() {
	wpc_menu_register_page( [
		'id'         => 'fb404',
		'parent_slug' => 'plugins.php',
		'menu_slug'  => 'fb404_page',
		'page_title' => '404 Fallback Settings',
		'menu_title' => '404 Fallback',
		'action'     => 'fb404_page_update_handler',
	] );

	wpc_menu_register_settings( 'fb404', [
		'general' => [
			'fb404_setting_fallback_url' => 'Fallback URL',
		],
	] );
	do_action( __FUNCTION__ );
}

add_action( 'init', 'fb404_add_settings_menu' );

/**
 * Templating for our menu page. The function must match the 'menu_slug' in the menu config.
 */
function fb404_page() {
	// Use the default page rendering instead of templating our page here.
	wpc_menu_page_render( 'fb404' );
}

/**
 * Handling the form submission. The function must match the 'action' key in the menu config.
 */
function fb404_page_update_handler() {
	// Use the default page update handler instead of processing our options in a custom way.
	wpc_menu_page_update_handler( 'fb404' );
}

function fb404_setting_fallback_url_render() {
	$config = wp_unslash( get_option( 'fb404_setting_fallback_url', '' ) );

	echo wp_kses(
		sprintf(
			'<input type="url" name="fb404_setting_fallback_url" value="%1$s" required size="48"/>',
			esc_attr( $config )
		),
		[
			'input' => [
				'name'     => [],
				'required' => [],
				'size'     => [],
				'type'     => [],
				'value'    => [],
			],
		]
	);
}

/**
 * Retrieves a sanitized version of the Request URI. If the request was made on a sub-folder multisite with an unmapped
 * domain, then it will remove the sub-folder from the request.
 *
 * @return string Request URI.
 */
function fb404_get_request() {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return '';
	}

	/**
	 * Retrieve the Request URI. We use the server variable rather than `$wp->request` as we wish to retain the query
	 * string parameters.
	 */
	$request = filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL );
	$request = wp_unslash( $request );

	/**
	 * If the site is on a Multisite and is using a sub-folder, make sure to remove the site path from the URL before
	 * redirecting. For sites that are mapped to domain, the path will be a single forward slash (/).
	 */
	if ( is_multisite() ) {
		$site = get_site();
		if ( ! empty( untrailingslashit( $site->path ) ) ) {
			$request = str_ireplace( $site->path, '', $request );
		}
	}

	/**
	 * We remove the forward slash (/) at the prefix for predictability.
	 */
	return ltrim( $request, '/' );
}

/**
 * Validates a given URL.
 *
 * @param string $url URL to validate.
 * @return string URL if valid. Empty string otherwise.
 */
function fb404_validate_url( $url = '' ) {
	if ( empty( $url ) ) {
		return '';
	}

	/**
	 * Get the URL setting and make sure it's a proper value.
	 */
	$original_url = strtolower( wp_unslash( $url ) );
	$url          = wp_kses_bad_protocol( $original_url, [ 'http', 'https' ] );
	if ( empty( $url ) || $original_url !== $url ) {
		return '';
	}

	$parsed_url = parse_url( $url );
	if ( ! $parsed_url || empty( $parsed_url['host'] ) ) {
		return '';
	}

	return $url;
}
