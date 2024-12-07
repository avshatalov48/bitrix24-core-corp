/**
 * @module tasks/layout/fields/subtask/theme/air-compact
 */
jn.define('tasks/layout/fields/subtask/theme/air-compact', (require, exports, module) => {
	const { AirCompactThemeField } = require('layout/ui/fields/base/theme/air-compact');
	const { SubtaskField } = require('tasks/layout/fields/subtask');

	/** @type {function(object): object} */
	const SubTasksField = AirCompactThemeField(SubtaskField);

	module.exports = {
		SubTasksField,
	};
});
