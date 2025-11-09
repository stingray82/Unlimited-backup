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

// ================
// = Package path =
// ================
define( 'AI1WMVE_PATH', __DIR__ );

// ===================
// = Controller Path =
// ===================
define( 'AI1WMVE_CONTROLLER_PATH', AI1WMVE_PATH . DIRECTORY_SEPARATOR . 'controller' );

// ==============
// = Model Path =
// ==============
define( 'AI1WMVE_MODEL_PATH', AI1WMVE_PATH . DIRECTORY_SEPARATOR . 'model' );

// ==============
// = Utils Path =
// ==============
define( 'AI1WMVE_UTILS_PATH', AI1WMVE_PATH . DIRECTORY_SEPARATOR . 'utils' );

// =============
// = View Path =
// =============
define( 'AI1WMVE_TEMPLATES_PATH', AI1WMVE_PATH . DIRECTORY_SEPARATOR . 'view' );

// ===========================
// = Purchase Activation URL =
// ===========================
define( 'AI1WMVE_PURCHASE_ACTIVATION_URL', 'https://servmask.com/purchase/activations' );

// ======================
// = ServMask Stats URL =
// ======================
define( 'AI1WMVE_STATS_URL', 'https://servmask.com/api/stats' );

// =================
// = Max File Size =
// =================
define( 'AI1WMVE_MAX_FILE_SIZE', 0 );

// ============================
// = Schedules Events Options =
// ============================
define( 'AI1WMVE_SCHEDULES_OPTIONS', 'ai1wmve_schedule_events' );

// ====================
// = Reset Theme Name =
// ====================
define( 'AI1WMVE_RESET_THEME_NAME', 'servmask' );

// ==========================
// = Reset Theme Style Name =
// ==========================
define( 'AI1WMVE_RESET_THEME_STYLE_NAME', AI1WMVE_RESET_THEME_NAME . DIRECTORY_SEPARATOR . 'style.css' );

// ==========================
// = Reset Theme Index Name =
// ==========================
define( 'AI1WMVE_RESET_THEME_INDEX_NAME', AI1WMVE_RESET_THEME_NAME . DIRECTORY_SEPARATOR . 'index.php' );

// ========================
// = Reset DB backup file =
// ========================
define( 'AI1WMVE_RESET_DB_BACKUP', 'reset-db-backup.json' );
