import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { apiClient } from 'booking.lib.api-client';

import { mapDtoToModel } from './mapper';

export class CalendarDataService
{
	async loadBookingInfo(bookingId: number): Promise<mixed>
	{
		try
		{
			const bookingInfoDto = await apiClient.post('CalendarData.bookingInfo', { bookingId });

			await Core.getStore().dispatch(`${Model.BookingInfo}/setBookingInfo`, mapDtoToModel(bookingInfoDto));
		}
		catch (error)
		{
			console.error('CalendarDataService. Load booking info error', error);
		}
	}
}

export const calendarDataService = new CalendarDataService();
