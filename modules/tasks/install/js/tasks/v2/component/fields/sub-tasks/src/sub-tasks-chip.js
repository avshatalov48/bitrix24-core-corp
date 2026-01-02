import { hint, type HintParams } from 'ui.vue3.directives.hint';

import { tooltip } from 'tasks.v2.component.elements.hint';
import { RelationTasksChip } from 'tasks.v2.component.fields.relation-tasks';
import { subTasksMeta } from './sub-tasks-meta';

// @vue/component
export const SubTasksChip = {
	components: {
		RelationTasksChip,
	},
	directives: { hint },
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	setup(): Object
	{
		return {
			subTasksMeta,
		};
	},
	computed: {
		isEdit(): boolean
		{
			return Number.isInteger(this.taskId) && this.taskId > 0;
		},
		tooltip(): Function
		{
			return (): HintParams => tooltip({
				text: this.loc('TASKS_V2_SUB_TASKS_DISABLED_HINT'),
				popupOptions: {
					offsetLeft: this.$el.offsetWidth / 2,
					targetContainer: document.querySelector(`[data-task-card-scroll="${this.taskId}"]`),
				},
				timeout: 200,
			});
		},
	},
	template: `
		<RelationTasksChip v-hint="tooltip" :taskId="taskId" :meta="subTasksMeta" :disabled="!isEdit"/>
	`,
};
