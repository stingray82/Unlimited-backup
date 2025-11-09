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

if ( ! class_exists( 'Ai1wmve_Array_Sorter' ) ) {

	class Ai1wmve_Array_Sorter {

		public static function string_asc( $key = null ) {
			if ( is_null( $key ) ) {
				return function ( $a, $b ) {
					return strcasecmp( $a, $b );
				};
			}

			return static::on_key( $key, static::string_asc() );
		}

		public static function string_desc( $key = null ) {
			if ( is_null( $key ) ) {
				return static::reverse( static::string_asc() );
			}

			return static::on_key( $key, static::string_desc() );
		}

		public static function numeric_asc( $key = null ) {
			if ( is_null( $key ) ) {
				return function ( $a, $b ) {
					return $a < $b ? -1 : ( $a === $b ? 0 : 1 );
				};
			}

			return static::on_key( $key, static::numeric_asc() );
		}

		public static function numeric_desc( $key = null ) {
			if ( is_null( $key ) ) {
				return static::reverse( static::numeric_asc() );
			}

			return static::on_key( $key, static::numeric_desc() );
		}

		public static function reverse( $comparator ) {
			return function ( $a, $b ) use ( $comparator ) {
				return $comparator( $b, $a );
			};
		}

		public static function on_key( $key, $function ) {
			return function ( $a, $b ) use ( $key, $function ) {
				return $function( $a[ $key ], $b[ $key ] );
			};
		}

		public static function sort( &$array, $comparator ) {
			usort( $array, $comparator );

			return $array;
		}
	}
}
