import { mapGetters } from 'ui.vue3.vuex';
import { Button as UiButton, AirButtonStyle, ButtonSize, ButtonIcon } from 'ui.vue3.components.button';
import { BIcon } from 'ui.icon-set.api.vue';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { Model, TaskStatus } from 'tasks.v2.const';
import { statusService } from 'tasks.v2.provider.service.status-service';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { More } from './more/more';
import './footer-edit.css';

type ButtonOptions = {
	text: string,
	color: string,
	disabled: boolean,
	onClick: Function,
};

// @vue/component
export const FooterEdit = {
	components: {
		UiButton,
		BIcon,
		More,
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
			AirButtonStyle,
			ButtonSize,
			ButtonIcon,
			Outline,
			TaskStatus,
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
		isCreator(): boolean
		{
			return this.currentUserId === this.task.creatorId;
		},
		isResponsible(): boolean
		{
			return this.currentUserId === this.task.responsibleId;
		},
		statusButton(): ButtonOptions | null
		{
			const statuses = {
				[TaskStatus.Pending]: {
					[this.isCreator]: this.getCompleteButton(),
					[this.isResponsible]: this.getStartButton(),
				},
				[TaskStatus.InProgress]: {
					[this.isResponsible]: this.getCompleteButton(),
					[this.isCreator]: this.getCompleteButton(),
				},
				[TaskStatus.Deferred]: {
					[this.isResponsible]: this.getRenewButton(),
					[this.isCreator]: this.getRenewButton(),
				},
				[TaskStatus.SupposedlyCompleted]: {
					[this.isResponsible]: this.getReviewButton(),
					[this.isCreator]: this.getApproveButton(),
				},
				[TaskStatus.Completed]: {
					[this.isResponsible && !this.isCreator]: this.getRenewButton(AirButtonStyle.PLAIN),
				},
			};

			return statuses[this.task.status].true;
		},
	},
	methods: {
		getStartButton(): ButtonOptions
		{
			if (!this.task.rights.start)
			{
				return null;
			}

			return {
				text: this.loc('TASKS_V2_TASK_FULL_CARD_START'),
				onClick: (): void => statusService.start(this.taskId),
			};
		},
		getCompleteButton(): ButtonOptions
		{
			if (!this.task.rights.complete)
			{
				return null;
			}

			return {
				text: this.loc('TASKS_V2_TASK_FULL_CARD_COMPLETE'),
				onClick: (): void => statusService.complete(this.taskId),
			};
		},
		getRenewButton(style: string): ButtonOptions
		{
			if (!this.task.rights.renew)
			{
				return null;
			}

			return {
				text: this.loc('TASKS_V2_TASK_FULL_CARD_RENEW'),
				style,
				onClick: (): void => statusService.renew(this.taskId),
			};
		},
		getReviewButton(): ButtonOptions
		{
			return {
				text: this.loc('TASKS_V2_TASK_FULL_CARD_ON_REVIEW'),
				disabled: true,
			};
		},
		getApproveButton(): ButtonOptions
		{
			if (!this.task.rights.approve)
			{
				return null;
			}

			return {
				text: this.loc('TASKS_V2_TASK_FULL_CARD_APPROVE'),
				onClick: (): void => statusService.complete(this.taskId),
			};
		},
	},
	template: `
		<div class="tasks-full-card-footer-edit">
			<UiButton
				v-if="statusButton"
				:text="statusButton.text"
				:size="ButtonSize.LARGE"
				:style="statusButton.style ?? AirButtonStyle.FILLED"
				:disabled="statusButton.disabled ?? false"
				data-task-status-button
				@click="statusButton.onClick"
			/>
			<More :taskId="taskId"/>
			<div class="tasks-full-card-footer-actions">
				<div class="tasks-full-card-footer-reaction">
					<BIcon :name="Outline.LIKE"/>
					<div class="tasks-full-card-footer-reaction-text">Нравится</div>
				</div>
				<div class="tasks-full-card-footer-views">
					<BIcon :name="Outline.OBSERVER"/>
					<div class="tasks-full-card-footer-views-text">1</div>
				</div>
			</div>
		</div>
	`,
};
