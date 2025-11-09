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

class Ai1wmue_Settings_Controller {

	public static function index() {
		$model = new Ai1wmue_Settings();

		Ai1wm_Template::render(
			'settings/index',
			array(
				'backups'     => $model->get_backups(),
				'total'       => $model->get_total(),
				'days'        => $model->get_days(),
				'destination' => $model->get_backups_path(),
			),
			AI1WMUE_TEMPLATES_PATH
		);
	}

	public static function settings( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_POST );
		}

		// Settings update
		if ( isset( $params['ai1wmue_update'] ) ) {
			$model = new Ai1wmue_Settings();

			// Set number of backups
			if ( ! empty( $params['ai1wmue_backups'] ) ) {
				$model->set_backups( (int) $params['ai1wmue_backups'] );
			} else {
				$model->set_backups( 0 );
			}

			// Set size of backups
			if ( ! empty( $params['ai1wmue_total'] ) && ! empty( $params['ai1wmue_total_unit'] ) ) {
				$model->set_total( (int) $params['ai1wmue_total'] . trim( $params['ai1wmue_total_unit'] ) );
			} else {
				$model->set_total( 0 );
			}

			// Set number of days
			if ( ! empty( $params['ai1wmue_days'] ) ) {
				$model->set_days( (int) $params['ai1wmue_days'] );
			} else {
				$model->set_days( 0 );
			}

			// Set backups path
			if ( ! empty( $params[ AI1WM_BACKUPS_PATH_OPTION ] ) ) {
				$model->set_backups_path( $params[ AI1WM_BACKUPS_PATH_OPTION ] );
			} else {
				$model->reset_backups_path();
			}

			// Set message
			Ai1wm_Message::flash( 'settings', __( 'Your changes have been saved.', AI1WMUE_PLUGIN_NAME ) );
		}

		// Redirect to settings page
		wp_redirect( network_admin_url( 'admin.php?page=ai1wmue_settings' ) );
		exit;
	}

	public static function list_folders( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_POST );
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		try {
			// Ensure that unauthorized people cannot access backups list action
			ai1wm_verify_secret_key( $secret_key );
		} catch ( Ai1wm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		// Set directory
		$directory = '/';
		if ( isset( $params['directory'] ) ) {
			$directory = $params['directory'];
		}

		// Iterate over content directory
		$iterator = new Ai1wm_Recursive_Directory_Iterator( $directory );

		$abs_path = realpath( ABSPATH );

		// Loop over content directory
		$names = $files = array();
		foreach ( $iterator as $item ) {
			if ( $item->isDir() && ! $item->isLink() && $item->isReadable() ) {
				try {
					$files[] = array(
						'name'     => $name = $item->getRealPath(),
						'writable' => $item->isWritable() && realpath( $name ) !== $abs_path,
						'date'     => human_time_diff( $item->getMTime() ),
					);
					$names[] = strtolower( $item->getRealPath() );
				} catch ( Exception $e ) {
				}
			}
		}

		array_multisort( $names, $files );

		echo json_encode( $files );
		exit;
	}
}
