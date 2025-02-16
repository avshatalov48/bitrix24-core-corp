import { Core } from 'booking.core';
import { ApiClient } from 'booking.lib.api-client';
import { Model } from 'booking.const';

class CountersService
{
	async fetchData(): Promise<void>
	{
		try
		{
			const counters = await new ApiClient().get('Counters.get', {});

			await Promise.all([
				Core.getStore().dispatch(`${Model.Counters}/set`, counters),
			]);
		}
		catch (error)
		{
			console.error('BookingCountersGetRequest: error', error);
		}
	}
}

export const countersService = new CountersService();
