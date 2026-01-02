import { RelationTasksChip } from 'tasks.v2.component.fields.relation-tasks';
import { relatedTasksMeta } from './related-tasks-meta';

// @vue/component
export const RelatedTasksChip = {
	components: {
		RelationTasksChip,
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
		<RelationTasksChip :taskId="taskId" :meta="relatedTasksMeta"/>
	`,
};
