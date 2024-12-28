(() => {
	const require = (ext) => jn.require(ext);

	const { CalendarType } = require('calendar/enums');
	const { EventView } = require('calendar/event-view-form');
	const { DataLoader } = require('calendar/event-view-form/data-loader');

	BX.onViewLoaded(async () => {
		const requestedEventId = BX.componentParameters.get('EVENT_ID', null);
		const eventDate = BX.componentParameters.get('EVENT_DATE', '');
		const ownerId = env.userId;
		const calType = CalendarType.USER;
		const requestCollabs = true;

		const { eventId, dateFromTs } = await DataLoader.loadEvent({
			eventId: requestedEventId,
			eventDate,
			requestCollabs,
		});

		layout.showComponent(
			new EventView({
				ownerId,
				calType,
				layout,
				eventDate,
				eventId,
				dateFromTs,
			}),
		);
	});
})();
