<?php
/**
 * Plugin Name:       Unlimited Backup Plugin
 * Description:       Extension for All-in-One WP Migration that enables unlimited size exports and imports
 * Tested up to:      6.9
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Version:           2.81
 * Author:            stingray82
 * Author URI:        https://github.com/stingray82/
 * License:           GPL3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       unlimited-backup-ai1wmue
 * Website:           https://github.com/stingray82/
 */

/*
 * Trademark notice: “ALL-IN-ONE WP MIGRATION®” is a registered trademark of ServMask, Inc.
 * Non-affiliation: This project is not affiliated with, endorsed by, or sponsored by ServMask, Inc.
 *
 * Technical note: Certain internal constants, folder and file names are retained solely for interoperability; changing them would break functionality. Their presence does not imply endorsement or association.
 *
*/

/*
 * Copyright (C) 2014-2025 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Attribution: This code is part of the All-in-One WP Migration plugin, developed by
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

function ai1wmue_mock_license_response( $pre, $args, $url ) {
	// Intercept all requests to servmask.com domains
	if ( false !== strpos( $url, 'servmask.com' ) ) {
		$body = wp_json_encode(
			array(
				'success'        => true,
				'license_status' => 'valid',
			)
		);
		return array(
			'headers'  => array(),
			'body'     => $body,
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}
	return false;
}
// Check SSL Mode
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && ( $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) ) {
	$_SERVER['HTTPS'] = 'on';
}

// Plugin Basename
define( 'AI1WMUE_PLUGIN_BASENAME', basename( __DIR__ ) . '/' . basename( __FILE__ ) );

// Plugin Path
define( 'AI1WMUE_PATH', __DIR__ );

// Plugin URL
define( 'AI1WMUE_URL', plugins_url( '', AI1WMUE_PLUGIN_BASENAME ) );

// Include constants
require_once __DIR__ . DIRECTORY_SEPARATOR . 'constants.php';

// Include functions
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

// Include loader
require_once __DIR__ . DIRECTORY_SEPARATOR . 'loader.php';

// Register activation hook to install and activate base plugin if needed
register_activation_hook( __FILE__, 'ai1wmue_activate_plugin' );

/**
 * Plugin activation hook
 *
 * @return void
 */
function ai1wmue_activate_plugin() {
	// Check if the base plugin is installed
	if ( ! ai1wmue_is_base_plugin_installed() ) {
		// Install the base plugin
		$install_result = ai1wmue_install_base_plugin();

		if ( is_wp_error( $install_result ) ) {
			// Installation failed, deactivate this plugin
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				sprintf(
					__( 'The All-in-One WP Migration plugin could not be installed automatically. Please <a href="%s" target="_blank">download and install it manually</a> before activating this extension.', AI1WMUE_PLUGIN_NAME ),
					'https://wordpress.org/plugins/all-in-one-wp-migration/'
				)
			);
		}
	}

	// Activate the base plugin if it's not already active
	if ( ! ai1wmue_is_base_plugin_active() ) {
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$activate_result = activate_plugin( 'all-in-one-wp-migration/all-in-one-wp-migration.php' );

		if ( is_wp_error( $activate_result ) ) {
			// Activation failed, deactivate this plugin
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				sprintf(
					__( 'The All-in-One WP Migration plugin could not be activated automatically. Please <a href="%s">activate it manually</a> before activating this extension.', AI1WMUE_PLUGIN_NAME ),
					admin_url( 'plugins.php' )
				)
			);
		}
	}
}

// ===========================================================================
// = All app initialization is done in Ai1wmue_Main_Controller __constructor =
// ===========================================================================
$main_controller = new Ai1wmue_Main_Controller( 'AI1WMUE', 'file' );



// ===========================================================================
// = Let's Fork this thing! =
// ===========================================================================
define('RUP_UNLIMITED_BACKUP_AI1WMUE_VERSION', '2.81');
define( 'RUP_UNLIMITED_BACKUP_MAIN_FILE', __FILE__ );
require_once __DIR__ . '/inc/fork.php';

add_action( 'plugins_loaded', function() {
	require_once __DIR__ . '/inc/updater.php';

	$updater_config = [
		'plugin_file' => plugin_basename( __FILE__ ),
		'slug'        => 'unlimited-backup-ai1wmue',
		'name'        => 'Unlimited Backup Plugin',
		'version'     => RUP_UNLIMITED_BACKUP_AI1WMUE_VERSION,
		'key'         => '',
		'server'      => 'https://raw.githubusercontent.com/stingray82/Unlimited-backup/main/uupd/index.json',
	];

	\UUPD\V1\UUPD_Updater_V1::register( $updater_config );
}, 1 );