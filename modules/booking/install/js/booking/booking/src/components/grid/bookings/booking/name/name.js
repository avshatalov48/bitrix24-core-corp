import { Model } from 'booking.const';
import type { BookingModel } from 'booking.model.bookings';
import type { ClientData, ClientModel } from 'booking.model.clients';
import './name.css';

export const Name = {
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
			return this.client?.name ?? this.booking.name;
		},
	},
	template: `
		<div
			class="booking-booking-booking-name"
			:title="bookingName"
			data-element="booking-booking-name"
			:data-id="bookingId"
			:data-resource-id="resourceId"
		>
			{{ bookingName }}
		</div>
	`,
};
