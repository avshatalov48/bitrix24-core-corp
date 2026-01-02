import { Type } from 'main.core';
import { Model } from 'tasks.v2.const';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { CheckListModel } from 'tasks.v2.model.check-list';
import { mapGetters } from 'ui.vue3.vuex';

import { CheckListManager } from '../../lib/check-list-manager';
import { Highlighter } from '../../lib/highlighter/highlighter';

import { CheckListParentItem } from './component/check-list-parent-item';
import { CheckListChildItem } from './component/check-list-child-item';
import { CheckListAddItem } from './component/check-list-add-item';
import { CheckListGroupCompletedList } from './component/check-list-group-completed-list';

import './check-list-widget.css';

// @vue/component
export const CheckListWidget = {
	name: 'CheckListWidget',
	components: {
		CheckListParentItem,
		CheckListChildItem,
		CheckListAddItem,
		CheckListGroupCompletedList,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		checkListId: {
			type: [Number, String],
			default: 0,
		},
		parentId: {
			type: [Number, String],
			default: 0,
		},
		isPreview: {
			type: Boolean,
			default: false,
		},
	},
	emits: [
		'show',
		'update',
		'addItem',
		'addItemFromBtn',
		'removeItem',
		'focus',
		'blur',
		'emptyBlur',
		'startGroupMode',
		'toggleGroupModeSelected',
		'openCheckList',
	],
	data(): Object
	{
		return {};
	},
	computed: {
		...mapGetters({
			currentUserId: `${Model.Interface}/currentUserId`,
			disableCheckListAnimations: `${Model.Interface}/disableCheckListAnimations`,
		}),
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		isEdit(): boolean
		{
			return Type.isNumber(this.taskId) && this.taskId > 0;
		},
		checkLists(): CheckListModel[]
		{
			return this.$store.getters[`${Model.CheckList}/getByIds`](this.task.checklist);
		},
		parentCheckLists(): CheckListModel[] {
			return this.checkLists
				.filter((checkList: CheckListModel) => {
					if (checkList.parentId !== 0 || checkList.hidden)
					{
						return false;
					}

					if (
						this.isEdit
						&& !this.isPreview
						&& !this.canEditCheckList(checkList)
					)
					{
						return false;
					}

					return !(this.isPreview && checkList.isComplete);
				})
				.sort((a: CheckListModel, b: CheckListModel) => {
					if (a.isComplete === b.isComplete)
					{
						return a.sortIndex - b.sortIndex;
					}

					return a.isComplete ? 1 : -1;
				});
		},
		totalCompletedParents(): number
		{
			return this.checkLists.filter((checkList: CheckListModel) => {
				return checkList.parentId === 0 && checkList.isComplete;
			}).length;
		},
		siblings(): CheckListModel[]
		{
			return this.checkLists
				.filter((item: CheckListModel) => item.parentId === this.parentId)
				.sort((a: CheckListModel, b: CheckListModel) => a.sortIndex - b.sortIndex);
		},
		canAddItem(): boolean
		{
			if (!this.isEdit)
			{
				return true;
			}

			return this.task.rights.checklistAdd === true;
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
		if (this.checkListManager.getItem(this.checkListId))
		{
			this.scrollContainer = this.$root.$el?.querySelector(['[data-list]']);

			const childWithEmptyTitle = this.checkListManager.getChildWithEmptyTitle(this.checkListId);

			const isFocusToCheckList = !childWithEmptyTitle;

			const targetItemId = isFocusToCheckList ? this.checkListId : childWithEmptyTitle.id;
			const scrollOffset = isFocusToCheckList ? 0 : 140;

			const targetItem = this.scrollContainer.querySelector([`[data-id="${targetItemId}"]`]);

			setTimeout(() => {
				if (isFocusToCheckList)
				{
					const highlightElement = this.scrollContainer.querySelector(
						[`[data-parent-container="${this.checkListId}"]`],
					);

					if (highlightElement)
					{
						void (new Highlighter()).highlight(highlightElement);
					}
				}

				if (targetItem)
				{
					this.scrollContainer.scrollTop = targetItem.offsetTop - scrollOffset;
				}
			}, 0);
		}

		this.$emit('show');
	},
	methods: {
		getItemOffset(item: CheckListModel): string
		{
			if (item.parentId === 0)
			{
				return '0';
			}

			const level = this.checkListManager.getItemLevel(item);
			if (level === 1)
			{
				return '0';
			}

			return `${(level - 1) * 12}px`;
		},
		getChildren(parent: CheckListModel): CheckListModel[]
		{
			return this.checkListManager
				.getAllChildren(parent.id)
				.filter((checkList: CheckListModel) => !checkList.hidden);
		},
		isCollapsed(item: CheckListModel, positionIndex: number): boolean
		{
			return this.checkListManager.isItemCollapsed(item, this.isPreview, positionIndex);
		},
		getFirstCompletedCheckList(): ?CheckListModel
		{
			const completedCheckLists = this.checkLists
				.filter((checkList: CheckListModel) => {
					return checkList.parentId === 0 && checkList.isComplete === true;
				})
				.sort((a: CheckListModel, b: CheckListModel) => a.sortIndex - b.sortIndex);

			return completedCheckLists[0];
		},
		showFirstCompletedCheckList(): void
		{
			const firstCompletedCheckList = this.getFirstCompletedCheckList();

			this.$emit('openCheckList', firstCompletedCheckList.id);
		},
		canEditCheckList(item: CheckListModel): boolean
		{
			if (!item.creator)
			{
				return true;
			}

			return (
				this.task.rights.checklistEdit === true
				|| item.creator.id === this.currentUserId
			);
		},
	},
	template: `
		<div class="check-list-widget-container">
			<TransitionGroup
				:css="!disableCheckListAnimations"
				name="check-list"
				mode="out-in"
				tag="ul"
				class="check-list-widget --parent"
				:class="{'--preview': isPreview}"
			>
				<li
					v-for="(parentItem, parentItemIndex) in parentCheckLists"
					:key="parentItem.id"
					class="check-list-widget-item --parent"
					:class="{
						'--preview': isPreview,
						'--collapsed': isCollapsed(parentItem, parentItemIndex),
						'--hidden': parentItem.hidden,
					}"
					:data-parent-container="parentItem.id"
				>
					<CheckListParentItem
						:id="parentItem.id"
						:taskId="taskId"
						:isPreview="isPreview"
						:positionIndex="parentItemIndex"
						@update="(id) => $emit('update', id)"
						@removeItem="(id) => $emit('removeItem', id)"
						@focus="(id) => $emit('focus', id)"
						@blur="(id) => $emit('blur', id)"
						@emptyBlur="(id) => $emit('emptyBlur', id)"
						@startGroupMode="(id) => $emit('startGroupMode', id)"
						@openCheckList="(id) => $emit('openCheckList', id)"
					/>
					<TransitionGroup
						:css="!disableCheckListAnimations"
						name="check-list"
						mode="out-in"
						tag="ul"
						class="check-list-widget"
					>
						<li
							v-if="!isCollapsed(parentItem, parentItemIndex)"
							v-for="childItem in getChildren(parentItem)"
							:key="childItem.id"
							class="check-list-widget-item"
						>
							<CheckListChildItem
								:id="childItem.id"
								:taskId="taskId"
								:itemOffset="getItemOffset(childItem)"
								:isPreview="isPreview"
								@update="(id) => $emit('update', id)"
								@addItem="(data) => $emit('addItem', data)"
								@removeItem="(id) => $emit('removeItem', id)"
								@focus="(id) => $emit('focus', id)"
								@blur="(id) => $emit('blur', id)"
								@emptyBlur="(id) => $emit('emptyBlur', id)"
								@toggleGroupModeSelected="(id) => $emit('toggleGroupModeSelected', id)"
							/>
						</li>
						<li
							v-if="!isCollapsed(parentItem, parentItemIndex) && canAddItem"
							key="add-item"
							class="check-list-widget-item"
						>
							<CheckListAddItem
								:isPreview="isPreview"
								@addItem="$emit('addItemFromBtn', parentItem.id)"
							/>
						</li>
					</TransitionGroup>
				</li>
				<li
					v-if="isPreview && totalCompletedParents > 0"
					key="completed-list"
					class="check-list-widget-item --completed-list"
				>
					<CheckListGroupCompletedList
						:totalCompletedParents="totalCompletedParents"
						@click="showFirstCompletedCheckList"
					/>
				</li>
			</TransitionGroup>
		</div>
	`,
};
