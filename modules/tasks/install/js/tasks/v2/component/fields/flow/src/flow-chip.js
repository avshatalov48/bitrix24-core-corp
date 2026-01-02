import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { Model } from 'tasks.v2.const';
import { Chip, ChipDesign } from 'tasks.v2.component.elements.chip';
import { fieldHighlighter } from 'tasks.v2.lib.field-highlighter';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { FlowModel } from 'tasks.v2.model.flows';

import { flowMeta } from './flow-meta';
import { flowDialog } from './flow-dialog';

// @vue/component
export const FlowChip = {
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
	setup(): Object
	{
		return {
			Outline,
			flowMeta,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		flow(): FlowModel
		{
			return this.$store.getters[`${Model.Flows}/getById`](this.task.flowId);
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
				return this.task.flowId > 0;
			}

			return this.$store.getters[`${Model.Tasks}/wasFieldFilled`](this.taskId, flowMeta.id);
		},
		isFilled(): boolean
		{
			return this.isAutonomous && this.task.flowId > 0;
		},
		text(): string
		{
			if (this.isFilled)
			{
				return this.flow.name;
			}

			return this.loc('TASKS_V2_FLOW_TITLE_CHIP');
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
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

			flowDialog.setTaskId(this.taskId).showTo(this.$el);

			if (!this.isAutonomous)
			{
				flowDialog.onUpdateOnce(this.highlightField);
			}
		},
		highlightField(): void
		{
			void fieldHighlighter.setContainer(this.$root.$el).highlight(flowMeta.id);
		},
		handleClear(): void
		{
			void taskService.update(
				this.taskId,
				{
					flowId: 0,
					groupId: 0,
				},
			);
		},
	},
	// TODO: remove title prop when flow popup added
	template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:icon="Outline.BOTTLENECK"
			:text="text"
			:withClear="isFilled"
			:trimmable="isFilled"
			:data-task-id="taskId"
			:data-task-chip-id="flowMeta.id"
			:data-task-chip-value="task.flowId"
			@click="handleClick"
			@clear="handleClear"
			:title="flow?.name ?? ''"
		/>
	`,
};
