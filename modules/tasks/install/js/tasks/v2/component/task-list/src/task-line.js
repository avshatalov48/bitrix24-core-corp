import { EventEmitter } from 'main.core.events';
import { DurationFormat } from 'main.date';

import type { DialogOptions } from 'ui.entity-selector';
import { TextSm, TextMd } from 'ui.system.typography.vue';
import { BIcon, Outline } from 'ui.icon-set.api.vue';

import { EventName, Model } from 'tasks.v2.const';
import { HoverPill } from 'tasks.v2.component.elements.hover-pill';
import { calendar } from 'tasks.v2.lib.calendar';
import { taskService } from 'tasks.v2.provider.service.task-service';
import { Responsible, responsibleMeta } from 'tasks.v2.component.fields.responsible';
import { DeadlinePopup } from 'tasks.v2.component.fields.deadline';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { UserModel } from 'tasks.v2.model.users';

import './task-line.css';

// @vue/component
export const TaskLine = {
	components: {
		BIcon,
		Responsible,
		DeadlinePopup,
		HoverPill,
		TextMd,
		TextSm,
	},
	props: {
		context: {
			type: String,
			required: true,
		},
		taskId: {
			type: [Number, String],
			required: true,
		},
		readonly: {
			type: Boolean,
			required: true,
		},
		last: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['remove'],
	setup(): Object
	{
		return {
			responsibleMeta,
			Outline,
		};
	},
	data(): Object
	{
		return {
			isDeadlinePopupShown: false,
			selectingDeadlineTs: null,
			nowTs: Date.now(),
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		responsible(): UserModel
		{
			return this.$store.getters[`${Model.Users}/getById`](this.task.responsibleId);
		},
		dialogOptions(): DialogOptions
		{
			return responsibleMeta.dialogOptions(this.context);
		},
		preselected(): [string, number][]
		{
			return [['user', this.task.responsibleId || this.currentUserId]];
		},
		dataset(): Object
		{
			return {
				'data-task-id': this.taskId,
				'data-task-field-id': responsibleMeta.id,
				'data-task-field-value': this.task.responsibleId,
			};
		},
		deadlineTs(): number
		{
			return this.selectingDeadlineTs ?? this.task.deadlineTs;
		},
		expiredDuration(): number
		{
			if (!this.deadlineTs)
			{
				return 0;
			}

			return this.nowTs - this.deadlineTs;
		},
		isExpired(): boolean
		{
			return this.expiredDuration > 0 && !this.isFlowFilledOnAdd;
		},
		deadlineFormatted(): string
		{
			if (this.isFlowFilledOnAdd)
			{
				return this.loc('TASKS_V2_DEADLINE_AUTO');
			}

			if (!this.deadlineTs)
			{
				return this.loc('TASKS_V2_DEADLINE_EMPTY');
			}

			if (this.isExpired)
			{
				return this.loc('TASKS_V2_TASK_LIST_DEADLINE_EXPIRED', {
					'#DURATION#': new DurationFormat(this.expiredDuration).formatClosest(),
				});
			}

			return calendar.formatDateTime(this.deadlineTs);
		},
		isEdit(): boolean
		{
			return Number.isInteger(this.taskId) && this.taskId > 0;
		},
		isFlowFilledOnAdd(): boolean
		{
			return this.task.flowId > 0 && !this.isEdit;
		},
		deadlineReadonly(): boolean
		{
			return this.readonly || !this.task.rights.deadline || this.isFlowFilledOnAdd;
		},
		detachReadonly(): boolean
		{
			const canDetach = this.task.rights.detachParent || this.task.rights.detachRelated;

			return this.readonly || !canDetach;
		},
	},
	mounted(): void
	{
		this.nowTsInterval = setInterval(() => {
			this.nowTs = Date.now();
		}, 1000);
	},
	beforeUnmount(): void
	{
		clearInterval(this.nowTsInterval);
	},
	methods: {
		handleEditDeadline(): void
		{
			if (this.deadlineReadonly)
			{
				return;
			}

			this.isDeadlinePopupShown = true;
		},
		handleClearDeadline(): void
		{
			if (this.deadlineReadonly)
			{
				return;
			}

			void taskService.update(
				this.taskId,
				{ deadlineTs: 0 },
			);
		},
		handleDeadlineUpdate(selectingDeadlineTs: number): void
		{
			this.selectingDeadlineTs = selectingDeadlineTs;
		},
		handleDeadlinePopupClose(): void
		{
			this.isDeadlinePopupShown = false;
			this.selectingDeadlineTs = null;
		},
		handleTaskOpen(): void
		{
			EventEmitter.emit(EventName.OpenFullCard, {
				taskId: this.taskId,
				widthOffset: 48,
			});
		},
		handleRemove(): void
		{
			if (this.readonly)
			{
				return;
			}

			this.$emit('remove');
		},
	},
	template: `
		<div class="tasks-task-line-container" :class="{ '--last': last }">
			<div class="tasks-task-line-wrapper">
				<div class="tasks-task-line-title-container" @click="handleTaskOpen">
					<TextMd class="tasks-task-line-title">
						{{ task.title }}
					</TextMd>
				</div>
				<div class="tasks-task-line-fields-container">
					<Responsible
						:context="context"
						:taskId="taskId"
						:selectorWithActionMenu="true"
						:avatarOnly="true"
					/>
					<div
						class="tasks-task-line-deadline"
						:class="{ '--expired': isExpired }"
						ref="deadline"
					>
						<HoverPill
							:withClear="Boolean(deadlineTs) && !deadlineReadonly"
							:readonly="deadlineReadonly"
							@click="handleEditDeadline"
							@clear="handleClearDeadline"
						>
							<TextSm class="tasks-task-line-deadline-text" :accent="isExpired">
								{{ deadlineFormatted }}
							</TextSm>
						</HoverPill>
					</div>
				</div>
				<div
					class="tasks-task-line-cross"
					:class="{ '--readonly': detachReadonly }"
					@click="handleRemove"
				>
					<BIcon :name="Outline.CROSS_L" :hoverable="true"/>
				</div>
			</div>
		</div>
		<DeadlinePopup
			v-if="isDeadlinePopupShown"
			:taskId="taskId"
			:bindElement="$refs.deadline"
			@update="handleDeadlineUpdate"
			@close="handleDeadlinePopupClose"
		/>
	`,
};
