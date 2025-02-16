import { DateTimeFormat } from 'main.date';
import { Mixin } from '../../mixin';

import './booking-time.css';

export const BookingTime = {
	name: 'BookingTime',
	mixins: [Mixin],
	props: {
		booking: {
			type: Object,
			required: true,
		},
	},
	data(): Object
	{
		return {};
	},
	computed: {
		getBookingDateFrom(): Date
		{
			return new Date(this.booking.datePeriod.from.timestamp * 1000);
		},
		getBookingDateTo(): Date
		{
			return new Date(this.booking.datePeriod.to.timestamp * 1000);
		},
		getTimeTo(): string
		{
			const bookingDateTo = new Date(this.booking.datePeriod.to.timestamp);

			return `${bookingDateTo.getHours()}:${bookingDateTo.getMinutes()}`;
		},
		getMonth(): string
		{
			return DateTimeFormat.format('F', this.getBookingDateFrom);
		},
		getDayOfWeek(): string
		{
			const weekDay = this.getBookingDateFrom.getDay();

			return this.loc(`BOOKING_CONFIRM_PAGE_CALENDAR_WEEK_DAY_${weekDay}`);
		},
		timeFromFormatted(): string
		{
			const timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');

			return DateTimeFormat.format(timeFormat, this.getBookingDateFrom);
		},
		timeFormatted(): string
		{
			const timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');

			return this.loc('BOOKING_CONFIRM_PAGE_TIME_RANGE', {
				'#FROM#': DateTimeFormat.format(timeFormat, this.getBookingDateFrom),
				'#TO#': DateTimeFormat.format(timeFormat, this.getBookingDateTo),
			});
		},
		timeDetailFormatted(): string
		{
			const timeFormat = DateTimeFormat.getFormat('SHORT_DAY_OF_WEEK_MONTH_FORMAT');

			return DateTimeFormat.format(timeFormat, this.getBookingDateFrom);
		},
		timeZoneFormatted(): string
		{
			const offset = this.getBookingDateFrom.getTimezoneOffset();

			const hours = Math.floor(Math.abs(offset) / 60);
			const minutes = Math.abs(offset) % 60;
			const sign = offset > 0 ? '-' : '+';

			return `GMT${sign}${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}, ${this.booking.datePeriod.from.timezone}`;
		},
	},
	template: `
		<div class="confirm-page-content-border">
			<div class="confirm-page-content-booking-time">
				<div class="confirm-page-content-booking-time-calendar">
					<div class="confirm-page-content-booking-time-calendar-container">
						<div class="confirm-page-content-booking-time-calendar-container-border"></div>
						<div class="confirm-page-content-booking-time-calendar-container-header"></div>
						<div class="confirm-page-content-booking-time-calendar-container-date">{{ getBookingDateFrom.getDate() }}</div>
						<div class="confirm-page-content-booking-time-calendar-container-month">{{ getMonth }}</div>
						<div class="confirm-page-content-booking-time-calendar-container-time">{{ timeFromFormatted }}</div>
					</div>
				</div>
				<div class="confirm-page-content-booking-time-detail">
					<div class="confirm-page-content-booking-time-detail-date">{{ timeDetailFormatted }}</div>
					<div class="confirm-page-content-booking-time-detail-time">{{ timeFormatted }}</div>
					<div class="confirm-page-content-booking-time-detail-timezone">{{ timeZoneFormatted }}</div>
				</div>
			</div>
		</div>
	`,
};
