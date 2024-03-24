/**
 * @module utils/date
 */
jn.define('utils/date', (require, exports, module) => {
	const { Moment } = require('utils/date/moment');
	const { Duration } = require('utils/date/duration');
	const { DynamicDateFormatter } = require('utils/date/dynamic-date-formatter');

	module.exports = {
		DynamicDateFormatter,
		Moment,
		Duration,
	};
});
