import { ParticipantList } from 'tasks.v2.component.elements.participant-list';
import { Model } from 'tasks.v2.const';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { UserModel } from 'tasks.v2.model.users';

import { accomplicesMeta } from './accomplices-meta';
import { AccomplicesMixin } from './accomplices-mixin';

// @vue/component
export const Accomplices = {
	name: 'TaskAccomplices',
	components: {
		ParticipantList,
	},
	mixins: [AccomplicesMixin],
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		users(): UserModel[]
		{
			return this.$store.getters[`${Model.Users}/getByIds`](this.task.accomplicesIds);
		},
		dataset(): Object
		{
			return {
				'data-task-id': this.taskId,
				'data-task-field-id': accomplicesMeta.id,
				'data-task-field-value': this.task.accomplicesIds.join(','),
			};
		},
	},
	template: `
		<ParticipantList
			:taskId="taskId"
			context="accomplices"
			:users="users"
			:dataset="dataset"
			@update="update"
		/>
	`,
};
