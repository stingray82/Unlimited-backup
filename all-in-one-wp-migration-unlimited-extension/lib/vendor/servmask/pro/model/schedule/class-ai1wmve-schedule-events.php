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

if ( ! class_exists( 'Ai1wmve_Schedule_Events' ) ) {
	class Ai1wmve_Schedule_Events {

		protected $events = array();

		public function __construct() {
			$this->events = get_option( AI1WMVE_SCHEDULES_OPTIONS, false );
			if ( false === $this->events ) {
				$this->create_default_events();
			}
		}

		/**
		 * Get all events
		 *
		 * @return array
		 */
		public function all() {
			return $this->events;
		}

		public function enabled() {
			return array_filter(
				$this->all(),
				function ( $event ) {
					return $event->is_enabled();
				}
			);
		}

		public function save( $data ) {
			$event = new Ai1wmve_Schedule_Event( $data );
			$event->clear_schedule()
				->create_schedule();
			$this->events[ $event->event_id() ] = $event;

			return $this->update_option();
		}

		public function find( $id ) {
			if ( isset( $this->events[ $id ] ) ) {
				return $this->events[ $id ];
			}

			return false;
		}

		public function find_or_new( $id ) {
			if ( $event = $this->find( $id ) ) {
				return $event;
			}

			return new Ai1wmve_Schedule_Event( array() );
		}

		public function delete( $id ) {
			if ( $event = $this->find( $id ) ) {
				$event->clear_schedule()
					->delete_logs();
				unset( $this->events[ $id ] );

				return $this->update_option();
			}

			throw new Ai1wmve_Schedules_Exception( __( 'Unable to find scheduled event', AI1WM_PLUGIN_NAME ) );
		}

		public function clear( $extension = null ) {
			if ( is_null( $extension ) ) {
				delete_option( AI1WMVE_SCHEDULES_OPTIONS );
				return;
			}

			foreach ( $this->events as $event_id => $event ) {
				if ( $event->storage() === $extension ) {
					$this->delete( $event_id );
				}
			}

			if ( empty( $this->events ) ) {
				delete_option( AI1WMVE_SCHEDULES_OPTIONS );
			}
		}

		protected function update_option() {
			return update_option( AI1WMVE_SCHEDULES_OPTIONS, $this->events );
		}

		protected function create_default_events() {
			$this->events = array();

			$this->save(
				array(
					'event_id'     => time(),
					'title'        => __( 'Daily backup' ),
					'type'         => Ai1wmve_Schedule_Event::TYPE_EXPORT,
					'storage'      => 'file',
					'status'       => Ai1wmve_Schedule_Event::STATUS_DISABLED,
					'schedule'     => array(
						'interval' => Ai1wmve_Schedule_Event::INTERVAL_DAILY,
						'hour'     => 2,
						'minute'   => 0,
					),
					'notification' => array(
						'reminder' => Ai1wmve_Schedule_Event::REMINDER_NONE,
					),
					'retention'    => array(
						'backups' => 7,
					),
				)
			);

			$this->save(
				array(
					'event_id'     => time() + 1,
					'title'        => __( 'Weekly backup' ),
					'type'         => Ai1wmve_Schedule_Event::TYPE_EXPORT,
					'storage'      => 'file',
					'status'       => Ai1wmve_Schedule_Event::STATUS_DISABLED,
					'schedule'     => array(
						'interval' => Ai1wmve_Schedule_Event::INTERVAL_WEEKLY,
						'weekday'  => 'sunday',
						'hour'     => 3,
						'minute'   => 0,
					),
					'notification' => array(
						'reminder' => Ai1wmve_Schedule_Event::REMINDER_NONE,
					),
					'retention'    => array(
						'backups' => 4,
					),
				)
			);

			$this->save(
				array(
					'event_id'     => time() + 2,
					'title'        => __( 'Monthly backup' ),
					'type'         => Ai1wmve_Schedule_Event::TYPE_EXPORT,
					'storage'      => 'file',
					'status'       => Ai1wmve_Schedule_Event::STATUS_DISABLED,
					'schedule'     => array(
						'interval' => Ai1wmve_Schedule_Event::INTERVAL_MONTHLY,
						'day'      => 1,
						'hour'     => 1,
						'minute'   => 0,
					),
					'notification' => array(
						'reminder' => Ai1wmve_Schedule_Event::REMINDER_NONE,
					),
					'retention'    => array(
						'backups' => 12,
					),
				)
			);
		}
	}
}
