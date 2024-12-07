/**
 * @module tasks/layout/fields/time-tracking/timer/timer-icon
 */
jn.define('tasks/layout/fields/time-tracking/timer/timer-icon', (require, exports, module) => {
	const { TimeTrackingTimer } = require('tasks/layout/fields/time-tracking/timer/timer');
	const { Icon } = require('assets/icons');

	class TimeTrackingTimerIcon extends TimeTrackingTimer
	{
		render()
		{
			return Image({
				named: Icon.TIMER.getIconName(),
				testId: this.props.testId,
				style: {
					height: 16,
					width: 16,
				},
				tintColor: this.props.color,
			});
		}
	}

	module.exports = { TimeTrackingTimerIcon };
});
