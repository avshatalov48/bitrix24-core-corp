/**
 * @module stafftrack/ui
 */
jn.define('stafftrack/ui', (require, exports, module) => {
	const { TextInputWithMaxHeight } = require('stafftrack/ui/text-input-with-max-height');
	const { ScrollViewWithMaxHeight } = require('stafftrack/ui/scroll-view-with-max-height');
	const {
		disabledCheckInIcon,
		checkInAhaIcon,
		todayStatisticsDaySumIcon,
		todayStatisticsEmptyStateIcon,
	} = require('stafftrack/ui/icons');

	module.exports = {
		TextInputWithMaxHeight,
		ScrollViewWithMaxHeight,
		disabledCheckInIcon,
		checkInAhaIcon,
		todayStatisticsDaySumIcon,
		todayStatisticsEmptyStateIcon,
	};
});
