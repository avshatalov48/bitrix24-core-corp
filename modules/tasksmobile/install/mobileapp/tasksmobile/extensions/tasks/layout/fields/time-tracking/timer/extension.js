/**
 * @module tasks/layout/fields/time-tracking/timer
 */
jn.define('tasks/layout/fields/time-tracking/timer', (require, exports, module) => {
	const { TimeTrackingTimer } = require('tasks/layout/fields/time-tracking/timer/timer');
	const { TimeTrackingTimerIcon } = require('tasks/layout/fields/time-tracking/timer/timer-icon');

	module.exports = { TimeTrackingTimer, TimeTrackingTimerIcon };
});
