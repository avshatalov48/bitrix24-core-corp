/**
 * @module calendar/app-rating-client
 */
jn.define('calendar/app-rating-client', (require, exports, module) => {
	const { AppRatingClientBase } = require('app-rating-client');
	const AppRatingUserEvent = {
		CALENDAR_EVENT_VIEWED: 'calendar_event_viewed',
	};

	/**
	 * @class AppRatingClient
	 */
	class AppRatingClient extends AppRatingClientBase
	{
		/**
		 * @public
		 * @returns {object}
		 */
		getLimits()
		{
			return {
				[AppRatingUserEvent.CALENDAR_EVENT_VIEWED]: 15,
			};
		}

		/**
		 * @public
		 * @returns {void}
		 */
		increaseCalendarEventViewedCounter()
		{
			void this.increaseCounter(AppRatingUserEvent.CALENDAR_EVENT_VIEWED);
		}

		/**
		 * @param {AppRatingProps} [props]
		 * @param {boolean} [props.openInComponent = false]
		 * @returns {void}
		 */
		tryOpenAppRatingAfterCalendarEventViewed(props)
		{
			this.tryOpenAppRating({
				event: AppRatingUserEvent.CALENDAR_EVENT_VIEWED,
				...props,
			});
		}
	}

	module.exports = {
		AppRatingClient: new AppRatingClient(),
		AppRatingUserEvent,
	};
});
