/**
 * @module tasks/layout/fields/related-task/theme/air
 */
jn.define('tasks/layout/fields/related-task/theme/air', (require, exports, module) => {
	const { withTheme } = require('layout/ui/fields/theme');
	const { RelatedTaskField } = require('tasks/layout/fields/related-task');
	const { AirTheme } = require('tasks/layout/fields/task/theme/air');

	/** @type {function(object): object} */
	const RelatedTasksField = withTheme(RelatedTaskField, AirTheme);

	module.exports = {
		RelatedTasksField,
	};
});
