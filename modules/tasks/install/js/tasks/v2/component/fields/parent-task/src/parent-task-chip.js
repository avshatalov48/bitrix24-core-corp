import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { Model } from 'tasks.v2.const';
import { Chip, ChipDesign } from 'tasks.v2.component.elements.chip';
import { fieldHighlighter } from 'tasks.v2.lib.field-highlighter';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { parentTaskMeta } from './parent-task-meta';
import { parentTaskDialog } from './parent-task-dialog';

// @vue/component
export const ParentTaskChip = {
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
	setup(): Object {
		return {
			parentTaskMeta,
			Outline,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		parentTask(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.task.parentId);
		},
		hasParent(): boolean
		{
			return this.task.parentId > 0;
		},
		icon(): string
		{
			return Outline.SUBTASK;
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
				return this.hasParent;
			}

			return this.$store.getters[`${Model.Tasks}/wasFieldFilled`](this.taskId, parentTaskMeta.id);
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
		text(): string
		{
			return parentTaskMeta.title;
		},
	},
	methods: {
		handleClick(): void
		{
			if (!this.isAutonomous && this.isSelected)
			{
				this.highlightField();

				return;
			}

			parentTaskDialog.setTaskId(this.taskId).showTo(this.$el);

			if (!this.isAutonomous)
			{
				parentTaskDialog.onUpdateOnce(this.highlightField);
			}
		},
		highlightField(): void
		{
			void fieldHighlighter.setContainer(this.$root.$el).highlight(parentTaskMeta.id);
		},
	},
	template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:text="text"
			:icon="icon"
			:data-task-id="taskId"
			:data-task-chip-id="parentTaskMeta.id"
			ref="chip"
			@click="handleClick"
		/>
	`,
};
