/**
 * @module tasks/layout/fields/date-plan/theme/air
 */
jn.define('tasks/layout/fields/date-plan/theme/air', (require, exports, module) => {

	const { withTheme } = require('layout/ui/fields/theme');
	const { DatePlanField: DatePlanFieldClass } = require('tasks/layout/fields/date-plan');
	const { DatePlanAirReduxContent } = require('tasks/layout/fields/date-plan/theme/air/redux-content');

	/**
	 * @param {DatePlanFieldClass} field
	 */
	const AirTheme = (field) => DatePlanAirReduxContent({ field });

	/** @type {function(object): object} */
	const DatePlanField = withTheme(DatePlanFieldClass, AirTheme);

	module.exports = {
		AirTheme,
		DatePlanField,
	};
});
