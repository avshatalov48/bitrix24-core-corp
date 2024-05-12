/**
 * @module analytics-label
 */
jn.define('analytics-label', (require, exports, module) => {
	/**
	 * @class AnalyticsLabel
	 */
	class AnalyticsLabel
	{
		/**
		 * @param {object} analytics
		 */
		static send(analytics)
		{
			const analyticsLabel = { ...analytics };

			analyticsLabel.platform = Application.getPlatform();

			if (Application.isBeta())
			{
				console.info('Sending analytics event', analyticsLabel);
			}

			BX.ajax.runAction('mobile.analytics.sendLabel', { analyticsLabel });
		}
	}

	module.exports = { AnalyticsLabel };
});
