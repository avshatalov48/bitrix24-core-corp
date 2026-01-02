import { DateTimeFormat } from 'main.date';
import { DatePicker, DatePickerEvent, getNextDate } from 'ui.date-picker';

import { locMixin } from 'booking.component.mixin.loc-mixin';

import './calendar-block.css';

// @vue/component
export const CalendarBlock = {
	name: 'CalendarBlock',
	mixins: [locMixin],
	props: {
		date: {
			type: Date,
			default: null,
		},
		resource: {
			type: Object,
			required: true,
		},
		titleOnly: {
			type: Boolean,
			default: false,
		},
		hasError: {
			type: Boolean,
			default: false,
		},
		errorMessage: {
			type: String,
			default: '',
		},
	},
	emits: ['updateDate'],
	data(): { viewDate: Date | null, disabledPrevMonth: boolean }
	{
		return {
			viewDate: null,
			disabledPrevMonth: true,
		};
	},
	computed: {
		formattedViewDate(): string
		{
			return DateTimeFormat.format('f Y', this.viewDate);
		},
		title(): string
		{
			if (this.date !== null && this.titleOnly)
			{
				return this.loc('BOOKING_CRM_FORMS_FIELD_TIME_TITLE', {
					'#DATE#': DateTimeFormat.format(this.loc('DAY_MONTH_FORMAT'), this.date),
				});
			}

			return this.loc('BOOKING_CRM_FORMS_FIELD_DATE_TIME_TITLE');
		},
	},
	created(): void
	{
		this.viewDate = this.date;
		const selectedDates = this.date instanceof Date
			? [this.date.getTime()]
			: [];

		this.datePicker = new DatePicker({
			selectedDates,
			startDate: new Date(),
			inline: true,
			hideHeader: true,
		});

		this.datePicker.subscribe(DatePickerEvent.SELECT, (event) => {
			const date = event.getData().date;
			const selectedDate = this.toDateFromUtc(date);
			this.setViewDate();

			if (selectedDate !== this.date)
			{
				this.$emit('updateDate', selectedDate);
			}
		});
	},
	mounted(): void
	{
		this.datePicker.setTargetNode(this.$refs.datePicker);
		this.datePicker.show();
	},
	beforeUnmount(): void
	{
		this.datePicker.destroy();
	},
	methods: {
		setPreviousMonth(): void
		{
			const viewDate = this.datePicker.getViewDate();

			if (this.checkPastDate())
			{
				this.updateDisabledPrevMonth();

				return;
			}

			this.datePicker.setViewDate(getNextDate(viewDate, 'month', -1));
			this.setViewDate();
		},
		setNextMonth(): void
		{
			const viewDate = this.datePicker.getViewDate();

			this.updateDisabledPrevMonth();
			this.datePicker.setViewDate(getNextDate(viewDate, 'month'));
			this.setViewDate();
		},
		setViewDate(): void
		{
			this.viewDate = this.toDateFromUtc(this.datePicker.getViewDate());
			this.updateDisabledPrevMonth();
		},
		toDateFromUtc(date: Date): Date
		{
			return new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate());
		},
		updateDisabledPrevMonth(): void
		{
			this.disabledPrevMonth = this.checkPastDate();
		},
		checkPastDate(): boolean
		{
			const viewDate = this.datePicker.getViewDate();
			const today = new Date();

			return viewDate.getMonth() <= today.getMonth() && viewDate.getYear() <= today.getYear();
		},
	},
	template: `
		<div
			class="booking-crm-forms-field booking--crm-forms--calendar-block"
			:class="{
				'--error': hasError,
			}"
		>
			<div class="b24-form-field-layout-section booking-crm-forms-field-title">{{ title }}</div>
			<div
				v-if="hasError"
				class="b24-form-control-alert-message"
				style="top: 30px"
			>
				{{ errorMessage }}
			</div>
			<div v-show="!titleOnly" class="booking--crm-forms--calendar-block-content">
				<div class="booking--crm-forms--calendar-block-datepicker-header">
					<div class="booking--crm-forms--calendar-block-datepicker-header-title">
						{{ formattedViewDate }}
					</div>
					<div
						class="booking--crm-forms--calendar-block-datepicker-header-button --left"
						:class="{ '--disabled': disabledPrevMonth }"
						@click="setPreviousMonth"
					>
						<div class="booking--crm-forms--calendar-block-datepicker-icon --chevron-left"></div>
					</div>
					<div
						class="booking--crm-forms--calendar-block-datepicker-header-button --right"
						@click="setNextMonth"
					>
						<div class="booking--crm-forms--calendar-block-datepicker-icon --chevron-right"></div>
					</div>
				</div>
				<div ref="datePicker" class="booking--crm-forms--calendar-block-datepicker"></div>
			</div>
		</div>
	`,
};
