import { Event, Text } from 'main.core';

import { hint, type HintParams } from 'ui.vue3.directives.hint';
import { BIcon } from 'ui.icon-set.api.vue';
import { BMenu, type MenuOptions, type MenuItemOptions } from 'ui.vue3.components.menu';
import { Actions, Outline } from 'ui.icon-set.api.core';

import { Model } from 'tasks.v2.const';
import { GrowingTextArea } from 'tasks.v2.component.elements.growing-text-area';
import { UserAvatarList } from 'tasks.v2.component.elements.user-avatar-list';
import { UserCheckbox } from 'tasks.v2.component.elements.user-checkbox';
import { ProgressBar } from 'tasks.v2.component.elements.progress-bar';
import { tooltip } from 'tasks.v2.component.elements.hint';
import { checkListService } from 'tasks.v2.provider.service.check-list-service';

import type { CheckListModel } from 'tasks.v2.model.check-list';
import type { UserModel } from 'tasks.v2.model.users';

import { CheckListItemMixin } from './check-list-item-mixin';
import { CheckListManager } from '../../../lib/check-list-manager';

// @vue/component
export const CheckListParentItem = {
	name: 'CheckListParentItem',
	components: {
		BIcon,
		BMenu,
		GrowingTextArea,
		UserAvatarList,
		UserCheckbox,
		ProgressBar,
	},
	directives: { hint },
	mixins: [
		CheckListItemMixin,
	],
	inject: ['setItemsRef'],
	props: {
		positionIndex: {
			type: Number,
			required: true,
		},
	},
	emits: [
		'startGroupMode',
		'openCheckList',
	],
	setup(): Object
	{
		return {
			Actions,
			Outline,
		};
	},
	data(): Object
	{
		return {
			isSticky: false,
			isMenuShown: false,
			menuRemoveSectionCode: 'removeSection',
		};
	},
	computed: {
		menuOptions(): MenuOptions
		{
			return {
				id: `check-list-parent-item-action-menu-${Text.getRandom()}`,
				bindElement: this.$refs.more.$el,
				minWidth: 250,
				offsetLeft: -100,
				sections: [
					{
						code: this.menuRemoveSectionCode,
					},
				],
				items: this.menuItems,
				targetContainer: document.body,
				closeByEsc: true,
			};
		},
		menuItems(): MenuItemOptions[]
		{
			const collapseItem = {
				title: (this.item.areCompletedCollapsed
					? this.loc('TASKS_V2_CHECK_LIST_ITEM_MENU_SHOW')
					: this.loc('TASKS_V2_CHECK_LIST_ITEM_MENU_HIDE')
				),
				icon: this.item.areCompletedCollapsed ? Outline.OBSERVER : Outline.CROSSED_EYE,
				dataset: {
					id: `MenuProfileHide-${this.id}`,
				},
				onClick: () => {
					const newValue = !this.item.areCompletedCollapsed;

					void this.updateCheckList(this.id, { areCompletedCollapsed: newValue });

					this.toggleCompleted(this.id, newValue);
				},
			};

			const groupActionsItem = {
				title: this.loc('TASKS_V2_CHECK_LIST_ITEM_MENU_GROUP'),
				icon: Outline.MULTICHOICE_ON,
				dataset: {
					id: `MenuProfileGroup-${this.id}`,
				},
				onClick: () => {
					if (this.isCollapsed(this.item))
					{
						this.toggleCollapse();
					}
					this.$emit('startGroupMode', this.id);
				},
			};

			const editItem = {
				sectionCode: this.menuRemoveSectionCode,
				title: this.loc('TASKS_V2_CHECK_LIST_ITEM_MENU_EDIT'),
				icon: Outline.EDIT_L,
				dataset: {
					id: `MenuProfileEdit-${this.id}`,
				},
				onClick: () => {
					if (this.canEdit)
					{
						this.$emit('openCheckList', this.id);
					}
				},
			};

			const removeItem = {
				sectionCode: this.menuRemoveSectionCode,
				design: 'alert',
				title: this.loc('TASKS_V2_CHECK_LIST_ITEM_MENU_REMOVE'),
				icon: Outline.TRASHCAN,
				dataset: {
					id: `MenuProfileRemove-${this.id}`,
				},
				onClick: this.removeItem.bind(this),
			};

			if (this.isPreview)
			{
				return [
					collapseItem,
					this.canModify ? editItem : null,
					this.canModify ? removeItem : null,
				];
			}

			return [
				collapseItem,
				groupActionsItem,
				removeItem,
			];
		},
		itemIcon(): string
		{
			return this.completed ? Outline.CHECK_L : Outline.CHECK_LIST;
		},
		checkListStatus(): string
		{
			const label = this.loc('TASKS_V2_CHECK_LIST_STATUS_LABEL_NEW');

			return label
				.replace('#completed#', this.completedCount)
				.replace('#total#', this.totalCount);
		},
		completedCount(): number
		{
			return this.checkLists.filter((checklist: CheckListModel) => {
				return (
					checklist.parentId === this.id
					&& (checklist.isComplete || checklist.localCompleteState)
				);
			}).length;
		},
		totalCount(): number
		{
			return this.checkLists.filter((checklist: CheckListModel) => {
				return checklist.parentId === this.id;
			}).length;
		},
		currentUserResponsible(): boolean
		{
			return this.currentUserId === this.task.responsibleId;
		},
		currentUser(): UserModel
		{
			return this.$store.getters[`${Model.Users}/getById`](this.currentUserId);
		},
		numberMyItems(): number
		{
			return this.checkListManager.findItemIdsWithUser(this.id, this.currentUserId).size;
		},
		myFilterTooltip(): Function
		{
			return (): HintParams => tooltip({
				text: (
					this.myFilterActive
						? this.loc('TASKS_V2_CHECK_LIST_MY_FILTER_HINT_ALL')
						: this.loc('TASKS_V2_CHECK_LIST_MY_FILTER_HINT_MY')
				),
				popupOptions: {
					offsetLeft: this.$refs.myFilter.offsetWidth / 2,
				},
			});
		},
		myFilterActive(): boolean
		{
			return this.item.myFilterActive;
		},
	},
	watch: {
		myFilterActive(value: boolean): void
		{
			this.handleMyFilter(value);
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
	mounted(): void
	{
		this.scrollContainer = this.$parent.$el?.closest('[data-list]');

		if (this.setItemsRef)
		{
			this.setItemsRef(this.id, this);
		}

		if (this.scrollContainer)
		{
			Event.bind(this.scrollContainer, 'scroll', this.handleScroll);

			void this.$nextTick(this.checkSticky);

			this.mutationObserver = new MutationObserver(() => {
				this.checkSticky();
			});
			this.mutationObserver.observe(
				this.scrollContainer,
				{
					childList: true,
					subtree: true,
				},
			);
		}
	},
	beforeUnmount(): void
	{
		if (this.scrollContainer)
		{
			Event.unbind(this.scrollContainer, 'scroll', this.handleScroll);
		}

		if (this.mutationObserver)
		{
			this.mutationObserver.disconnect();
		}

		if (this.setItemsRef)
		{
			this.setItemsRef(this.id, null);
		}
	},
	methods: {
		handleScroll(): void
		{
			this.checkSticky();
		},
		handleTextClick(): void
		{
			if (this.isPreview && this.canModify)
			{
				this.$emit('openCheckList', this.id);
			}
		},
		handleMyFilter(checked: boolean): void
		{
			const myItemIds = this.checkListManager.findItemIdsWithUser(
				this.id,
				this.currentUserId,
			);

			this.$store.dispatch(`${Model.CheckList}/update`, {
				id: this.id,
				fields: { myFilterActive: checked },
			});

			if (checked === true)
			{
				const idsToHide = this.checkListManager.getAllChildren(this.id)
					.filter((item: CheckListModel) => !myItemIds.has(item.id))
					.map((item: CheckListModel) => item.id);

				this.checkListManager.hideItems(
					idsToHide,
					(updates: CheckListModel[]) => this.upsertCheckLists(updates),
				);
			}
			else
			{
				const childrenIds = this.checkListManager
					.getAllChildren(this.id)
					.map((item: CheckListModel) => item.id);

				this.checkListManager.showItems(
					childrenIds,
					(updates: CheckListModel[]) => this.upsertCheckLists(updates),
				);
			}
		},
		checkSticky(): void
		{
			if (!this.scrollContainer || !this.$refs.item)
			{
				return;
			}

			const stickyRect = this.$refs.item.getBoundingClientRect();
			const containerRect = this.scrollContainer.getBoundingClientRect();

			this.isSticky = stickyRect.top <= (containerRect.top + stickyRect.height / 2);
		},
		showMenu(): void
		{
			this.isMenuShown = true;
		},
		toggleCollapse(): void
		{
			const localCollapsedState = !this.isCollapsed(this.item);

			if (this.isPreview && this.isEdit)
			{
				if (localCollapsedState === true)
				{
					void checkListService.collapse(this.taskId, this.id);
				}
				else
				{
					void checkListService.expand(this.taskId, this.id);
				}
			}

			this.$store.dispatch(`${Model.CheckList}/update`, {
				id: this.id,
				fields: { localCollapsedState },
			});
		},
		isCollapsed(item: CheckListModel): boolean
		{
			return this.checkListManager.isItemCollapsed(item, this.isPreview, this.positionIndex);
		},
		toggleCompleted(itemId: number | string, collapsed: boolean): void
		{
			this.checkListManager.getAllCompletedChildren(itemId)
				.forEach((item: CheckListModel) => {
					if (collapsed === false)
					{
						this.checkListManager.showItems(
							[item.id],
							(updates: CheckListModel[]) => this.upsertCheckLists(updates),
						);
					}
					else
					{
						this.checkListManager.hideItems(
							[item.id],
							(updates: CheckListModel[]) => this.upsertCheckLists(updates),
						);
					}
				});
		},
	},
	template: `
		<div
			ref="item"
			class="check-list-widget-parent-item"
			:class="{
				'--complete': completed,
				'--collapsed': isCollapsed(item),
				'--preview': isPreview,
				'--editable': canModify,
			}"
			:data-id="id"
			:data-parent="id"
		>
			<div class="check-list-widget-parent-item-label-container">
				<BIcon :name="itemIcon"/>
			</div>
			<div class="check-list-widget-parent-item-title-container">
				<GrowingTextArea
					ref="growingTextArea"
					class="check-list-widget-parent-item-title"
					:data-check-list-id="'check-list-parent-item-title-' + id"
					:modelValue="item.title"
					:placeholder="loc('TASKS_V2_CHECK_LIST_ITEM_PLACEHOLDER')"
					:readonly="groupMode || isPreview"
					:fontColor="textColor"
					:fontSize="17"
					:lineHeight="20"
					:fontWeight="500"
					@click="handleTextClick"
					@update:modelValue="updateTitle"
					@input="updateTitle"
					@focus="handleFocus"
					@emptyFocus="focusToItem"
					@blur="handleBlur"
					@emptyBlur="handleEmptyBlur"
				/>
				<template v-if="hasAttachments">
					<div class="check-list-widget-item-attach --parent">
						<div v-if="hasUsers" class="check-list-widget-item-attach-users">
							<div v-if="hasAccomplices" class="check-list-widget-item-attach-users-list">
								<BIcon :name="Outline.GROUP"/>
								<UserAvatarList :users="accomplices"/>
							</div>
							<div v-if="hasAuditors" class="check-list-widget-item-attach-users-list">
								<BIcon :name="Outline.OBSERVER"/>
								<UserAvatarList :users="auditors"/>
							</div>
						</div>
					</div>
				</template>
				<div class="check-list-widget-parent-item-title-status">
					<div class="check-list-widget-parent-item-title-status-label">
						{{ checkListStatus }}
					</div>
					<ProgressBar
						:totalValue="totalCount"
						:completedValue="completedCount"
						:width="56"
						:height="5"
						color="var(--ui-color-accent-main-primary-alt)"
						bgColor="var(--ui-color-base-7)"
						:borderRadius="30"
					/>
				</div>
			</div>
			<div class="check-list-widget-parent-item-action">
				<div class="check-list-widget-parent-item-main-action">
					<div
						ref="myFilter"
						class="check-list-widget-parent-item-main-action-filter"
						v-hint="myFilterTooltip"
					>
						<UserCheckbox
							v-if="!currentUserResponsible && numberMyItems > 0"
							:init-user="currentUser"
							:number="numberMyItems"
							v-model:checked="item.myFilterActive"
						/>
					</div>
					<div class="check-list-widget-parent-item-main-action-actions">
						<BIcon ref="more" :name="Outline.MORE_L" @click="showMenu" />
						<BIcon
							:name="isCollapsed(item) ? Actions.CHEVRON_DOWN : Actions.CHEVRON_UP"
							@click="toggleCollapse()"
						/>
					</div>
				</div>
				<div v-if="isSticky && !isPreview" class="check-list-widget-parent-item-empty"></div>
			</div>
			<BMenu v-if="isMenuShown" :options="menuOptions" @close="isMenuShown = false"/>
		</div>
	`,
};
