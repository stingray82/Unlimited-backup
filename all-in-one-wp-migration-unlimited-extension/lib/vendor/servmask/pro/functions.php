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

if ( ! function_exists( 'ai1wmve_clear_scheduled_events' ) ) {
	/**
	 * Clears all scheduled events for selected storage type
	 * If $extension is null then it clears ALL the events
	 *
	 * @param $extension
	 */
	function ai1wmve_clear_scheduled_events( $extension = null ) {
		$events = new Ai1wmve_Schedule_Events();
		$events->clear( $extension );
	}
}

if ( ! function_exists( 'ai1wmve_is_running' ) ) {
	/**
	 * Check whether export/import is running
	 *
	 * @return boolean
	 */
	function ai1wmve_is_running() {
		if ( isset( $_GET['file'] ) || isset( $_POST['file'] ) ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'ai1wmve_reset_db_backup_path' ) ) {
	/**
	 * Get db-options-backup.json absolute path
	 *
	 * @return string
	 */
	function ai1wmve_reset_db_backup_path() {
		return AI1WM_STORAGE_PATH . DIRECTORY_SEPARATOR . AI1WMVE_RESET_DB_BACKUP;
	}
}
