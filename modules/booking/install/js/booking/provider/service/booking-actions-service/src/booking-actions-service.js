import { Model } from 'booking.const';
import { Core } from 'booking.core';
import { ApiClient } from 'booking.lib.api-client';

class BookingActionsService
{
	async getDealData(bookingId: string): Promise<void>
	{
		return Promise.resolve();
	}

	async getDocData(bookingId: string): Promise<void>
	{
		return Promise.resolve();
	}

	async getMessageData(bookingId: number): Promise<void>
	{
		const status = await (new ApiClient()).post('MessageStatus.get', { bookingId });

		await Promise.all([
			Core.getStore().dispatch(`${Model.MessageStatus}/upsert`, { bookingId, status }),
		]);
	}

	async sendMessage(bookingId: number, notificationType: string): Promise<void>
	{
		return (new ApiClient()).post('Message.send', { bookingId, notificationType });
	}

	async getVisitData(bookingId: string): Promise<void>
	{
		return Promise.resolve();
	}
}

export const bookingActionsService = new BookingActionsService();
