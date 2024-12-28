(() => {
	const require = (ext) => jn.require(ext);
	const { EventAjax } = require('calendar/ajax/event');

	class CalendarBackgroundAction
	{
		constructor()
		{
			CalendarBackgroundAction.bindEvents();
		}

		static bindEvents()
		{
			BX.addCustomEvent('calendar::event::open', async (data) => {
				const { eventId, eventDate } = data;
				const { Entry } = await requireLazy('calendar:entry');

				void Entry.openEventViewForm({
					eventId: Number(eventId),
					eventDate: eventDate || '',
				});
			});

			BX.addCustomEvent('calendar::event::ics', (data) => {
				const { eventId } = data;

				// eslint-disable-next-line default-case
				switch (Application.getPlatform())
				{
					case 'android':
						viewer.openDocument(
							`/bitrix/services/main/ajax.php?action=calendar.api.calendarentryajax.getIcsFile&eventId=${eventId}`,
							'event.ics',
						);
						break;
					case 'ios':
						// eslint-disable-next-line promise/catch-or-return
						EventAjax.getIcsLink({ eventId })
							.then((response) => {
								if (response.status !== 'success')
								{
									return;
								}
								PageManager.openPage({ url: response.data.link });
							});
						break;
				}
			});
		}
	}

	return new CalendarBackgroundAction();
})();
