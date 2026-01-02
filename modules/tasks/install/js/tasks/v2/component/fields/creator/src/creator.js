import { mapGetters } from 'ui.vue3.vuex';
import type { DialogOptions } from 'ui.entity-selector';

import { Model } from 'tasks.v2.const';
import { Participant } from 'tasks.v2.component.elements.participant';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { UserModel } from 'tasks.v2.model.users';

import { creatorMeta } from './creator-meta';

// @vue/component
export const Creator = {
	name: 'TaskCreator',
	components: {
		Participant,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		context: {
			type: String,
			required: true,
		},
		selectorWithActionMenu: {
			type: Boolean,
			default: false,
		},
	},
	data(): Object
	{
		return {
			localContext: `${this.context}-${this.$options.name}`,
		};
	},
	computed: {
		...mapGetters({
			currentUserId: `${Model.Interface}/currentUserId`,
		}),
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		dialogOptions(): DialogOptions
		{
			return creatorMeta.dialogOptions(this.localContext);
		},
		preselected(): [string, number][]
		{
			return [['user', this.task.creatorId || this.currentUserId]];
		},
		cantChangeHint(): string
		{
			if (this.task.flowId > 0)
			{
				return this.loc('TASKS_V2_CREATOR_CANT_CHANGE_FLOW');
			}

			return this.loc('TASKS_V2_CREATOR_CANT_CHANGE');
		},
		dataset(): Object
		{
			return {
				'data-task-id': this.taskId,
				'data-task-field-id': creatorMeta.id,
				'data-task-field-value': this.task.creatorId,
			};
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
	},
	methods: {
		handleHintClick(): void
		{
			if (this.task.flowId > 0)
			{
				return;
			}

			void taskService.update(
				this.taskId,
				{
					responsibleId: this.currentUserId,
				},
			);
		},
		updateTask(user: UserModel): void
		{
			void this.$store.dispatch(`${Model.Users}/upsert`, user);
			void taskService.update(
				this.taskId,
				{
					creatorId: user.id,
				},
			);
		},
	},
	template: `
		<Participant
			:taskId="taskId"
			:dialogOptions="dialogOptions"
			:preselected="preselected"
			:canChange="() => task.responsibleId === currentUserId && (task.flowId ?? 0) <= 0"
			:cantChangeHint="cantChangeHint"
			:hintClickHandler="handleHintClick"
			:selectorWithActionMenu="selectorWithActionMenu"
			:dataset="dataset"
			:readonly="readonly"
			@update="updateTask"
		/>
	`,
};
