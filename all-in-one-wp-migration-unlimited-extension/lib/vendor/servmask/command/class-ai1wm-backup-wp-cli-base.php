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

if ( defined( 'WP_CLI' ) && ! class_exists( 'Ai1wm_Backup_WP_CLI_Base' ) ) {

	abstract class Ai1wm_Backup_WP_CLI_Base extends WP_CLI_Command {

		public function __construct() {

			if ( ! defined( 'AI1WM_PLUGIN_NAME' ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'Extension requires All-in-One WP Migration plugin to be activated. ', 'all-in-one-wp-migration' ),
						__( 'You can get a copy of it here: https://wordpress.org/plugins/all-in-one-wp-migration/', 'all-in-one-wp-migration' ),
					)
				);
				exit;
			}

			if ( is_multisite() && ! defined( 'AI1WMME_PLUGIN_NAME' ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'WordPress Multisite is supported via our All-in-One WP Migration Multisite Extension.', AI1WM_PLUGIN_NAME ),
						__( 'You can get a copy of it here: https://servmask.com/products/multisite-extension', AI1WM_PLUGIN_NAME ),
					)
				);
				exit;
			}

			if ( ! is_dir( AI1WM_STORAGE_PATH ) ) {
				if ( ! mkdir( AI1WM_STORAGE_PATH ) ) {
					WP_CLI::error_multi_line(
						array(
							sprintf( __( 'All-in-One WP Migration is not able to create <strong>%s</strong> folder.', AI1WM_PLUGIN_NAME ), AI1WM_STORAGE_PATH ),
							__( 'You will need to create this folder and grant it read/write/execute permissions (0777) for the All-in-One WP Migration plugin to function properly.', AI1WM_PLUGIN_NAME ),
						)
					);
					exit;
				}
			}

			if ( ! is_dir( AI1WM_BACKUPS_PATH ) ) {
				if ( ! mkdir( AI1WM_BACKUPS_PATH ) ) {
					WP_CLI::error_multi_line(
						array(
							sprintf( __( 'All-in-One WP Migration is not able to create <strong>%s</strong> folder.', AI1WM_PLUGIN_NAME ), AI1WM_BACKUPS_PATH ),
							__( 'You will need to create this folder and grant it read/write/execute permissions (0777) for the All-in-One WP Migration plugin to function properly.', AI1WM_PLUGIN_NAME ),
						)
					);
					exit;
				}
			}
		}

		/**
		 * Builds export params from command line input
		 */
		protected function build_export_params( $args = array(), $assoc_args = array() ) {
			$params = array(
				'cli_args'   => $assoc_args,
				'secret_key' => get_option( AI1WM_SECRET_KEY, false ),
			);

			if ( isset( $assoc_args['password'] ) ) {
				if ( function_exists( 'ai1wm_can_encrypt' ) && ai1wm_can_encrypt() ) {
					if ( $assoc_args['password'] === true || empty( $assoc_args['password'] ) ) {
						$assoc_args['password'] = readline( 'Please enter a password to protect this backup: ' );
					}

					if ( empty( $assoc_args['password'] ) ) {
						WP_CLI::error( __( 'Encryption password must not be empty.', AI1WM_PLUGIN_NAME ) );
					}

					$params['options']['encrypt_backups']  = true;
					$params['options']['encrypt_password'] = $assoc_args['password'];
				} else {
					WP_CLI::error( __( 'Your system doesn\'t support encryption.', AI1WM_PLUGIN_NAME ) );
				}
			}

			if ( isset( $assoc_args['exclude-spam-comments'] ) ) {
				$params['options']['no_spam_comments'] = true;
			}

			if ( isset( $assoc_args['exclude-post-revisions'] ) ) {
				$params['options']['no_post_revisions'] = true;
			}

			if ( isset( $assoc_args['exclude-media'] ) ) {
				$params['options']['no_media'] = true;
			}

			if ( isset( $assoc_args['exclude-themes'] ) ) {
				$params['options']['no_themes'] = true;
			}

			if ( isset( $assoc_args['exclude-inactive-themes'] ) ) {
				$params['options']['no_inactive_themes'] = true;
			}

			if ( isset( $assoc_args['exclude-muplugins'] ) ) {
				$params['options']['no_muplugins'] = true;
			}

			if ( isset( $assoc_args['exclude-plugins'] ) ) {
				$params['options']['no_plugins'] = true;
			}

			if ( isset( $assoc_args['exclude-inactive-plugins'] ) ) {
				$params['options']['no_inactive_plugins'] = true;
			}

			if ( isset( $assoc_args['exclude-cache'] ) ) {
				$params['options']['no_cache'] = true;
			}

			if ( isset( $assoc_args['exclude-database'] ) ) {
				$params['options']['no_database'] = true;
			} else {
				// Exclude some of the tables
				if ( isset( $assoc_args['exclude-tables'] ) ) {
					$mysql = Ai1wm_Database_Utility::create_client();

					// Include table prefixes
					if ( ai1wm_table_prefix() ) {
						$mysql->add_table_prefix_filter( ai1wm_table_prefix() );

						// Include table prefixes (Webba Booking and CiviCRM)
						foreach ( array( 'wbk_', 'civicrm_' ) as $table_name ) {
							$mysql->add_table_prefix_filter( $table_name );
						}
					}
					$all_tables = $mysql->get_tables();

					if ( $assoc_args['exclude-tables'] === true || empty( $assoc_args['exclude-tables'] ) ) {
						$tables = new cli\Table();

						$tables->setHeaders(
							array(
								'name' => sprintf( 'Tables to exclude (%s)', DB_NAME ),
							)
						);

						foreach ( $all_tables as $table_name ) {
							$tables->addRow(
								array(
									'name' => $table_name,
								)
							);
						}

						$tables->display();
						$excluded_tables = array();

						while ( $table = trim( readline( 'Enter table name to exclude from backup (q=quit, empty=continue): ' ) ) ) {
							switch ( $table ) {
								case 'q':
									exit;

								default:
									if ( ! in_array( $table, $all_tables ) ) {
										WP_CLI::warning( __( 'Unknown table: ', AI1WM_PLUGIN_NAME ) . $table );
										break;
									}
									$excluded_tables[] = $table;
							}
						}
					} else {
						$excluded_tables = array_intersect(
							$all_tables,
							array_filter( explode( ',', $assoc_args['exclude-tables'] ), 'trim' )
						);
					}

					if ( ! empty( $excluded_tables ) ) {
						$params['options']['exclude_db_tables'] = true;
						$params['excluded_db_tables']           = implode( ',', $excluded_tables );
					}
				}

				// Include additional tables
				if ( isset( $assoc_args['include-tables'] ) ) {
					$mysql = Ai1wm_Database_Utility::create_client();

					// Include table prefixes
					if ( ai1wm_table_prefix() ) {
						$mysql->add_table_prefix_filter( '', sprintf( '(%s|%s|%s)', ai1wm_table_prefix(), 'wbk_', 'civicrm_' ) );
					} else {
						$mysql->add_table_prefix_filter( '', sprintf( '(%s|%s)', 'wbk_', 'civicrm_' ) );
					}

					if ( $assoc_args['include-tables'] === true || empty( $assoc_args['include-tables'] ) ) {
						$tables = new cli\Table();

						$tables->setHeaders(
							array(
								'name' => sprintf( 'Tables to include (%s)', DB_NAME ),
							)
						);

						foreach ( $mysql->get_tables() as $table_name ) {
							$tables->addRow(
								array(
									'name' => $table_name,
								)
							);
						}

						$tables->display();
						$included_tables = array();

						while ( $table = trim( readline( 'Enter table name to include in backup (q=quit, empty=continue): ' ) ) ) {
							switch ( $table ) {
								case 'q':
									exit;

								default:
									if ( ! in_array( $table, $all_tables ) ) {
										WP_CLI::warning( __( 'Unknown table: ', AI1WM_PLUGIN_NAME ) . $table );
										break;
									}
									$included_tables[] = $table;
							}
						}
					} else {
						$included_tables = array_intersect(
							$all_tables,
							array_filter( explode( ',', $assoc_args['include-tables'] ), 'trim' )
						);
					}

					if ( ! empty( $included_tables ) ) {
						$params['options']['include_db_tables'] = true;
						$params['included_db_tables']           = implode( ',', $included_tables );
					}
				}
			}

			if ( isset( $assoc_args['exclude-email-replace'] ) ) {
				$params['options']['no_email_replace'] = true;
			}

			if ( isset( $assoc_args['replace'] ) ) {
				for ( $i = 0; $i < count( $args ); $i += 2 ) {
					if ( isset( $args[ $i ] ) && isset( $args[ $i + 1 ] ) ) {
						$params['options']['replace']['old_value'][] = $args[ $i ];
						$params['options']['replace']['new_value'][] = $args[ $i + 1 ];
					}
				}
			}

			if ( is_multisite() && isset( $assoc_args['sites'] ) ) {
				$sites = array();
				if ( ! is_bool( $assoc_args['sites'] ) ) {
					$sites = array_filter( explode( ',', $assoc_args['sites'] ), 'trim' );
				}

				if ( ! empty( $sites ) ) {
					$params['options']['sites'] = $sites;
				} else {
					while ( ( $site_id = readline( 'Enter site ID (q=quit, l=list sites): ' ) ) ) {
						switch ( $site_id ) {
							case 'q':
								exit;

							case 'l':
								WP_CLI::runcommand( 'site list --fields=blog_id,url' );
								break;

							default:
								if ( ! get_blog_details( $site_id ) ) {
									WP_CLI::error_multi_line(
										array(
											__( 'A site with this ID does not exist.', AI1WM_PLUGIN_NAME ),
											__( 'To list the sites type `l`.', AI1WM_PLUGIN_NAME ),
										)
									);
									break;
								}

								$params['options']['sites'][] = $site_id;
						}
					}
				}
			}

			return $params;
		}

		protected function run_backup( $params ) {
			WP_CLI::log( __( 'Backup in progress...', AI1WM_PLUGIN_NAME ) );

			try {

				// Disable completed timeout
				add_filter( 'ai1wm_completed_timeout', '__return_zero' );

				// Run export filters
				$params = Ai1wm_Export_Controller::export( $params );

			} catch ( Exception $e ) {
				WP_CLI::error( __( sprintf( 'Unable to backup: %s', $e->getMessage() ), AI1WM_PLUGIN_NAME ) );
			}

			WP_CLI::success( __( 'Backup complete.', AI1WM_PLUGIN_NAME ) );
			WP_CLI::log( sprintf( __( 'Backup file: %s', AI1WM_PLUGIN_NAME ), ai1wm_archive_name( $params ) ) );

			return $params;
		}

		protected function run_restore( $params ) {
			WP_CLI::log( __( 'Restore in progress...', AI1WM_PLUGIN_NAME ) );

			try {

				// Disable completed timeout
				add_filter( 'ai1wm_completed_timeout', '__return_zero' );

				// Run import filters
				Ai1wm_Import_Controller::import( $params );

			} catch ( Exception $e ) {
				WP_CLI::error( __( sprintf( 'Unable to import: %s', $e->getMessage() ), AI1WM_PLUGIN_NAME ) );
			}

			WP_CLI::success( __( 'Restore complete.', AI1WM_PLUGIN_NAME ) );
		}
	}
}
