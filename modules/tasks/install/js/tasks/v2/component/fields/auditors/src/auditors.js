import { Model } from 'tasks.v2.const';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { UserModel } from 'tasks.v2.model.users';
import { ParticipantList } from 'tasks.v2.component.elements.participant-list';

import { auditorsMeta } from './auditors-meta';
import { AuditorsMixin } from './auditors-mixin';

// @vue/component
export const Auditors = {
	name: 'TaskAuditors',
	components: {
		ParticipantList,
	},
	mixins: [AuditorsMixin],
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
			return this.$store.getters[`${Model.Users}/getByIds`](this.task.auditorsIds);
		},
		dataset(): Object
		{
			return {
				'data-task-id': this.taskId,
				'data-task-field-id': auditorsMeta.id,
				'data-task-field-value': this.task.auditorsIds.join(','),
			};
		},
	},
	template: `
		<ParticipantList
			:taskId="taskId"
			context="auditors"
			:users="users"
			:dataset="dataset"
			@update="update"
		/>
	`,
};
