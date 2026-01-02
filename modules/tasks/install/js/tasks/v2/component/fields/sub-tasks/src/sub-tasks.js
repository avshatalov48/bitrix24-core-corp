import { RelationTasks } from 'tasks.v2.component.fields.relation-tasks';
import { subTasksMeta } from './sub-tasks-meta';

// @vue/component
export const SubTasks = {
	name: 'TaskSubTasks',
	components: {
		RelationTasks,
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
			subTasksMeta,
		};
	},
	template: `
		<RelationTasks :taskId="taskId" :meta="subTasksMeta"/>
	`,
};
