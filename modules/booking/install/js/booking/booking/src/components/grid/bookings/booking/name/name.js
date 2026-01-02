// @vue/component

import { Model } from 'booking.const';
import { Name } from 'booking.component.booking';
import type { BookingModel } from 'booking.model.bookings';
import type { ClientData, ClientModel } from 'booking.model.clients';

import './name.css';

export const BookingName = {
	name: 'BookingName',
	components: {
		Name,
	},
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
		},
	},
	computed: {
		booking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
		},
		client(): ClientModel
		{
			const clientData: ClientData = this.booking.primaryClient;

			return clientData ? this.$store.getters[`${Model.Clients}/getByClientData`](clientData) : null;
		},
		bookingName(): string
		{
			return this.client?.name || this.booking.name || this.loc('BOOKING_BOOKING_DEFAULT_BOOKING_NAME');
		},
	},
	template: `
		<Name
			:name="bookingName"
			className="booking-booking-booking-name"
			:dataAttributes="{
				'data-element': 'booking-booking-name',
				'data-id': bookingId,
				'data-resource-id':resourceId,
			}"
		/>
	`,
};
