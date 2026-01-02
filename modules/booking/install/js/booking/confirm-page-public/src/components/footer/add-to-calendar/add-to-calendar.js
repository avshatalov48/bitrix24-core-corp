import { Button, ButtonSize, ButtonColor } from 'booking.component.button';
import './add-to-calendar.css';
// eslint-disable-next-line no-unused-vars
import { BookingModel } from 'booking.model.bookings';

// @vue/component
export const AddToCalendar = {
	name: 'AddToCalendar',
	components: {
		// eslint-disable-next-line vue/no-reserved-component-names
		Button,
	},
	props: {
		/**
		 * @type {BookingModel}
		 */
		booking: {
			type: Object,
			required: true,
		},
		icsDownloadRequested: {
			type: Boolean,
			required: true,
		},
	},
	emits: ['downloadIcs'],
	setup(): Object
	{
		return {
			ButtonSize,
			ButtonColor,
		};
	},
	template: `
		<div v-if="!booking.isDeleted" class="add-to-calendar__container">
			<Button
				:text="loc('BOOKING_CONFIRM_PAGE_ADD_TO_CALENDAR_BTN')"
				:buttonClass="'add-to-calendar__btn'"
				:size="ButtonSize.SMALL"
				:color="ButtonColor.LIGHT_BORDER"
				:waiting="icsDownloadRequested"
				@click="$emit('downloadIcs')"
			/>
		</div>
	`,
};
