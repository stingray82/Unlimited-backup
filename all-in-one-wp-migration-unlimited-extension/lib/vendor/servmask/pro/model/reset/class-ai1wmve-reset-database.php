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

class Ai1wmve_Reset_Database {

	protected $params = array();

	protected $backup = array();

	protected function __construct( $params ) {
		$this->params = $params;
	}

	public static function execute( $params ) {
		// Skip reset step
		if ( ! isset( $params['ai1wm_reset_database'] ) ) {
			return $params;
		}

		$reset = new static( $params );

		$reset->backup_options()
			->backup_user()
			->backup_blog()
			->save_backup()
			->drop_wp_tables()
			->reinstall_db()
			->restore_data();

		// Set progress
		if ( isset( $params['ai1wm_reset_plugins'], $params['ai1wm_reset_themes'], $params['ai1wm_reset_media'], $params['ai1wm_reset_database'] ) ) {
			$message = __( 'Your site has been fully reset to its original, pristine condition, as if it were newly installed. All content, themes, plugins, and settings have been removed. You now have a clean slate to rebuild your site exactly the way you want it. If you need to undo this action, please restore your site from a backup if available.', AI1WM_PLUGIN_NAME );
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::success( $message );
			} else {
				Ai1wm_Status::done( __( 'Reset Successful' ), $message );
			}
		} else {
			$message = __( 'The reset of your site\'s database has been successfully completed. WordPress has been restored to its original state, similar to a new installation. Please remember, this action has removed all content, settings, and user data. Proceed to set up your site again or restore from a backup if necessary.', AI1WM_PLUGIN_NAME );
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::success( $message );
			} else {
				Ai1wm_Status::done( __( 'Reset Successful' ), $message );
			}
			exit;
		}

		return $params;
	}

	/**
	 * Save all AI1WM options from DB as raw values
	 *
	 * @return $this
	 */
	protected function backup_options() {
		global $wpdb;

		$ai1wm_options = $wpdb->get_results(
			sprintf( 'SELECT `option_name`, `option_value` FROM `%s` WHERE `option_name` LIKE \'ai1wm%%\'', ai1wm_table_prefix() . 'options' )
		);

		foreach ( $ai1wm_options as $ai1wm_option ) {
			$this->backup['options'][ $ai1wm_option->option_name ] = $ai1wm_option->option_value;
		}

		return $this;
	}

	/**
	 * Save user data and sessions
	 *
	 * @return $this
	 */
	protected function backup_user() {
		if ( $user = wp_get_current_user() ) {
			$this->backup['user_data'] = (array) $user->data;

			if ( get_user_meta( $user->ID, 'session_tokens' ) ) {
				$this->backup['user_sessions'] = get_user_meta( $user->ID, 'session_tokens', true );
			}
		}

		return $this;
	}

	/**
	 * Save blog data
	 *
	 * @return $this
	 */
	protected function backup_blog() {
		$this->backup['blog'] = array(
			'name'     => get_option( 'blogname' ),
			'public'   => get_option( 'blog_public' ),
			'site_url' => get_option( 'siteurl' ),
		);

		$this->backup['active_plugins'] = ai1wm_active_plugins();

		if ( is_multisite() ) {
			$this->backup['active_sitewide_plugins'] = ai1wm_active_sitewide_plugins();
		}

		$this->backup['theme'] = array(
			'stylesheet' => ai1wm_active_stylesheet(),
			'template'   => ai1wm_active_template(),
		);

		return $this;
	}

	/**
	 * Save backup data into file
	 *
	 * @return $this
	 */
	protected function save_backup() {
		if ( ! empty( $this->backup ) ) {
			$handle = ai1wm_open( ai1wmve_reset_db_backup_path(), 'w' );
			ai1wm_write( $handle, json_encode( $this->backup ) );
			ai1wm_close( $handle );

			$this->backup = array();
		}

		return $this;
	}

	/**
	 * Let WordPress' installer create tables with necessary data
	 *
	 * @return $this
	 */
	protected function reinstall_db() {
		if ( ! function_exists( 'wp_install' ) ) {
			// We need functions from here
			require_once ABSPATH . '/wp-admin/includes/upgrade.php';
		}

		// Force creating all WP tables
		add_filter( 'wp_should_upgrade_global_tables', '__return_true', 1000 );

		wp_check_mysql_version();
		ai1wm_cache_flush();
		make_db_current_silent();
		populate_options();
		populate_roles();

		return $this;
	}

	/**
	 * Drop all WP database tables
	 *
	 * @return $this
	 */
	protected function drop_wp_tables() {
		$mysql = Ai1wm_Database_Utility::create_client();

		// Include table prefixes
		if ( ai1wm_table_prefix() ) {
			$mysql->add_table_prefix_filter( ai1wm_table_prefix() );
		}

		$mysql->flush();

		return $this;
	}

	/**
	 * Load backup data from file and populate database
	 *
	 * @return void
	 */
	protected function restore_data() {
		global $wpdb;

		// Read backup file
		$handle = ai1wm_open( ai1wmve_reset_db_backup_path(), 'r' );

		// Parse file
		$backup = ai1wm_read( $handle, filesize( ai1wmve_reset_db_backup_path() ) );
		$backup = json_decode( $backup, true );

		// Close handle
		ai1wm_close( $handle );

		remove_all_actions( 'update_option_blogname' );
		remove_all_actions( 'update_option_blogdescription' );

		update_option( 'blogname', $backup['blog']['name'] );
		update_option( 'admin_email', $backup['user_data']['user_email'] );
		update_option( 'blog_public', $backup['blog']['public'] );

		$guess_url = ( wp_guess_url() !== 'http:' ) ? wp_guess_url() : $backup['blog']['site_url'];

		update_option( 'siteurl', $guess_url );
		update_option( 'home', $guess_url );

		// If it's not a public blog, default_pingback_flag option to false (0)
		if ( ! $backup['blog']['public'] ) {
			update_option( 'default_pingback_flag', 0 );
		}

		// User
		$user_id = wp_insert_user(
			array(
				'user_pass'     => $this->params['ai1wm_reset_password'],
				'user_login'    => $backup['user_data']['user_login'],
				'user_nicename' => $backup['user_data']['user_nicename'],
				'user_url'      => $backup['user_data']['user_url'],
				'user_email'    => $backup['user_data']['user_email'],
				'display_name'  => $backup['user_data']['display_name'],
				'role'          => 'administrator',
			)
		);

		$user = new WP_User( $user_id );
		wp_install_defaults( $user_id );
		wp_install_maybe_enable_pretty_permalinks();
		flush_rewrite_rules();
		ai1wm_cache_flush();

		// Restore user's session tokens
		if ( isset( $backup['user_sessions'] ) ) {
			add_user_meta( $user->ID, 'session_tokens', $backup['user_sessions'] );
		}

		wp_set_auth_cookie( $user_id, true );

		/**
		 * Fires after a site is fully installed.
		 *
		 * @since 3.9.0
		 *
		 * @param WP_User $user The site owner.
		 */
		do_action( 'wp_install', $user );

		if ( is_multisite() ) {
			grant_super_admin( $user_id );

			$domain = parse_url( $guess_url, PHP_URL_HOST );
			$path   = trailingslashit( parse_url( $guess_url, PHP_URL_PATH ) );

			$wpdb->insert(
				ai1wm_table_prefix() . 'site',
				array(
					'domain' => $domain,
					'path'   => $path,
				)
			);

			wpmu_create_blog( $domain, $path, $backup['blog']['name'], $user_id );
		}

		// Let's restore options
		ai1wm_activate_plugins( $backup['active_plugins'] );
		if ( isset( $backup['active_sitewide_plugins'] ) ) {
			ai1wm_activate_sitewide_plugins( $backup['active_sitewide_plugins'] );
		}
		ai1wm_activate_template( $backup['theme']['template'] );
		ai1wm_activate_stylesheet( $backup['theme']['stylesheet'] );

		foreach ( $backup['options'] as $name => $value ) {
			$wpdb->insert(
				ai1wm_table_prefix() . 'options',
				array(
					'option_name'  => $name,
					'option_value' => $value,
				)
			);
		}

		ai1wm_unlink( ai1wmve_reset_db_backup_path() );
	}
}
