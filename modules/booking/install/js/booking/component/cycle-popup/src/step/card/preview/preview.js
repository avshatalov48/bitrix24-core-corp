import { Module } from 'booking.const';
import { Booking } from './booking/booking';
import { Crm } from './crm/crm';
import './preview.css';

// @vue/component
export const Preview = {
	components: {
		Booking,
		Crm,
	},
	props: {
		mode: {
			type: String,
			required: true,
		},
		type: {
			type: String,
			required: true,
		},
	},
	computed: {
		isBooking(): boolean
		{
			return this.mode === Module.Booking;
		},
	},
	template: `
		<div class="booking-cycle-popup-card-preview">
			<Booking v-if="isBooking" :type="type"/>
			<Crm v-else :type="type"/>
		</div>
	`,
};
