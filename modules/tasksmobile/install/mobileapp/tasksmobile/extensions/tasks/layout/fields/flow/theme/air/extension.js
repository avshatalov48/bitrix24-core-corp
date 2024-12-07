/**
 * @module tasks/layout/fields/flow/theme/air
 */
jn.define('tasks/layout/fields/flow/theme/air', (require, exports, module) => {
	const { TaskFlowFieldClass } = require('tasks/layout/fields/flow');
	const { withTheme } = require('layout/ui/fields/theme');
	const { AirTheme } = require('layout/ui/fields/entity-selector/theme/air');

	/** @type {function(object): object} */
	const TaskFlowField = withTheme(TaskFlowFieldClass, AirTheme);

	module.exports = {
		TaskFlowField,
	};
});
