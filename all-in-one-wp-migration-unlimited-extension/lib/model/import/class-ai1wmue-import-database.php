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

class Ai1wmue_Import_Database {

	public static function execute( $params ) {

		$model = new Ai1wmue_Settings();

		// Set progress
		Ai1wm_Status::info( __( 'Updating settings...', AI1WMUE_PLUGIN_NAME ) );

		// Read retention.json file
		$handle = ai1wm_open( ai1wmue_retention_path( $params ), 'r' );

		// Parse settings.json file
		$settings = ai1wm_read( $handle, filesize( ai1wmue_retention_path( $params ) ) );
		$settings = json_decode( $settings, true );

		// Close handle
		ai1wm_close( $handle );

		// Update retention settings
		$model->set_backups( $settings['ai1wmue_backups'] );
		$model->set_total( $settings['ai1wmue_total'] );
		$model->set_days( $settings['ai1wmue_days'] );

		// Set progress
		Ai1wm_Status::info( __( 'Done updating settings.', AI1WMUE_PLUGIN_NAME ) );

		return $params;
	}
}
