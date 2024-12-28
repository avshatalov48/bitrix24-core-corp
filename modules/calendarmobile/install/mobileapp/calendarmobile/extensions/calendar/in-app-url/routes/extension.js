/**
 * @module calendar/in-app-url/routes
 */
jn.define('calendar/in-app-url/routes', (require, exports, module) => {

	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = (inAppUrl) => {
		inAppUrl.register('/company/personal/user/:userId/calendar/\\?EVENT_ID=:eventId(?:&EVENT_DATE=:eventDate)?', eventOpenHandler)
			.name('calendar:event:user');
		inAppUrl.register('/workgroups/group/:groupId/calendar/\\?EVENT_ID=:eventId(?:&EVENT_DATE=:eventDate)?', eventOpenHandler)
			.name('calendar:event:group');
		inAppUrl.register('/contacts/personal/user/:userId/calendar/\\?EVENT_ID=:eventId(?:&EVENT_DATE=:eventDate)?', eventOpenHandler)
			.name('calendar:event:extranet:user');
		inAppUrl.register('/calendar/\\?EVENT_ID=:eventId(?:&EVENT_DATE=:eventDate)?', eventOpenHandler)
			.name('calendar:event:company');
		inAppUrl.register('calendar/ics/\\?EVENT_ID=:eventId', downloadIcsHandler)
			.name('calendar:event:ics');
	};

	const eventOpenHandler = async (params, { queryParams, url }) => {
		const eventParams = {};
		const resolveParams = ['EVENT_ID', 'EVENT_DATE'];
		resolveParams.forEach((param) => {
			const value = queryParams[param];
			if (value)
			{
				eventParams[param] = value;
			}
		});

		const { Entry } = await requireLazy('calendar:entry');

		void Entry.openEventViewForm({
			eventId: Number(eventParams.EVENT_ID),
			eventDate: eventParams.EVENT_DATE || '',
		});
	};

	const downloadIcsHandler = ({ eventId }) => (
		BXMobileApp.Events.postToComponent('calendar::event::ics', { eventId })
	);
});
