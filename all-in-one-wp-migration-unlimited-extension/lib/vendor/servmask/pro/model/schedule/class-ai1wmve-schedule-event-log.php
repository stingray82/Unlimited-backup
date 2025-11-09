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

if ( ! class_exists( 'Ai1wmve_Schedule_Event_Log' ) ) {
	class Ai1wmve_Schedule_Event_Log {

		const MAX_RECORDS = 30;

		protected $event_id;

		protected $records = array();

		public function __construct( $event_id ) {
			$this->event_id = $event_id;
			$this->load();
		}

		public function load() {
			$this->records = get_option( Ai1wmve_Schedule_Event::option_key( 'log', $this->event_id ), array() );
		}

		public function save() {
			if ( count( $this->records ) > self::MAX_RECORDS ) {
				array_pop( $this->records );
			}
			update_option( Ai1wmve_Schedule_Event::option_key( 'log', $this->event_id ), $this->records );
		}

		public function add( $status, $message = null ) {
			$data = array(
				'time'    => (int) get_date_from_gmt( 'now', 'U' ),
				'status'  => $status,
				'message' => $message,
			);
			array_unshift( $this->records, $data );

			$this->save();
		}

		public function records() {
			return $this->records;
		}
	}
}
