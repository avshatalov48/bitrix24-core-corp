/**
 * @module tasks/layout/fields/date-plan/theme/air-compact
 */
jn.define('tasks/layout/fields/date-plan/theme/air-compact', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { AirCompactThemeView } = require('layout/ui/fields/base/theme/air-compact');
	const { withTheme } = require('layout/ui/fields/theme');
	const { DatePlanField: DatePlanFieldClass } = require('tasks/layout/fields/date-plan');

	/**
	 * @param {DatePlanField} field
	 * @return {Chip}
	 * @constructor
	 */
	const AirTheme = (field) => {
		return AirCompactThemeView({
			testId: field.testId,
			bindContainerRef: field.bindContainerRef,
			empty: field.isEmpty(),
			readOnly: field.isReadOnly(),
			leftIcon: {
				icon: Icon.CLOCK,
			},
			text: Loc.getMessage('M_TASKS_DATE_PLAN_CHIP_TEXT'),
			onClick: field.getContentClickHandler(),
		});
	};

	/** @type {function(object): object} */
	const DatePlanField = withTheme(DatePlanFieldClass, AirTheme);

	module.exports = {
		AirTheme,
		DatePlanField,
	};
});
