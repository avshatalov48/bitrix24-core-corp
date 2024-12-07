/**
 * @module tasks/layout/fields/subtask/theme/air
 */
jn.define('tasks/layout/fields/subtask/theme/air', (require, exports, module) => {
	const { withTheme } = require('layout/ui/fields/theme');
	const { SubtaskField } = require('tasks/layout/fields/subtask');
	const { AirTheme } = require('tasks/layout/fields/task/theme/air');

	/** @type {function(object): object} */
	const SubTasksField = withTheme(SubtaskField, AirTheme);

	module.exports = {
		SubTasksField,
	};
});
