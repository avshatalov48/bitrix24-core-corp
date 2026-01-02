import { DateTimeFormat } from 'main.date';
import type { BaseEvent } from 'main.core.events';

import { DatePicker, DatePickerEvent } from 'ui.date-picker';
import { BIcon } from 'ui.icon-set.api.vue';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { Model, Analytics } from 'tasks.v2.const';
import { calendar } from 'tasks.v2.lib.calendar';
import { timezone } from 'tasks.v2.lib.timezone';
import { analytics } from 'tasks.v2.lib.analytics';
import type { TaskModel } from 'tasks.v2.model.tasks';

import './deadline-popup-content.css';

type Preset = {
	id: string,
	title: string,
	timestamp: number,
	formatted: string,
};

// @vue/component
export const DeadlinePopupContent = {
	components: {
		BIcon,
	},
	inject: ['analytics', 'cardType'],
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	emits: ['update', 'close'],
	setup(): Object
	{
		return {
			Outline,
			today: new Date(),
			/** @type DatePicker */
			datePicker: null,
			dateTs: null,
			hour: null,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		presets(): Preset[]
		{
			const [year, month, date] = [this.today.getFullYear(), this.today.getMonth(), this.today.getDate()];
			const format = DateTimeFormat.getFormat('DAY_OF_WEEK_MONTH_FORMAT');

			return [
				{
					id: `today-${this.taskId}`,
					title: this.loc('TASKS_V2_DEADLINE_TODAY'),
					date: new Date(year, month, date),
				},
				{
					id: `tomorrow-${this.taskId}`,
					title: this.loc('TASKS_V2_DEADLINE_TOMORROW'),
					date: new Date(year, month, date + 1),
				},
				{
					id: `end-week-${this.taskId}`,
					title: this.loc('TASKS_V2_DEADLINE_IN_THE_END_OF_THE_WEEK'),
					date: new Date(year, month, date - this.today.getDay() + 5),
				},
				{
					id: `in-week-${this.taskId}`,
					title: this.loc('TASKS_V2_DEADLINE_IN_A_WEEK'),
					date: new Date(year, month, date + 7),
				},
				{
					id: `month-${this.taskId}`,
					title: this.loc('TASKS_V2_DEADLINE_IN_THE_END_OF_THE_MONTH'),
					date: new Date(year, month + 1, 0),
				},
			].map((preset): Preset => ({
				id: preset.id,
				title: preset.title,
				timestamp: preset.date.getTime(),
				formatted: DateTimeFormat.format(format, preset.date),
			}));
		},
	},
	created(): void
	{
		this.datePicker = this.createDatePicker();

		const date = new Date(this.task.deadlineTs + timezone.getOffset(this.task.deadlineTs));
		this.dateTs = new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
		this.hour = date.getHours();
	},
	mounted(): void
	{
		this.datePicker.setTargetNode(this.$refs.picker);
		this.datePicker.show();
	},
	beforeUnmount(): void
	{
		this.datePicker.destroy();
	},
	methods: {
		createDatePicker(): DatePicker
		{
			const offset = timezone.getOffset(this.task.deadlineTs);
			const picker = new DatePicker({
				selectedDates: this.task.deadlineTs ? [this.task.deadlineTs + offset] : null,
				defaultTime: calendar.defaultTime,
				inline: true,
				enableTime: true,
				events: {
					[DatePickerEvent.SELECT]: (event: BaseEvent) => {
						const { date } = event.getData();
						const dateTs = calendar.createDateFromUtc(date).getTime();
						this.$emit('update', dateTs - timezone.getOffset(dateTs));
					},
				},
			});

			picker.getPicker('day').subscribe('onSelect', (event: BaseEvent) => {
				const { year, month, day } = event.getData();
				const dateTs = new Date(year, month, day).getTime();

				this.close();
				this.sendAnalytics(Analytics.Element.Calendar);

				this.dateTs = dateTs;
			});

			picker.getPicker('time').subscribe('onSelect', (event: BaseEvent) => {
				const { hour, minute } = event.getData();
				if (Number.isInteger(minute) || hour === this.hour)
				{
					this.close();
				}
				this.sendAnalytics(Analytics.Element.Calendar);

				this.hour = hour;
			});

			return picker;
		},
		focusDate(timestamp: ?number): void
		{
			this.datePicker.setFocusDate(timestamp);
		},
		selectPresetDate(timestamp: number): void
		{
			const date = new Date(timestamp);
			const [y, m, d] = [date.getFullYear(), date.getMonth(), date.getDate()];

			this.datePicker.selectDate(new Date(`${m + 1}/${d}/${y} ${calendar.defaultTime}`));
			this.sendAnalytics(Analytics.Element.DeadlinePreset);

			this.close();
		},
		close(): void
		{
			this.$emit('close');
		},
		sendAnalytics(element: string): void
		{
			analytics.sendDeadlineSet(this.analytics, {
				cardType: this.cardType,
				element,
			});
		},
	},
	template: `
		<div class="tasks-field-deadline-popup">
			<div class="tasks-field-deadline-picker-container">
				<div class="tasks-field-deadline-picker" ref="picker">
					<BIcon v-if="false" class="tasks-field-deadline-picker-settings" :name="Outline.FILTER_2_LINES"/>
				</div>
			</div>
			<div class="tasks-field-deadline-presets">
				<template v-for="(preset, index) of presets" :key="index">
					<div
						:data-task-preset-id="preset.id"
						class="tasks-field-deadline-preset"
						@click="selectPresetDate(preset.timestamp)"
						@mouseenter="focusDate(preset.timestamp)"
						@mouseleave="focusDate(null)"
					>
						<div class="tasks-field-deadline-preset-title">{{ preset.title }}</div>
						<div class="tasks-field-deadline-preset-date">{{ preset.formatted }}</div>
					</div>
				</template>
			</div>
			<div v-if="false" class="tasks-field-deadline-settings">
			</div>
		</div>
	`,
};
