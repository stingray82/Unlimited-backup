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

class Ai1wmve_Reset_Media {

	public static function execute( $params ) {
		// Skip reset step
		if ( ! isset( $params['ai1wm_reset_media'] ) ) {
			return $params;
		}

		// Flag to hold if file data has been processed
		$completed = true;

		// Start time
		$start = microtime( true );

		// Iterate over content directory
		$iterator = new Ai1wm_Recursive_Directory_Iterator( ai1wm_get_uploads_dir() );

		// Recursively iterate over directory
		$iterator = new Ai1wm_Recursive_Iterator_Iterator( $iterator, RecursiveIteratorIterator::CHILD_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD );

		// Get media files
		foreach ( $iterator as $item ) {
			if ( $item->isDir() ) {
				@rmdir( $item->getPathname() );
			} else {
				@unlink( $item->getPathname() );
			}

			// More than 10 seconds have passed, break and do another request
			if ( ( $timeout = apply_filters( 'ai1wm_completed_timeout', 10 ) ) ) {
				if ( ( microtime( true ) - $start ) > $timeout ) {
					$completed = false;
					break;
				}
			}
		}

		if ( is_multisite() ) {
			foreach ( ai1wmme_get_sites() as $site ) {
				static::delete_media_from_db( $site['BlogID'] );
			}
		} else {
			static::delete_media_from_db();
		}

		if ( $completed ) {

			// Set progress
			if ( ! isset( $params['ai1wm_reset_plugins'], $params['ai1wm_reset_themes'], $params['ai1wm_reset_media'], $params['ai1wm_reset_database'] ) ) {
				$message = __( 'The reset of your media files has been successfully completed. Your site\'s media library is now empty, and you can start afresh with new uploads.', AI1WM_PLUGIN_NAME );
				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					WP_CLI::success( $message );
				} else {
					Ai1wm_Status::done( __( 'Reset Successful' ), $message );
				}
				exit;
			}

			// Unset completed flag
			unset( $params['completed'] );

		} else {

			// Set completed flag
			$params['completed'] = $completed;
		}

		return $params;
	}

	protected static function delete_media_from_db( $blog_id = null ) {
		global $wpdb;

		$wpdb->query( sprintf( "DELETE FROM `%s` WHERE `post_type` = 'attachment'", ai1wm_table_prefix( $blog_id ) . 'posts' ) );
		$wpdb->query( sprintf( "DELETE FROM `%s` WHERE `meta_key` = '_wp_attached_file' OR `meta_key` = '_wp_attachment_metadata'", ai1wm_table_prefix( $blog_id ) . 'postmeta' ) );
	}
}
