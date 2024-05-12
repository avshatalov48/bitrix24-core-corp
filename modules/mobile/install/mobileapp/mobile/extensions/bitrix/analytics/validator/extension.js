/**
 * @module analytics/validator
 */
jn.define('analytics/validator', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @param {AnalyticsDTO} analytics
	 * @return {bool}
	 */
	function isValidAnalyticsData(analytics)
	{
		const { event, tool, category } = analytics;

		if (!Type.isStringFilled(event))
		{
			console.error('Analytics: Parameter {event} must be a filled string.');

			return false;
		}

		if (!Type.isStringFilled(tool))
		{
			console.error('Analytics: Parameter {tool} must be a filled string.');

			return false;
		}

		if (!Type.isStringFilled(category))
		{
			console.error('Analytics: Parameter {category} must be a filled string.');

			return false;
		}

		for (const field of ['p1', 'p2', 'p3', 'p4', 'p5'])
		{
			const value = analytics[field];

			if (!Type.isStringFilled(value))
			{
				continue;
			}

			if (value.split('_').length !== 2)
			{
				console.error(`Analytics: Parameter {${field}} must be a string containing a single underscore, (${value}) given.`);

				return false;
			}
		}

		return true;
	}

	module.exports = { isValidAnalyticsData };
});
