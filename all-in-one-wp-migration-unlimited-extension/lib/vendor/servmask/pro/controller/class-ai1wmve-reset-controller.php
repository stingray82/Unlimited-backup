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

class Ai1wmve_Reset_Controller {

	public static function index() {
		Ai1wm_Template::render(
			'reset/index',
			array(
				'backups'      => Ai1wm_Backups::get_files(),
				'labels'       => Ai1wm_Backups::get_labels(),
				'downloadable' => Ai1wm_Backups::are_downloadable(),
				'user'         => wp_get_current_user(),
			),
			AI1WMVE_TEMPLATES_PATH
		);
	}

	public static function reset( $params = array() ) {
		global $ai1wm_params;
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( array_merge( $_GET, $_POST ) );
		}

		// Set priority
		if ( ! isset( $params['priority'] ) ) {
			$params['priority'] = 10;
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		try {
			// Ensure that unauthorized people cannot access reset action
			ai1wm_verify_secret_key( $secret_key );
		} catch ( Ai1wm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		$ai1wm_params = $params;

		// Loop over filters
		if ( ( $filters = ai1wm_get_filters( 'ai1wm_reset' ) ) ) {
			while ( $hooks = current( $filters ) ) {
				if ( intval( $params['priority'] ) === key( $filters ) ) {
					foreach ( $hooks as $hook ) {
						try {
							// Run function hook
							$params = call_user_func_array( $hook['function'], array( $params ) );
						} catch ( Exception $e ) {
							if ( defined( 'WP_CLI' ) ) {
								WP_CLI::error( sprintf( __( 'Unable to Proceed: %s', AI1WM_PLUGIN_NAME ), $e->getMessage() ) );
							} else {
								Ai1wm_Status::error( __( 'Unable to Proceed', AI1WM_PLUGIN_NAME ), $e->getMessage() );
								Ai1wm_Notification::error( __( 'Unable to Proceed', AI1WM_PLUGIN_NAME ), $e->getMessage() );
							}
							exit;
						}
					}

					// Set completed
					$completed = true;
					if ( isset( $params['completed'] ) ) {
						$completed = (bool) $params['completed'];
					}

					// Do request
					if ( $completed === false || ( $next = next( $filters ) ) && ( $params['priority'] = key( $filters ) ) ) {
						if ( defined( 'WP_CLI' ) ) {
							if ( ! defined( 'DOING_CRON' ) ) {
								continue;
							}
						}

						if ( isset( $params['ai1wm_manual_reset'] ) ) {
							ai1wm_json_response( $params );
							exit;
						}

						wp_remote_request(
							apply_filters( 'ai1wm_http_reset_url', add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_reset' ) ) ),
							array(
								'method'    => apply_filters( 'ai1wm_http_reset_method', 'POST' ),
								'timeout'   => apply_filters( 'ai1wm_http_reset_timeout', 10 ),
								'blocking'  => apply_filters( 'ai1wm_http_reset_blocking', false ),
								'sslverify' => apply_filters( 'ai1wm_http_reset_sslverify', false ),
								'headers'   => apply_filters( 'ai1wm_http_reset_headers', array() ),
								'body'      => apply_filters( 'ai1wm_http_reset_body', $params ),
							)
						);
						exit;
					}
				}

				next( $filters );
			}
		}

		return $params;
	}
}
