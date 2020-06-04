<?php

/**
 * Code used by site menus.
 */

/**
 * List of menus and their setup configuration
 *
 * @param string $menu_id Identifier of the menu configuration to access. Leave empty to return all the configurations
 *
 * @return array Menu configuration(s)
 */
function wpc_menu_page_configs( $menu_id = '' ) {
	return wpc_menu_configs( 'wpc_menu_page_configs', $menu_id );
}

function wpc_menu_page_render( $menu_id ) {
	$config = wpc_menu_page_configs( $menu_id );
	wpc_menu_render( 'site', $config );
}

/**
 * Registration of the setting groups to the menu_slug.
 */
function wpc_menu_page_settings_registration() {
	$menus = wpc_menu_page_configs();
	wpc_menu_registration( $menus );
}

add_action( 'admin_init', 'wpc_menu_page_settings_registration' );

/**
 * A generic way to handle the form post request.
 *
 * @param string $menu_id Menu page configuration id
 */
function wpc_menu_page_update_handler( $menu_id ) {
	// Make sure we are posting from our options page. Note we must add the '-options' postfix
	// when we check the referrer.
	$config  = wpc_menu_page_configs( $menu_id );
	$updated = wpc_menu_update_options( 'site', $config, true );

	wp_redirect( add_query_arg( [
		'page'    => $config['menu_slug'],
		'updated' => (string) $updated,
	], admin_url( 'admin.php' ) ) );
	exit;

}

/**
 * Register a grouped setting to a menu page id.
 *
 * @example Add a setting to the general settings group of the cluster menu-page:
 *        wpc_menu_register_page_settings( 'cluster', [
 *            'general' => [
 *                'wpc_cluster_use_production_images' => 'Use Production Images',
 *            ],
 *        ] );
 *
 * @param string|array $menu_id Menu identifier or configuration
 * @param array $grouped_settings Array of setting groups.
 */
function wpc_menu_register_settings( $menu_id, $grouped_settings ) {

	add_filter( 'wpc_menu_page_configs', function ( $menus ) use ( $menu_id, $grouped_settings ) {
		if ( empty( $menus[ $menu_id ]['setting_sections'] ) ) {
			$menus[ $menu_id ]['setting_sections'] = [];
		}
		$menus[ $menu_id ]['setting_sections'] = array_merge_recursive( $menus[ $menu_id ]['setting_sections'], $grouped_settings );

		return $menus;
	}, 11 );

}

/**
 * Register a menu-page.
 *
 * @example Add a setting to the general settings group of the cluster menu-page:
 *        wpc_menu_register_page( [
 *            'id'         => 'cluster',
 *            'menu_slug'  => 'wpc_network_cluster_page',
 *            'page_title' => 'Cluster Settings',
 *            'menu_title' => 'Cluster',
 * ] );
 *
 * The following keys are optional:
 *   - action: points to the function that will update the options instead
 *     of wpc_menu_page_update_handler()
 *
 * @param $config
 */
function wpc_menu_register_page( $config ) {
	add_filter( 'wpc_menu_page_configs', function ( $menus ) use ( $config ) {
		$menus[ $config['id'] ] = $config;

		return $menus;
	} );

	$defaults = [
		'page_title' => 'My Menu Page',
		'menu_title' => 'My Menu',
	];
	$config   = wp_parse_args( $config, $defaults );

	add_action( 'admin_menu', function () use ( $config ) {
		$menu_slug = $config['menu_slug'];

		if ( empty( $config['parent_slug'] ) ) {
			add_menu_page(
				$config['page_title'],
				$config['menu_title'],
				'manage_options',
				$menu_slug,
				$menu_slug
			);
		} else {
			add_submenu_page(
				$config['parent_slug'],
				$config['page_title'],
				$config['menu_title'],
				'manage_options',
				$menu_slug,
				$menu_slug
			);
		}
	} );

	$action = $config['action'];

	/**
	 * This function here is hooked up to a special action and necessary to process the saving of the options.
	 */
	add_action( 'admin_post_' . $action, $action );
}
