import 'ui.icon-set.main';

import { Client } from 'booking.component.actions-popup';
import { bookingService } from 'booking.provider.service.booking-service';
import type { BookingModel } from 'booking.model.bookings';
import type {
	AddClientsPayload,
	UpdateClientsPayload,
	UpdateNotePayload,
} from 'booking.component.actions-popup';

// @vue/component
export const BookingClient = {
	name: 'BookingActionsPopupClient',
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	emits: ['freeze', 'unfreeze'],
	computed: {
		booking(): BookingModel
		{
			return this.$store.getters['bookings/getById'](this.bookingId);
		},
	},
	methods: {
		async addClients({ clients }: AddClientsPayload): Promise<void>
		{
			await bookingService.update({
				id: this.booking.id,
				clients,
			});
		},
		async updateClients({ clients }: UpdateClientsPayload): Promise<void>
		{
			await bookingService.update({
				id: this.booking.id,
				clients,
			});
		},
		async updateNote({ note }: UpdateNotePayload): Promise<void>
		{
			await bookingService.update({
				id: this.booking.id,
				note,
			});
		},
	},
	components: {
		Client,
	},
	template: `
		<Client
			:id="bookingId"
			:clients="booking.clients"
			:primaryClientData="booking.primaryClient"
			:note="booking.note"
			:dataId="bookingId"
			:dataAttributes="{
				'data-booking-id': bookingId,
			}"
			dataElementPrefix="booking"
			@freeze="$emit('freeze')"
			@unfreeze="$emit('unfreeze')"
			@addClients="addClients"
			@updateClients="updateClients"
			@updateNote="updateNote"
		/>
	`,
};
