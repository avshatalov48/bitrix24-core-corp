import { Model } from 'tasks.v2.const';
import { Chip, ChipDesign } from 'tasks.v2.component.elements.chip';
import { fieldHighlighter } from 'tasks.v2.lib.field-highlighter';
import type { TaskModel } from 'tasks.v2.model.tasks';

// @vue/component
export const RelationTasksChip = {
	components: {
		Chip,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		/** @type RelationMeta */
		meta: {
			type: Object,
			required: true,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		count(): number
		{
			return this.task[this.meta.idsField].length;
		},
		design(): string
		{
			if (this.disabled)
			{
				return ChipDesign.ShadowDisabled;
			}

			return this.isSelected ? ChipDesign.ShadowAccent : ChipDesign.ShadowNoAccent;
		},
		isSelected(): boolean
		{
			return this.$store.getters[`${Model.Tasks}/wasFieldFilled`](this.taskId, this.meta.id);
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
	},
	methods: {
		handleClick(): void
		{
			if (this.disabled)
			{
				return;
			}

			if (this.isSelected)
			{
				this.highlightField();

				return;
			}

			this.meta.dialog.setTaskId(this.taskId).onUpdateOnce(this.highlightField).showTo(this.$el);
		},
		highlightField(): void
		{
			void fieldHighlighter.setContainer(this.$root.$el).highlight(this.meta.id);
		},
	},
	template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:text="meta.chipTitle"
			:icon="meta.icon"
			:data-task-id="taskId"
			:data-task-chip-id="meta.id"
			ref="chip"
			@click="handleClick"
		/>
	`,
};
