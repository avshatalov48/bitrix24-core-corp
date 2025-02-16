export type DictionaryState = {
	counters: DictionaryModel,
	notifications: DictionaryModel,
	notificationTemplates: DictionaryModel,
	pushCommands: DictionaryModel,
	bookings: {
		visitStatuses: DictionaryModel,
	},
};

export type DictionaryModel = {
	Symbol('uniqueKey'): string,
};
