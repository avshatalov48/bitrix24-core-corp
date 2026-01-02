/**
 * @module layout/ui/app-rating/src/strategies/strategy
 */
jn.define('layout/ui/app-rating/src/strategies/strategy', (require, exports, module) => {

	/**
	 * @interface
	 */
	class BaseAppRatingStrategy
	{
		/**
		 * @param {Object} props
		 * @param {string} props.botId
		 * @param {number} props.userRate
		 * @returns {boolean}
		 */
		isApplicable(props)
		{
			throw new Error('Method isApplicable must be implemented');
		}

		/**
		 * @param {Object} props
		 * @param {string} props.botId
		 * @param {Object} props.parentWidget
		 * @param {Function} props.onGoToStoreButtonClick
		 * @returns {BaseAppRatingStrategy||void}
		 */
		execute(props)
		{
			throw new Error('Method execute must be implemented');
		}

		/**
		 * @returns {boolean}
		 */
		shouldRender()
		{
			return true;
		}

		/**
		 * @param {string} triggerEvent
		 * @param {boolean} sendAnalytics
		 */
		buttonHandler(triggerEvent, sendAnalytics)
		{
			throw new Error('Method buttonHandler must be implemented');
		}
	}

	module.exports = {
		BaseAppRatingStrategy,
	};
});
