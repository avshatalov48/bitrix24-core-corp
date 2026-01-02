import { RelationTasks } from 'tasks.v2.component.fields.relation-tasks';
import { relatedTasksMeta } from './related-tasks-meta';

// @vue/component
export const RelatedTasks = {
	name: 'TaskRelatedTasks',
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
			relatedTasksMeta,
		};
	},
	template: `
		<RelationTasks :taskId="taskId" :meta="relatedTasksMeta"/>
	`,
};
