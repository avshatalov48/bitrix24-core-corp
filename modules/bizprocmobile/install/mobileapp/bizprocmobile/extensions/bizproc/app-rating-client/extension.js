/**
 * @module bizproc/app-rating-client
 */
jn.define('bizproc/app-rating-client', (require, exports, module) => {
	const { AppRatingClientBase } = require('app-rating-client');
	const AppRatingUserEvent = {
		BUSINESS_PROCESSES_EXECUTED: 'business_process_executed',
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
				[AppRatingUserEvent.BUSINESS_PROCESSES_EXECUTED]: 2,
			};
		}

		/**
		 * @public
		 * @returns {Promise<void>}
		 */
		increaseBusinessProcessExecutedCounter()
		{
			void this.increaseCounter(AppRatingUserEvent.BUSINESS_PROCESSES_EXECUTED);
		}

		/**
		 * @public
		 * @param {AppRatingProps} [props]
		 * @param {boolean} [props.openInComponent = false]
		 * @returns {void}
		 */
		tryOpenAppRatingAfterBusinessProcessExecuted(props = {})
		{
			this.tryOpenAppRating({
				event: AppRatingUserEvent.BUSINESS_PROCESSES_EXECUTED,
				openInComponent: true,
				...props,
			});
		}
	}

	module.exports = {
		AppRatingClient: new AppRatingClient(),
		AppRatingUserEvent,
	};
});
