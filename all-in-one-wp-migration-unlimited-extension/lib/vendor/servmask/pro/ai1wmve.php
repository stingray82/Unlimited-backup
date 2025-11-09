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

if ( defined( 'AI1WMVE_PATH' ) ) {
	// Here we check if retention classes are loaded
	// as it might happen some extension to have
	// older version of the pro package loaded
	if ( ! class_exists( 'Ai1wmve_Export_Retention_Base' ) ) {
		require_once __DIR__ .
			DIRECTORY_SEPARATOR .
			'model' .
			DIRECTORY_SEPARATOR .
			'export' .
			DIRECTORY_SEPARATOR .
			'class-ai1wmve-export-retention-base.php';

		require_once __DIR__ .
			DIRECTORY_SEPARATOR .
			'model' .
			DIRECTORY_SEPARATOR .
			'export' .
			DIRECTORY_SEPARATOR .
			'class-ai1wmve-export-retention-file.php';
	}

	// Here we check if utils classes are loaded
	// as it might happen some extension to have
	// older version of the pro package loaded
	if ( ! class_exists( 'Ai1wmve_Array_Sorter' ) ) {
		require_once __DIR__ .
			DIRECTORY_SEPARATOR .
			'utils' .
			DIRECTORY_SEPARATOR .
			'class-ai1wmve-array-sorter.php';

		require_once __DIR__ .
			DIRECTORY_SEPARATOR .
			'utils' .
			DIRECTORY_SEPARATOR .
			'class-ai1wmve-file-sorter.php';
	}

	return;
}

// Include constants
require_once __DIR__ .
	DIRECTORY_SEPARATOR .
	'constants.php';

// Include exceptions
require_once __DIR__ .
	DIRECTORY_SEPARATOR .
	'exceptions.php';

// Include functions
require_once __DIR__ .
	DIRECTORY_SEPARATOR .
	'functions.php';

// Include utils
require_once AI1WMVE_UTILS_PATH .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-array-sorter.php';

require_once AI1WMVE_UTILS_PATH .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-file-sorter.php';

// Include controllers
require_once AI1WMVE_CONTROLLER_PATH .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-main-controller.php';

require_once AI1WMVE_CONTROLLER_PATH .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-export-controller.php';

require_once AI1WMVE_CONTROLLER_PATH .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-schedules-controller.php';

require_once AI1WMVE_CONTROLLER_PATH .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-reset-controller.php';

// Include models
require_once AI1WMVE_MODEL_PATH .
	DIRECTORY_SEPARATOR .
	'schedule' .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-schedule-events.php';

require_once AI1WMVE_MODEL_PATH .
	DIRECTORY_SEPARATOR .
	'schedule' .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-schedule-event.php';

require_once AI1WMVE_MODEL_PATH .
	DIRECTORY_SEPARATOR .
	'schedule' .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-schedule-event-log.php';

require_once AI1WMVE_MODEL_PATH .
	DIRECTORY_SEPARATOR .
	'export' .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-export-retention-base.php';

require_once AI1WMVE_MODEL_PATH .
	DIRECTORY_SEPARATOR .
	'export' .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-export-retention-file.php';

require_once AI1WMVE_MODEL_PATH .
	DIRECTORY_SEPARATOR .
	'reset' .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-reset-init.php';

require_once AI1WMVE_MODEL_PATH .
	DIRECTORY_SEPARATOR .
	'reset' .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-reset-plugins.php';

require_once AI1WMVE_MODEL_PATH .
	DIRECTORY_SEPARATOR .
	'reset' .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-reset-themes.php';

require_once AI1WMVE_MODEL_PATH .
	DIRECTORY_SEPARATOR .
	'reset' .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-reset-media.php';

require_once AI1WMVE_MODEL_PATH .
	DIRECTORY_SEPARATOR .
	'reset' .
	DIRECTORY_SEPARATOR .
	'class-ai1wmve-reset-database.php';
