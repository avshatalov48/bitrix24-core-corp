/**
 * @module stafftrack/app-rating-client
 */
jn.define('stafftrack/app-rating-client', (require, exports, module) => {
	const { AppRatingClientBase } = require('app-rating-client');
	const AppRatingUserEvent = {
		CHECKIN: 'checkin',
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
				[AppRatingUserEvent.CHECKIN]: 10,
			};
		}

		/**
		 * @public
		 * @returns {void}
		 */
		increaseCheckinCounterAndTryOpenAppRating()
		{
			BX.postComponentEvent('app-rating-manager:increaseCounterAndTryOpen', [{
				event: AppRatingUserEvent.CHECKIN,
			}]);
		}
	}

	module.exports = {
		AppRatingClient: new AppRatingClient(),
	};
});
