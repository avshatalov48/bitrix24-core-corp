import { Extension } from 'main.core';
import type { Store } from 'ui.vue3.vuex';

import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { DictionaryDataExtractor } from './dictionary-data-extractor';

class DictionaryService
{
	async fetchData(): Promise<void>
	{
		try
		{
			const data = Extension.getSettings('booking.provider.service.dictionary-service');
			const extractor = new DictionaryDataExtractor(data);

			await Promise.all([
				this.$store.dispatch(`${Model.Dictionary}/setCounters`, extractor.getCounters()),
				this.$store.dispatch(`${Model.Dictionary}/setNotifications`, extractor.getNotifications()),
				this.$store.dispatch(`${Model.Dictionary}/setNotificationTemplates`, extractor.getNotificationTemplates()),
				this.$store.dispatch(`${Model.Dictionary}/setPushCommands`, extractor.getPushCommands()),
				this.$store.dispatch(`${Model.Dictionary}/setBookings`, extractor.getBookings()),
			]);
		}
		catch (error)
		{
			console.error('BookingDictionaryGetRequest: error', error);
		}
	}

	get $store(): Store
	{
		return Core.getStore();
	}
}

export const dictionaryService = new DictionaryService();
