(() => {
	/**
	 * @class AnalyticsLabel
	 */
	class AnalyticsLabel
	{
		/**
		 * @param {object} analyticsLabel
		 */
		static send(analyticsLabel)
		{
			if (AnalyticsLabel.isDebug)
			{
				console.info('Sending analytics event', analyticsLabel);
			}

			BX.ajax.runAction('mobile.analytics.sendLabel', {analyticsLabel});
		}

		/**
		 * @param {boolean} isDebug
		 */
		static debug(isDebug = true)
		{
			AnalyticsLabel.isDebug = Boolean(isDebug);
		}
	}

	AnalyticsLabel.isDebug = false;
	this.AnalyticsLabel = AnalyticsLabel;
})();
