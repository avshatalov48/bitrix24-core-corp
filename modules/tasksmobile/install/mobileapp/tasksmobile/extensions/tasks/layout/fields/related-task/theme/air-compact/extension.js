/**
 * @module tasks/layout/fields/related-task/theme/air-compact
 */
jn.define('tasks/layout/fields/related-task/theme/air-compact', (require, exports, module) => {
	const { AirCompactThemeField } = require('layout/ui/fields/base/theme/air-compact');
	const { RelatedTaskField } = require('tasks/layout/fields/related-task');

	/** @type {function(object): object} */
	const RelatedTasksField = AirCompactThemeField(RelatedTaskField);

	module.exports = {
		RelatedTasksField,
	};
});
