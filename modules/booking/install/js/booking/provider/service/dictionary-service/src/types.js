import type { DictionaryModel } from 'booking.model.dictionary';

export type DictionaryResponse = {
	counters: DictionaryModel,
	notifications: DictionaryModel,
	pushCommands: DictionaryModel,
	notificationTemplateTypes: DictionaryModel,
	bookings: {
		visitStatuses: DictionaryModel,
	},
};
