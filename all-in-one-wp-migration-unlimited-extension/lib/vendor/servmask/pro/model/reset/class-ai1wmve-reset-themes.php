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

class Ai1wmve_Reset_Themes {

	/**
	 * @var string[] List of default themes per WP version
	 *
	 * Order matters - values (versions) must go from newest to oldest version
	 */
	protected static $default_themes = array(
		'twentytwentyfive'  => '6.7',
		'twentytwentyfour'  => '6.4',
		'twentytwentythree' => '6.1',
		'twentytwentytwo'   => '5.9',
		'twentytwentyone'   => '5.6',
		'twentytwenty'      => '5.3',
		'twentynineteen'    => '5.0',
		'twentyseventeen'   => '4.7',
		'twentysixteen'     => '4.4',
		'twentyfifteen'     => '4.1',
		'twentyfourteen'    => '3.8',
		'twentythirteen'    => '3.6',
		'twentytwelve'      => '3.5',
		'twentyeleven'      => '3.2',
	);

	public static function execute( $params ) {
		// Skip reset step
		if ( ! isset( $params['ai1wm_reset_themes'] ) ) {
			return $params;
		}

		$themes = wp_get_themes();

		// Unset default theme
		if ( ( $default_theme = static::get_default_theme( $themes ) ) ) {
			unset( $themes[ $default_theme->stylesheet ] );
		}

		// Unset parent theme
		if ( ( $parent = $default_theme->parent ) && isset( $themes[ $parent ] ) ) {
			unset( $themes[ $parent ] );
		}

		if ( ! empty( $themes ) ) {
			foreach ( $themes as $name => $info ) {
				delete_theme( $name );
			}

			if ( is_multisite() ) {
				foreach ( ai1wmme_get_sites() as $site ) {
					static::reset_theme_options( $site['BlogID'] );

					switch_to_blog( $site['BlogID'] );
					ai1wm_activate_template( $default_theme->stylesheet );
					ai1wm_activate_stylesheet( $default_theme->stylesheet );
					restore_current_blog();
				}
			} else {
				static::reset_theme_options();
				ai1wm_activate_template( $default_theme->stylesheet );
				ai1wm_activate_stylesheet( $default_theme->stylesheet );
			}
		}

		// Set progress
		if ( ! isset( $params['ai1wm_reset_plugins'], $params['ai1wm_reset_themes'], $params['ai1wm_reset_media'], $params['ai1wm_reset_database'] ) ) {
			$message = __( 'Your theme has been successfully reset. All themes except the default WordPress theme have been removed, and the default theme is now active. This process has cleared all customizations and settings related to other themes. You can now start fresh with theme setup or install a new theme to customize your site\'s appearance.', AI1WM_PLUGIN_NAME );
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
	 * Get default theme to reset to
	 *
	 * @param WP_Theme[] $all_themes Array of (installed) WP themes
	 *
	 * @return WP_Theme Default theme based on WP version, or we create SM theme
	 */
	protected static function get_default_theme( $all_themes ) {
		global $wp_version;

		if ( $theme_slug = static::get_default_theme_slug( $wp_version ) ) {
			return static::ensure_theme_installed( $theme_slug, $all_themes );
		}

		// Fallback to ServMask-Theme if install fails
		return static::create_default_theme();
	}

	/**
	 * Determines the default theme based on the WordPress version.
	 *
	 * @param string $wp_version The current WordPress version.
	 * @return string|null The default theme slug or null if none is suitable.
	 */
	protected static function get_default_theme_slug( $wp_version ) {
		foreach ( static::$default_themes as $default_theme => $version ) {
			if ( version_compare( $wp_version, $version, '>=' ) ) {
				return $default_theme;
			}
		}

		return null;
	}

	/**
	 * Ensures that the specified theme is installed.
	 *
	 * @param string $theme_slug The slug of the theme to check/install.
	 * @param array $all_themes The list of all available themes.
	 * @return mixed The theme details if the theme is installed, otherwise the result of the installation process.
	 */
	public static function ensure_theme_installed( $theme_slug, $all_themes ) {
		if ( isset( $all_themes[ $theme_slug ] ) ) {
			return $all_themes[ $theme_slug ];
		}

		return static::install_default_theme( $theme_slug );
	}

	protected static function reset_theme_options( $blog_id = null ) {
		global $wpdb;

		$wpdb->query( sprintf( "DELETE FROM `%s` WHERE `option_name` LIKE 'mods\_%%' OR `option_name` LIKE 'theme\_mods\_%%'", ai1wm_table_prefix( $blog_id ) . 'options' ) );
	}

	protected static function install_default_theme( $theme ) {
		$api = themes_api( 'theme_information', array( 'slug' => $theme ) );
		if ( ! is_wp_error( $api ) ) {

			if ( ! class_exists( 'Theme_Upgrader' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}

			$upgrader = new Theme_Upgrader();
			if ( $upgrader->install( $api->download_link ) === true ) {
				return wp_get_theme( $theme );
			}
		}

		// Fallback to ServMask-Theme if install fails
		return static::create_default_theme();
	}

	protected static function create_default_theme() {
		// Create theme files
		Ai1wm_Directory::create( get_theme_root() . DIRECTORY_SEPARATOR . AI1WMVE_RESET_THEME_NAME );
		Ai1wm_File::create(
			get_theme_root() . DIRECTORY_SEPARATOR . AI1WMVE_RESET_THEME_STYLE_NAME,
			implode(
				PHP_EOL,
				array(
					'/*',
					'Theme Name: SevMask',
					'Theme URI: https://servmask.com/',
					'Author: ServMask',
					'Author URI: https://servmask.com/',
					'Description: ServMask Default Theme',
					'Version: 1.0',
					'Requires at least: 3.3',
					'Tested up to: 6.4',
					'Requires PHP: 5.3',
					'License: GNU General Public License v2 or later',
					'License URI: http://www.gnu.org/licenses/gpl-2.0.html',
					'*/',
				)
			)
		);

		Ai1wm_File::create(
			get_theme_root() . DIRECTORY_SEPARATOR . AI1WMVE_RESET_THEME_INDEX_NAME,
			implode(
				PHP_EOL,
				array(
					'<!DOCTYPE html>',
					'<html>',
					'<head>',
					'<title><?php bloginfo( "name" ); ?></title>',
					'<?php wp_head(); ?>',
					'</head>',
					'<body>',
					'<header>',
					'<h1><?php bloginfo( "name" ); ?></h1>',
					'</header>',
					'<main>',
					'<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>',
					'<h2><?php the_title(); ?></h2>',
					'<?php the_content(); ?>',
					'<?php endwhile; endif; ?>',
					'</main>',
					'<footer>',
					'<p><?php bloginfo( "name" ); ?> <?php echo date( "Y" ); ?></p>',
					'</footer>',
					'<?php wp_footer(); ?>',
					'</body>',
					'</html>',
				)
			)
		);

		return wp_get_theme( AI1WMVE_RESET_THEME_NAME );
	}
}
