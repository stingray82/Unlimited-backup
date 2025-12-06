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

class Ai1wmue_Main_Controller extends Ai1wmve_Main_Controller {

	/**
	 * Register plugin menus
	 *
	 * @return void
	 */
	public function admin_menu() {
		// Sub-level Settings menu
		add_submenu_page(
			'ai1wm_export',
			__( 'Settings', AI1WMUE_PLUGIN_NAME ),
			__( 'Settings', AI1WMUE_PLUGIN_NAME ),
			'export',
			'ai1wmue_settings',
			'Ai1wmue_Settings_Controller::index'
		);
	}

	/**
	 * Register listeners for actions
	 *
	 * @return void
	 */
	protected function activate_actions() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_export_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_import_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backups_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_reset_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_scripts_and_styles' ), 20 );
	}

	/**
	 * Export and import commands
	 *
	 * @return void
	 */
	public function ai1wm_commands() {
		if ( ai1wmue_is_running() ) {
			remove_all_filters( 'ai1wm_export', 280 );
			remove_all_filters( 'ai1wm_import', 5 );

			add_filter( 'ai1wm_export', 'Ai1wmue_Export_Retention::execute', 280 );
			add_filter( 'ai1wm_import', 'Ai1wmue_Import_Upload::execute', 5 );
			add_filter( 'ai1wm_import', 'Ai1wmue_Import_Settings::execute', 290 );
			add_filter( 'ai1wm_import', 'Ai1wmue_Import_Database::execute', 310 );
		}
	}

	/**
	 * Check whether All-in-One WP Migration is loaded
	 *
	 * @return void
	 */
	public function ai1wm_loaded() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );

		// Settings
		add_action( 'admin_post_ai1wmue_settings', 'Ai1wmue_Settings_Controller::settings' );
	}

	/**
	 * WP CLI commands
	 *
	 * @return void
	 */
	public function ai1wmve_wp_cli() {
		if ( defined( 'WP_CLI' ) ) {
			WP_CLI::add_command(
				'ai1wm',
				'Ai1wm_Backup_WP_CLI_Command',
				array(
					'shortdesc'     => __( 'All-in-One WP Migration Command', AI1WMUE_PLUGIN_NAME ),
					'before_invoke' => array( $this, 'activate_extension_commands' ),
				)
			);
		}
	}

	/**
	 * Activates extension specific commands
	 *
	 * @return void
	 */
	public function activate_extension_commands() {
		$_GET['file'] = 1;
		$this->ai1wm_commands();
	}

	/**
	 * Display All-in-One WP Migration notice
	 *
	 * @return void
	 */
	public function ai1wm_notice() {
		// Check if the base plugin is installed but not activated
		if ( ai1wmue_is_base_plugin_installed() && ! ai1wmue_is_base_plugin_active() ) {
			?>
			<div class="error">
				<p>
					<?php
					_e(
						sprintf(
							'Unlimited Extension requires All-in-One WP Migration plugin to be activated. <a href="%s">Activate Now</a>',
							wp_nonce_url( 'plugins.php?action=activate&plugin=all-in-one-wp-migration/all-in-one-wp-migration.php', 'activate-plugin_all-in-one-wp-migration/all-in-one-wp-migration.php' )
						),
						AI1WMUE_PLUGIN_NAME
					);
					?>
				</p>
			</div>
			<?php
		} elseif ( ! ai1wmue_is_base_plugin_installed() ) {
			// Base plugin is not installed
			?>
			<div class="error">
				<p>
					<?php
					_e(
						sprintf(
							'Unlimited Extension requires All-in-One WP Migration plugin to be installed. <a href="%s">Install Now</a> or <a href="%s" target="_blank">Download Manually</a>',
							wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=all-in-one-wp-migration' ), 'install-plugin_all-in-one-wp-migration' ),
							'https://wordpress.org/plugins/all-in-one-wp-migration/'
						),
						AI1WMUE_PLUGIN_NAME
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Enqueue scripts and styles for Export Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_export_scripts_and_styles( $hook ) {
		if ( stripos( 'toplevel_page_ai1wm_export', $hook ) === false ) {
			return;
		}

		add_action( 'admin_print_scripts', array( $this, 'google_tag_manager' ) );
	}

	/**
	 * Enqueue scripts and styles for Import Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_import_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wm_import', $hook ) === false ) {
			return;
		}

		wp_enqueue_script(
			'ai1wmue_import',
			Ai1wm_Template::asset_link( 'javascript/import.min.js', 'AI1WMUE' ),
			array( 'ai1wm_import' )
		);

		wp_enqueue_script(
			'ai1wmue_wasm_exec',
			Ai1wm_Template::asset_link( 'javascript/vendor/wasm_exec.js', 'AI1WMUE' ),
			array( 'ai1wmue_import' )
		);

		// Base service
		wp_localize_script(
			'ai1wmue_import',
			'ai1wmue_base_service',
			array(
				'url' => AI1WMUE_SERVICE_URL,
				'key' => AI1WMUE_PURCHASE_ID,
			)
		);

		// Site details
		wp_localize_script(
			'ai1wmue_import',
			'ai1wmue_site_details',
			array(
				'site_url'    => site_url(),
				'admin_email' => get_option( 'admin_email' ),
			)
		);

		// File uploader
		wp_localize_script(
			'ai1wmue_import',
			'ai1wmue_file_uploader',
			array(
				'config'  => array(
					'chunk_size'  => (int) apply_filters( 'ai1wm_max_chunk_size', AI1WM_MAX_CHUNK_SIZE ),
					'max_retries' => (int) apply_filters( 'ai1wm_max_chunk_retries', AI1WM_MAX_CHUNK_RETRIES ),
				),
				'url'     => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_import' ) ) ),
				'params'  => array(
					'priority'   => 5,
					'secret_key' => get_option( AI1WM_SECRET_KEY ),
				),
				'filters' => array(
					'ai1wm_archive_extension' => array( 'wpress' ),
				),
			)
		);

		add_action( 'admin_print_scripts', array( $this, 'google_tag_manager' ) );
	}

	/**
	 * Enqueue scripts and styles for Backups Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_backups_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wm_backups', $hook ) === false ) {
			return;
		}

		wp_enqueue_script(
			'ai1wmue_backups',
			Ai1wm_Template::asset_link( 'javascript/backups.min.js', 'AI1WMUE' ),
			array( 'ai1wm_backups' )
		);

		wp_enqueue_script(
			'ai1wmue_wasm_exec',
			Ai1wm_Template::asset_link( 'javascript/vendor/wasm_exec.js', 'AI1WMUE' ),
			array( 'ai1wmue_backups' )
		);

		// Base service
		wp_localize_script(
			'ai1wmue_backups',
			'ai1wmue_base_service',
			array(
				'url' => AI1WMUE_SERVICE_URL,
				'key' => AI1WMUE_PURCHASE_ID,
			)
		);

		// Site details
		wp_localize_script(
			'ai1wmue_backups',
			'ai1wmue_site_details',
			array(
				'site_url'    => site_url(),
				'admin_email' => get_option( 'admin_email' ),
			)
		);

		add_action( 'admin_print_scripts', array( $this, 'google_tag_manager' ) );
	}

	/**
	 * Enqueue scripts and styles for Reset Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_reset_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wmve_reset', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmue_reset',
				Ai1wm_Template::asset_link( 'css/reset-tools.min.rtl.css', 'AI1WMUE' ),
				array( 'ai1wm_backups' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmue_reset',
				Ai1wm_Template::asset_link( 'css/reset-tools.min.css', 'AI1WMUE' ),
				array( 'ai1wm_backups' )
			);
		}

		wp_enqueue_script(
			'ai1wmue_backups',
			Ai1wm_Template::asset_link( 'javascript/backups.min.js', 'AI1WMUE' ),
			array( 'ai1wm_backups' )
		);

		wp_enqueue_script(
			'ai1wmue_reset',
			Ai1wm_Template::asset_link( 'javascript/reset-tools.min.js', 'AI1WMUE' ),
			array( 'ai1wmue_backups' )
		);

		wp_enqueue_script(
			'ai1wmue_wasm_exec',
			Ai1wm_Template::asset_link( 'javascript/vendor/wasm_exec.js', 'AI1WMUE' ),
			array( 'ai1wmue_reset' )
		);

		// Base service
		wp_localize_script(
			'ai1wmue_reset',
			'ai1wmue_base_service',
			array(
				'url' => AI1WMUE_SERVICE_URL,
				'key' => AI1WMUE_PURCHASE_ID,
			)
		);

		// Site details
		wp_localize_script(
			'ai1wmue_reset',
			'ai1wmue_site_details',
			array(
				'site_url'    => site_url(),
				'admin_email' => get_option( 'admin_email' ),
			)
		);

		wp_localize_script(
			'ai1wmue_reset',
			'ai1wmve_reset',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_reset' ) ) ),
				),
				'status'     => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1, 'secret_key' => get_option( AI1WM_SECRET_KEY ) ), admin_url( 'admin-ajax.php?action=ai1wm_status' ) ) ),
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wmue_reset',
			'ai1wmve_locale',
			array(
				// Reset type agnostic translations
				'reset_in_progress'           => __( 'Reset In Progress', AI1WMUE_PLUGIN_NAME ),
				'reset_in_progress_info'      => __( 'Your request is being processed. This may take a few moments. Please do not close this window or navigate away from this page while the reset is in progress.', AI1WMUE_PLUGIN_NAME ),
				'stop_resetting_your_website' => __( 'You are about to stop resetting your website, are you sure?', AI1WMUE_PLUGIN_NAME ),
				'unable_to_stop_the_reset'    => __( 'Unable to stop the reset. Refresh the page and try again', AI1WMUE_PLUGIN_NAME ),
				'unable_to_start_the_reset'   => __( 'Unable to start the reset. Refresh the page and try again', AI1WMUE_PLUGIN_NAME ),
				'unable_to_reset'             => __( 'Unable to reset', AI1WMUE_PLUGIN_NAME ),
				'create_snapshot_title'       => __( 'Create a new snapshot', AI1WMUE_PLUGIN_NAME ),
				'create_snapshot_btn'         => __( 'Create snapshot', AI1WMUE_PLUGIN_NAME ),
				'cancel'                      => __( 'Cancel', AI1WMUE_PLUGIN_NAME ),
				'done'                        => __( 'Done', AI1WMUE_PLUGIN_NAME ),
				'stop'                        => __( 'Stop Reset', AI1WMUE_PLUGIN_NAME ),
				'close'                       => __( 'Close', AI1WMUE_PLUGIN_NAME ),
				'backup_btn'                  => __( 'Create Backup', AI1WMUE_PLUGIN_NAME ),
				'retry'                       => __( 'Retry', AI1WMUE_PLUGIN_NAME ),

				// Translations for each of a reset type
				'plugins'                     => array(
					'name'          => __( 'Plugin Purge', AI1WMUE_PLUGIN_NAME ),
					'description'   => __( 'Quickly removes all installed plugins from your WordPress site. Ideal for troubleshooting conflicts or starting fresh with plugin installations.', AI1WMUE_PLUGIN_NAME ),
					'help'          => __( 'This tool will remove all installed plugins from your site.', AI1WMUE_PLUGIN_NAME ),
					'reset_btn'     => __( 'Purge Plugins', AI1WMUE_PLUGIN_NAME ),
					'confirm_title' => __( 'Confirm Plugin Purge', AI1WMUE_PLUGIN_NAME ),
					'confirm_text'  => __( 'Are you sure you want to purge your plugins? This will delete all the plugins.', AI1WMUE_PLUGIN_NAME ),
					'confirm_btn'   => __( 'Purge Plugins', AI1WMUE_PLUGIN_NAME ),
				),
				'themes'                      => array(
					'name'          => __( 'Theme Reset', AI1WMUE_PLUGIN_NAME ),
					'description'   => __( 'Deletes all themes and reactivates the default WordPress theme. Useful for reverting to a clean state or resolving theme-related issues.', AI1WMUE_PLUGIN_NAME ),
					'help'          => __( 'This tool will delete all themes and revert to the default WordPress theme.', AI1WMUE_PLUGIN_NAME ),
					'reset_btn'     => __( 'Theme Reset', AI1WMUE_PLUGIN_NAME ),
					'confirm_title' => __( 'Confirm Theme Reset', AI1WMUE_PLUGIN_NAME ),
					'confirm_text'  => __( 'Are you sure you want to reset your themes? This will delete all your current themes and reactivate the default WordPress theme.', AI1WMUE_PLUGIN_NAME ),
					'confirm_btn'   => __( 'Theme Reset', AI1WMUE_PLUGIN_NAME ),
				),
				'media'                       => array(
					'name'          => __( 'Media Clean-Up', AI1WMUE_PLUGIN_NAME ),
					'description'   => __( 'Erases all media files from the site\'s media library. Ideal for clearing outdated or unnecessary media to declutter your site.', AI1WMUE_PLUGIN_NAME ),
					'help'          => __( 'This tool will delete all media files from your site\'s media library.', AI1WMUE_PLUGIN_NAME ),
					'reset_btn'     => __( 'Media Clean-Up', AI1WMUE_PLUGIN_NAME ),
					'confirm_title' => __( 'Confirm Media Clean-Up', AI1WMUE_PLUGIN_NAME ),
					'confirm_text'  => __( 'Are you sure you want to erase all media files from your site media library?', AI1WMUE_PLUGIN_NAME ),
					'confirm_btn'   => __( 'Media Clean-Up', AI1WMUE_PLUGIN_NAME ),
				),
				'database'                    => array(
					'name'          => __( 'Reset Database', AI1WMUE_PLUGIN_NAME ),
					'description'   => __( 'This action will permanently erase all existing data within your database and revert your WordPress site to its default state. This includes posts, pages, comments, settings, and user data. Useful for reverting to a clean state and starting fresh.', AI1WMUE_PLUGIN_NAME ),
					'help'          => __( 'This tool will delete all existing data within your database and revert your WordPress site to its default state.', AI1WMUE_PLUGIN_NAME ),
					'reset_btn'     => __( 'Reset Database', AI1WMUE_PLUGIN_NAME ),
					'confirm_title' => __( 'Confirm Database Reset', AI1WMUE_PLUGIN_NAME ),
					'confirm_text'  => __( 'Are you sure you want to reset your database? This action will permanently erase all existing data within your database and revert your WordPress site to its default state. This includes posts, pages, comments, settings, and user data. Once completed, this action cannot be undone.', AI1WMUE_PLUGIN_NAME ),
					'confirm_btn'   => __( 'Reset Database', AI1WMUE_PLUGIN_NAME ),
				),
				'all'                         => array(
					'name'          => __( 'Full Site Reset', AI1WMUE_PLUGIN_NAME ),
					'description'   => __( 'Completely resets the site, restoring WordPress to its initial installation state. Best for starting entirely from scratch or for a clean slate on the site.', AI1WMUE_PLUGIN_NAME ),
					'help'          => __( 'This tool will reset your entire WordPress site to its default installation state.', AI1WMUE_PLUGIN_NAME ),
					'reset_btn'     => __( 'Full Site Reset', AI1WMUE_PLUGIN_NAME ),
					'confirm_title' => __( 'Confirm Site Reset', AI1WMUE_PLUGIN_NAME ),
					'confirm_text'  => __( 'Are you sure you want to erase all media files from your site media library? This action is ideal for clearing outdated or unnecessary media to declutter your site.', AI1WMUE_PLUGIN_NAME ),
					'confirm_btn'   => __( 'Full Site Reset', AI1WMUE_PLUGIN_NAME ),
				),
			)
		);

		add_action( 'admin_print_scripts', array( $this, 'google_tag_manager' ) );
	}

	/**
	 * Enqueue scripts and styles for Settings Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_settings_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wmue_settings', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmue_settings',
				Ai1wm_Template::asset_link( 'css/settings.min.rtl.css', 'AI1WMUE' ),
				array( 'ai1wm_servmask' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmue_settings',
				Ai1wm_Template::asset_link( 'css/settings.min.css', 'AI1WMUE' ),
				array( 'ai1wm_servmask' )
			);
		}

		wp_enqueue_script(
			'ai1wmue_settings',
			Ai1wm_Template::asset_link( 'javascript/settings.min.js', 'AI1WMUE' ),
			array( 'ai1wm_settings' )
		);

		wp_localize_script(
			'ai1wmue_settings',
			'ai1wm_feedback',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_feedback' ) ) ),
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wmue_settings',
			'ai1wmue_folder_browser',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wmue_folder_browser' ) ) ),
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wmue_settings',
			'ai1wmue_locale',
			array(
				'folder_browser_change' => __( 'Change', AI1WMUE_PLUGIN_NAME ),
				'title_name'            => __( 'Name', AI1WMUE_PLUGIN_NAME ),
				'title_date'            => __( 'Date', AI1WMUE_PLUGIN_NAME ),
				'empty_list_message'    => __( 'No folders to list. Click on the navbar to go back.', AI1WMUE_PLUGIN_NAME ),
				'legend_select_info'    => __( 'Select with a click', AI1WMUE_PLUGIN_NAME ),
				'legend_open_info'      => __( 'Open with two clicks', AI1WMUE_PLUGIN_NAME ),
				'button_close'          => __( 'Close', AI1WMUE_PLUGIN_NAME ),
				'button_select'         => __( 'Select folder &gt;', AI1WMUE_PLUGIN_NAME ),
				'show_more'             => __( 'more', AI1WMUE_PLUGIN_NAME ),
				'show_less'             => __( 'less', AI1WMUE_PLUGIN_NAME ),
			)
		);

		add_action( 'admin_print_scripts', array( $this, 'google_tag_manager' ) );
	}

	/**
	 * Register initial router
	 *
	 * @return void
	 */
	public function router() {
		if ( current_user_can( 'export' ) ) {
			add_action( 'wp_ajax_ai1wmue_folder_browser', 'Ai1wmue_Settings_Controller::list_folders' );
		}
	}

	/**
	 * Add links to plugin list page
	 *
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file === AI1WMUE_PLUGIN_BASENAME ) {
			$links[] = __( '<a href="https://help.servmask.com/knowledgebase/unlimited-extension-user-guide/" target="_blank">User Guide</a>', AI1WMUE_PLUGIN_NAME );
			$links[] = __( '<a href="https://servmask.com/contact-support" target="_blank">Contact Support</a>', AI1WMUE_PLUGIN_NAME );
		}

		return $links;
	}

	public function google_tag_manager() {
		Ai1wm_Template::render(
			'common/google-tag-manager',
			array(),
			AI1WMUE_TEMPLATES_PATH
		);
	}
}
