/**
 * @module tasks/layout/fields/result/theme/air
 */
jn.define('tasks/layout/fields/result/theme/air', (require, exports, module) => {
	const { withTheme } = require('layout/ui/fields/theme');
	const { TaskResultField: TaskResultFieldClass } = require('tasks/layout/fields/result');
	const { TaskResultAirReduxContent } = require('tasks/layout/fields/result/theme/air/redux-content');

	/**
	 * @param {TaskResultFieldClass} field
	 */
	const AirTheme = (field) => TaskResultAirReduxContent({ field });

	/** @type {function(object): object} */
	const TaskResultField = withTheme(TaskResultFieldClass, AirTheme);

	module.exports = {
		AirTheme,
		TaskResultField,
	};
});
