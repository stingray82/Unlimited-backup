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

if ( ! class_exists( 'Ai1wmve_File_Sorter' ) ) {

	class Ai1wmve_File_Sorter extends Ai1wmve_Array_Sorter {

		public static function by_date_asc( $date_key = 'date' ) {
			return static::numeric_asc( $date_key );
		}

		public static function by_date_desc( $date_key = 'date' ) {
			return static::reverse( static::by_date_asc( $date_key ) );
		}

		public static function by_type_desc( $type_key = 'type' ) {
			return static::string_desc( $type_key );
		}

		public static function by_type_desc_name_asc( $name_key = 'name', $type_key = 'type' ) {
			$sorted_type = static::by_type_desc( $type_key );
			$sorted_name = static::string_asc( $name_key );

			return function ( $a, $b ) use ( $sorted_type, $sorted_name ) {
				$sorted_items = $sorted_type( $a, $b );
				if ( $sorted_items !== 0 ) {
					return $sorted_items;
				}

				return $sorted_name( $a, $b );
			};
		}

		public static function by_type_desc_date_asc( $date_key = 'date', $type_key = 'type' ) {
			$sorted_type = static::by_type_desc( $type_key );
			$sorted_date = static::by_date_asc( $date_key );

			return function ( $a, $b ) use ( $sorted_type, $sorted_date ) {
				$sorted_items = $sorted_type( $a, $b );
				if ( $sorted_items !== 0 ) {
					return $sorted_items;
				}

				return $sorted_date( $a, $b );
			};
		}

		public static function by_type_desc_date_desc( $date_key = 'date', $type_key = 'type' ) {
			$sorted_type = static::by_type_desc( $type_key );
			$sorted_date = static::by_date_desc( $date_key );

			return function ( $a, $b ) use ( $sorted_type, $sorted_date ) {
				$sorted_items = $sorted_type( $a, $b );
				if ( $sorted_items !== 0 ) {
					return $sorted_items;
				}

				return $sorted_date( $a, $b );
			};
		}
	}
}
