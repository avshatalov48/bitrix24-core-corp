/**
 * @module statemanager/redux/middleware/analytics-sender
 */
jn.define('statemanager/redux/middleware/analytics-sender', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');

	const analyticsSenderMiddleware = (store) => (next) => (action) => {
		const analyticsLabel = action.meta?.arg?.analyticsLabel;

		if (
			!analyticsLabel
			|| (action.meta?.$isSync === true || action.meta?.$isSync === undefined)
		)
		{
			return next(action);
		}

		const analyticsLabels = Array.isArray(analyticsLabel) ? analyticsLabel : [analyticsLabel];
		const analyticsEvents = analyticsLabels.map((label) => new AnalyticsEvent(label));

		if (action.type.endsWith('/fulfilled'))
		{
			analyticsEvents.forEach((analyticsEvent) => {
				analyticsEvent.setStatus('success').send();
			});
		}
		else if (action.type.endsWith('/rejected'))
		{
			analyticsEvents.forEach((analyticsEvent) => {
				analyticsEvent.setStatus('error').send();
			});
		}

		return next(action);
	};

	module.exports = { analyticsSenderMiddleware };
});
