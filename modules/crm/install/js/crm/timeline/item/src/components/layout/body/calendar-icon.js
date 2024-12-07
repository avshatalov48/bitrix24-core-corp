import { DatetimeConverter } from 'crm.timeline.tools';
import { DateTimeFormat } from 'main.date';

export const CalendarIcon = {
	props: {
		timestamp: {
			type: Number,
			required: true,
			default: 0,
		},
		calendarEventId: {
			type: Number,
			required: false,
			default: null,
		},
	},

	computed: {
		date(): string
		{
			return this.formatUserTime('d');
		},
		month(): string
		{
			return this.formatUserTime('F');
		},
		dayWeek(): string
		{
			return this.formatUserTime('D');
		},
		time(): string
		{
			return this.getDateTimeConverter().toTimeString();
		},
		userTime(): Date
		{
			return this.getDateTimeConverter().getValue();
		},
		hasCalendarEventId(): boolean
		{
			return (this.calendarEventId > 0);
		},
	},

	methods: {
		getDateTimeConverter(): DatetimeConverter
		{
			return DatetimeConverter.createFromServerTimestamp(this.timestamp).toUserTime();
		},
		formatUserTime(format: string): string
		{
			return DateTimeFormat.format(format, this.userTime);
		},
	},

	template: `
		<div class="crm-timeline__calendar-icon-container">
			<div v-if="hasCalendarEventId" class="crm-timeline__calendar-icon_event_icon"></div>
			<div class="crm-timeline__calendar-icon">
				<header class="crm-timeline__calendar-icon_top">
					<div class="crm-timeline__calendar-icon_bullets">
						<div class="crm-timeline__calendar-icon_bullet"></div>
						<div class="crm-timeline__calendar-icon_bullet"></div>
					</div>
				</header>
				<main class="crm-timeline__calendar-icon_content">
					<div class="crm-timeline__calendar-icon_day">{{ date }}</div>
					<div class="crm-timeline__calendar-icon_month">{{ month }}</div>
					<div class="crm-timeline__calendar-icon_date">
						<span class="crm-timeline__calendar-icon_day-week">{{ dayWeek }}</span>
						<span class="crm-timeline__calendar-icon_time">{{ time }}</span>
					</div>
				</main>
			</div>
		</div>
	`,
};
