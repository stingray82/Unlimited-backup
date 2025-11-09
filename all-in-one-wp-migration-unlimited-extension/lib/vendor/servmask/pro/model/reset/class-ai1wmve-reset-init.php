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

class Ai1wmve_Reset_Init {

	public static function execute( $params ) {

		// Set progress
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( __( 'Reset in progress. This may take a few moments.', AI1WM_PLUGIN_NAME ) );
		} else {
			Ai1wm_Status::info( __( 'Your request is being processed. This may take a few moments. Please do not close this window or navigate away from this page while the reset is in progress.', AI1WM_PLUGIN_NAME ) );
		}

		// Check if password is provided
		if ( ! isset( $params['ai1wm_reset_password'] ) ) {
			throw new Ai1wmve_Error_Exception( __( 'To start the reset process, please enter current user password.', AI1WM_PLUGIN_NAME ) );
		}

		$user = wp_get_current_user();

		// Check for password
		if ( isset( $user->data->user_pass, $user->ID ) ) {
			if ( wp_check_password( $params['ai1wm_reset_password'], $user->data->user_pass, $user->ID ) ) {
				// If we don't reset DB, unset user's password
				if ( ! isset( $params['ai1wm_reset_database'] ) ) {
					unset( $params['ai1wm_reset_password'] );
				}

				return $params;
			}
		}

		throw new Ai1wmve_Error_Exception( __( 'The entered password is not valid. Please ensure you\'re entering the correct password. It\'s essential for security reasons to verify your identity before making significant changes to your site.', AI1WM_PLUGIN_NAME ) );
	}
}
