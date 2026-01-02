import { mapGetters } from 'ui.vue3.vuex';

import { AddClient } from 'booking.component.booking';
import { AhaMoment, HelpDesk, Model } from 'booking.const';
import { bookingService } from 'booking.provider.service.booking-service';
import { ahaMoments } from 'booking.lib.aha-moments';
import { isRealId } from 'booking.lib.is-real-id';
import type { ClientData } from 'booking.model.clients';

import './add-client.css';

export const BookingAddClient = {
	name: 'BookingAddClient',
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
		},
		expired: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		...mapGetters({
			getBookingById: `${Model.Bookings}/getById`,
		}),
	},
	mounted(): void
	{
		if (isRealId(this.bookingId))
		{
			ahaMoments.setBookingForAhaMoment(this.bookingId);
		}

		if (ahaMoments.shouldShow(AhaMoment.AddClient, { bookingId: this.bookingId }))
		{
			void this.showAhaMoment();
		}
	},
	methods: {
		async addClientsToBook(clients: ClientData[]): Promise<void>
		{
			const booking = this.getBookingById(this.bookingId);
			await bookingService.update({
				id: booking.id,
				clients,
			});
		},
		async showAhaMoment(): Promise<void>
		{
			await ahaMoments.show({
				id: 'booking-add-client',
				title: this.loc('BOOKING_AHA_ADD_CLIENT_TITLE'),
				text: this.loc('BOOKING_AHA_ADD_CLIENT_TEXT_MSGVER_1'),
				target: this.$refs.bookingAddClientBtn?.$refs?.button,
			});

			ahaMoments.setShown(AhaMoment.AddClient);
		},
	},
	components: {
		AddClient,
	},
	template: `
		<AddClient
			:expired="expired"
			:dataAttributes="{
				'data-id': bookingId,
				'data-element': 'booking-add-client-button',
				'data-resource-id': resourceId,
			}"
			buttonClass="booking-booking-booking-add-client"
			ref="bookingAddClientBtn"
			@add="addClientsToBook"
		/>
	`,
};
