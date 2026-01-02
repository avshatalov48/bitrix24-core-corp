import { mapGetters } from 'ui.vue3.vuex';
import { BIcon, Outline } from 'ui.icon-set.api.vue';
import type { DialogOptions } from 'ui.entity-selector';

import { Model } from 'tasks.v2.const';
import { Participant } from 'tasks.v2.component.elements.participant';
import { analytics } from 'tasks.v2.lib.analytics';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { UserModel } from 'tasks.v2.model.users';

import { responsibleMeta } from './responsible-meta';
import './responsible.css';

// @vue/component
export const Responsible = {
	name: 'TaskResponsible',
	components: {
		Participant,
		BIcon,
	},
	inject: ['analytics', 'cardType'],
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
		avatarOnly: {
			type: Boolean,
			default: false,
		},
	},
	setup(): Object
	{
		return {
			BIcon,
			Outline,
		};
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
			return responsibleMeta.dialogOptions(this.localContext);
		},
		preselected(): [string, number][]
		{
			return [['user', this.task.responsibleId || this.currentUserId]];
		},
		isEdit(): boolean
		{
			return Number.isInteger(this.taskId) && this.taskId > 0;
		},
		isFlowFilledOnAdd(): boolean
		{
			return this.task.flowId > 0 && !this.isEdit;
		},
		dataset(): Object
		{
			return {
				'data-task-id': this.taskId,
				'data-task-field-id': responsibleMeta.id,
				'data-task-field-value': this.task.responsibleId,
			};
		},
		readonly(): boolean
		{
			return !this.task.rights.delegate;
		},
	},
	methods: {
		handleHintClick(): void
		{
			void taskService.update(
				this.taskId,
				{
					creatorId: this.currentUserId,
				},
			);
		},
		updateTask(user: UserModel): void
		{
			void this.$store.dispatch(`${Model.Users}/upsert`, user);

			if (this.task.responsibleId !== user.id)
			{
				this.sendAnalytics();
			}

			void taskService.update(
				this.taskId,
				{
					responsibleId: user.id,
				},
			);
		},
		sendAnalytics(): void
		{
			analytics.sendAssigneeChange(this.analytics, {
				cardType: this.cardType,
			});
		},
	},
	template: `
		<div v-if="isFlowFilledOnAdd" class="tasks-field-responsible-auto">
			<BIcon :name="Outline.BOTTLENECK" color="var(--ui-color-accent-main-primary)"></BIcon>
			<div v-if="!avatarOnly">
				{{ loc('TASKS_V2_RESPONSIBLE_AUTO') }}
			</div>
		</div>
		<Participant
			v-else
			:taskId="taskId"
			:dialogOptions="dialogOptions"
			:preselected="preselected"
			:canChange="() => task.rights.delegate || task.creatorId === currentUserId"
			:cantChangeHint="loc('TASKS_V2_RESPONSIBLE_CANT_CHANGE')"
			:hintClickHandler="handleHintClick"
			:selectorWithActionMenu="selectorWithActionMenu"
			:dataset="dataset"
			:readonly="readonly"
			:avatarOnly="avatarOnly"
			@update="updateTask"
		/>
	`,
};
