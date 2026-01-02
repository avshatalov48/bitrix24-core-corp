import { hint, type HintParams } from 'ui.vue3.directives.hint';
import { BIcon } from 'ui.icon-set.api.vue';

import { Core } from 'tasks.v2.core';
import { Model } from 'tasks.v2.const';

import 'tasks.v2.component.elements.hint';

import type { CheckListModel } from 'tasks.v2.model.check-list';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { PanelAction, PanelMeta } from './check-list-item-panel-meta';
import { CheckListManager } from '../../lib/check-list-manager';

import type { VisibleSections, VisibleActions, Section, Item, ActiveActions } from './check-list-item-panel-meta';

import './check-list-item-panel.css';

// @vue/component
export const CheckListItemPanel = {
	name: 'CheckListItemPanel',
	components: {
		BIcon,
	},
	directives: { hint },
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		currentItem: {
			type: Object,
			default: () => null,
		},
	},
	emits: ['action'],
	data(): Object
	{
		return {
			currentHintElement: null,
			currentHintText: '',
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		checkLists(): CheckListModel[]
		{
			return this.$store.getters[`${Model.CheckList}/getByIds`](this.task.checklist);
		},
		sections(): VisibleSections
		{
			return PanelMeta.defaultSections
				.filter((section) => this.visibleSections.includes(section.name))
				.map((section) => ({
					...section,
					items: section.items
						.filter((item: Item) => this.visibleActions.includes(item.action))
						.map((item: Item) => ({
							...item,
							disabled: this.isItemDisabled(item),
							active: this.isItemActive(item),
							hoverable: item.hoverable ?? true,
						})),
				}))
				.filter((section) => section.items.length > 0)
			;
		},
		tooltip(): Function
		{
			return (): HintParams => ({
				text: this.currentHintText,
				timeout: 500,
				popupOptions: {
					className: 'tasks-hint',
					background: 'var(--ui-color-bg-content-inapp)',
					darkMode: false,
					offsetLeft: -(this.currentHintElement?.offsetWidth ?? 0),
					padding: 6,
					bindOptions: {
						forceBindPosition: true,
						forceTop: true,
						position: 'top',
					},
					targetContainer: document.body,
				},
			});
		},
		visibleSections(): VisibleSections
		{
			return PanelMeta.defaultSections.map((section: Section) => section.name);
		},
		visibleActions(): VisibleActions
		{
			if (!this.currentItem)
			{
				return [];
			}

			let actions = [
				PanelAction.SetImportant,
				PanelAction.MoveRight,
				PanelAction.MoveLeft,
				PanelAction.AssignAccomplice,
				PanelAction.AssignAuditor,
				PanelAction.Forward,
				PanelAction.Delete,
			];

			if (this.itemGroupModeSelected)
			{
				actions.push(PanelAction.Cancel);
			}
			else
			{
				actions.push(PanelAction.AttachFile);
			}

			if (this.currentItem.parentId === 0)
			{
				actions = [
					PanelAction.AssignAccomplice,
					PanelAction.AssignAuditor,
				];
			}

			const limits = Core.getParams().limits;
			const stakeholdersActions = new Set([
				PanelAction.AssignAccomplice,
				PanelAction.AssignAuditor,
			]);

			return actions.filter((action: string) => {
				const isDisabledStakeholders = stakeholdersActions.has(action) && !limits.stakeholders;

				return !isDisabledStakeholders;
			});
		},
		disabledActions(): []
		{
			if (!this.currentItem)
			{
				return [];
			}

			const disabledActions = [];

			const itemLevel = this.checkListManager.getItemLevel(this.currentItem);
			const canModify = this.currentItem.actions.modify === true;
			const canRemove = this.currentItem.actions.remove === true;

			const conditionHandlers = {
				[PanelAction.SetImportant]: () => {
					return canModify === false;
				},
				[PanelAction.AttachFile]: () => {
					return canModify === false;
				},
				[PanelAction.MoveLeft]: () => {
					return itemLevel === 1 || canModify === false;
				},
				[PanelAction.MoveRight]: () => {
					return (
						itemLevel === 5
						|| this.currentItem.sortIndex === 0
						|| canModify === false
					);
				},
				[PanelAction.AssignAccomplice]: () => {
					return canModify === false;
				},
				[PanelAction.AssignAuditor]: () => {
					return canModify === false;
				},
				[PanelAction.Forward]: () => {
					return (
						canModify === false
						|| this.currentItem.title === ''
					);
				},
				[PanelAction.Delete]: () => {
					return canRemove === false;
				},
			};

			Object.entries(conditionHandlers)
				.forEach(([action: string, condition: function]) => {
					if (condition())
					{
						disabledActions.push(action);
					}
				})
			;

			return disabledActions;
		},
		activeActions(): ActiveActions
		{
			if (!this.currentItem)
			{
				return [];
			}

			const actions = [];

			if (this.currentItem.isImportant)
			{
				actions.push(PanelAction.SetImportant);
			}

			return actions;
		},
		itemGroupModeSelected(): boolean
		{
			if (!this.currentItem)
			{
				return false;
			}

			return this.currentItem.groupMode?.selected === true;
		},
	},
	created(): void
	{
		this.checkListManager = new CheckListManager({
			computed: {
				checkLists: () => this.checkLists,
			},
		});
	},
	methods: {
		isItemDisabled(item: Item): boolean
		{
			return item.disabled ?? this.disabledActions.includes(item.action);
		},
		isItemActive(item: Item): boolean
		{
			return item.active ?? this.activeActions.includes(item.action);
		},
		getItemIcon(item: Item): string
		{
			return (item.active && item.activeIcon) ? item.activeIcon : item.icon;
		},
		handleItemClick(event: MouseEvent, item: Item): void
		{
			if (!item.disabled)
			{
				this.$emit('action', {
					action: item.action,
					node: event.currentTarget,
				});
			}
		},
		handleItemMouseEnter(event: MouseEvent, item: Item): void
		{
			this.currentHintElement = event.currentTarget;
			this.currentHintText = item.hint ? this.loc(item.hint) : null;
		},
	},
	template: `
		<div v-if="sections.length > 0" class="check-list-widget-item-panel" @mousedown.prevent>
			<template v-for="section in sections" :key="section.name">
				<div class="check-list-widget-item-panel-section" :class="'--' + section.name">
					<template v-for="item in section.items" :key="item.action" >
						<div
							v-hint="tooltip"
							class="check-list-widget-item-panel-section-item"
							:class="{
								'--disabled': item.disabled,
								'--active': item.active,
								[item.className]: Boolean(item.className),
							}"
							@click="handleItemClick($event, item)"
							@mouseenter="handleItemMouseEnter($event, item)"
						>
							<BIcon :name="getItemIcon(item)" :hoverable="item.hoverable"/>
							<span v-if="item.label">{{ loc(item.label) }}</span>
						</div>
					</template>
				</div>
			</template>
		</div>
	`,
};
