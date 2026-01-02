import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { Model } from 'tasks.v2.const';
import { Chip, ChipDesign } from 'tasks.v2.component.elements.chip';
import { fieldHighlighter } from 'tasks.v2.lib.field-highlighter';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { tagsMeta } from './tags-meta';
import { tagsDialog } from './tags-dialog';

// @vue/component
export const TagsChip = {
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
			tagsMeta,
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
			return this.$store.getters[`${Model.Tasks}/wasFieldFilled`](this.taskId, tagsMeta.id);
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

			tagsDialog.setTaskId(this.taskId).showTo(this.$el);
			tagsDialog.onUpdateOnce(this.highlightField);
		},
		highlightField(): void
		{
			void fieldHighlighter.setContainer(this.$root.$el).highlight(tagsMeta.id);
		},
	},
	template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:icon="Outline.TAG"
			:text="loc('TASKS_V2_TAGS_TITLE_CHIP')"
			:data-task-id="taskId"
			:data-task-chip-id="tagsMeta.id"
			:data-task-chip-value="task.tags.join(',')"
			@click="handleClick"
		/>
	`,
};
