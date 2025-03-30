import { DateTimeFormat } from 'main.date';
import { mapGetters } from 'ui.vue3.vuex';
import { Model } from 'booking.const';
import type { BookingModel } from 'booking.model.bookings';
import { ChangeTimePopup } from './change-time-popup/change-time-popup';
import './booking-time.css';

export const BookingTime = {
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
		},
		dateFromTs: {
			type: Number,
			required: true,
		},
		dateToTs: {
			type: Number,
			required: true,
		},
	},
	data(): Object
	{
		return {
			showPopup: false,
		};
	},
	computed: {
		...mapGetters({
			offset: `${Model.Interface}/offset`,
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
		}),
		booking(): BookingModel
		{
			return this.$store.getters['bookings/getById'](this.bookingId);
		},
		timeFormatted(): string
		{
			const timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');

			return this.loc('BOOKING_BOOKING_TIME_RANGE', {
				'#FROM#': DateTimeFormat.format(timeFormat, (this.dateFromTs + this.offset) / 1000),
				'#TO#': DateTimeFormat.format(timeFormat, (this.dateToTs + this.offset) / 1000),
			});
		},
	},
	methods: {
		clickHandler(): void
		{
			if (!this.isFeatureEnabled)
			{
				return;
			}

			this.showPopup = true;
		},
		closePopup(): void
		{
			this.showPopup = false;
		},
	},
	components: {
		ChangeTimePopup,
	},
	template: `
		<div
			class="booking-booking-booking-time"
			:class="{'--lock': !isFeatureEnabled}"
			data-element="booking-booking-time"
			:data-booking-id="bookingId"
			:data-resource-id="resourceId"
			:data-from="booking.dateFromTs"
			:data-to="booking.dateToTs"
			ref="time"
			@click="clickHandler"
		>
			{{ timeFormatted }}
		</div>
		<ChangeTimePopup
			v-if="showPopup"
			:bookingId="bookingId"
			:targetNode="$refs.time"
			@close="closePopup"
		/>
	`,
};
