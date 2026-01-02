import { Chip, ChipDesign } from 'ui.system.chip.vue';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { Model } from 'tasks.v2.const';
import { fieldHighlighter } from 'tasks.v2.lib.field-highlighter';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { crmMeta } from './crm-meta';
import { crmDialog } from './crm-dialog';
import './crm.css';

// @vue/component
export const CrmChip = {
	components: {
		Chip,
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
			crmMeta,
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
			return this.$store.getters[`${Model.Tasks}/wasFieldFilled`](this.taskId, crmMeta.id);
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

			crmDialog.setTaskId(this.taskId).showTo(this.$el);
			crmDialog.onUpdateOnce(this.highlightField);
		},
		highlightField(): void
		{
			void fieldHighlighter.setContainer(this.$root.$el).highlight(crmMeta.id);
		},
	},
	template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:icon="Outline.CRM"
			:text="loc('TASKS_V2_CRM_TITLE_CHIP')"
			:data-task-id="taskId"
			:data-task-chip-id="crmMeta.id"
			:data-task-crm-item-ids="task.crmItemIds?.join(',')"
			@click="handleClick"
		/>
	`,
};
