import { DatetimeConverter } from 'crm.timeline.tools';
import { BaseEvent } from 'main.core.events';
import { DatePicker } from 'ui.date-picker';

import { Chevron } from './chevron';

export const PeriodRow = {
	components: {
		Chevron,
	},
	props: {
		startPoint: {
			type: String,
			required: true,
		},
		endPoint: {
			type: String,
			required: true,
		},
		modelValue: {
			type: Object,
			default: {},
		},
	},
	emits: ['update:modelValue'],

	data(): Object
	{
		return {
			currentStartPoint: this.startPoint,
			currentEndPoint: this.endPoint,
			isExpanded: false,
		};
	},

	created(): void
	{
		this.datePicker = new DatePicker({
			rangeStartInput: this.$refs.start,
			rangeEndInput: this.$refs.end,
			selectionMode: 'range',
			defaultTime: '08:00',
			dateFormat: DatetimeConverter.getSiteDateTimeFormat(true),
			timeFormat: DatetimeConverter.getSiteShortTimeFormat(),
			selectedDates: [this.currentStartPoint, this.currentEndPoint],
			enableTime: true,
			events: {
				onSelectChange: (event: BaseEvent) => {
					const dates = event
						.getTarget()
						.getSelectedDates()
						.map((date: Date) => event.getTarget().formatDate(date, DatetimeConverter.getSiteDateTimeFormat(true)))
					;

					if (dates[0])
					{
						this.currentStartPoint = dates[0];
					}

					if (dates[1])
					{
						this.currentEndPoint = dates[1];
					}

					this.$emit('update:modelValue', {
						startPoint: this.currentStartPoint,
						endPoint: this.currentEndPoint,
					});
				},
				onShow: () => {
					this.isExpanded = true;
				},
				onHide: () => {
					this.isExpanded = false;
				},
			},
		});
	},

	mounted(): void
	{
		this.datePicker.setTargetNode(this.$refs.wrapper);
		this.datePicker.selectRange(this.currentStartPoint, this.currentEndPoint);
	},

	beforeUnmount(): void
	{
		this.datePicker.destroy();
	},

	methods: {
		showPicker(event: PointerEvent): void
		{
			event.preventDefault();
			event.stopPropagation();

			this.datePicker.show();
		},
	},

	template: `
		<div
			class="crm-copilot__call-assessment-availability-selector-row"
			ref="wrapper"
		>
			<div class="crm-copilot__call-assessment-availability-selector-row-wrapper">
				<input
					type="text"
					ref="start"
					class="crm-copilot__call-assessment-availability-selector-input"
					autocomplete="off"
					readonly="readonly"
					@click="showPicker"
					:value="currentStartPoint"
				>
				<Chevron 
					:isExpanded="isExpanded"
					@click="showPicker"
				/>
			</div>
			<div class="crm-copilot__call-assessment-availability-selector-delimiter"></div>
			<div class="crm-copilot__call-assessment-availability-selector-row-wrapper">
				<input
					type="text"
					ref="end"
					class="crm-copilot__call-assessment-availability-selector-input"
					autocomplete="off"
					readonly="readonly"
					@click="showPicker"
					:value="currentEndPoint"
				>
				<Chevron 
					:isExpanded="isExpanded"
					@click="showPicker"
				/>
			</div>
		</div>
	`,
};
