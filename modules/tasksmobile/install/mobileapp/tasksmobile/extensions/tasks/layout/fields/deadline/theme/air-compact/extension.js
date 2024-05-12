/**
 * @module tasks/layout/fields/deadline/theme/air-compact
 */
jn.define('tasks/layout/fields/deadline/theme/air-compact', (require, exports, module) => {
	const { AirCompactThemeField } = require('layout/ui/fields/datetime/theme/air-compact');
	const { DeadlineField: DeadlineFieldClass } = require('tasks/layout/fields/deadline');

	/** @type {function(object): object} */
	const DeadlineField = AirCompactThemeField(DeadlineFieldClass);

	module.exports = {
		DeadlineField,
	};
});
