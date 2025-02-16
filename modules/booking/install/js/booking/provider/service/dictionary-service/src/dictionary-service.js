import { Core } from 'booking.core';
import { ApiClient } from 'booking.lib.api-client';
import { DictionaryDataExtractor } from './dictionary-data-extractor';

class DictionaryService
{
	async fetchData(): Promise<void>
	{
		try
		{
			const data = await new ApiClient().get('Dictionary.get', {});
			const extractor = new DictionaryDataExtractor(data);

			return Promise.all([
				Core.getStore().dispatch('dictionary/setCounters', extractor.getCounters()),
				Core.getStore().dispatch('dictionary/setNotifications', extractor.getNotifications()),
				Core.getStore().dispatch('dictionary/setNotificationTemplates', extractor.getNotificationTemplates()),
				Core.getStore().dispatch('dictionary/setPushCommands', extractor.getPushCommands()),
				Core.getStore().dispatch('dictionary/setBookings', extractor.getBookings()),
			]);
		}
		catch (error)
		{
			console.error('BookingDictionaryGetRequest: error', error);
		}
	}
}

export const dictionaryService = new DictionaryService();
