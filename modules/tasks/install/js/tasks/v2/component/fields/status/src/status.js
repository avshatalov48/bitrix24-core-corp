import { DateTimeFormat } from 'main.date';
import { BIcon } from 'ui.icon-set.api.vue';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { Hint } from 'tasks.v2.component.elements.hint';
import { Model, TaskStatus } from 'tasks.v2.const';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { statusMeta } from './status-meta';
import './status.css';

// @vue/component
export const Status = {
	components: {
		BIcon,
		Hint,
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
			statusMeta,
		};
	},
	data(): Object
	{
		return {
			isHintShown: false,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		icon(): string
		{
			const statuses = {
				[TaskStatus.Pending]: Outline.HOURGLASS,
				[TaskStatus.InProgress]: Outline.NEXT,
				[TaskStatus.SupposedlyCompleted]: Outline.REFRESH,
				[TaskStatus.Completed]: Outline.SENDED,
				[TaskStatus.Deferred]: Outline.PAUSE_L,
			};

			return statuses[this.task.status] ?? Outline.HOURGLASS;
		},
		statusFormatted(): string
		{
			const statuses = {
				[TaskStatus.Pending]: this.loc('TASKS_V2_STATUS_PENDING'),
				[TaskStatus.InProgress]: this.loc('TASKS_V2_STATUS_IN_PROGRESS'),
				[TaskStatus.SupposedlyCompleted]: this.loc('TASKS_V2_STATUS_SUPPOSEDLY_COMPLETED'),
				[TaskStatus.Completed]: this.loc('TASKS_V2_STATUS_COMPLETED'),
				[TaskStatus.Deferred]: this.loc('TASKS_V2_STATUS_DEFERRED'),
			};

			return statuses[this.task.status] ?? this.loc('TASKS_V2_STATUS_PENDING');
		},
		statusAtFormatted(): string
		{
			const statuses = {
				[TaskStatus.Pending]: 'TASKS_V2_STATUS_PENDING_FROM',
				[TaskStatus.InProgress]: 'TASKS_V2_STATUS_IN_PROGRESS_FROM',
				[TaskStatus.SupposedlyCompleted]: 'TASKS_V2_STATUS_SUPPOSEDLY_COMPLETED_FROM',
				[TaskStatus.Completed]: 'TASKS_V2_STATUS_COMPLETED_AT',
				[TaskStatus.Deferred]: 'TASKS_V2_STATUS_DEFERRED_AT',
			};

			return this.loc(statuses[this.task.status], {
				'#DATE#': this.formatDate(this.task.statusChangedTs),
				'#TIME#': this.formatTime(this.task.statusChangedTs),
			});
		},
		createdAtFormatted(): string
		{
			return this.loc('TASKS_V2_STATUS_CREATED_AT', {
				'#DATE#': this.formatDate(this.task.createdTs),
				'#TIME#': this.formatTime(this.task.createdTs),
			});
		},
	},
	methods: {
		formatDate(timestamp: number): string
		{
			return DateTimeFormat.format(DateTimeFormat.getFormat('SHORT_DATE_FORMAT'), timestamp / 1000);
		},
		formatTime(timestamp: number): string
		{
			return DateTimeFormat.format(DateTimeFormat.getFormat('SHORT_TIME_FORMAT'), timestamp / 1000);
		},
		handleClick(): void
		{
			this.clearTimeouts();
			if (this.isHintShown)
			{
				this.closePopup();
			}
			else
			{
				this.showPopup();
			}
		},
		handleMouseEnter(): void
		{
			this.clearTimeouts();
			this.showTimeout = setTimeout(() => this.showPopup(), 100);
		},
		handleMouseLeave(): void
		{
			this.clearTimeouts();
			this.closePopup();
		},
		showPopup(): void
		{
			this.clearTimeouts();
			this.isHintShown = true;
		},
		closePopup(): void
		{
			this.clearTimeouts();
			this.isHintShown = false;
		},
		clearTimeouts(): void
		{
			clearTimeout(this.showTimeout);
		},
	},
	template: `
		<div
			class="tasks-field-status"
			:data-task-id="taskId"
			:data-task-field-id="statusMeta.id"
			:data-task-field-value="task.status"
			:data-task-created-ts="task.createdTs"
			:data-task-status-changes-ts="task.statusChangedTs"
			ref="container"
			@click="handleClick"
			@mouseenter="handleMouseEnter"
			@mouseleave="handleMouseLeave"
		>
			<BIcon class="tasks-field-status-icon" :name="icon"/>
			<div class="tasks-field-status-text">{{ statusFormatted }}</div>
		</div>
		<Hint v-if="isHintShown" :bindElement="$refs.container" @close="closePopup">
			<div class="tasks-field-status-hint">
				<div>{{ statusAtFormatted }}</div>
				<div>{{ createdAtFormatted }}</div>
			</div>
		</Hint>
	`,
};
