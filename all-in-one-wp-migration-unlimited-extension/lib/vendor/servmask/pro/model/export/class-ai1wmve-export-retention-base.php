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

if ( ! class_exists( 'Ai1wmve_Export_Retention_Base' ) ) {
	abstract class Ai1wmve_Export_Retention_Base {

		/**
		 * Params
		 *
		 * @var array
		 */
		protected $params;

		protected $filter_events_files = null;

		public static function execute( $params, $client = null ) {
			$retention = new static( $params, $client );

			return $retention->run();
		}

		protected function run() {
			// No backups, no need to apply backup retention
			$backups = $this->get_filtered_files();
			if ( count( $backups ) === 0 ) {
				return $this->params;
			}

			// The order is very important - we delete files by date, by size, and finally by total count
			$this->delete_backups_older_than();
			$this->delete_backups_when_total_size_over();
			$this->delete_backups_when_total_count_over();

			return $this->params;
		}

		protected function __construct( $params, $client = null ) {
			$this->params = $params;
			$this->setup_client( $client );
			if ( isset( $this->params['event_id'] ) ) {
				$this->filter_events_files = sprintf( '-%s.wpress', $this->params['event_id'] );
			}
		}

		protected function delete_backups_older_than() {
			$days = $this->get_days();
			if ( $days > 0 ) {
				$backups = $this->get_filtered_files();
				foreach ( $backups as $backup ) {
					if ( $backup[ $this->file_date_key() ] <= time() - $days * 86400 ) {
						$this->delete_file( $backup );
					}
				}
			}
		}

		protected function get_days() {
			if ( isset( $this->params['event_id'], $this->params['event_retention_days'] ) ) {
				return intval( $this->params['event_retention_days'] );
			}

			return intval( get_option( $this->get_prefixed_option( 'days' ), 0 ) );
		}

		protected function delete_backups_when_total_size_over() {
			$retention_size = ai1wm_parse_size( $this->get_size() );
			if ( $retention_size > 0 ) {
				$backups = $this->get_filtered_files();

				// Get the size of the latest backup before we remove it
				$size_of_backups = $backups[0][ $this->file_size_key() ];

				// Remove the latest backup, the user should have at least one backup
				array_shift( $backups );

				foreach ( $backups as $backup ) {
					if ( $size_of_backups + $backup[ $this->file_size_key() ] > $retention_size ) {
						$this->delete_file( $backup );
					} else {
						$size_of_backups += $backup[ $this->file_size_key() ];
					}
				}
			}
		}

		protected function get_size() {
			if ( isset( $this->params['event_id'], $this->params['event_retention_size'] ) ) {
				return $this->params['event_retention_size'];
			}

			return get_option( $this->get_prefixed_option( 'total' ), 0 );
		}

		protected function get_prefixed_option( $name ) {
			return sprintf( '%s_%s', $this->get_options_prefix(), $name );
		}

		protected function delete_backups_when_total_count_over() {
			$limit = $this->get_limit();
			if ( $limit > 0 ) {
				$backups = $this->get_filtered_files();
				if ( count( $backups ) > $limit ) {
					for ( $i = $limit; $i < count( $backups ); $i++ ) {
						$this->delete_file( $backups[ $i ] );
					}
				}
			}
		}

		protected function get_limit() {
			if ( isset( $this->params['event_id'], $this->params['event_retention_backups'] ) ) {
				return intval( $this->params['event_retention_backups'] );
			}

			return intval( get_option( $this->get_prefixed_option( 'backups' ), 0 ) );
		}

		/**
		 * @deprecated Will be removed
		 *
		 * @param $first_backup
		 * @param $second_backup
		 *
		 * @return int
		 */
		public static function sort_by_date_desc( $first_backup, $second_backup ) {
			return intval( $second_backup['date'] ) - intval( $first_backup['date'] );
		}

		protected function get_filtered_files() {
			$files = $this->get_files();

			// Here we need to reset the array keys using "array_values"
			return array_values(
				is_null( $this->filter_events_files )
				? array_filter( $files, array( $this, 'is_not_event_file' ) )
				: array_filter( $files, array( $this, 'is_event_file' ) )
			);
		}

		protected function is_not_event_file( $file ) {
			// Check if file name does not end with "-event_id.wpress"
			return preg_match( '/-[0-9]+.wpress$/', $file[ $this->file_name_key() ] ) !== 1;
		}

		protected function is_event_file( $file ) {
			// Check if file name ends with "-event_id.wpress" ($this->filter_events_files)
			return substr_compare(
				$file[ $this->file_name_key() ],
				$this->filter_events_files,
				-strlen( $this->filter_events_files )
			) === 0;
		}

		protected function file_name_key() {
			return 'name';
		}

		protected function file_size_key() {
			return 'bytes';
		}

		protected function file_date_key() {
			return 'date';
		}

		abstract protected function get_files();

		abstract protected function delete_file( $backup );

		abstract protected function setup_client( $client );

		abstract protected function get_options_prefix();
	}
}
