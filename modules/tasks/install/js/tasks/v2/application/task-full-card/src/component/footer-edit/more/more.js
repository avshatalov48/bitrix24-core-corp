import { Event, Runtime } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';
import { BMenu, MenuItemDesign, type MenuOptions, type MenuItemOptions } from 'ui.vue3.components.menu';
import { BIcon } from 'ui.icon-set.api.vue';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { EventName, Model, TaskStatus } from 'tasks.v2.const';
import { responsibleMeta } from 'tasks.v2.component.fields.responsible';
import { EntitySelectorDialog } from 'tasks.v2.lib.entity-selector-dialog';
import { taskService } from 'tasks.v2.provider.service.task-service';
import { statusService } from 'tasks.v2.provider.service.status-service';
import type { TaskModel } from 'tasks.v2.model.tasks';

import './more.css';

// @vue/component
export const More = {
	name: 'TaskFullCardMoreActionsStatus',
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
		preselected(): [string, number][]
		{
			return [['user', this.task.responsibleId || this.currentUserId]];
		},
		isCreator(): boolean
		{
			return this.currentUserId === this.task.creatorId;
		},
		isResponsible(): boolean
		{
			return this.currentUserId === this.task.responsibleId;
		},
		menuOptions(): Function
		{
			return (): MenuOptions => ({
				id: 'tasks-full-card-footer-more-menu',
				bindElement: this.$refs.button.$el,
				items: this.menuItems,
			});
		},
		menuItems(): MenuItemOptions[]
		{
			const statuses = {
				[TaskStatus.Pending]: {
					[this.isResponsible]: [
						this.getCompleteItem(),
						this.getDefferItem(),
						this.getDelegateItem(),
					],
					[this.isCreator]: [
						this.getDefferItem(),
						this.getDelegateItem(),
						this.getDeleteItem(),
					],
					[this.isResponsible && this.isCreator]: [
						this.getCompleteItem(),
						this.getDefferItem(),
						this.getDelegateItem(),
						this.getDeleteItem(),
					],
				},
				[TaskStatus.InProgress]: {
					[this.isResponsible]: [
						this.getPauseItem(),
						this.getDefferItem(),
						this.getDelegateItem(),
					],
					[this.isCreator]: [
						this.getDefferItem(),
						this.getDelegateItem(),
						this.getDeleteItem(),
					],
					[this.isResponsible && this.isCreator]: [
						this.getPauseItem(),
						this.getDefferItem(),
						this.getDelegateItem(),
						this.getDeleteItem(),
					],
				},
				[TaskStatus.Deferred]: {
					[this.isResponsible]: [
						this.getCompleteItem(),
						this.getDelegateItem(),
					],
					[this.isCreator]: [
						this.getCompleteItem(),
						this.getDelegateItem(),
						this.getDeleteItem(),
					],
				},
				[TaskStatus.SupposedlyCompleted]: {
					[this.isResponsible]: [
						this.getFixItem(),
						this.getDelegateItem(),
					],
					[this.isCreator]: [
						this.getDisapproveItem(),
						this.getDelegateItem(),
						this.getDeleteItem(),
					],
					[this.isResponsible && this.isCreator]: [
						this.getCompleteItem(),
						this.getDelegateItem(),
						this.getDeleteItem(),
					],
				},
				[TaskStatus.Completed]: {
					[this.isCreator]: [
						this.getRenewItem(),
						this.getDeleteItem(),
					],
				},
			};

			return statuses[this.task.status].true;
		},
	},
	methods: {
		handleClick(): void
		{
			this.isMenuShown = true;
		},
		getDefferItem(): MenuItemOptions
		{
			if (!this.task.rights.defer)
			{
				return null;
			}

			return {
				title: this.loc('TASKS_V2_TASK_FULL_CARD_DEFER'),
				icon: Outline.PAUSE_L,
				onClick: (): void => statusService.defer(this.taskId),
			};
		},
		getPauseItem(): MenuItemOptions
		{
			if (!this.task.rights.pause)
			{
				return null;
			}

			return {
				title: this.loc('TASKS_V2_TASK_FULL_CARD_PAUSE'),
				icon: Outline.STOP_L,
				onClick: (): void => statusService.pause(this.taskId),
			};
		},
		getRenewItem(): MenuItemOptions
		{
			if (!this.task.rights.renew)
			{
				return null;
			}

			return {
				title: this.loc('TASKS_V2_TASK_FULL_CARD_RENEW'),
				icon: Outline.UNDO,
				onClick: (): void => statusService.renew(this.taskId),
			};
		},
		getFixItem(): MenuItemOptions
		{
			if (!this.task.rights.renew)
			{
				return null;
			}

			return {
				title: this.loc('TASKS_V2_TASK_FULL_CARD_FIX'),
				icon: Outline.UNDO,
				onClick: (): void => statusService.renew(this.taskId),
			};
		},
		getDisapproveItem(): MenuItemOptions
		{
			if (!this.task.rights.disapprove)
			{
				return null;
			}

			return {
				title: this.loc('TASKS_V2_TASK_FULL_CARD_DISAPPROVE'),
				icon: Outline.UNDO,
				onClick: (): void => statusService.disapprove(this.taskId),
			};
		},
		getCompleteItem(): MenuItemOptions
		{
			if (!this.task.rights.complete)
			{
				return null;
			}

			return {
				title: this.loc('TASKS_V2_TASK_FULL_CARD_COMPLETE'),
				icon: Outline.SENDED,
				onClick: (): void => statusService.complete(this.taskId),
			};
		},
		getDelegateItem(): MenuItemOptions
		{
			if (!this.task.rights.delegate)
			{
				return null;
			}

			return {
				title: this.loc('TASKS_V2_TASK_FULL_CARD_DELEGATE'),
				icon: Outline.DELEGATE,
				onClick: (): void => {
					const onItemChangeDebounced = Runtime.debounce(this.handleDelegateSelect, 10, this);

					this.dialog ??= new EntitySelectorDialog({
						...responsibleMeta.dialogOptions(this.$options.name),
						preselectedItems: this.preselected,
						events: {
							'Item:onSelect': onItemChangeDebounced,
							'Item:onDeselect': onItemChangeDebounced,
						},
					});
					this.dialog.selectItemsByIds(this.preselected);
					this.dialog.showTo(this.$refs.button.$el);
				},
			};
		},
		handleDelegateSelect(): void
		{
			const responsibleId = this.dialog.getSelectedItems()[0]?.getId() ?? 0;

			void taskService.update(
				this.taskId,
				{ responsibleId },
			);
		},
		getDeleteItem(): MenuItemOptions
		{
			if (!this.task.rights.remove)
			{
				return null;
			}

			return {
				design: MenuItemDesign.Alert,
				title: this.loc('TASKS_V2_TASK_FULL_CARD_DELETE'),
				icon: Outline.TRASHCAN,
				onClick: (): void => {
					void taskService.delete(this.taskId);
					Event.EventEmitter.emit(EventName.CloseFullCard, { taskId: this.taskId });
				},
			};
		},
	},
	template: `
		<BIcon
			v-if="menuItems"
			class="tasks-full-card-footer-more"
			:name="Outline.MORE_L"
			data-task-more-button
			ref="button"
			@click="handleClick"
		/>
		<BMenu v-if="isMenuShown" :options="menuOptions()" @close="isMenuShown = false"/>
	`,
};
