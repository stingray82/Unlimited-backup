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

<table class="ai1wmve-schedules">
	<thead>
		<tr>
			<th class="ai1wm-column-title"><?php _e( 'Event name', AI1WM_PLUGIN_NAME ); ?></th>
			<th class="ai1wm-column-status"><?php _e( 'Status', AI1WM_PLUGIN_NAME ); ?></th>
			<th class="ai1wm-column-period"><?php _e( 'Period', AI1WM_PLUGIN_NAME ); ?></th>
			<th class="ai1wm-column-time"><?php _e( 'Time to start', AI1WM_PLUGIN_NAME ); ?></th>
			<th class="ai1wm-column-last-run"><?php _e( 'Last run', AI1WM_PLUGIN_NAME ); ?></th>
			<th class="ai1wm-column-actions"></th>
		</tr>
	</thead>
	<tbody class="ai1wmve-schedules-empty <?php echo count( $events ) > 0 ? '' : 'ai1wmve-schedules-empty-show'; ?>">
		<tr>
			<td colspan="6">
				<?php _e( 'Here are no records yet.', AI1WM_PLUGIN_NAME ); ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'create-event' ), network_admin_url( 'admin.php?page=ai1wmve_schedules' ) ) ); ?>">
					<?php _e( 'Create new event', AI1WM_PLUGIN_NAME ); ?>
				</a>
			</td>
		</tr>
	</tbody>
	<tbody class="ai1wmve-schedules-list">
		<?php foreach ( $events as $event ) : ?>
		<tr>
			<td class="ai1wm-column-title">
				<?php echo $event->title(); ?>
			</td>
			<td class="ai1wm-column-status ai1wm-column-status-<?php echo strtolower( $event->status() ); ?>">
				<?php echo $event->status(); ?>
			</td>
			<td class="ai1wm-column-period">
				<?php echo $event->period(); ?>
			</td>
			<td class="ai1wm-column-time">
				<?php echo $event->time(); ?>
			</td>
			<td class="ai1wm-column-last-run ai1wm-column-last-status-<?php echo strtolower( $event->last_run() ); ?>"">
				<span><?php echo $event->last_run(); ?></span>
			</td>
			<td class="ai1wm-column-actions ai1wmve-schedule-actions">
				<div>
					<a href="#" role="menu" aria-haspopup="true" class="ai1wmve-schedule-dots" title="<?php _e( 'More' ); ?>" aria-label="<?php _e( 'More' ); ?>">
						<i class="ai1wm-icon-dots-horizontal-triple"></i>
					</a>
					<div class="ai1wmve-schedule-dots-menu">
						<ul role="menu">
							<li>
								<a tabindex="-1" href="#" data-event_id="<?php echo esc_attr( $event->event_id() ); ?>" role="menuitem" aria-label="<?php _e( 'Start', AI1WM_PLUGIN_NAME ); ?>" class="ai1wmve-schedule-start">
									<i class="ai1wm-icon-play"></i>
									<span><?php _e( 'Start', AI1WM_PLUGIN_NAME ); ?></span>
								</a>
							</li>
							<li>
								<a tabindex="-1" href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit-event', 'event_id' => $event->event_id() ), network_admin_url( 'admin.php?page=ai1wmve_schedules' ) ) ); ?>" role="menuitem" aria-label="<?php _e( 'Edit', AI1WM_PLUGIN_NAME ); ?>" class="ai1wmve-schedule-edit">
									<i class="ai1wm-icon-edit-pencil"></i>
									<?php _e( 'Edit', AI1WM_PLUGIN_NAME ); ?>
								</a>
							</li>
							<li>
								<a tabindex="-1" href="#" data-event_id="<?php echo esc_attr( $event->event_id() ); ?>" data-event_title="<?php echo esc_attr( $event->title() ); ?>" role="menuitem" aria-label="<?php _e( 'View log', AI1WM_PLUGIN_NAME ); ?>" class="ai1wmve-schedule-view-log">
									<i class="ai1wm-icon-eye"></i>
									<span><?php _e( 'View log', AI1WM_PLUGIN_NAME ); ?></span>
								</a>
							</li>
							<li>
								<a tabindex="-1" href="#" data-event_id="<?php echo esc_attr( $event->event_id() ); ?>" role="menuitem" aria-label="<?php _e( 'Clean log', AI1WM_PLUGIN_NAME ); ?>" class="ai1wmve-schedule-clean-log">
									<i class="ai1wm-icon-broom"></i>
									<span><?php _e( 'Clean log', AI1WM_PLUGIN_NAME ); ?></span>
								</a>
							</li>
							<li class="divider"></li>
							<li>
								<a tabindex="-1" href="#" data-event_id="<?php echo esc_attr( $event->event_id() ); ?>" role="menuitem" aria-label="<?php _e( 'Delete', AI1WM_PLUGIN_NAME ); ?>" class="ai1wmve-schedule-delete">
									<i class="ai1wm-icon-close"></i>
									<span><?php _e( 'Delete', AI1WM_PLUGIN_NAME ); ?></span>
								</a>
							</li>
						</ul>
					</div>
				</div>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>

