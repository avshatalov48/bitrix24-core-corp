import {DateTimeFormat} from 'main.date';
import { DatetimeConverter } from 'crm.timeline.tools';

export const CalendarIcon = {
	props: {
		timestamp: {
			type: Number,
			required: true,
			default: 0,
		}
	},

	computed: {
		userTime() {
			return DatetimeConverter.createFromServerTimestamp(this.timestamp).toUserTime().getValue();
		},
		date() {
			return DateTimeFormat.format('d', this.userTime);
		},
		month() {
			return DateTimeFormat.format('F', this.userTime);
		},
		dayWeek() {
			return DateTimeFormat.format('D', this.userTime);
		},

		time() {
			return DatetimeConverter.createFromServerTimestamp(this.timestamp).toUserTime().toTimeString();
		},
	},
	template: `
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
	`
}