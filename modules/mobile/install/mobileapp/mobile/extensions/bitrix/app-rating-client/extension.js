/**
 * @module app-rating-client
 */
jn.define('app-rating-client', (require, exports, module) => {
	const { AppRatingManager } = require('app-rating-manager');

	/**
	 * @abstract
	 * @class AppRatingClientBase
	 */
	class AppRatingClientBase
	{
		constructor()
		{
			AppRatingManager.registerLimits(this.getLimits());
		}

		/**
		 * @abstract
		 * @returns {object}
		 */
		getLimits()
		{
			throw new Error('Method "getLimits" must be implemented');
		}

		/**
		 * @public
		 * @param {AppRatingProps} [props]
		 * @param {boolean} [props.openInComponent = false]
		 * @param {boolean} [props.event = null]
		 */
		tryOpenAppRating(props)
		{
			AppRatingManager.tryOpenAppRating(props);
		}

		/**
		 * @public
		 * @param {string} event
		 */
		async increaseCounter(event)
		{
			await AppRatingManager.increaseCounter(event);
		}
	}

	module.exports = {
		AppRatingClientBase,
	};
});
