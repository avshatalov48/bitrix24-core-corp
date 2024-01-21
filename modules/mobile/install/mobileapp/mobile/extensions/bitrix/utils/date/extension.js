/**
 * @module utils/date
 */
jn.define('utils/date', (require, exports, module) => {
	const { Moment } = require('utils/date/moment');
	const { Duration } = require('utils/date/duration');
	const {
		ConfigurableDateByTimeDeltaTokens,
		ConfigurableDateBySeconds,
	} = require('utils/date/configurable-date');

	module.exports = {
		ConfigurableDateByTimeDeltaTokens,
		ConfigurableDateBySeconds,
		Moment,
		Duration,
	};
});
