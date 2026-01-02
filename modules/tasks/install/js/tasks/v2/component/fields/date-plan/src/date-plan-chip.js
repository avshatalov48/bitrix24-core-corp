import { Chip, ChipDesign } from 'ui.system.chip.vue';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { Model } from 'tasks.v2.const';
import { fieldHighlighter } from 'tasks.v2.lib.field-highlighter';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { datePlanMeta } from './date-plan-meta';

// @vue/component
export const DatePlanChip = {
	components: {
		Chip,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	emits: ['open'],
	setup(): Object
	{
		return {
			Outline,
			datePlanMeta,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		design(): string
		{
			return this.isSelected ? ChipDesign.ShadowAccent : ChipDesign.ShadowNoAccent;
		},
		isSelected(): boolean
		{
			return this.$store.getters[`${Model.Tasks}/wasFieldFilled`](this.taskId, datePlanMeta.id);
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
	},
	methods: {
		handleClick(): void
		{
			if (this.isSelected)
			{
				this.highlightField();

				return;
			}

			this.$emit('open');
		},
		highlightField(): void
		{
			void fieldHighlighter.setContainer(this.$root.$el).highlight(datePlanMeta.id);
		},
	},
	template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:icon="Outline.PLANNING"
			:text="loc('TASKS_V2_DATE_PLAN_TITLE_CHIP')"
			:data-task-id="taskId"
			:data-task-chip-id="datePlanMeta.id"
			:data-task-plan-start="task.startPlanTs"
			:data-task-plan-end="task.endPlanTs"
			@click="handleClick"
		/>
	`,
};
