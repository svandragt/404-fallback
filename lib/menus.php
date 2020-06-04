<?php
/**
 * Code used by both networkmenus and sitemenus
 */

/**
 * Accessor for creating menupage configurations
 *
 * @param string $filter_id Extension point for other code to hook menus into.
 * @param string $menu_id Identifier of the menu to retrieve from the filter.
 *
 * @return array Menu configuration
 */
function wpc_menu_configs( $filter_id, $menu_id = '' ) {
	/*
	 * Amend the networkmenupage configurations
	 * Almost always it's better to use  one of the following functions instead of calling this directly:
	 *   - function wpc_menu_register_networkpage( $config )
	 *   - function wpc_menu_register_network_settings( $menu_id, $grouped_settings )
	 */
	$menus = apply_filters( $filter_id, [] );

	// All menus
	if ( false === isset( $menus[ $menu_id ] ) ) {
		return $menus;
	}

	return $menus[ $menu_id ];
}

/**
 * @param $menus
 */
function wpc_menu_registration( array $menus ): void {
	// for each menu's data build out sections and register the section's settings
	foreach ( array_keys( $menus ) as $id ) {
		$config = $menus[ $id ];
		if ( empty( $config['setting_sections'] ) ) {
			continue;
		}
		$sections = $config['setting_sections'];

		if ( is_iterable( $sections ) ) {
			foreach ( $sections as $section => $settings ) {
				$page = $config['menu_slug'];
				add_settings_section( $section, strtoupper( $section ), null, $page );

				foreach ( (array) $settings as $name => $title ) {
					$sanitization = is_callable( $name . '_sanitize' ) ? $name . '_sanitize' : [];
					register_setting( $section, $name, $sanitization );
					add_settings_field( $name, $title,
						$name . '_render', $page,
						$section );
				}
			}
		}
	}
}

/**
 * Update a single option
 *
 * @param string $type Type: network or site
 * @param bool $delete_missing Whether to delete the option if not submitted?
 * @param string $option Option name
 *
 * @return bool False if value was not updated, true if updated.
 */
function wpc_menu_update_option( $type, $delete_missing, $option ): bool {
	$updated = false;

	if ( isset( $_POST[ $option ] ) ) {
		if ( 'network' === $type ) {
			$updated = update_site_option( $option, $_POST[ $option ] );
		} else {
			$updated = update_option( $option, $_POST[ $option ] );

		}
	} elseif ( $delete_missing ) {
		if ( 'network' === $type ) {
			$updated = delete_site_option( $option );
		} else {
			$updated = delete_option( $option );
		}
	}

	return $updated;
}

/**
 * Update the options for a given network page configuration
 *
 * @param string $type network|site configuration type
 * @param array|string $config menu configuration or id of one.
 * @param bool $delete_missing if missing options should be deleted or skipped
 *
 * @return bool if any of the options was updated or deleted
 */
function wpc_menu_update_options( $type, $config, $delete_missing ) {
	global $new_whitelist_options;

	check_admin_referer( $config['menu_slug'] . '-options' );

	$did_update = false;

	// Update or delete all the options
	$sections = $config['setting_sections'];
	if ( false === is_iterable( $sections ) ) {
		return $did_update ? 'true' : 'false';
	}
	foreach ( (array) $sections as $section => $options ) {

		$options = $new_whitelist_options[ $section ];
		foreach ( (array) $options as $option ) {
			$updated = wpc_menu_update_option( $type, $delete_missing, $option );
			if ( $updated ) {
				$did_update = true;
			}
		}
	}

	return $did_update ? 'true' : 'false';

}

function wpc_menu_render( $type, $config ) {
	// Nonce is verified by wpc_menu_fields
	if ( isset( $_GET['updated'] ) ): ?>
		<div id="message" class="updated notice is-dismissible"><p><?php _e( 'Options saved.' ); ?></p></div>
	<?php endif;

	$full_action = [
		'network' => 'edit.php?action=' . esc_attr( $config['action'] ),
		'site'    => admin_url( 'admin-post.php' ),
	];
	?>
	<div class="wrap custom-options">
		<header class="custom-options__header">
			<h1><?php echo esc_html( $config['page_title'] ); ?></h1>
		</header>

		<form action="<?php echo $full_action[ $type ] ?>" method="POST">
			<?php
			wpc_menu_fields( $type, $config );
			do_settings_sections( $config['menu_slug'] );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Configuration type aware settings fields
 *
 * @param string $type network|site
 * @param array $config menu configuration
 */
function wpc_menu_fields( $type, $config ) {
	settings_fields( $config['menu_slug'] );
	if ( 'network' !== $type ) {
		echo "<input type='hidden' name='action' value='" . esc_attr( $config['action'] ) . "' />";
	}
}
