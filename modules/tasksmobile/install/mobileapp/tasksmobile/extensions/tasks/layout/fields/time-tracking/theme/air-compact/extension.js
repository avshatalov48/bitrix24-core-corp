/**
 * @module tasks/layout/fields/time-tracking/theme/air-compact
 */
jn.define('tasks/layout/fields/time-tracking/theme/air-compact', (require, exports, module) => {
	const { Loc } = require('tasks/loc');
	const { Icon } = require('assets/icons');
	const { AirCompactThemeView } = require('layout/ui/fields/base/theme/air-compact');
	const { withTheme } = require('layout/ui/fields/theme');
	const { TimeTrackingFieldClass } = require('tasks/layout/fields/time-tracking');

	/**
	 * @param {TimeTrackingField} field
	 * @return {Chip}
	 * @constructor
	 */
	const AirTheme = ({ field }) => {
		return AirCompactThemeView({
			testId: field.testId,
			bindContainerRef: field.bindContainerRef,
			empty: field.isEmpty(),
			readOnly: field.isReadOnly(),
			isRestricted: field.isRestricted(),
			multiple: false,
			leftIcon: {
				icon: field.isRestricted() ? Icon.LOCK : Icon.TIMER,
			},
			text: Loc.getMessage('M_TASKS_FIELDS_TIME_TRACKING'),
			onClick: field.onContentClick,
		});
	};

	const TimeTrackingField = withTheme(TimeTrackingFieldClass, AirTheme);

	module.exports = {
		AirTheme,
		TimeTrackingField,
	};
});
