import { BIcon, Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

import { Model } from 'tasks.v2.const';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { TaskModel } from 'tasks.v2.model.tasks';

import './importance.css';

// @vue/component
export const Importance = {
	components: {
		BIcon,
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
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
	},
	methods: {
		handleClick(): void
		{
			if (this.readonly)
			{
				return;
			}

			const isImportant = !this.task.isImportant;

			void taskService.update(
				this.taskId,
				{ isImportant },
			);
		},
	},
	template: `
		<div
			class="tasks-field-importance"
			:class="{ '--active': task.isImportant, '--readonly': readonly }"
			:data-task-id="taskId"
			:data-task-field-id="'isImportant'"
			:data-task-field-value="task.isImportant"
			@click="handleClick"
		>
			<BIcon :name="task.isImportant ? Outline.FIRE_SOLID : Outline.FIRE" :hoverable="false"/>
		</div>
	`,
};
