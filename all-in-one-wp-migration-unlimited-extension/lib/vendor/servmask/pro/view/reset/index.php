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
?>

<div class="ai1wm-container ai1wm-reset-container">
	<div class="ai1wm-row">
		<div class="ai1wm-left">
			<div class="ai1wm-holder" id="ai1wm-reset-tools">
				<h3 class="mt-0"><?php _e( 'Reset Hub', AI1WM_PLUGIN_NAME ); ?></h3>

				<div class="ai1wm-tool-container ai1wm-tools">
					<h4 class="mt-0 mb-0"><?php _e( 'Tools', AI1WM_PLUGIN_NAME ); ?></h4>

					<div class="ai1wm-btn-container">
						<a href="#ai1wm-reset-plugins" class="ai1wm-btn"><i class="ai1wm-icon-power-cord"></i> <?php _e( 'Plugin Purge', AI1WM_PLUGIN_NAME ); ?></a>
					</div>

					<div class="ai1wm-btn-container">
						<a href="#ai1wm-reset-themes" class="ai1wm-btn"><i class="ai1wm-icon-stack"></i> <?php _e( 'Theme Reset', AI1WM_PLUGIN_NAME ); ?></a>
					</div>

					<div class="ai1wm-btn-container">
						<a href="#ai1wm-reset-media" class="ai1wm-btn"><i class="ai1wm-icon-image"></i> <?php _e( 'Media Clean-Up', AI1WM_PLUGIN_NAME ); ?></a>
					</div>

					<div class="ai1wm-btn-container">
						<a href="#ai1wm-reset-database" class="ai1wm-btn"><i class="ai1wm-icon-database"></i> <?php _e( 'Database Reset', AI1WM_PLUGIN_NAME ); ?></a>
					</div>

					<div class="ai1wm-btn-container">
						<a href="#ai1wm-reset-all" class="ai1wm-btn"><i class="ai1wm-icon-file-zip"></i> <?php _e( 'Full Site Reset', AI1WM_PLUGIN_NAME ); ?></a>
					</div>
				</div>

				<reset-tool
						reset-type="plugins"
						icon="ai1wm-icon-power-cord"
				></reset-tool>

				<reset-tool
						reset-type="themes"
						icon="ai1wm-icon-stack"
				></reset-tool>

				<reset-tool
						reset-type="media"
						icon="ai1wm-icon-image"
				></reset-tool>

				<reset-tool
						reset-type="database"
						icon="ai1wm-icon-database"
				></reset-tool>

				<reset-tool
						reset-type="all"
						icon="ai1wm-icon-arrow-down"
				></reset-tool>

				<reset-confirmation
						please-remember-html="<?php esc_attr_e( __( '<strong><i class="ai1wm-icon-notification"></i> Please remember:</strong> Always <a href="#" class="ai1wm-show-create-snapshot-link">create a backup</a> before proceeding, so you can undo these changes if needed.', AI1WM_PLUGIN_NAME ) ); ?>"
						confirm-password-label="<?php esc_attr_e( __( 'To confirm, enter your current password:', AI1WM_PLUGIN_NAME ) ); ?>"
						password-placeholder="<?php esc_attr_e( sprintf( _x( 'Enter password for %s', 'password for the current user', AI1WM_PLUGIN_NAME ), $user->data->user_login ) ); ?>"
				></reset-confirmation>
				<create-snapshot-modal></create-snapshot-modal>
				<reset-loader></reset-loader>
			</div>

			<div class="ai1wm-holder" style="margin-top: 20px;">
				<h1>
					<i class="ai1wm-icon-export"></i>
					<?php _e( 'Backups', AI1WM_PLUGIN_NAME ); ?>
				</h1>

				<?php if ( is_readable( AI1WM_BACKUPS_PATH ) && is_writable( AI1WM_BACKUPS_PATH ) ) : ?>
					<div id="ai1wm-backups-list">
						<?php include AI1WM_TEMPLATES_PATH . '/backups/backups-list.php'; ?>
					</div>

					<form action="" method="post" id="ai1wm-export-form" class="ai1wm-clear">
						<div id="ai1wm-backups-create">
							<p class="ai1wm-backups-empty-spinner-holder ai1wm-hide">
								<span class="spinner"></span>
								<?php _e( 'Refreshing backup list...', AI1WM_PLUGIN_NAME ); ?>
							</p>
							<p class="ai1wm-backups-empty <?php echo empty( $backups ) ? null : 'ai1wm-hide'; ?>">
								<?php _e( 'There are no backups available at this time, why not create a new one?', AI1WM_PLUGIN_NAME ); ?>
							</p>
							<p>
								<a href="#" id="ai1wm-create-backup" class="ai1wm-button-green">
									<i class="ai1wm-icon-export"></i>
									<?php _e( 'Create backup', AI1WM_PLUGIN_NAME ); ?>
								</a>
							</p>
						</div>
						<input type="hidden" id="ai1wm-reset-label" name="ai1wm_reset_label" value="" />
						<input type="hidden" name="ai1wm_manual_export" value="1" />
					</form>

					<?php do_action( 'ai1wm_backups_left_end' ); ?>

				<?php else : ?>

					<?php include AI1WM_TEMPLATES_PATH . '/backups/backups-permissions.php'; ?>

				<?php endif; ?>
			</div>

			<div id="ai1wm-backups-list-archive-browser">
				<archive-browser></archive-browser>
			</div>
		</div>

		<?php require AI1WM_TEMPLATES_PATH . '/common/sidebar-right.php'; ?>
	</div>
</div>
