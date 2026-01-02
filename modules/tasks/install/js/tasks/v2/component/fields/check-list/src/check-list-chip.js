import { Text } from 'main.core';

import { Animated, Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { fieldHighlighter } from 'tasks.v2.lib.field-highlighter';
import { Chip, ChipDesign } from 'tasks.v2.component.elements.chip';
import { fileService, EntityTypes } from 'tasks.v2.provider.service.file-service';
import { EventName, Model } from 'tasks.v2.const';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { CheckListModel } from 'tasks.v2.model.check-list';

import { checkListMeta } from './lib/check-list-meta';

// @vue/component
export const CheckListChip = {
	components: {
		Chip,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		isAutonomous: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['showCheckList'],
	setup(): Object
	{
		return {
			Outline,
			checkListMeta,
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
		isUploading(): boolean
		{
			return this.task.checklist?.some((itemId) => {
				return fileService.get(
					itemId,
					EntityTypes.CheckListItem,
					{ parentEntityId: this.taskId },
				).isUploading();
			});
		},
		design(): string
		{
			return {
				[!this.isAutonomous && !this.isSelected]: ChipDesign.ShadowNoAccent,
				[!this.isAutonomous && this.isSelected]: ChipDesign.ShadowAccent,
				[this.isAutonomous && !this.isSelected]: ChipDesign.OutlineNoAccent,
				[this.isAutonomous && this.isSelected]: ChipDesign.OutlineAccent,
			}.true;
		},
		isSelected(): boolean
		{
			if (this.isAutonomous)
			{
				return this.checkLists.length > 0;
			}

			return this.wasFilled || this.checkLists.length > 0;
		},
		wasFilled(): boolean
		{
			return this.$store.getters[`${Model.Tasks}/wasFieldFilled`](this.taskId, checkListMeta.id);
		},
		checkListItemCount(): number
		{
			return (this.checkLists.filter((checkList: CheckListModel) => checkList.parentId !== 0)).length;
		},
		text(): string
		{
			if (this.isAutonomous && this.checkListItemCount > 0)
			{
				const completedCount = this.getCompletedCount();

				return this.loc(
					'TASKS_V2_CHECK_LIST_COUNT_TITLE',
					{
						'#count#': completedCount,
						'#total#': this.checkListItemCount,
					},
				);
			}

			return this.loc('TASKS_V2_CHECK_LIST_CHIP_TITLE');
		},
		icon(): string
		{
			if (this.isUploading && !this.wasFilled)
			{
				return Animated.LOADER_WAIT;
			}

			return Outline.CHECK_LIST;
		},
	},
	mounted(): void
	{
		this.$bitrix.eventEmitter.subscribe(EventName.CloseCheckList, this.handleFieldClose);
	},
	beforeUnmount(): void
	{
		this.$bitrix.eventEmitter.unsubscribe(EventName.CloseCheckList, this.handleFieldClose);
	},
	methods: {
		handleClick(): void
		{
			if (this.isAutonomous)
			{
				void this.showCheckList();
			}
			else
			{
				// eslint-disable-next-line no-lonely-if
				if (this.isSelected)
				{
					this.highlightField();
				}
				else
				{
					void this.showCheckList();
				}
			}
		},
		async showCheckList(): Promise<void>
		{
			if (!this.isSelected)
			{
				await this.buildEmptyCheckList();
			}

			this.$emit('showCheckList');
		},
		async buildEmptyCheckList(): Promise<void>
		{
			const parentId = Text.getRandom();
			const childId = Text.getRandom();

			await this.$store.dispatch(`${Model.CheckList}/insertMany`, [
				{
					id: parentId,
					nodeId: parentId,
					title: this.loc('TASKS_V2_CHECK_LIST_TITLE_NUMBER', { '#number#': 1 }),
				},
				{
					id: childId,
					nodeId: childId,
					parentId,
				},
			]);

			await this.$store.dispatch(`${Model.Tasks}/update`, {
				id: this.taskId,
				fields: {
					checklist: [parentId, childId],
				},
			});
		},
		highlightField(): void
		{
			void fieldHighlighter.setContainer(this.$root.$el).highlight(checkListMeta.id);
		},
		getCompletedCount(): number
		{
			return this.checkLists.filter((checklist: CheckListModel) => {
				return checklist.isComplete && checklist.parentId !== 0;
			}).length;
		},
		handleFieldClose(): void
		{
			if (this.isAutonomous)
			{
				this.$el.focus();
			}
		},
	},
	template: `
		<Chip
			:design="design"
			:icon="icon"
			:text="text"
			:data-task-id="taskId"
			:data-task-chip-id="checkListMeta.id"
			@click="handleClick"
		/>
	`,
};
