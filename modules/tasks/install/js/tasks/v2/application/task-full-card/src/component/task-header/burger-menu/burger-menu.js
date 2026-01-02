import { Tag, Loc } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';
import { AirButtonStyle, ButtonSize } from 'ui.vue3.components.button';
import { BMenu, type MenuOptions, type MenuItemOptions } from 'ui.vue3.components.menu';
import { BIcon, Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';
import 'ui.icon-set.main';
import { Model } from 'tasks.v2.const';
import { Core } from 'tasks.v2.core';
import { taskService } from 'tasks.v2.provider.service.task-service';
import { TaskCard } from 'tasks.v2.application.task-card';
import type { TaskModel } from 'tasks.v2.model.tasks';

import './burger-menu.css';


const MENU_SECTION_PERSONAL = 'MENU_SECTION_PERSONAL';
const MENU_SECTION_TASKS = 'MENU_SECTION_TASKS';
const MENU_SECTION_COPY = 'MENU_SECTION_COPY';
const MENU_SECTION_LINKS = 'MENU_SECTION_LINKS';

// @vue/component
export const BurgerMenu = {
	name: 'TaskFullCardBurgerMenu',
	components: {
		BIcon,
		BMenu,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	setup(): Object
	{
		return {
			Outline,
			userRights: Core.getParams().rights,
		};
	},
	data(): Object
	{
		return {
			isMenuShown: false,
		};
	},
	computed: {
		...mapGetters({
			currentUserId: `${Model.Interface}/currentUserId`,
		}),
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		menuOptions(): Function
		{
			return (): MenuOptions => ({
				id: 'tasks-full-card-header-burger-menu',
				bindElement: this.$refs.button.$el,
				sections: [
					{
						code: MENU_SECTION_PERSONAL,
					},
					{
						code: MENU_SECTION_TASKS,
					},
					{
						code: MENU_SECTION_COPY,
					},
					{
						code: MENU_SECTION_LINKS,
					},
				],
				items: this.menuItems,
			});
		},
		menuItems(): MenuItemOptions[]
		{
			const actionsMenuItems = [];

			if (this.task.rights.favorite)
			{
				actionsMenuItems.push(this.getToggleFavoriteItem());
			}

			if (this.task.rights.watch)
			{
				actionsMenuItems.push(this.getBecomeAuditorItem());
			}

			if (this.task.rights.mute)
			{
				actionsMenuItems.push(this.getToggleMuteItem());
			}

			if (this.userRights.tasks.create)
			{
				actionsMenuItems.push(this.getCreateNewTaskItem());
			}

			if (this.task.rights.createSubtask)
			{
				actionsMenuItems.push(this.getCreateSubtaskItem());
			}

			if (this.task.rights.copy)
			{
				actionsMenuItems.push(this.getCreateDuplicateTaskItem());
			}

			if (this.userRights.tasks.createFromTemplate)
			{
				actionsMenuItems.push(this.getCreateNewTaskWithTemplateItem());
			}

			if (this.task.rights.saveAsTemplate)
			{
				actionsMenuItems.push(this.getCreateTemplateFromTaskItem());
			}

			actionsMenuItems.push(
				this.getCopyTaskIdItem(),
				this.getGoToBitrixMarketItem(),
			);

			if (this.userRights.tasks.robot)
			{
				actionsMenuItems.push(this.getGoToRobotsItem());
			}

			return actionsMenuItems;
		},
	},
	methods: {
		getNotificationLayoutWithTitle({ title, text }): Node
		{
			return Tag.render`
				<div>
					<p style="margin: 0">${title}</p>
					<p style="margin: 5px 0 0 0; font-size: 12px">${text}</p>
				</div>
			`;
		},

		// handlers
		handleClickOpener(): void
		{
			this.isMenuShown = true;
		},
		// handlers end

		// button getters
		getToggleFavoriteItem(): MenuItemOptions
		{
			const isTurningOn = !this.task.inFavorite.includes(this.currentUserId);

			return {
				sectionCode: MENU_SECTION_PERSONAL,
				title: isTurningOn
					? this.loc('TASKS_V2_TASK_FULL_CARD_FAVOR_ACTION')
					: this.loc('TASKS_V2_TASK_FULL_CARD_UNFAVOR_ACTION'),
				icon: isTurningOn ? Outline.FAVORITE : Outline.NON_FAVORITE,
				onClick: async (): void => {
					if (isTurningOn)
					{
						const isSuccess = await taskService.addFavorite(this.task, this.currentUserId);
						BX.UI.Notification.Center.notify({
							content: isSuccess
								? this.loc('TASKS_V2_TASK_FULL_CARD_FAVOR_NOTIF_SUCC')
								: this.loc('TASKS_V2_TASK_FULL_CARD_FAVOR_NOTIF_FAIL'),
						});
					}
					else
					{
						const isSuccess = await taskService.removeFavorite(this.task, this.currentUserId);
						BX.UI.Notification.Center.notify({
							content: isSuccess
								? this.loc('TASKS_V2_TASK_FULL_CARD_UNFAVOR_NOTIF_SUCC')
								: this.loc('TASKS_V2_TASK_FULL_CARD_UNFAVOR_NOTIF_FAIL'),
						});
					}
				},
			};
		},
		getBecomeAuditorItem(): MenuItemOptions
		{
			const auditorsIdsCurrent = this.task.auditorsIds;
			const isBecomingAuditor = !auditorsIdsCurrent.includes(this.currentUserId);

			return {
				sectionCode: MENU_SECTION_PERSONAL,
				title: isBecomingAuditor
					? this.loc('TASKS_V2_TASK_FULL_CARD_BECOME_AUDITOR_ACTION')
					: this.loc('TASKS_V2_TASK_FULL_CARD_STOP_BEING_AUDITOR_ACTION'),
				icon: isBecomingAuditor
					? Outline.OBSERVER
					: Outline.CROSSED_EYE,
				onClick: async (): void => {
					if (isBecomingAuditor)
					{
						const isSuccess = await taskService.setAuditors(
							this.task.id,
							[...auditorsIdsCurrent, this.currentUserId],
						);
						BX.UI.Notification.Center.notify({
							content: isSuccess
								? this.loc('TASKS_V2_TASK_FULL_CARD_BECOME_AUDITOR_NOTIF_SUCC')
								: this.loc('TASKS_V2_TASK_FULL_CARD_BECOME_AUDITOR_NOTIF_FAIL'),
						});
					}
					else
					{
						const isSuccess = await taskService.setAuditors(
							this.task.id,
							auditorsIdsCurrent.filter(auditorId => auditorId !== this.currentUserId),
						);
						BX.UI.Notification.Center.notify({
							content: isSuccess
								? this.loc('TASKS_V2_TASK_FULL_CARD_STOP_BEING_AUDITOR_NOTIF_SUCC')
								: this.loc('TASKS_V2_TASK_FULL_CARD_STOP_BEING_AUDITOR_NOTIF_FAIL'),
						});
					}
				},
			};
		},
		getToggleMuteItem(): MenuItemOptions
		{
			const isMuting = !this.task.inMute.includes(this.currentUserId);

			return {
				sectionCode: MENU_SECTION_PERSONAL,
				title: isMuting
					? this.loc('TASKS_V2_TASK_FULL_CARD_MUTE_ACTION')
					: this.loc('TASKS_V2_TASK_FULL_CARD_UNMUTE_ACTION'),
				icon: isMuting ? Outline.SOUND_OFF : Outline.SOUND_ON,
				onClick: async (): void => {
					if (isMuting)
					{
						const isSuccess = await taskService.muteTask(this.task, this.currentUserId);
						BX.UI.Notification.Center.notify({
							content: isSuccess
								? this.getNotificationLayoutWithTitle({
									title: this.loc('TASKS_V2_TASK_FULL_CARD_MUTE_NOTIF_SUCC_TITLE'),
									text: this.loc('TASKS_V2_TASK_FULL_CARD_MUTE_NOTIF_SUCC_DESCR'),
								})
								: this.loc('TASKS_V2_TASK_FULL_CARD_MUTE_NOTIF_FAIL'),
						});
					}
					else
					{
						const isSuccess = await taskService.unmuteTask(this.task, this.currentUserId);
						BX.UI.Notification.Center.notify({
							content: isSuccess
								? this.getNotificationLayoutWithTitle({
									title: this.loc('TASKS_V2_TASK_FULL_CARD_UNMUTE_NOTIF_SUCC_TITLE'),
									text: this.loc('TASKS_V2_TASK_FULL_CARD_UNMUTE_NOTIF_SUCC_DESCR'),
								})
								: this.loc('TASKS_V2_TASK_FULL_CARD_UNMUTE_NOTIF_FAIL'),
						});
					}
				},
			};
		},
		getCreateNewTaskItem(): MenuItemOptions
		{
			return {
				sectionCode: MENU_SECTION_TASKS,
				title: this.loc('TASKS_V2_TASK_FULL_CARD_CREATE_STANDALONE_TASK'),
				icon: Outline.TASK,
				onClick: async (): void => {
					TaskCard.showCompactCard({});
				},
			};
		},
		getCreateSubtaskItem(): MenuItemOptions
		{
			return {
				sectionCode: MENU_SECTION_TASKS,
				title: this.loc('TASKS_V2_TASK_FULL_CARD_CREATE_SUBTASK'),
				// icon: Outline.SUBTASK,
				icon: Outline.RELATED_TASKS,
				onClick: async (): void => {
					TaskCard.showCompactCard({ parentId: this.taskId });
				},
			};
		},
		getCreateDuplicateTaskItem(): MenuItemOptions
		{
			return {
				sectionCode: MENU_SECTION_TASKS,
				title: this.loc('TASKS_V2_TASK_FULL_CARD_CREATE_DUPLICATE_TASK'),
				icon: Outline.DUPLICATE,
				onClick: async (): void => {
					TaskCard.showCompactCard({});
				},
			};
		},
		getCreateNewTaskWithTemplateItem(): MenuItemOptions
		{
			return {
				sectionCode: MENU_SECTION_TASKS,
				title: this.loc('TASKS_V2_TASK_FULL_CARD_CREATE_STANDALONE_TASK_WITH_TEMPLATE'),
				icon: Outline.CHEVRON_RIGHT_L,
				onClick: async (): void => {
					TaskCard.showCompactCard({});
				},
			};
		},
		getCreateTemplateFromTaskItem(): MenuItemOptions
		{
			return {
				sectionCode: MENU_SECTION_TASKS,
				title: this.loc('TASKS_V2_TASK_FULL_CARD_CREATE_TEMPLATE_FROM_TASK'),
				icon: Outline.TEMPLATE_TASK,
				onClick: async (): void => {
					TaskCard.showCompactCard({});
				},
			};
		},
		getCopyTaskIdItem(): MenuItemOptions
		{
			return {
				sectionCode: MENU_SECTION_COPY,
				title: this.loc('TASKS_V2_TASK_FULL_CARD_COPY_TASK_ID_ACTION'),
				icon: Outline.COPY,
				onClick: async (): void => {
					const isCopyingSuccess = BX.clipboard.copy(this.taskId);
					if (isCopyingSuccess)
					{
						BX.UI.Notification.Center.notify({
							content: this.loc('TASKS_V2_TASK_FULL_CARD_COPY_TASK_ID_NOTIF'),
						});
					}
				},
			};
		},
		getGoToBitrixMarketItem(): MenuItemOptions
		{
			return {
				sectionCode: MENU_SECTION_LINKS,
				uiButtonOptions: {
					icon: Outline.MARKET,
					text: this.loc('TASKS_V2_TASK_FULL_CARD_GO_TO_BITRIX_MARKET'),
					size: ButtonSize.SMALL,
					useAirDesign: true,
					style: AirButtonStyle.OUTLINE,
					wide: true,
					disabled: false,
					onclick: async (): void => {
						BX.rest.Marketplace.open({ PLACEMENT: 'TASK_LIST_CONTEXT_MENU' });
					},
				},
			};
		},
		getGoToRobotsItem(): MenuItemOptions
		{
			return {
				sectionCode: MENU_SECTION_LINKS,
				uiButtonOptions: {
					icon: Outline.ROBOT,
					text: this.loc('TASKS_V2_TASK_FULL_CARD_GO_TO_ROBOTS'),
					size: ButtonSize.SMALL,
					useAirDesign: true,
					style: AirButtonStyle.OUTLINE,
					wide: true,
					disabled: false,
					onclick: (): void => {
						BX.SidePanel.Instance.open(
							`/bitrix/components/bitrix/tasks.automation/slider.php?site_id=${
								Loc.getMessage('SITE_ID')
							}&project_id=${this.task.groupId}&task_id=${this.taskId}`,
							{ cacheable: false, customLeftBoundary: 0, loader: 'bizproc:automation-loader' },
						);
					},
				},
			};
		},
		// button getters end
	},
	template: `
		<div
			v-if="menuItems"
			class="tasks-full-card-header-burger-container"
			@click="handleClickOpener"
		>
			<BIcon
				class="tasks-full-card-header-burger"
				ref="button"
				:name="Outline.MORE_L"
			/>
		</div>
		<BMenu v-if="isMenuShown" :options="menuOptions()" @close="isMenuShown = false"/>
	`,
};
