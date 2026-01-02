import { DurationFormat } from 'main.date';
import { TextMd, Text2Xs } from 'ui.system.typography.vue';
import { BIcon, Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

import { Model } from 'tasks.v2.const';
import { HoverPill } from 'tasks.v2.component.elements.hover-pill';
import { calendar } from 'tasks.v2.lib.calendar';
import { heightTransition } from 'tasks.v2.lib.height-transition';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { DeadlinePopup } from './deadline-popup/deadline-popup';
import { deadlineMeta } from './deadline-meta';
import './deadline.css';

// @vue/component
export const Deadline = {
	components: {
		TextMd,
		Text2Xs,
		HoverPill,
		BIcon,
		DeadlinePopup,
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
			deadlineMeta,
		};
	},
	data(): Object
	{
		return {
			nowTs: Date.now(),
			isPopupShown: false,
			selectingDeadlineTs: null,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
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
		expiredFormatted(): string
		{
			return this.loc('TASKS_V2_DEADLINE_EXPIRED', {
				'#EXPIRED_DURATION#': new DurationFormat(this.expiredDuration).formatClosest(),
			});
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

			return calendar.formatDateTime(this.deadlineTs);
		},
		iconName(): string
		{
			if (this.isFlowFilledOnAdd)
			{
				return Outline.BOTTLENECK;
			}

			return Outline.CALENDAR_WITH_SLOTS;
		},
		isEdit(): boolean
		{
			return Number.isInteger(this.taskId) && this.taskId > 0;
		},
		isFlowFilledOnAdd(): boolean
		{
			return this.task.flowId > 0 && !this.isEdit;
		},
		readonly(): boolean
		{
			return !this.task.rights.deadline || this.isFlowFilledOnAdd;
		},
	},
	mounted(): void
	{
		heightTransition.animate(this.$refs.container);
		this.nowTsInterval = setInterval(() => {
			this.nowTs = Date.now();
		}, 1000);
	},
	updated(): void
	{
		heightTransition.animate(this.$refs.container);
	},
	beforeUnmount(): void
	{
		clearInterval(this.nowTsInterval);
	},
	methods: {
		handleClick(): void
		{
			if (this.readonly)
			{
				return;
			}

			this.isPopupShown = true;
		},
		handleCrossClick(): void
		{
			this.$refs.popup?.clear();

			void taskService.update(
				this.taskId,
				{
					deadlineTs: 0,
				},
			);
		},
		handleUpdate(selectingDeadlineTs: number): void
		{
			this.selectingDeadlineTs = selectingDeadlineTs;
		},
		handleClose(): void
		{
			this.isPopupShown = false;
			this.selectingDeadlineTs = null;
			this.$refs.deadline?.$el?.focus();
		},
		handleKeydown(event: KeyboardEvent): void
		{
			if (event.key === 'Enter' && !(event.ctrlKey || event.metaKey))
			{
				void this.handleClick();
			}
		},
	},
	template: `
		<div
			class="tasks-field-deadline"
			:class="{ '--expired': isExpired, '--readonly': readonly, '--filled': deadlineTs }"
			:data-task-id="taskId"
			:data-task-field-id="deadlineMeta.id"
			:data-task-field-value="task.deadlineTs"
			ref="container"
		>
			<HoverPill
				:withClear="Boolean(deadlineTs) && !readonly"
				:readonly="readonly"
				ref="deadline"
				@click="handleClick"
				@clear="handleCrossClick"
				@keydown="handleKeydown"
			>
				<BIcon class="tasks-field-deadline-icon" :name="iconName"/>
				<TextMd class="tasks-field-deadline-text" :accent="isExpired">{{ deadlineFormatted }}</TextMd>
			</HoverPill>
			<Text2Xs v-if="isExpired" class="tasks-field-deadline-expired">{{ expiredFormatted }}</Text2Xs>
		</div>
		<DeadlinePopup
			v-if="isPopupShown"
			:taskId="taskId"
			:bindElement="$refs.deadline.$el"
			ref="popup"
			@update="handleUpdate"
			@close="handleClose"
		/>
	`,
};
