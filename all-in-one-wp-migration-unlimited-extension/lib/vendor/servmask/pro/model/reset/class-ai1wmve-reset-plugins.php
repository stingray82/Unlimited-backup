<?php
/**
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

class Ai1wmve_Reset_Plugins {

	public static function execute( $params ) {
		// Skip reset step
		if ( ! isset( $params['ai1wm_reset_plugins'] ) ) {
			return $params;
		}

		$plugins = array();

		// Get all plugins
		foreach ( array_keys( get_plugins() ) as $plugin ) {
			if ( strstr( $plugin, AI1WM_PLUGIN_NAME ) ) {
				continue;
			}

			$plugins[] = $plugin;
		}

		// Deactivate plugins
		if ( ! isset( $params['plugins_deactivated'] ) ) {

			// Loop over plugins
			if ( is_multisite() ) {
				foreach ( ai1wmme_get_sites() as $site ) {
					switch_to_blog( $site['BlogID'] );
					deactivate_plugins( $plugins, true );
					restore_current_blog();
				}
			} else {
				deactivate_plugins( $plugins, true );
			}

			$params['plugins_deactivated'] = true;
		}

		// Flag to hold if file data has been processed
		$completed = true;

		// Start time
		$start = microtime( true );

		// Uninstall plugins
		foreach ( $plugins as $plugin ) {
			uninstall_plugin( $plugin );

			// Delete plugin files;
			Ai1wm_Directory::delete( ai1wm_get_plugins_dir() . DIRECTORY_SEPARATOR . dirname( $plugin ) );

			// More than 10 seconds have passed, break and do another request
			if ( ( $timeout = apply_filters( 'ai1wm_completed_timeout', 10 ) ) ) {
				if ( ( microtime( true ) - $start ) > $timeout ) {
					$completed = false;
					break;
				}
			}
		}

		if ( $completed ) {

			// Set progress
			if ( ! isset( $params['ai1wm_reset_plugins'], $params['ai1wm_reset_themes'], $params['ai1wm_reset_media'], $params['ai1wm_reset_database'] ) ) {
				$message = __( 'All installed plugins have been successfully removed from your site. This action has cleared any settings, data, and customizations associated with those plugins. Your site is now in a clean state, free of any plugins. You can begin reinstalling your preferred plugins to configure your site with the functionality you need.', AI1WM_PLUGIN_NAME );
				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					WP_CLI::success( $message );
				} else {
					Ai1wm_Status::done( __( 'Reset Successful' ), $message );
				}
				exit;
			}

			// Unset plugins deactivated
			unset( $params['plugins_deactivated'] );

			// Unset completed flag
			unset( $params['completed'] );

		} else {

			// Set completed flag
			$params['completed'] = $completed;
		}

		return $params;
	}
}
