/**
 * @module calendar/in-app-url/routes
 */
jn.define('calendar/in-app-url/routes', (require, exports, module) => {

	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = (inAppUrl) => {
		inAppUrl.register('/company/personal/user/:userId/calendar/\\?EVENT_ID=:eventId', eventOpenHandler)
			.name('calendar:event:user');
		inAppUrl.register('/calendar/\\?EVENT_ID=:eventId', eventOpenHandler)
			.name('calendar:event:company');
		inAppUrl.register('/workgroups/group/:groupId/calendar/\\?EVENT_ID=:eventId', eventOpenHandler)
			.name('calendar:event:group');
	}

	const eventOpenHandler = ({eventId}) => {
		PageManager.openPage({
			url: `/mobile/calendar/view_event.php?event_id=${eventId}`,
			backdrop: {
				shouldResizeContent: true,
				showOnTop: true,
				topPosition: 100
			},
		})
	};
});