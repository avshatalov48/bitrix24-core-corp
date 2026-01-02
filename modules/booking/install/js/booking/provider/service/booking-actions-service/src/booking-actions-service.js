import type { Store } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import { Core } from 'booking.core';
import { apiClient } from 'booking.lib.api-client';
import type { MessageModel } from 'booking.model.bookings';

class BookingActionsService
{
	async getDocData(bookingId: string): Promise<void>
	{
		return Promise.resolve();
	}

	async getMessageData(bookingId: number): Promise<void>
	{
		const status = await apiClient.post('MessageStatus.get', { bookingId });

		await Promise.all([
			this.$store.dispatch(`${Model.MessageStatus}/upsert`, { bookingId, status }),
		]);
	}

	async sendMessage(bookingId: number, notificationType: string): Promise<void>
	{
		const message: MessageModel = await apiClient.post('Message.send', { bookingId, notificationType });

		const booking = this.$store.getters[`${Model.Bookings}/getById`](bookingId);

		void this.$store.dispatch(`${Model.Bookings}/update`, {
			id: booking.id,
			booking: {
				...booking,
				messages: [...(booking.messages ?? []), message],
			},
		});
	}

	get $store(): Store
	{
		return Core.getStore();
	}
}

export const bookingActionsService = new BookingActionsService();
