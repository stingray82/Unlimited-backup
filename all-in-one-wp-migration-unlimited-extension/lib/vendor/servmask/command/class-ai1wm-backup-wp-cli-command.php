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

if ( class_exists( 'Ai1wm_Backup_WP_CLI_Base' ) && ! class_exists( 'Ai1wm_Backup_WP_CLI_Command' ) ) {

	class Ai1wm_Backup_WP_CLI_Command extends Ai1wm_Backup_WP_CLI_Base {

		protected $reset_types = array(
			'plugins',
			'themes',
			'media',
			'database',
			'full',
		);

		/**
		 * Creates a new backup.
		 *
		 * ## OPTIONS
		 *
		 * [--sites[=<comma_separated_ids>]]
		 * : Export sites by id (To list sites use: wp site list --fields=blog_id,url)
		 *
		 * [--password[=<password>]]
		 * : Encrypt backup with password
		 *
		 * [--exclude-spam-comments]
		 * : Do not export spam comments
		 *
		 * [--exclude-post-revisions]
		 * : Do not export post revisions
		 *
		 * [--exclude-media]
		 * : Do not export media library (files)
		 *
		 * [--exclude-themes]
		 * : Do not export themes (files)
		 *
		 * [--exclude-inactive-themes]
		 * : Do not export inactive themes (files)
		 *
		 * [--exclude-muplugins]
		 * : Do not export must-use plugins (files)
		 *
		 * [--exclude-plugins]
		 * : Do not export plugins (files)
		 *
		 * [--exclude-inactive-plugins]
		 * : Do not export inactive plugins (files)
		 *
		 * [--exclude-cache]
		 * : Do not export cache (files)
		 *
		 * [--exclude-database]
		 * : Do not export database (sql)
		 *
		 * [--exclude-tables[=<comma_separated_names>]]
		 * : Do not export selected database tables (sql)
		 *
		 * [--include-tables[=<comma_separated_names>]]
		 * : Include the selected non‑WP tables (sql)
		 *
		 * [--exclude-email-replace]
		 * : Do not replace email domain (sql)
		 *
		 * [--replace]
		 * : Find and replace text in the database
		 *
		 * [<find>...]
		 * : A string to find for within the database
		 *
		 * [<replace>...]
		 * : Replace instances of the first string with this new string
		 *
		 * ## EXAMPLES
		 *
		 * $ wp ai1wm backup --replace "wp" "WordPress"
		 * Backup in progress...
		 * Backup complete.
		 * Backup file: migration-wp-20170913-095743-931.wpress
		 * Backup location: /repos/migration/wp/wp-content/ai1wm-backups/migration-wp-20170913-095743-931.wpress
		 *
		 * @subcommand backup
		 */
		public function backup( $args = array(), $assoc_args = array() ) {
			$params = $this->run_backup(
				$this->build_export_params( $args, $assoc_args )
			);

			WP_CLI::log( sprintf( __( 'Backup location: %s', AI1WM_PLUGIN_NAME ), ai1wm_backup_path( $params ) ) );
		}

		/**
		 * Get a list of backup files.
		 *
		 * ## EXAMPLES
		 *
		 * $ wp ai1wm list-backups
		 * +------------------------------------------------+--------------+-----------+
		 * | Backup name                                    | Date created | Size      |
		 * +------------------------------------------------+--------------+-----------+
		 * | migration-wp-20170908-152313-435.wpress        | 4 days ago   | 536.77 MB |
		 * | migration-wp-20170908-152103-603.wpress        | 4 days ago   | 536.77 MB |
		 * | migration-wp-20170908-152036-162.wpress        | 4 days ago   | 536.77 MB |
		 * | migration-wp-20170908-151428-266.wpress        | 4 days ago   | 536.77 MB |
		 * +------------------------------------------------+--------------+-----------+
		 *
		 * @subcommand list-backups
		 */
		public function list_backups( array $args, array $assoc_args ) {
			$backups = new cli\Table();

			$backups->setHeaders(
				array(
					'name' => __( 'Backup name', AI1WM_PLUGIN_NAME ),
					'date' => __( 'Date created', AI1WM_PLUGIN_NAME ),
					'size' => __( 'Size', AI1WM_PLUGIN_NAME ),
				)
			);

			$model = new Ai1wm_Backups();
			foreach ( $model->get_files() as $backup ) {
				$backups->addRow(
					array(
						'name' => $backup['filename'],
						'date' => sprintf( __( '%s ago', AI1WM_PLUGIN_NAME ), human_time_diff( $backup['mtime'] ) ),
						'size' => ai1wm_size_format( $backup['size'], 2 ),
					)
				);
			}

			$backups->display();
		}

		/**
		 * Browse backup files.
		 *
		 * ## OPTIONS
		 *
		 * <backup-file>
		 * : The path for backup file
		 *
		 * ## EXAMPLES
		 *
		 * $ wp ai1wm browse-backup wp-servmask.test-20220316-090649-a7baqq.wpress
		 * +------------------------------------------------+--------------+-----------+
		 * | File name                                      | Date created | Size      |
		 * +------------------------------------------------+--------------+-----------+
		 * | package.json                                   | 1 week ago   | 854.00 B  |
		 * | index.php                                      | 10 years ago | 28.00 B   |
		 * | plugins/akismet/LICENSE.txt                    | 7 years ago  | 17.67 KB  |
		 * | plugins/akismet/views/predefined.php           | 2 years ago  | 318.00 B  |
		 * +------------------------------------------------+--------------+-----------+
		 *
		 * @subcommand browse-backup
		 */
		public function browse_backup( $args = array(), $assoc_args = array() ) {
			$params = array(
				'cli_args'             => $assoc_args,
				'secret_key'           => get_option( AI1WM_SECRET_KEY, false ),
				'ai1wm_manual_restore' => true,
			);

			if ( ! isset( $args[0] ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'A backup name must be provided in order to proceed with the list backup files process.', AI1WM_PLUGIN_NAME ),
						__( 'Example: wp ai1wm backup_files migration-wp-20170913-095743-931.wpress', AI1WM_PLUGIN_NAME ),
					)
				);
				exit;
			}

			if ( ! is_file( ai1wm_backup_path( array( 'archive' => $args[0] ) ) ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'The backup file could not be located in wp-content/ai1wm-backups folder.', AI1WM_PLUGIN_NAME ),
						__( 'To list available backups use: wp ai1wm list-backups', AI1WM_PLUGIN_NAME ),
					)
				);
				exit;
			}

			if ( ! isset( $params['archive'] ) ) {
				$params['archive'] = $args[0];
			}

			if ( ! empty( $assoc_args['storage'] ) ) {
				$params['storage'] = $assoc_args['storage'];
			}

			try {
				// Disable completed timeout
				add_filter( 'ai1wm_completed_timeout', '__return_zero' );

				$table = new cli\Table();

				$table->setHeaders(
					array(
						'name' => __( 'File name', AI1WM_PLUGIN_NAME ),
						'date' => __( 'Date created', AI1WM_PLUGIN_NAME ),
						'size' => __( 'Size', AI1WM_PLUGIN_NAME ),
					)
				);

				$archive = new Ai1wm_Extractor( ai1wm_archive_path( $params ) );
				$files   = $archive->list_files();

				foreach ( $files as $file ) {
					$table->addRow(
						array(
							'name' => $file['filename'],
							'date' => sprintf( __( '%s ago', AI1WM_PLUGIN_NAME ), human_time_diff( $file['mtime'] ) ),
							'size' => ai1wm_size_format( $file['size'], 2 ),
						)
					);
				}

				$table->display();
			} catch ( Exception $e ) {
				WP_CLI::error( $e->getMessage() );
			}
		}

		/**
		 * Extract backup to provided directory.
		 *
		 * ## OPTIONS
		 *
		 * <backup-file>
		 * : The path for backup file
		 *
		 * [--extract-path]
		 * : Extract backup files to provided path (wp ai1wm extract-backup wp-servmask.test-20220316-090649-a7baqq.wpress --extract-path=/tmp)
		 *
		 * ## EXAMPLES
		 *
		 * $ wp ai1wm extract-backup wp-servmask.test-20220316-090649-a7baqq.wpress --extract-path=/tmp
		 *
		 * @subcommand extract-backup
		 */
		public function extract_backup( $args = array(), $assoc_args = array() ) {
			$params = array(
				'cli_args'             => $assoc_args,
				'secret_key'           => get_option( AI1WM_SECRET_KEY, false ),
				'ai1wm_manual_restore' => true,
			);

			if ( ! isset( $args[0] ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'A backup name must be provided in order to proceed with the extract process.', AI1WM_PLUGIN_NAME ),
						__( 'Example: wp ai1wm backup-extract migration-wp-20170913-095743-931.wpress', AI1WM_PLUGIN_NAME ),
					)
				);
				exit;
			}

			if ( ! isset( $assoc_args['extract-path'] ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'A backup extract path must be provided and writable in order to proceed with the extract process.', AI1WM_PLUGIN_NAME ),
						__( 'Example: wp ai1wm backup-extract migration-wp-20170913-095743-931.wpress --extract-path=/tmp', AI1WM_PLUGIN_NAME ),
					)
				);
				exit;
			}

			if ( ! is_file( ai1wm_backup_path( array( 'archive' => $args[0] ) ) ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'The backup file could not be located in wp-content/ai1wm-backups folder.', AI1WM_PLUGIN_NAME ),
						__( 'To list available backups use: wp ai1wm list-backups', AI1WM_PLUGIN_NAME ),
					)
				);
				exit;
			}

			$params['extract_path'] = $assoc_args['extract-path'];

			if ( ! is_writable( $params['extract_path'] ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'The extract folder is not writable. Check that directory exists and writable.', AI1WM_PLUGIN_NAME ),
					)
				);
				exit;
			}

			if ( ! isset( $params['archive'] ) ) {
				$params['archive'] = $args[0];
			}

			if ( ! empty( $assoc_args['storage'] ) ) {
				$params['storage'] = $assoc_args['storage'];
			}

			try {
				// Disable completed timeout
				add_filter( 'ai1wm_completed_timeout', '__return_zero' );

				$archive = new Ai1wm_Extractor( ai1wm_archive_path( $params ) );

				while ( $archive->has_not_reached_eof() ) {
					$archive->extract_one_file_to( $params['extract_path'] );
				}
			} catch ( Exception $e ) {
				WP_CLI::error( sprintf( __( 'Unable to extract files: %s', AI1WM_PLUGIN_NAME ), $e->getMessage() ) );
				exit;
			}
		}

		/**
		 * Restores a backup.
		 *
		 * ## OPTIONS
		 *
		 * <file>
		 * : Name of the backup file
		 *
		 * [--yes]
		 * : Automatically confirm the restore operation
		 *
		 * ## EXAMPLES
		 *
		 * $ wp ai1wm restore migration-wp-20170913-095743-931.wpress
		 * Restore in progress...
		 * Restore complete.
		 *
		 * @subcommand restore
		 */
		public function restore( $args = array(), $assoc_args = array() ) {
			$params = array(
				'cli_args'   => $assoc_args,
				'secret_key' => get_option( AI1WM_SECRET_KEY, false ),
			);

			if ( ! isset( $args[0] ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'A backup name must be provided in order to proceed with the restore process.', AI1WM_PLUGIN_NAME ),
						__( 'Example: wp ai1wm restore migration-wp-20170913-095743-931.wpress', AI1WM_PLUGIN_NAME ),
					)
				);
				exit;
			}

			if ( ! is_file( ai1wm_backup_path( array( 'archive' => $args[0] ) ) ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'The backup file could not be located in wp-content/ai1wm-backups folder.', AI1WM_PLUGIN_NAME ),
						__( 'To list available backups use: wp ai1wm list-backups', AI1WM_PLUGIN_NAME ),
					)
				);
				exit;
			}

			if ( ! isset( $params['archive'] ) ) {
				$params['archive'] = $args[0];
			}

			if ( ! isset( $params['storage'] ) ) {
				$params['storage'] = ai1wm_storage_folder();
			}

			if ( ! isset( $params['ai1wm_manual_restore'] ) ) {
				$params['ai1wm_manual_restore'] = 1;
			}

			$this->run_restore( $params );
		}

		/**
		 * Reset Hub functionality.
		 *
		 * ## OPTIONS
		 *
		 * <type>
		 * : Type of reset (plugins, themes, media, database or full)
		 *
		 * --user=<id|login|email>
		 * : Set the WordPress user
		 *
		 * [--backup]
		 * : Create backup (snapshot) before reset
		 *
		 * ## EXAMPLES
		 *
		 * $ wp ai1wm reset database --user=1
		 * Reset in progress...
		 * Success: Reset complete.
		 *
		 * @subcommand reset
		 */
		public function reset( $args = array(), $assoc_args = array() ) {
			$params = array(
				'cli_args'   => $assoc_args,
				'secret_key' => get_option( AI1WM_SECRET_KEY, false ),
			);

			if ( ! isset( $args[0] ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'A reset type must be provided in order to proceed with the reset process.', AI1WM_PLUGIN_NAME ),
						__( 'Example: wp ai1wm reset database', AI1WM_PLUGIN_NAME ),
					)
				);
				exit;
			}

			if ( ! in_array( $args[0], $this->reset_types ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'Unsupported reset type.', AI1WM_PLUGIN_NAME ),
						sprintf( __( 'Supported types are: %s', AI1WM_PLUGIN_NAME ), implode( ', ', $this->reset_types ) ),
					)
				);
				exit;
			}

			if ( ! current_user_can( 'export' ) ) {
				WP_CLI::error( __( 'Insufficient permissions to run reset.', AI1WM_PLUGIN_NAME ) );
			}

			switch ( $args[0] ) {
				case 'plugins':
					$params['ai1wm_reset_plugins'] = 1;
					break;

				case 'themes':
					$params['ai1wm_reset_themes'] = 1;
					break;

				case 'media':
					$params['ai1wm_reset_media'] = 1;
					break;

				case 'database':
					$params['ai1wm_reset_database'] = 1;
					break;

				default:
					$params = array_merge(
						$params,
						array(
							'ai1wm_reset_plugins'  => 1,
							'ai1wm_reset_themes'   => 1,
							'ai1wm_reset_media'    => 1,
							'ai1wm_reset_database' => 1,
						)
					);
			}

			$user                           = wp_get_current_user();
			$params['ai1wm_reset_password'] = readline( sprintf( __( 'To continue, enter your current user [%s] password: ', AI1WM_PLUGIN_NAME ), $user->user_login ) );

			if ( isset( $assoc_args['backup'] ) ) {
				$backup_params = $this->build_export_params( $args, $assoc_args );

				$backup_params['ai1wm_reset_label'] = $args[0];
				$backup_params['file']              = 1;

				WP_CLI::log( '' );
				$this->run_backup( $backup_params );
				WP_CLI::log( '' );
			}

			try {
				// Disable completed timeout
				add_filter( 'ai1wm_completed_timeout', '__return_zero' );

				// Run reset filters
				Ai1wmve_Reset_Controller::reset( $params );
			} catch ( Exception $e ) {
				WP_CLI::error( __( sprintf( 'Unable to reset: %s', $e->getMessage() ), AI1WM_PLUGIN_NAME ) );
			}
		}

		/**
		 * Check for plugin updates
		 *
		 * ## EXAMPLES
		 *
		 * $ wp ai1wm check-for-updates
		 * Success: Check for updates completed.
		 *
		 * @subcommand check-for-updates
		 */
		public function check_for_updates( $args = array(), $assoc_args = array() ) {
			try {
				// Check for updtes
				Ai1wm_Updater::check_for_updates();

				WP_CLI::success( __( 'Check for updates completed.', AI1WM_PLUGIN_NAME ) );
			} catch ( Exception $e ) {
				WP_CLI::error( __( sprintf( 'Unable to check for updates: %s', $e->getMessage() ), AI1WM_PLUGIN_NAME ) );
			}
		}
	}
}
