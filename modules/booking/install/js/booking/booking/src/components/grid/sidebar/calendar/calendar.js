import { DateTimeFormat } from 'main.date';
import { DatePicker, DatePickerEvent, getNextDate, createDate } from 'ui.date-picker';
import { createNamespacedHelpers } from 'ui.vue3.vuex';

import { DateFormat, Model } from 'booking.const';

import './calendar.css';

const { mapGetters: mapInterfaceGetters } = createNamespacedHelpers(Model.Interface);

export const Calendar = {
	data(): Object
	{
		return {
			expanded: true,
		};
	},
	created(): void
	{
		this.datePicker = new DatePicker({
			selectedDates: [this.selectedDateTs],
			inline: true,
			hideHeader: true,
		});

		this.setViewDate();

		this.datePicker.subscribe(DatePickerEvent.SELECT, (event) => {
			const date = event.getData().date;
			const selectedDate = this.createDateFromUtc(date);
			void this.$store.dispatch(`${Model.Interface}/setSelectedDateTs`, selectedDate.getTime());
			this.setViewDate();
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
	computed: {
		...mapInterfaceGetters({
			filteredMarks: 'filteredMarks',
			freeMarks: 'freeMarks',
			isFilterMode: 'isFilterMode',
			getCounterMarks: 'getCounterMarks',
			offset: 'offset',
		}),
		selectedDateTs(): number
		{
			return this.$store.getters[`${Model.Interface}/selectedDateTs`] + this.offset;
		},
		viewDateTs(): number
		{
			return this.$store.getters[`${Model.Interface}/viewDateTs`] + this.offset;
		},
		counterMarks(): string[]
		{
			if (this.isFilterMode)
			{
				return this.getCounterMarks(this.filteredMarks);
			}

			return this.getCounterMarks();
		},
		formattedDate(): string
		{
			const format = this.expanded
				? this.loc('BOOKING_MONTH_YEAR_FORMAT')
				: DateTimeFormat.getFormat('LONG_DATE_FORMAT')
			;

			const timestamp = this.expanded
				? this.viewDateTs / 1000
				: this.selectedDateTs / 1000
			;

			return DateTimeFormat.format(format, timestamp);
		},
	},
	methods: {
		onCollapseClick(): void
		{
			this.expanded = !this.expanded;
		},
		onPreviousClick(): void
		{
			if (this.expanded)
			{
				this.previousMonth();
			}
			else
			{
				this.previousDate();
			}
		},
		onNextClick(): void
		{
			if (this.expanded)
			{
				this.nextMonth();
			}
			else
			{
				this.nextDate();
			}
		},
		previousDate(): void
		{
			const selectedDate = this.datePicker.getSelectedDate() || this.datePicker.getToday();
			this.datePicker.selectDate(getNextDate(selectedDate, 'day', -1));
			this.setViewDate();
		},
		nextDate(): void
		{
			const selectedDate = this.datePicker.getSelectedDate() || this.datePicker.getToday();
			this.datePicker.selectDate(getNextDate(selectedDate, 'day'));
			this.setViewDate();
		},
		previousMonth(): void
		{
			const viewDate = this.datePicker.getViewDate();
			this.datePicker.setViewDate(getNextDate(viewDate, 'month', -1));
			this.setViewDate();
		},
		nextMonth(): void
		{
			const viewDate = this.datePicker.getViewDate();
			this.datePicker.setViewDate(getNextDate(viewDate, 'month'));
			this.setViewDate();
		},
		setViewDate(): void
		{
			const viewDate = this.createDateFromUtc(this.datePicker.getViewDate());
			const viewDateTs = viewDate.setDate(1);

			void this.$store.dispatch(`${Model.Interface}/setViewDateTs`, viewDateTs);
		},
		createDateFromUtc(date: Date): Date
		{
			return new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate());
		},
		updateMarks(): void
		{
			if (this.isFilterMode)
			{
				this.setFilterMarks();
			}
			else
			{
				this.setFreeMarks();
			}

			this.setCounterMarks();
		},
		setFreeMarks(): void
		{
			const bgColorFree = 'rgba(var(--ui-color-background-success-rgb), 0.7)';
			const dates = this.prepareDates(this.freeMarks);

			this.datePicker.setDayColors([
				{
					matcher: dates,
					bgColor: bgColorFree,
				},
			]);
		},
		setFilterMarks(): void
		{
			const bgColorFilter = 'rgba(var(--ui-color-primary-rgb), 0.20)';
			const dates = this.prepareDates(this.filteredMarks);

			this.datePicker.setDayColors([
				{
					matcher: dates,
					bgColor: bgColorFilter,
				},
			]);
		},
		setCounterMarks(): void
		{
			const dates = this.prepareDates(this.counterMarks);

			this.datePicker.setDayMarks([
				{
					matcher: dates,
					bgColor: 'red',
				},
			]);
		},
		prepareDates(dates: string[]): string[]
		{
			return dates.map((markDate: string): string => {
				const date = DateTimeFormat.parse(markDate, false, DateFormat.ServerParse);

				return this.prepareTimestamp(date.getTime());
			});
		},
		prepareTimestamp(timestamp: number): string
		{
			const dateFormat = DateTimeFormat.getFormat('FORMAT_DATE');

			return DateTimeFormat.format(dateFormat, timestamp / 1000);
		},
	},
	watch: {
		selectedDateTs(selectedDateTs: number): void
		{
			this.datePicker.selectDate(createDate(selectedDateTs));
			this.updateMarks();
		},
		filteredMarks(): void
		{
			this.updateMarks();
		},
		freeMarks(): void
		{
			this.updateMarks();
		},
		counterMarks(): void
		{
			this.setCounterMarks();
		},
		isFilterMode(): void
		{
			this.updateMarks();
		},
	},
	template: `
		<div class="booking-booking-sidebar-calendar">
			<div class="booking-booking-sidebar-calendar-header">
				<div class="booking-booking-sidebar-calendar-button" @click="onPreviousClick">
					<div class="ui-icon-set --chevron-left"></div>
				</div>
				<div class="booking-booking-sidebar-calendar-title">
					{{ formattedDate }}
				</div>
				<div class="booking-booking-sidebar-calendar-button" @click="onNextClick">
					<div class="ui-icon-set --chevron-right"></div>
				</div>
				<div class="booking-booking-sidebar-calendar-button --collapse" @click="onCollapseClick">
					<div v-if="expanded" class="ui-icon-set --collapse"></div>
					<div v-else class="ui-icon-set --expand-1"></div>
				</div>
			</div>
			<div
				class="booking-booking-sidebar-calendar-date-picker"
				:class="{'--expanded': expanded}"
				ref="datePicker"
			></div>
		</div>
	`,
};
