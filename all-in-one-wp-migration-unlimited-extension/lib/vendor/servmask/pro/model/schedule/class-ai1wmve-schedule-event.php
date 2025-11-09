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

if ( ! class_exists( 'Ai1wmve_Schedule_Event' ) ) {
	class Ai1wmve_Schedule_Event {

		const STATUS_ENABLED  = 'Enabled';
		const STATUS_DISABLED = 'Disabled';

		const TYPE_EXPORT = 'Export';
		const TYPE_IMPORT = 'Import';

		const LAST_STATUS_NONE    = 'None';
		const LAST_STATUS_FAILED  = 'Failed';
		const LAST_STATUS_SUCCESS = 'Success';
		const LAST_STATUS_RUNNING = 'Running';

		const INTERVAL_HOURLY  = 'Hourly';
		const INTERVAL_DAILY   = 'Daily';
		const INTERVAL_WEEKLY  = 'Weekly';
		const INTERVAL_MONTHLY = 'Monthly';
		const INTERVAL_N_HOUR  = 'N-Hour';
		const INTERVAL_N_DAYS  = 'N-Days';

		const REMINDER_NONE    = 'Off';
		const REMINDER_SUCCESS = 'Success';
		const REMINDER_FAILED  = 'Failed';
		const REMINDER_ALWAYS  = 'Always';

		const CRON_HOOK = 'ai1wmve_schedule_event';

		protected $event_id;

		protected $title = '';

		protected $type = '';

		protected $storage = '';

		protected $status = self::STATUS_ENABLED;

		protected $repeating = true;

		protected $is_running = false;

		protected $options = array();

		protected $schedule = array(
			'interval' => '',
			'weekday'  => '',
			'day'      => '',
			'hour'     => '',
			'minute'   => '',
			'n'        => '',
		);

		protected $notification = array(
			'reminder' => '',
			'status'   => '',
			'email'    => '',
		);

		protected $excluded_files;

		protected $excluded_db_tables;

		protected $included_db_tables;

		protected $password;

		protected $incremental = false;

		protected $sites = array();

		protected $retention = array(
			'backups'    => 0,
			'total'      => 0,
			'total_unit' => 'MB',
			'days'       => 0,
		);

		public function __construct( $data ) {
			if ( ! is_array( $data ) ) {
				throw new Ai1wmve_Schedules_Exception( __( 'Event data must be array', AI1WM_PLUGIN_NAME ) );
			}

			if ( empty( $data['event_id'] ) ) {
				$data['event_id'] = time();
			}

			if ( empty( $data['storage'] ) ) {
				$data['storage'] = 'file';
			}

			foreach ( $data as $name => $value ) {
				if ( property_exists( $this, $name ) ) {
					if ( method_exists( $this, $name ) ) {
						$this->{$name}( $value );
					} else {
						$this->{$name} = is_array( $this->{$name} ) && is_array( $value ) ? array_merge( $this->{$name}, $value ) : $value;
					}
				}
			}
		}

		public function password( $password = null ) {
			if ( $password && is_string( $password ) ) {
				$this->password = base64_encode( $password );
			} elseif ( $this->password && is_string( $this->password ) ) {
				return base64_decode( $this->password );
			}

			return $this->password;
		}

		public function __call( $name, $arguments ) {
			if ( property_exists( $this, $name ) ) {
				return $this->{$name};
			}
		}

		public function period() {
			if ( ! $this->repeating ) {
				return __( 'Do not repeat', AI1WM_PLUGIN_NAME );
			}

			switch ( $this->schedule['interval'] ) {
				case self::INTERVAL_HOURLY:
					return $this->incremental ? __( 'Continuous', AI1WM_PLUGIN_NAME ) : __( 'Per hour', AI1WM_PLUGIN_NAME );

				case self::INTERVAL_DAILY:
					return $this->incremental ? __( 'Once per day', AI1WM_PLUGIN_NAME ) : __( 'Per day', AI1WM_PLUGIN_NAME );

				case self::INTERVAL_WEEKLY:
					return __( 'Per week', AI1WM_PLUGIN_NAME );

				case self::INTERVAL_MONTHLY:
					return __( 'Per month', AI1WM_PLUGIN_NAME );

				default:
					return __( 'Unknown period', AI1WM_PLUGIN_NAME );
			}
		}

		public function time() {
			if ( $this->incremental && $this->schedule['interval'] === self::INTERVAL_HOURLY ) {
				return '-';
			}

			if ( $this->repeating ) {
				return get_date_from_gmt( date( 'Y-m-d H:i:s', $this->timestamp() ), 'H:i' );
			}

			return '';
		}

		public function is_enabled() {
			return $this->status === self::STATUS_ENABLED;
		}

		public function to_json() {
			return json_encode( $this->to_array() );
		}

		public function to_array() {
			return array(
				'event_id'           => $this->event_id,
				'title'              => $this->title,
				'type'               => $this->type,
				'status'             => $this->status,
				'storage'            => $this->storage,
				'repeating'          => $this->repeating,
				'period'             => $this->period(),
				'time'               => $this->time(),
				'last_run'           => $this->last_run(),
				'options'            => $this->options,
				'schedule'           => $this->schedule,
				'notification'       => $this->notification,
				'excluded_files'     => $this->excluded_files,
				'excluded_db_tables' => $this->excluded_db_tables,
				'included_db_tables' => $this->included_db_tables,
				'password'           => $this->password(),
				'incremental'        => $this->incremental,
				'sites'              => array_map( 'intval', $this->sites ),
				'retention'          => $this->retention,
			);
		}

		public function advanced_options() {
			return array(
				self::TYPE_EXPORT => array(
					'no_spam_comments',
					'no_post_revisions',
					'no_media',
					'no_themes',
					'no_inactive_themes',
					'no_muplugins',
					'no_plugins',
					'no_inactive_plugins',
					'no_cache',
					'no_database',
					'no_email_replace',
				),
				self::TYPE_IMPORT => array(),
			);
		}

		public function run() {
			try {
				$this->is_running = true;
				$this->create_schedule();

				Ai1wm_Export_Controller::export( $this->params() );
			} catch ( Exception $e ) {
				$this->mark_failed( $e->getMessage() );
			}
		}

		public function clear_schedule() {
			Ai1wm_Cron::delete( self::CRON_HOOK, $this->cron_args() );

			return $this;
		}

		public function create_schedule() {
			if ( ! $this->is_enabled() ) {
				return false;
			}

			if ( wp_next_scheduled( self::CRON_HOOK, $this->cron_args() ) !== false ) {
				return false;
			}

			// Do not create next run if it's not repeating and running
			if ( ! $this->repeating && $this->is_running ) {
				return false;
			}

			return wp_schedule_single_event( $this->timestamp(), self::CRON_HOOK, $this->cron_args() );
		}

		public function schedule_now() {
			return wp_schedule_single_event( time(), self::CRON_HOOK, $this->cron_args() );
		}

		protected function timestamp() {
			$time = empty( $this->schedule['weekday'] ) ? 'now' : $this->schedule['weekday'];
			/** @var \DateTime $cron_time */
			$cron_time = date_create( $time, wp_timezone() );
			$cron_time->setTime( intval( $this->schedule['hour'] ), intval( $this->schedule['minute'] ) );

			if ( $this->schedule['interval'] === self::INTERVAL_MONTHLY && is_numeric( $this->schedule['day'] ) ) {
				$cron_time->setDate( $cron_time->format( 'Y' ), $cron_time->format( 'm' ), $this->schedule['day'] );
			}

			while ( $cron_time < date_create() ) {
				$cron_time->add( $this->get_interval() );
			}

			return $cron_time->setTimezone( new DateTimeZone( 'UTC' ) )->getTimestamp();
		}

		protected function get_interval() {
			switch ( $this->schedule['interval'] ) {
				case self::INTERVAL_HOURLY:
					return DateInterval::createFromDateString( '1 hour' );

				case self::INTERVAL_N_HOUR:
					return DateInterval::createFromDateString( $this->schedule['n'] . ' hours' );

				case self::INTERVAL_N_DAYS:
					return DateInterval::createFromDateString( $this->schedule['n'] . ' days' );

				case self::INTERVAL_WEEKLY:
					return DateInterval::createFromDateString( '1 week' );

				case self::INTERVAL_MONTHLY:
					return DateInterval::createFromDateString( '1 month' );

				case self::INTERVAL_DAILY:
				default:
					return DateInterval::createFromDateString( '1 day' );
			}
		}

		protected function recurrence() {
			switch ( $this->schedule['interval'] ) {
				case self::INTERVAL_HOURLY:
				case self::INTERVAL_N_HOUR:
					return 'hourly';

				case self::INTERVAL_DAILY:
				case self::INTERVAL_N_DAYS:
					return 'daily';

				case self::INTERVAL_WEEKLY:
					return 'weekly';

				case self::INTERVAL_MONTHLY:
					return 'monthly';

				default:
					throw new Ai1wmve_Schedules_Exception( __( 'Undefined schedule interval!', AI1WM_PLUGIN_NAME ) );
			}
		}

		public function cron_args() {
			return array( intval( $this->event_id ) );
		}

		public function mark_success( $params = null ) {
			$this->last_run( self::LAST_STATUS_SUCCESS );

			$log = new Ai1wmve_Schedule_Event_Log( $this->event_id );
			$log->add( self::LAST_STATUS_SUCCESS );

			$this->notify_success( $params );
		}

		public function mark_running( $params = null ) {
			$this->last_run( self::LAST_STATUS_RUNNING );

			$log = new Ai1wmve_Schedule_Event_Log( $this->event_id );
			$log->add( self::LAST_STATUS_RUNNING );

			$this->notify_running( $params );
		}

		public function mark_failed( $message = null ) {
			$this->last_run( self::LAST_STATUS_FAILED );

			$log = new Ai1wmve_Schedule_Event_Log( $this->event_id );
			$log->add( self::LAST_STATUS_FAILED, $message );

			$this->notify_failed( $message );
		}

		public function last_run( $status = null ) {
			$option_key = self::option_key( 'last_run', $this->event_id );
			if ( ! is_null( $status ) ) {
				return update_option( $option_key, $status );
			}

			return get_option( $option_key, self::LAST_STATUS_NONE );
		}

		protected function notify_success( $params ) {
			add_filter( 'ai1wm_notification_ok_toggle', array( $this, 'is_success_notification_enabled' ) );
			add_filter( 'ai1wm_notification_ok_email', array( $this, 'notification_email' ) );

			if ( is_file( ai1wm_backup_path( $params ) ) ) {
				$file_size = ai1wm_backup_size( $params );
			} else {
				$file_size = ai1wm_archive_size( $params );
			}

			$notification = sprintf( __( '<p>Your site %s was successfully exported.</p>', AI1WM_PLUGIN_NAME ), site_url() ) .
				sprintf( __( '<p>Date: %s</p>', AI1WM_PLUGIN_NAME ), date_i18n( 'r' ) ) .
				sprintf( __( '<p>Backup file: %s</p>', AI1WM_PLUGIN_NAME ), ai1wm_archive_name( $params ) );

			if ( $file_size ) {
				$notification .= sprintf( __( '<p>Size: %s</p>', AI1WM_PLUGIN_NAME ), $file_size );
			}

			Ai1wm_Notification::ok(
				sprintf( __( 'Scheduled event has completed (%s)', AI1WM_PLUGIN_NAME ), parse_url( site_url(), PHP_URL_HOST ) . parse_url( site_url(), PHP_URL_PATH ) ),
				$notification
			);
		}

		protected function notify_running( $params ) {
		}

		protected function notify_failed( $error ) {
			add_filter( 'ai1wm_notification_error_toggle', array( $this, 'is_failed_notification_enabled' ) );
			add_filter( 'ai1wm_notification_error_email', array( $this, 'notification_email' ) );

			$notification = sprintf( __( '<p>Your site %s export failed.</p>', AI1WM_PLUGIN_NAME ), site_url() ) .
				sprintf( __( '<p>Date: %s</p>', AI1WM_PLUGIN_NAME ), date_i18n( 'r' ) );

			if ( $error ) {
				$notification .= sprintf( __( '<p>Error message: %s</p>', AI1WM_PLUGIN_NAME ), $error );
			}

			Ai1wm_Notification::error(
				sprintf( __( 'Scheduled event has failed (%s)', AI1WM_PLUGIN_NAME ), parse_url( site_url(), PHP_URL_HOST ) . parse_url( site_url(), PHP_URL_PATH ) ),
				$notification
			);
		}

		public function is_success_notification_enabled() {
			return $this->notification['status'] === static::STATUS_ENABLED && ( $this->notification['reminder'] === static::REMINDER_SUCCESS || $this->notification['reminder'] === static::REMINDER_ALWAYS );
		}

		public function is_failed_notification_enabled() {
			return $this->notification['status'] === static::STATUS_ENABLED && ( $this->notification['reminder'] === static::REMINDER_FAILED || $this->notification['reminder'] === static::REMINDER_ALWAYS );
		}

		public function notification_email() {
			if ( $this->notification['email'] ) {
				return $this->notification['email'];
			}

			return false;
		}

		public function logs() {
			$log = new Ai1wmve_Schedule_Event_Log( $this->event_id );

			return array_map(
				function ( $log ) {
					$log['id']            = $log['time'];
					$log['time']          = date_i18n( 'Y-m-d H:i:s', $log['time'] );
					$log['status_locale'] = __( $log['status'], AI1WM_PLUGIN_NAME );
					$log['status_class']  = strtolower( $log['status'] );

					return $log;
				},
				$log->records()
			);
		}

		public function delete_logs() {
			delete_option( self::option_key( 'last_run', $this->event_id ) );
			delete_option( self::option_key( 'log', $this->event_id ) );
		}

		public static function option_key( $option, $event_id ) {
			return self::CRON_HOOK . '_' . $option . '_' . $event_id;
		}

		/**
		 * @return array
		 */
		protected function params() {
			$params = array(
				'event_id'                => $this->event_id,
				'secret_key'              => get_option( AI1WM_SECRET_KEY ),
				$this->storage            => 1,
				'options'                 => array(),
				'event_retention_days'    => $this->retention['days'],
				'event_retention_size'    => (int) $this->retention['total'] . trim( $this->retention['total_unit'] ),
				'event_retention_backups' => $this->retention['backups'],
			);
			foreach ( $this->options as $option ) {
				$params['options'][ $option ] = 1;
			}

			if ( $password = $this->password() ) {
				$params['options']['encrypt_backups']  = 1;
				$params['options']['encrypt_password'] = $password;
			}

			if ( ! empty( $this->excluded_files ) ) {
				$params['options']['exclude_files'] = 1;
				$params['excluded_files']           = $this->excluded_files;
			}

			if ( ! empty( $this->excluded_db_tables ) ) {
				$params['options']['exclude_db_tables'] = 1;
				$params['excluded_db_tables']           = $this->excluded_db_tables;
			}

			if ( ! empty( $this->included_db_tables ) ) {
				$params['options']['include_db_tables'] = 1;
				$params['included_db_tables']           = $this->included_db_tables;
			}

			if ( $this->incremental ) {
				$params['incremental'] = 1;
			}

			$blog_id = null;
			if ( ! empty( $this->sites ) ) {
				$params['options']['sites'] = $this->sites;
				if ( count( $this->sites ) === 1 ) {
					$blog_id = array_shift( $this->sites );
				}
			}

			$params['archive'] = $this->archive( $blog_id );

			return $params;
		}

		/**
		 * @param $blog_id
		 *
		 * @return string
		 */
		protected function archive( $blog_id = null ) {
			return str_replace(
				'.wpress',
				sprintf( '-%s.wpress', $this->event_id ),
				ai1wm_archive_file( $blog_id )
			);
		}
	}
}
