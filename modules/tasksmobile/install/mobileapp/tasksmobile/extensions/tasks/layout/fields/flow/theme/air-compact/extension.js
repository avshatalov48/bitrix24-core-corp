/**
 * @module tasks/layout/fields/flow/theme/air-compact
 */
jn.define('tasks/layout/fields/flow/theme/air-compact', (require, exports, module) => {
	const { TaskFlowFieldClass } = require('tasks/layout/fields/flow');
	const { AirCompactThemeField } = require('layout/ui/fields/base/theme/air-compact');

	/** @type {function(object): object} */
	const TaskFlowField = AirCompactThemeField(TaskFlowFieldClass);

	module.exports = {
		TaskFlowField,
	};
});
