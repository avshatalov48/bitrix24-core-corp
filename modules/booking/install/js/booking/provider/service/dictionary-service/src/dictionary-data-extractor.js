import type { DictionaryResponse } from './types';
import type { DictionaryModel } from 'booking.model.dictionary';

export class DictionaryDataExtractor
{
	#response: DictionaryResponse;

	constructor(response: DictionaryResponse)
	{
		this.#response = response;
	}

	getCounters(): DictionaryModel
	{
		return this.#response.counters;
	}

	getNotifications(): DictionaryModel
	{
		return this.#response.notifications;
	}

	getNotificationTemplates(): DictionaryModel
	{
		return this.#response.notificationTemplateTypes;
	}

	getPushCommands(): DictionaryModel
	{
		return this.#response.pushCommands;
	}

	getBookings(): DictionaryModel
	{
		return this.#response.bookings;
	}
}
